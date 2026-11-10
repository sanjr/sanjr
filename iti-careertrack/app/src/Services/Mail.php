<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\HttpException;

/**
 * Email & circulars between DVET offices and ITIs.
 *
 * Mailboxes belong to an office: STATE/0 (DVET), REGION/<id> (RO), DISTRICT/<id> (DVTO), ITI/<id>.
 *  - State Admin writes to all ITIs of Maharashtra or a chosen part (region, district, type, ticked ITIs)
 *    and to RO / DVTO offices.
 *  - RO Admin writes to the ITIs and DVTO offices of his / her region only.
 *  - Principal / DVTO read and reply.
 * Every message stays in the portal (Inbox / Sent / Drafts, read + reply status per ITI) and is also
 * sent by e-mail (SMTP, config.php → 'mail') to the ITI address and its Principal — through a queue,
 * so 500 ITIs do not block the page. Without SMTP settings the portal works in DEMO mode (no e-mails).
 */
final class Mail
{
    public const PRIORITIES = ['NORMAL' => 'Normal', 'HIGH' => 'Important', 'URGENT' => 'Urgent'];
    public const MAX_FILES = 10;

    /** extension => accepted detected MIME types (Office files are ZIP / OLE inside) */
    private const FILES = [
        'pdf' => ['application/pdf'], 'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'],
        'doc' => ['application/msword', 'application/vnd.ms-office', 'application/CDFV2', 'application/x-ole-storage'],
        'xls' => ['application/vnd.ms-excel', 'application/vnd.ms-office', 'application/CDFV2', 'application/x-ole-storage'],
        'ppt' => ['application/vnd.ms-powerpoint', 'application/vnd.ms-office', 'application/CDFV2', 'application/x-ole-storage'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream'],
        'csv' => ['text/plain', 'text/csv', 'application/csv'], 'txt' => ['text/plain'], 'zip' => ['application/zip'],
    ];
    /** MIME type sent when the file is downloaded / attached (never the uploader's claim) */
    private const SERVE = [
        'pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'doc' => 'application/msword', 'xls' => 'application/vnd.ms-excel', 'ppt' => 'application/vnd.ms-powerpoint',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'csv' => 'text/csv', 'txt' => 'text/plain', 'zip' => 'application/zip',
    ];

    // ------------------------------------------------------------------ mailboxes and rights

    /** @return array{0:string,1:int}|null the office mailbox of the current login */
    public static function myBox(): ?array
    {
        return match (Auth::roleCode()) {
            'STATE_ADMIN' => ['STATE', 0],
            'RO_ADMIN' => Auth::regionId() ? ['REGION', Auth::regionId()] : null,
            'DVTO' => Auth::districtId() ? ['DISTRICT', Auth::districtId()] : null,
            'PRINCIPAL' => Auth::itiId() ? ['ITI', Auth::itiId()] : null,
            default => null,
        };
    }

    public static function requireBox(): array
    {
        return self::myBox() ?? throw new HttpException(403);
    }

    /** Only State Admin and RO Admin write new (circular) messages. */
    public static function canCompose(): bool
    {
        return Auth::is('STATE_ADMIN', 'RO_ADMIN') && self::myBox() !== null;
    }

    public static function isLive(): bool
    {
        return trim((string) config('mail.host', '')) !== '' && filter_var((string) config('mail.from', ''), FILTER_VALIDATE_EMAIL) !== false;
    }

    /** SQL giving the display name of a mailbox. */
    public static function boxNameSql(string $type, string $id): string
    {
        return "CASE $type WHEN 'STATE' THEN 'DVET State Office'
                   WHEN 'REGION' THEN (SELECT CONCAT(xg.name, ' Office (RO)') FROM regions xg WHERE xg.id = $id)
                   WHEN 'DISTRICT' THEN (SELECT CONCAT('DVTO Office, ', xd.name) FROM districts xd WHERE xd.id = $id)
                   WHEN 'ITI' THEN (SELECT xi.name FROM itis xi WHERE xi.id = $id) END";
    }

    public static function boxName(string $type, int $id): string
    {
        return (string) DB::value('SELECT ' . self::boxNameSql('?', '?'), [$type, $id, $id, $id]);
    }

    public static function unreadCount(): int
    {
        static $n = null;
        if ($n !== null) {
            return $n;
        }
        $box = self::myBox();
        try {
            return $n = $box ? (int) DB::value("SELECT COUNT(*) FROM mail_recipients r JOIN mail_messages m ON m.id = r.message_id
                                                 WHERE r.box_type = ? AND r.box_id = ? AND r.read_at IS NULL AND r.archived = 0 AND m.status = 'SENT'", $box) : 0;
        } catch (\Throwable) {
            return $n = 0;     // tables not there yet (before the update ran)
        }
    }

    /** The message, if the current login may open it (sender office, receiving office, or own draft). */
    public static function visible(int $id): array
    {
        [$bt, $bi] = self::requireBox();
        $m = DB::one('SELECT m.*, u.full_name AS from_person, ' . self::boxNameSql('m.from_box_type', 'm.from_box_id') . ' AS from_name
                        FROM mail_messages m JOIN users u ON u.id = m.from_user_id WHERE m.id = ?', [$id]);
        if (!$m) {
            throw new HttpException(404);
        }
        $mine = $m['from_box_type'] === $bt && (int) $m['from_box_id'] === $bi;
        if ($m['status'] === 'DRAFT') {
            if (!$mine || (int) $m['from_user_id'] !== Auth::id()) {
                throw new HttpException(404);
            }
        } elseif (!$mine && !DB::value('SELECT 1 FROM mail_recipients WHERE message_id = ? AND box_type = ? AND box_id = ?', [$id, $bt, $bi])) {
            throw new HttpException(404);
        }
        $m['is_mine'] = $mine;
        return $m;
    }

    // ------------------------------------------------------------------ recipients

    /** Options for the compose form, limited to what this login may write to. */
    public static function audienceOptions(): array
    {
        $state = Auth::is('STATE_ADMIN');
        $rid = Auth::regionId();
        $w = $state ? '1=1' : 'd.region_id = ?';
        $p = $state ? [] : [$rid];
        return [
            'state' => $state,
            'regions' => $state ? DB::all('SELECT id, name FROM regions ORDER BY id') : DB::all('SELECT id, name FROM regions WHERE id = ?', [$rid]),
            'districts' => DB::all("SELECT d.id, d.name, d.region_id FROM districts d WHERE $w ORDER BY d.name", $p),
            'itis' => DB::all("SELECT i.id, i.iti_code, i.name, i.ownership_type, i.district_id, d.region_id, d.name AS district
                                 FROM itis i JOIN districts d ON d.id = i.district_id WHERE i.status = 'ACTIVE' AND $w ORDER BY d.name, i.name", $p),
        ];
    }

    /** Read the "To" part of the compose form into a clean, scope-checked audience. */
    public static function audienceFromInput(array $in): array
    {
        $ids = static fn ($v) => array_values(array_unique(array_filter(array_map('intval', (array) $v))));
        $a = [
            'iti' => !empty($in['to_iti']) ? 1 : 0,
            'region' => (int) ($in['region_id'] ?? 0),
            'district' => (int) ($in['district_id'] ?? 0),
            'own' => in_array($in['ownership'] ?? '', ['GOVERNMENT', 'PRIVATE'], true) ? $in['ownership'] : '',
            'mode' => ($in['iti_mode'] ?? 'all') === 'pick' ? 'pick' : 'all',
            'iti_ids' => array_slice($ids($in['iti_ids'] ?? []), 0, 2000),
            'ro' => !empty($in['to_ro']) && Auth::is('STATE_ADMIN') ? 1 : 0,
            'ro_ids' => $ids($in['ro_ids'] ?? []),
            'dvto' => !empty($in['to_dvto']) ? 1 : 0,
        ];
        if (!Auth::is('STATE_ADMIN')) {
            $a['region'] = (int) Auth::regionId();
        }
        return $a;
    }

    /**
     * Mailboxes for an audience (scope enforced here again: an RO Admin never reaches another region).
     * @return array{boxes: list<array{0:string,1:int}>, label: string}
     */
    public static function resolve(array $a): array
    {
        $state = Auth::is('STATE_ADMIN');
        $scopeW = $state ? '1=1' : 'd.region_id = ' . (int) Auth::regionId();
        $boxes = [];
        $parts = [];
        if ($a['iti']) {
            $w = "i.status = 'ACTIVE' AND $scopeW";
            $p = [];
            if ($a['mode'] === 'pick') {
                $w .= $a['iti_ids'] ? ' AND i.id IN (' . DB::in($a['iti_ids']) . ')' : ' AND 1=0';
                $p = $a['iti_ids'];
            } else {
                if ($a['region']) { $w .= ' AND d.region_id = ?'; $p[] = $a['region']; }
                if ($a['district']) { $w .= ' AND i.district_id = ?'; $p[] = $a['district']; }
                if ($a['own']) { $w .= ' AND i.ownership_type = ?'; $p[] = $a['own']; }
            }
            $list = DB::all("SELECT i.id FROM itis i JOIN districts d ON d.id = i.district_id WHERE $w", $p);
            foreach ($list as $r) {
                $boxes[] = ['ITI', (int) $r['id']];
            }
            if ($a['mode'] === 'pick') {
                $parts[] = count($list) . ' selected ITIs';
            } else {
                $where = $a['district'] ? (string) DB::value('SELECT name FROM districts WHERE id = ?', [$a['district']]) . ' district'
                    : ($a['region'] ? (string) DB::value('SELECT name FROM regions WHERE id = ?', [$a['region']]) : 'Maharashtra');
                $parts[] = 'All ' . ($a['own'] ? strtolower(label($a['own'])) . ' ' : '') . 'ITIs — ' . $where . ' (' . count($list) . ')';
            }
        }
        if ($a['ro'] && $state) {
            $ro = $a['ro_ids'] ? DB::all('SELECT id FROM regions WHERE id IN (' . DB::in($a['ro_ids']) . ')', $a['ro_ids']) : DB::all('SELECT id FROM regions');
            foreach ($ro as $r) {
                $boxes[] = ['REGION', (int) $r['id']];
            }
            $parts[] = count($ro) . ' RO offices';
        }
        if ($a['dvto']) {
            $w = $scopeW;
            $p = [];
            if ($a['region']) { $w .= ' AND d.region_id = ?'; $p[] = $a['region']; }
            if ($a['district']) { $w .= ' AND d.id = ?'; $p[] = $a['district']; }
            $ds = DB::all("SELECT d.id FROM districts d WHERE $w", $p);
            foreach ($ds as $r) {
                $boxes[] = ['DISTRICT', (int) $r['id']];
            }
            $parts[] = count($ds) . ' DVTO offices';
        }
        return ['boxes' => $boxes, 'label' => mb_substr(implode(' + ', $parts), 0, 250)];
    }

    /**
     * E-mail addresses of mailboxes: ITI = official ITI address + Principal(s); RO / DVTO / State = the
     * admins of that office. Test addresses (.local, .test, example.*) are left out.
     * @param list<array{0:string,1:int}> $boxes
     * @return array<string, list<string>> "TYPE:id" => addresses
     */
    public static function emailsOf(array $boxes): array
    {
        $out = [];
        $by = ['ITI' => [], 'REGION' => [], 'DISTRICT' => [], 'STATE' => []];
        foreach ($boxes as [$t, $i]) {
            $by[$t][] = $i;
            $out["$t:$i"] = [];
        }
        $add = static function (string $key, ?string $email) use (&$out): void {
            $e = strtolower(trim((string) $email));
            if ($e !== '' && isset($out[$key]) && self::deliverable($e) && !in_array($e, $out[$key], true)) {
                $out[$key][] = $e;
            }
        };
        $role = static fn (string $code, string $col, array $ids) => DB::all(
            "SELECT ura.$col AS box_id, u.email FROM user_role_assignments ura JOIN roles r ON r.id = ura.role_id JOIN users u ON u.id = ura.user_id
              WHERE r.code = ? AND ura.status = 'ACTIVE' AND CURDATE() >= ura.valid_from AND (ura.valid_to IS NULL OR CURDATE() <= ura.valid_to)
                AND u.status = 'ACTIVE' AND u.email IS NOT NULL" . ($ids ? " AND ura.$col IN (" . DB::in($ids) . ')' : ''), array_merge([$code], $ids));
        foreach (array_chunk($by['ITI'], 1000) as $chunk) {
            foreach (DB::all('SELECT id, email FROM itis WHERE id IN (' . DB::in($chunk) . ')', $chunk) as $r) {
                $add('ITI:' . $r['id'], $r['email']);
            }
            foreach ($role('PRINCIPAL', 'iti_id', $chunk) as $r) {
                $add('ITI:' . $r['box_id'], $r['email']);
            }
        }
        if ($by['REGION']) {
            foreach ($role('RO_ADMIN', 'region_id', $by['REGION']) as $r) {
                $add('REGION:' . $r['box_id'], $r['email']);
            }
        }
        if ($by['DISTRICT']) {
            foreach ($role('DVTO', 'district_id', $by['DISTRICT']) as $r) {
                $add('DISTRICT:' . $r['box_id'], $r['email']);
            }
        }
        if ($by['STATE']) {
            foreach ($role('STATE_ADMIN', 'id', []) as $r) {
                $add('STATE:0', $r['email']);
            }
        }
        return $out;
    }

    public static function deliverable(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && !preg_match('/@(.+\.)?(local|test|invalid|localhost|example(\.com|\.org|\.net)?)$/i', $email);
    }

    // ------------------------------------------------------------------ writing

    /** Save (new or existing) draft from the form; returns the message id. */
    public static function saveDraft(?int $id, array $in, array $files): int
    {
        [$bt, $bi] = self::requireBox();
        if (!self::canCompose()) {
            throw new HttpException(403);
        }
        $a = self::audienceFromInput($in);
        $row = [
            'subject' => mb_substr(trim(preg_replace('/[\r\n\t]+/', ' ', (string) ($in['subject'] ?? ''))), 0, 200),
            'body' => mb_substr(str_replace("\r\n", "\n", (string) ($in['body'] ?? '')), 0, 60000),
            'priority' => isset(self::PRIORITIES[$in['priority'] ?? '']) ? $in['priority'] : 'NORMAL',
            'reply_by' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($in['reply_by'] ?? '')) ? $in['reply_by'] : null,
            'audience' => json_encode($a),
            'extra_emails' => self::extraEmails((string) ($in['extra_emails'] ?? '')),
        ];
        if ($row['reply_by'] !== null && $row['reply_by'] < date('Y-m-d')) {
            throw new \DomainException('"Reply required by" cannot be a past date.');
        }
        return DB::tx(function () use ($id, $row, $bt, $bi, $files, $in) {
            if ($id) {
                $m = self::visible($id);
                if ($m['status'] !== 'DRAFT') {
                    throw new HttpException(404);
                }
                $set = implode(', ', array_map(static fn ($k) => "`$k` = ?", array_keys($row)));
                DB::run("UPDATE mail_messages SET $set WHERE id = ?", [...array_values($row), $id]);
                $drop = array_map('intval', (array) ($in['remove_att'] ?? []));
                foreach ($drop ? DB::all('SELECT * FROM mail_attachments WHERE message_id = ? AND id IN (' . DB::in($drop) . ')', [$id, ...$drop]) : [] as $att) {
                    @unlink(self::path($att));
                    DB::run('DELETE FROM mail_attachments WHERE id = ?', [$att['id']]);
                }
            } else {
                $id = DB::insert('mail_messages', $row + ['from_user_id' => Auth::id(), 'from_box_type' => $bt, 'from_box_id' => $bi, 'status' => 'DRAFT']);
            }
            self::storeFiles($id, $files);
            return $id;
        });
    }

    /** Send a draft: recipients, e-mail queue. Returns [recipients, e-mails queued]. */
    public static function sendDraft(int $id): array
    {
        $m = self::visible($id);
        if ($m['status'] !== 'DRAFT' || !self::canCompose()) {
            throw new HttpException(404);
        }
        if (trim($m['subject']) === '' || trim($m['body']) === '') {
            throw new \DomainException('Write the subject and the message.');
        }
        $r = self::resolve(json_decode((string) $m['audience'], true) ?: []);
        if (!$r['boxes'] && !$m['extra_emails']) {
            throw new \DomainException('Choose at least one recipient (ITIs, RO offices or DVTO offices).');
        }
        return DB::tx(function () use ($m, $r, $id) {
            $q = self::deliver($id, $r['boxes'], array_filter(explode(',', (string) $m['extra_emails'])));
            DB::run("UPDATE mail_messages SET status = 'SENT', sent_at = NOW(), thread_id = COALESCE(thread_id, id), audience_label = ?, recipients_count = ? WHERE id = ?",
                [$r['label'] ?: 'Other e-mail addresses', count($r['boxes']), $id]);
            \App\Core\Audit::log('MAIL_SEND', 'mail_messages', $id, null, null, null, ['to' => $r['label'], 'offices' => count($r['boxes']), 'emails' => $q]);
            return [count($r['boxes']), $q];
        });
    }

    /** Reply (or follow-up) to the other party of a conversation. */
    public static function reply(int $id, string $body, array $files): int
    {
        [$bt, $bi] = self::requireBox();
        $m = self::visible($id);
        $other = self::otherParty($m);
        if ($m['status'] !== 'SENT' || !$other) {
            throw new HttpException(403);
        }
        $body = trim(str_replace("\r\n", "\n", $body));
        if ($body === '') {
            throw new \DomainException('Write the reply.');
        }
        return DB::tx(function () use ($m, $other, $body, $files, $bt, $bi) {
            $subject = preg_match('/^re:/i', $m['subject']) ? $m['subject'] : 'Re: ' . $m['subject'];
            $nid = DB::insert('mail_messages', ['parent_id' => $m['id'], 'thread_id' => $m['thread_id'] ?: $m['id'], 'from_user_id' => Auth::id(),
                'from_box_type' => $bt, 'from_box_id' => $bi, 'subject' => mb_substr($subject, 0, 200), 'body' => mb_substr($body, 0, 60000),
                'priority' => 'NORMAL', 'status' => 'DRAFT', 'audience_label' => self::boxName($other[0], $other[1]), 'recipients_count' => 1]);
            self::storeFiles($nid, $files);
            self::deliver($nid, [$other], []);
            DB::run("UPDATE mail_messages SET status = 'SENT', sent_at = NOW() WHERE id = ?", [$nid]);
            // the messages of the other office in this conversation are now answered
            DB::run("UPDATE mail_recipients r JOIN mail_messages x ON x.id = r.message_id SET r.replied_at = NOW()
                      WHERE x.thread_id = ? AND x.from_box_type = ? AND x.from_box_id = ? AND r.box_type = ? AND r.box_id = ? AND r.replied_at IS NULL",
                [$m['thread_id'] ?: $m['id'], $other[0], $other[1], $bt, $bi]);
            return $nid;
        });
    }

    /**
     * The office on the other side of this message: the sender (when I received it), or the only
     * recipient (when I sent it to one office). A circular I sent to many offices has none.
     * @return array{0:string,1:int}|null
     */
    public static function otherParty(array $m): ?array
    {
        if (!$m['is_mine']) {
            return [$m['from_box_type'], (int) $m['from_box_id']];
        }
        if ((int) $m['recipients_count'] !== 1) {
            return null;
        }
        $r = DB::one('SELECT box_type, box_id FROM mail_recipients WHERE message_id = ? LIMIT 1', [$m['id']]);
        return $r ? [$r['box_type'], (int) $r['box_id']] : null;
    }

    /** Messages of the conversation between my office and $other (oldest first). */
    public static function conversation(array $m, array $other): array
    {
        [$bt, $bi] = self::requireBox();
        return DB::all('SELECT DISTINCT m.*, u.full_name AS from_person, ' . self::boxNameSql('m.from_box_type', 'm.from_box_id') . " AS from_name
                          FROM mail_messages m JOIN users u ON u.id = m.from_user_id JOIN mail_recipients r ON r.message_id = m.id
                         WHERE m.thread_id = ? AND m.status = 'SENT'
                           AND ((m.from_box_type = ? AND m.from_box_id = ? AND r.box_type = ? AND r.box_id = ?)
                             OR (m.from_box_type = ? AND m.from_box_id = ? AND r.box_type = ? AND r.box_id = ?))
                         ORDER BY m.sent_at, m.id LIMIT 200",
            [$m['thread_id'] ?: $m['id'], $bt, $bi, $other[0], $other[1], $other[0], $other[1], $bt, $bi]);
    }

    /** Create recipient rows + the e-mail queue. Returns the number of e-mails queued. */
    private static function deliver(int $id, array $boxes, array $extra): int
    {
        self::rememberBase();
        $status = self::isLive() ? 'QUEUED' : 'DEMO';
        $emails = self::emailsOf($boxes);
        $seen = [];
        $n = 0;
        foreach (array_chunk($boxes, 400) as $chunk) {
            DB::run('INSERT IGNORE INTO mail_recipients (message_id, box_type, box_id) VALUES ' . implode(',', array_fill(0, count($chunk), '(?,?,?)')),
                array_merge(...array_map(static fn ($b) => [$id, $b[0], $b[1]], $chunk)));
        }
        $rid = [];
        foreach (DB::all('SELECT id, box_type, box_id FROM mail_recipients WHERE message_id = ?', [$id]) as $r) {
            $rid[$r['box_type'] . ':' . $r['box_id']] = (int) $r['id'];
        }
        $rows = [];
        foreach ($emails as $key => $list) {
            foreach ($list as $e) {
                if (!isset($seen[$e]) && isset($rid[$key])) {
                    $seen[$e] = 1;
                    $rows[] = [$id, $rid[$key], $e, $status];
                }
            }
        }
        foreach ($extra as $e) {
            $e = strtolower(trim($e));
            if ($e !== '' && !isset($seen[$e]) && self::deliverable($e)) {
                $seen[$e] = 1;
                $rows[] = [$id, null, $e, $status];
            }
        }
        foreach (array_chunk($rows, 400) as $chunk) {
            DB::run('INSERT INTO mail_deliveries (message_id, recipient_id, email, status) VALUES ' . implode(',', array_fill(0, count($chunk), '(?,?,?,?)')),
                array_merge(...$chunk));
            $n += count($chunk);
        }
        return $n;
    }

    /** Send the e-mail again to offices that have not opened the message yet. */
    public static function remind(int $id): int
    {
        $m = self::visible($id);
        if (!$m['is_mine'] || $m['status'] !== 'SENT') {
            throw new HttpException(404);
        }
        if (!self::isLive()) {
            throw new \DomainException('E-mail is not connected (DEMO mode) — reminders are shown in the portal only. Set up SMTP in config.php.');
        }
        self::rememberBase();
        $unread = DB::all('SELECT id, box_type, box_id FROM mail_recipients WHERE message_id = ? AND read_at IS NULL', [$id]);
        $emails = self::emailsOf(array_map(static fn ($r) => [$r['box_type'], (int) $r['box_id']], $unread));
        $bad = array_flip(array_column(DB::all("SELECT DISTINCT email FROM mail_deliveries WHERE message_id = ? AND status = 'FAILED'", [$id]), 'email'));
        $rows = [];
        foreach ($unread as $r) {
            foreach ($emails[$r['box_type'] . ':' . $r['box_id']] ?? [] as $e) {
                if (isset($bad[$e])) {
                    continue;   // the mail server already said this address does not exist
                }
                $rows[] = [$id, (int) $r['id'], $e, 'QUEUED', 'REMINDER'];
            }
        }
        foreach (array_chunk($rows, 400) as $chunk) {
            DB::run('INSERT INTO mail_deliveries (message_id, recipient_id, email, status, kind) VALUES ' . implode(',', array_fill(0, count($chunk), '(?,?,?,?,?)')),
                array_merge(...$chunk));
        }
        \App\Core\Audit::log('MAIL_REMIND', 'mail_messages', $id, null, null, null, ['emails' => count($rows)]);
        return count($rows);
    }

    private static function extraEmails(string $s): ?string
    {
        $out = [];
        foreach (preg_split('/[\s,;]+/', $s) ?: [] as $e) {
            $e = strtolower(trim($e));
            if ($e === '') {
                continue;
            }
            if (!filter_var($e, FILTER_VALIDATE_EMAIL)) {
                throw new \DomainException("\"$e\" is not a valid e-mail address.");
            }
            $out[$e] = 1;
        }
        if (count($out) > 20) {
            throw new \DomainException('At most 20 extra e-mail addresses.');
        }
        return $out ? implode(',', array_keys($out)) : null;
    }

    // ------------------------------------------------------------------ attachments

    public static function dir(): string
    {
        return rtrim((string) config('storage.documents'), '/') . '/mail';
    }

    public static function path(array $att): string
    {
        $p = self::dir() . '/' . $att['storage_key'];
        $real = realpath($p);
        $base = realpath(self::dir());
        if ($real === false || $base === false || !str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
            return $p;   // file missing → caller gets 404 on is_file()
        }
        return $real;
    }

    public static function serveType(string $filename): string
    {
        return self::SERVE[strtolower(pathinfo($filename, PATHINFO_EXTENSION))] ?? 'application/octet-stream';
    }

    /** Save uploaded files (input name="attachments[]"). */
    private static function storeFiles(int $id, array $files): void
    {
        if (!isset($files['name']) || !is_array($files['name'])) {
            return;
        }
        $maxFile = (int) config('mail.max_file_mb', 10) * 1048576;
        $maxTotal = (int) config('mail.max_total_mb', 25) * 1048576;
        $have = DB::one('SELECT COUNT(*) AS n, COALESCE(SUM(size_bytes), 0) AS s FROM mail_attachments WHERE message_id = ?', [$id]);
        $count = (int) $have['n'];
        $total = (int) $have['s'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        foreach ($files['name'] as $i => $name) {
            $err = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            if ($err === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $name = mb_substr(basename(str_replace('\\', '/', (string) $name)), 0, 200);
            if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
                throw new \DomainException("$name is larger than the server allows.");
            }
            if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($files['tmp_name'][$i])) {
                throw new \DomainException("$name: upload failed, please try again.");
            }
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $size = (int) $files['size'][$i];
            $mime = $finfo->file($files['tmp_name'][$i]) ?: '';
            if (!isset(self::FILES[$ext]) || !in_array($mime, self::FILES[$ext], true)) {
                throw new \DomainException("$name: only PDF, Word, Excel, PowerPoint, JPG, PNG, CSV, TXT or ZIP files can be attached.");
            }
            if ($size <= 0 || $size > $maxFile) {
                throw new \DomainException("$name is larger than " . ($maxFile / 1048576) . ' MB.');
            }
            if (++$count > self::MAX_FILES) {
                throw new \DomainException('At most ' . self::MAX_FILES . ' attachments per message.');
            }
            if (($total += $size) > $maxTotal) {
                throw new \DomainException('All attachments together may be at most ' . ($maxTotal / 1048576) . ' MB.');
            }
            $rel = date('Y/m/') . bin2hex(random_bytes(16)) . '.' . $ext;
            $dest = self::dir() . '/' . $rel;
            if (!is_dir(dirname($dest)) && !@mkdir(dirname($dest), 0750, true) && !is_dir(dirname($dest))) {
                throw new \RuntimeException('Attachment folder is not writable.');
            }
            if (!move_uploaded_file($files['tmp_name'][$i], $dest)) {
                throw new \RuntimeException('Could not save the attachment.');
            }
            DB::insert('mail_attachments', ['message_id' => $id, 'storage_key' => $rel, 'original_filename' => $name, 'mime_type' => self::serveType($name),
                'size_bytes' => $size, 'sha256' => hash_file('sha256', $dest)]);
        }
    }

    // ------------------------------------------------------------------ e-mail sending (queue)

    /**
     * Send queued e-mails (all messages, or one). Safe to call from several places at once:
     * rows are claimed first. Keeps to config mail.per_hour (hosting limit).
     * @return array{sent:int, failed:int, left:int, reason:string}
     */
    public static function pump(int $limit = 20, ?int $messageId = null): array
    {
        $only = $messageId ? ' AND message_id = ' . (int) $messageId : '';
        $left = static fn () => (int) DB::value("SELECT COUNT(*) FROM mail_deliveries WHERE status IN ('QUEUED','SENDING')$only");
        if (!self::isLive()) {
            return ['sent' => 0, 'failed' => 0, 'left' => 0, 'reason' => 'demo'];
        }
        DB::run("UPDATE mail_deliveries SET status = 'QUEUED', claim = NULL WHERE status = 'SENDING' AND updated_at < NOW() - INTERVAL 15 MINUTE");
        $room = (int) config('mail.per_hour', 300) - (int) DB::value("SELECT COUNT(*) FROM mail_deliveries WHERE status = 'SENT' AND sent_at > NOW() - INTERVAL 1 HOUR");
        $limit = min($limit, $room);
        if ($limit <= 0) {
            return ['sent' => 0, 'failed' => 0, 'left' => $left(), 'reason' => 'hourly limit'];
        }
        $claim = bin2hex(random_bytes(8));
        DB::run("UPDATE mail_deliveries SET status = 'SENDING', claim = ? WHERE status = 'QUEUED'$only ORDER BY id LIMIT $limit", [$claim]);
        $rows = DB::all('SELECT * FROM mail_deliveries WHERE claim = ? AND status = ? ORDER BY message_id, id', [$claim, 'SENDING']);
        $sent = $failed = 0;
        $smtp = null;
        $built = [];
        foreach ($rows as $d) {
            try {
                $built[$d['message_id']] ??= self::build((int) $d['message_id']);
                if (!$smtp || !$smtp->isOpen()) {
                    $smtp = new Smtp((array) config('mail'));
                    $smtp->open();
                }
                $smtp->send((string) config('mail.from'), $d['email'], self::mime($built[$d['message_id']], $d['email'], $d['kind'] === 'REMINDER'));
                DB::run("UPDATE mail_deliveries SET status = 'SENT', sent_at = NOW(), attempts = attempts + 1, error = NULL, claim = NULL WHERE id = ?", [$d['id']]);
                $sent++;
            } catch (\Throwable $e) {
                $code = $e instanceof SmtpException ? $e->smtpCode : 0;
                $final = $code >= 500 || (int) $d['attempts'] + 1 >= 3;
                DB::run('UPDATE mail_deliveries SET status = ?, attempts = attempts + 1, error = ?, claim = NULL WHERE id = ?',
                    [$final ? 'FAILED' : 'QUEUED', mb_substr($e->getMessage(), 0, 250), $d['id']]);
                $final ? $failed++ : null;
                if ($code === 0 || $code === 421) {   // connection problem: stop this run, try later
                    $smtp?->close();
                    DB::run("UPDATE mail_deliveries SET status = 'QUEUED', claim = NULL WHERE claim = ? AND status = 'SENDING'", [$claim]);
                    return ['sent' => $sent, 'failed' => $failed, 'left' => $left(), 'reason' => mb_substr($e->getMessage(), 0, 120)];
                }
                $smtp?->reset();
            }
        }
        $smtp?->close();
        return ['sent' => $sent, 'failed' => $failed, 'left' => $left(), 'reason' => ''];
    }

    /** Send one test e-mail now (State Admin, settings check). */
    public static function test(string $to): void
    {
        if (!self::isLive()) {
            throw new \DomainException('E-mail is not set up: fill the \'mail\' part of config.php (SMTP host, port, user, password, from).');
        }
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new \DomainException('Enter a valid e-mail address.');
        }
        $smtp = new Smtp((array) config('mail'));
        $smtp->open();
        $b = ['subject' => 'Test e-mail from ITI CareerTrack', 'from' => 'DVET State Office', 'person' => (string) (Auth::user()['full_name'] ?? ''),
            'body' => "This is a test e-mail. If you can read this, e-mail sending from ITI CareerTrack works.\n\nयह ई-मेल मिळाला म्हणजे ई-मेल सेटिंग बरोबर आहे.",
            'priority' => 'NORMAL', 'reply_by' => null, 'link' => absolute_url('/mail'), 'files' => [], 'id' => 0, 'reply_to' => null];
        try {
            $smtp->send((string) config('mail.from'), $to, self::mime($b, $to, false));
        } finally {
            $smtp->close();
        }
    }

    /** Everything needed to write the e-mails of one message (attachments read once per run). */
    private static function build(int $id): array
    {
        $m = DB::one('SELECT m.*, u.full_name AS person, u.email AS person_email, ' . self::boxNameSql('m.from_box_type', 'm.from_box_id') . ' AS from_name
                        FROM mail_messages m JOIN users u ON u.id = m.from_user_id WHERE m.id = ?', [$id]);
        $files = [];
        $total = 0;
        $cap = (int) config('mail.attach_in_email_mb', 10) * 1048576;
        foreach (DB::all('SELECT * FROM mail_attachments WHERE message_id = ? ORDER BY id', [$id]) as $a) {
            $p = self::path($a);
            $inline = is_file($p) && ($total += (int) $a['size_bytes']) <= $cap;
            $files[] = ['name' => $a['original_filename'], 'type' => self::serveType($a['original_filename']), 'size' => (int) $a['size_bytes'],
                'data' => $inline ? (string) file_get_contents($p) : null];
        }
        return ['id' => $id, 'subject' => $m['subject'], 'from' => (string) $m['from_name'], 'person' => (string) $m['person'], 'body' => $m['body'],
            'priority' => $m['priority'], 'reply_by' => $m['reply_by'], 'files' => $files,
            'reply_to' => self::deliverable((string) $m['person_email']) ? $m['person_email'] : null,
            'link' => ($lb = self::linkBase()) !== '' ? $lb . '/mail/' . $id : ''];
    }

    /** Remember the site address (from a web request) for the links in e-mails sent later by cron. */
    private static function rememberBase(): void
    {
        if (PHP_SAPI !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
            DB::run('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
                ['mail_link_base', mb_substr(rtrim(absolute_url('/'), '/'), 0, 255)]);
        }
    }

    /** Link in the e-mail: config app.url (cron has no HTTP_HOST) or the current host. */
    private static function linkBase(): string
    {
        $u = rtrim((string) config('app.url', ''), '/');
        if ($u === '' && PHP_SAPI === 'cli') {
            $u = rtrim((string) DB::value("SELECT setting_value FROM settings WHERE setting_key = 'mail_link_base'"), '/');
        }
        return $u !== '' || PHP_SAPI === 'cli' ? $u : rtrim(absolute_url('/'), '/');
    }

    private static function hdr(string $s): string
    {
        $s = trim(preg_replace('/[\r\n]+/', ' ', $s));
        return preg_match('/^[\x20-\x7e]*$/', $s) ? $s : '=?UTF-8?B?' . base64_encode($s) . '?=';
    }

    /** RFC 5322 message: text + HTML part, attachments; one recipient per e-mail (ITIs never see each other's addresses). */
    public static function mime(array $b, string $to, bool $reminder): string
    {
        $subject = ($reminder ? 'Reminder: ' : '') . ($b['priority'] === 'URGENT' ? '[URGENT] ' : ($b['priority'] === 'HIGH' ? '[Important] ' : '')) . $b['subject'];
        $fromName = (string) config('mail.from_name', 'ITI CareerTrack (DVET)');
        $host = substr((string) strrchr((string) config('mail.from'), '@'), 1) ?: 'localhost';
        $mix = 'mx' . bin2hex(random_bytes(8));
        $alt = 'al' . bin2hex(random_bytes(8));
        $lines = [];
        $lines[] = "From: {$b['from']} — {$b['person']}";
        if ($b['reply_by']) {
            $lines[] = 'Reply required by: ' . date('d M Y', strtotime($b['reply_by']));
        }
        $text = implode("\n", $lines) . "\n\n" . $b['body'] . "\n\n";
        foreach ($b['files'] as $f) {
            $text .= 'Attachment: ' . $f['name'] . ($f['data'] === null ? ' (open in the portal)' : '') . "\n";
        }
        $text .= $b['link'] !== '' ? "\nOpen in ITI CareerTrack and reply there (the reply is recorded): {$b['link']}\n" : "\nOpen ITI CareerTrack (Email & Circulars) and reply there.\n";
        $esc = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        $html = '<!doctype html><html><body style="margin:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a">'
            . '<div style="max-width:640px;margin:0 auto;padding:20px"><div style="background:#1e3a8a;color:#fff;padding:14px 18px;border-radius:8px 8px 0 0;font-weight:bold">'
            . $esc($fromName) . '</div><div style="background:#fff;padding:18px;border-radius:0 0 8px 8px;border:1px solid #e2e8f0">'
            . ($b['priority'] !== 'NORMAL' ? '<div style="display:inline-block;background:' . ($b['priority'] === 'URGENT' ? '#dc2626' : '#d97706') . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:12px;margin-bottom:8px">'
                . $esc(self::PRIORITIES[$b['priority']]) . '</div>' : '')
            . '<h2 style="margin:0 0 6px;font-size:18px">' . $esc(($reminder ? 'Reminder: ' : '') . $b['subject']) . '</h2>'
            . '<div style="color:#475569;font-size:13px;margin-bottom:14px">From: <b>' . $esc($b['from']) . '</b> — ' . $esc($b['person'])
            . ($b['reply_by'] ? '<br>Reply required by: <b>' . $esc(date('d M Y', strtotime($b['reply_by']))) . '</b>' : '') . '</div>'
            . '<div style="font-size:14px;line-height:1.55">' . nl2br($esc($b['body'])) . '</div>';
        if ($b['files']) {
            $html .= '<div style="margin-top:14px;padding-top:10px;border-top:1px solid #e2e8f0;font-size:13px"><b>Attachments</b><br>';
            foreach ($b['files'] as $f) {
                $html .= '📎 ' . $esc($f['name']) . ' (' . $esc(self::size($f['size'])) . ')' . ($f['data'] === null ? ' — open in the portal' : '') . '<br>';
            }
            $html .= '</div>';
        }
        $html .= ($b['link'] !== '' ? '<p style="margin-top:18px"><a href="' . $esc($b['link']) . '" style="background:#2563eb;color:#fff;padding:9px 16px;border-radius:6px;text-decoration:none;font-weight:bold">Open in ITI CareerTrack</a></p>' : '')
            . '<p style="color:#64748b;font-size:12px">Please reply inside the portal so that your reply is recorded with DVET.</p></div></div></body></html>';

        $h = [
            'Date: ' . date('r'),
            'From: ' . self::hdr($fromName) . ' <' . config('mail.from') . '>',
            'To: <' . $to . '>',
            'Subject: ' . self::hdr($subject),
            'Message-ID: <ct' . $b['id'] . '.' . bin2hex(random_bytes(6)) . '@' . $host . '>',
            'MIME-Version: 1.0',
        ];
        if ($b['reply_to']) {
            $h[] = 'Reply-To: <' . $b['reply_to'] . '>';
        }
        if ($b['priority'] !== 'NORMAL') {
            $h[] = 'X-Priority: ' . ($b['priority'] === 'URGENT' ? '1' : '2');
            $h[] = 'Importance: High';
        }
        $h[] = 'Content-Type: multipart/mixed; boundary="' . $mix . '"';
        $out = implode("\r\n", $h) . "\r\n\r\n";
        $out .= "--$mix\r\nContent-Type: multipart/alternative; boundary=\"$alt\"\r\n\r\n";
        $out .= "--$alt\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text)) . "\r\n";
        $out .= "--$alt\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html)) . "\r\n";
        $out .= "--$alt--\r\n";
        foreach ($b['files'] as $f) {
            if ($f['data'] === null) {
                continue;
            }
            $n = self::hdr($f['name']);
            $out .= "--$mix\r\nContent-Type: {$f['type']}; name=\"$n\"\r\nContent-Disposition: attachment; filename=\"$n\"\r\nContent-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($f['data'])) . "\r\n";
        }
        return $out . "--$mix--\r\n";
    }

    public static function size(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1) . ' MB' : max(1, (int) round($bytes / 1024)) . ' KB';
    }
}
