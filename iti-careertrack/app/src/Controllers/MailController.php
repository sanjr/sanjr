<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\HttpException;
use App\Core\View;
use App\Services\Mail;
use App\Services\Spreadsheet;

/**
 * Email & circulars dashboard: Inbox, Starred, Sent, Drafts, Archive; compose with attachments;
 * read / reply status of every ITI; reminders; e-mail delivery through the SMTP queue.
 */
final class MailController extends Controller
{
    public const FOLDERS = ['inbox' => 'Inbox', 'starred' => 'Starred', 'sent' => 'Sent', 'drafts' => 'Drafts', 'archive' => 'Archive'];

    public function index(): void
    {
        [$bt, $bi] = Mail::requireBox();
        $f = (string) input('f', 'inbox');
        if (!isset(self::FOLDERS[$f]) || (in_array($f, ['drafts'], true) && !Mail::canCompose())) {
            $f = 'inbox';
        }
        $q = trim((string) input('q', ''));
        $from = Mail::boxNameSql('m.from_box_type', 'm.from_box_id');
        $att = '(SELECT COUNT(*) FROM mail_attachments a WHERE a.message_id = m.id) AS att';
        $search = '';
        $p = [];
        if ($q !== '') {
            $search = ' AND (m.subject LIKE ? OR m.body LIKE ?)';
            $p = ["%$q%", "%$q%"];
        }
        if (in_array($f, ['inbox', 'starred', 'archive'], true)) {
            $w = $f === 'archive' ? 'r.archived = 1' : ($f === 'starred' ? 'r.starred = 1' : 'r.archived = 0');
            $sql = "SELECT m.id, m.subject, m.body, m.priority, m.reply_by, m.sent_at, m.from_box_type, r.read_at, r.replied_at, r.starred, $att,
                           $from AS from_name, u.full_name AS person
                      FROM mail_recipients r JOIN mail_messages m ON m.id = r.message_id JOIN users u ON u.id = m.from_user_id
                     WHERE r.box_type = ? AND r.box_id = ? AND m.status = 'SENT' AND $w$search ORDER BY m.sent_at DESC, m.id DESC";
            $params = [$bt, $bi, ...$p];
        } elseif ($f === 'sent') {
            $sql = "SELECT m.id, m.subject, m.body, m.priority, m.reply_by, m.sent_at, m.audience_label, m.recipients_count, $att, u.full_name AS person,
                           (SELECT COUNT(*) FROM mail_recipients x WHERE x.message_id = m.id AND x.read_at IS NOT NULL) AS read_n,
                           (SELECT COUNT(*) FROM mail_recipients x WHERE x.message_id = m.id AND x.replied_at IS NOT NULL) AS replied_n
                      FROM mail_messages m JOIN users u ON u.id = m.from_user_id
                     WHERE m.from_box_type = ? AND m.from_box_id = ? AND m.status = 'SENT'$search ORDER BY m.sent_at DESC, m.id DESC";
            $params = [$bt, $bi, ...$p];
        } else {
            $sql = "SELECT m.id, m.subject, m.body, m.priority, m.updated_at AS sent_at, m.audience_label, $att
                      FROM mail_messages m WHERE m.from_user_id = ? AND m.from_box_type = ? AND m.from_box_id = ? AND m.status = 'DRAFT'$search ORDER BY m.updated_at DESC";
            $params = [Auth::id(), $bt, $bi, ...$p];
        }
        $box = [$bt, $bi];
        $this->render('mail/index', ['title' => 'Email & Circulars', 'f' => $f, 'q' => $q, 'p' => $this->paginate($sql, $params, 25),
            'compose' => Mail::canCompose(), 'live' => Mail::isLive(), 'boxName' => Mail::boxName($bt, $bi),
            'counts' => self::counts($box), 'kpi' => self::kpis($box),
            'itiEmail' => $bt === 'ITI' ? (string) DB::value('SELECT email FROM itis WHERE id = ?', [$bi]) : null,
            'myEmail' => (string) (Auth::user()['email'] ?? '')]);
    }

    private static function counts(array $box): array
    {
        return DB::one("SELECT
              (SELECT COUNT(*) FROM mail_recipients r JOIN mail_messages m ON m.id = r.message_id WHERE r.box_type = ? AND r.box_id = ? AND m.status = 'SENT' AND r.archived = 0 AND r.read_at IS NULL) AS inbox,
              (SELECT COUNT(*) FROM mail_recipients r WHERE r.box_type = ? AND r.box_id = ? AND r.starred = 1) AS starred,
              (SELECT COUNT(*) FROM mail_messages m WHERE m.from_user_id = ? AND m.status = 'DRAFT' AND m.from_box_type = ? AND m.from_box_id = ?) AS drafts",
            [$box[0], $box[1], $box[0], $box[1], Auth::id(), $box[0], $box[1]]) ?? [];
    }

    private static function kpis(array $box): array
    {
        $k = DB::one("SELECT COUNT(*) AS received, SUM(r.read_at IS NULL) AS unread,
                             SUM(m.reply_by IS NOT NULL AND r.replied_at IS NULL AND m.reply_by >= CURDATE()) AS to_answer,
                             SUM(m.reply_by IS NOT NULL AND r.replied_at IS NULL AND m.reply_by < CURDATE()) AS overdue
                        FROM mail_recipients r JOIN mail_messages m ON m.id = r.message_id
                       WHERE r.box_type = ? AND r.box_id = ? AND m.status = 'SENT' AND m.sent_at > NOW() - INTERVAL 90 DAY", $box) ?? [];
        $s = DB::one("SELECT COUNT(DISTINCT m.id) AS sent, COUNT(r.id) AS offices, SUM(r.read_at IS NOT NULL) AS read_n, SUM(r.replied_at IS NOT NULL) AS replied_n,
                             SUM(m.reply_by IS NOT NULL AND r.replied_at IS NULL) AS waiting
                        FROM mail_messages m LEFT JOIN mail_recipients r ON r.message_id = m.id
                       WHERE m.from_box_type = ? AND m.from_box_id = ? AND m.status = 'SENT' AND m.sent_at > NOW() - INTERVAL 90 DAY", $box) ?? [];
        $e = DB::one("SELECT SUM(d.status = 'SENT') AS sent, SUM(d.status IN ('QUEUED','SENDING')) AS queued, SUM(d.status = 'FAILED') AS failed
                        FROM mail_deliveries d JOIN mail_messages m ON m.id = d.message_id
                       WHERE m.from_box_type = ? AND m.from_box_id = ? AND d.created_at > NOW() - INTERVAL 90 DAY", $box) ?? [];
        return $k + ['sent' => $s['sent'] ?? 0, 'offices' => $s['offices'] ?? 0, 'read_n' => $s['read_n'] ?? 0, 'replied_n' => $s['replied_n'] ?? 0,
            'waiting' => $s['waiting'] ?? 0, 'email_sent' => $e['sent'] ?? 0, 'email_queued' => $e['queued'] ?? 0, 'email_failed' => $e['failed'] ?? 0];
    }

    public function compose(): void
    {
        Mail::requireBox();
        if (!Mail::canCompose()) {
            throw new HttpException(403);
        }
        $draft = null;
        $atts = [];
        if ($id = int_input('draft')) {
            $draft = Mail::visible($id);
            if ($draft['status'] !== 'DRAFT') {
                throw new HttpException(404);
            }
            $atts = DB::all('SELECT * FROM mail_attachments WHERE message_id = ? ORDER BY id', [$id]);
        }
        $a = $draft ? (json_decode((string) $draft['audience'], true) ?: []) : [];
        if (($forward = int_input('forward')) && !$draft) {   // "use as a new message"
            $src = Mail::visible($forward);
            $draft = ['id' => null, 'subject' => $src['subject'], 'body' => $src['body'], 'priority' => $src['priority'], 'reply_by' => null, 'extra_emails' => null];
        }
        $this->render('mail/compose', ['title' => $draft && $draft['id'] ? 'Edit draft' : 'New message', 'draft' => $draft, 'atts' => $atts,
            'a' => $a + ['iti' => 1, 'region' => 0, 'district' => 0, 'own' => '', 'mode' => 'all', 'iti_ids' => [], 'ro' => 0, 'ro_ids' => [], 'dvto' => 0],
            'opt' => Mail::audienceOptions(), 'live' => Mail::isLive(),
            'maxFile' => (int) config('mail.max_file_mb', 10), 'maxTotal' => (int) config('mail.max_total_mb', 25)]);
    }

    public function save(): void
    {
        $id = Mail::saveDraft(int_input('draft_id'), $_POST, $_FILES['attachments'] ?? []);
        if (input('action') !== 'send') {
            flash('success', 'Draft saved.');
            redirect('/mail/compose', ['draft' => $id]);
        }
        try {
            [$offices, $emails] = Mail::sendDraft($id);
        } catch (\DomainException $e) {
            flash('error', $e->getMessage());
            redirect('/mail/compose', ['draft' => $id]);
        }
        Mail::pump(10, $id);
        flash('success', "Message sent to $offices offices" . (Mail::isLive() ? " — $emails e-mails are being sent." : ' (DEMO mode: shown in their portal Inbox; no e-mails sent).'));
        redirect('/mail/' . $id);
    }

    public function deleteDraft(int $id): void
    {
        $m = Mail::visible($id);
        if ($m['status'] !== 'DRAFT') {
            throw new HttpException(404);
        }
        foreach (DB::all('SELECT * FROM mail_attachments WHERE message_id = ?', [$id]) as $a) {
            @unlink(Mail::path($a));
        }
        DB::run('DELETE FROM mail_attachments WHERE message_id = ?', [$id]);
        DB::run('DELETE FROM mail_messages WHERE id = ?', [$id]);
        flash('success', 'Draft deleted.');
        redirect('/mail', ['f' => 'drafts']);
    }

    public function show(int $id): void
    {
        [$bt, $bi] = Mail::requireBox();
        $m = Mail::visible($id);
        if ($m['status'] === 'DRAFT') {
            redirect('/mail/compose', ['draft' => $id]);
        }
        $mine = DB::one('SELECT * FROM mail_recipients WHERE message_id = ? AND box_type = ? AND box_id = ?', [$id, $bt, $bi]);
        if ($mine && !$mine['read_at']) {
            DB::run('UPDATE mail_recipients SET read_at = NOW(), read_by = ? WHERE id = ?', [Auth::id(), $mine['id']]);
        }
        $other = Mail::otherParty($m);
        $data = ['title' => $m['subject'], 'm' => $m, 'mine' => $mine, 'other' => $other, 'otherName' => $other ? Mail::boxName($other[0], $other[1]) : null,
            'conversation' => $other ? Mail::conversation($m, $other) : [], 'live' => Mail::isLive(), 'compose' => Mail::canCompose()];
        $data['files'] = self::filesOf(array_map(static fn ($c) => (int) $c['id'], $data['conversation']) ?: [$id]);
        if ($m['is_mine']) {
            $data += self::recipientStatus($m);
        }
        $this->render('mail/show', $data);
    }

    /** @return array<int, list<array>> message id => attachments */
    private static function filesOf(array $ids): array
    {
        $out = [];
        foreach (DB::all('SELECT * FROM mail_attachments WHERE message_id IN (' . DB::in($ids) . ') ORDER BY id', $ids) as $a) {
            $out[(int) $a['message_id']][] = $a;
        }
        return $out;
    }

    /** Sender view of a circular: per office read / reply / e-mail status. */
    private static function recipientStatus(array $m): array
    {
        $id = (int) $m['id'];
        $show = (string) input('r', 'all');
        $w = match ($show) {
            'unread' => ' AND r.read_at IS NULL', 'read' => ' AND r.read_at IS NOT NULL', 'replied' => ' AND r.replied_at IS NOT NULL',
            'not_replied' => ' AND r.replied_at IS NULL', 'failed' => " AND EXISTS (SELECT 1 FROM mail_deliveries x WHERE x.recipient_id = r.id AND x.status = 'FAILED')",
            'no_email' => ' AND NOT EXISTS (SELECT 1 FROM mail_deliveries x WHERE x.recipient_id = r.id)', default => '',
        };
        $p = [$id];
        if ($q = trim((string) input('rq', ''))) {
            $w .= ' AND (i.name LIKE ? OR i.iti_code LIKE ? OR d.name LIKE ?)';
            array_push($p, "%$q%", "%$q%", "%$q%");
        }
        $name = Mail::boxNameSql('r.box_type', 'r.box_id');
        $rows = DB::all("SELECT r.*, $name AS name, i.iti_code, COALESCE(d.name, dd.name) AS district, rb.full_name AS reader,
                                (SELECT GROUP_CONCAT(CONCAT(x.email, '|', x.status, '|', COALESCE(x.error, '')) ORDER BY x.id SEPARATOR '\n') FROM mail_deliveries x WHERE x.recipient_id = r.id) AS emails,
                                (SELECT MAX(rep.id) FROM mail_messages rep WHERE rep.thread_id = ? AND rep.from_box_type = r.box_type AND rep.from_box_id = r.box_id AND rep.status = 'SENT') AS reply_id
                           FROM mail_recipients r
                           LEFT JOIN itis i ON r.box_type = 'ITI' AND i.id = r.box_id
                           LEFT JOIN districts d ON d.id = i.district_id
                           LEFT JOIN districts dd ON r.box_type = 'DISTRICT' AND dd.id = r.box_id
                           LEFT JOIN users rb ON rb.id = r.read_by
                          WHERE r.message_id = ?$w ORDER BY r.read_at IS NULL DESC, r.box_type, name LIMIT 1000", [(int) ($m['thread_id'] ?: $id), ...$p]);
        $st = DB::one("SELECT COUNT(*) AS total, SUM(read_at IS NOT NULL) AS read_n, SUM(replied_at IS NOT NULL) AS replied_n,
                              SUM(NOT EXISTS (SELECT 1 FROM mail_deliveries x WHERE x.recipient_id = r.id)) AS no_email
                         FROM mail_recipients r WHERE r.message_id = ?", [$id]) ?? [];
        $em = DB::one("SELECT SUM(status = 'SENT') AS sent, SUM(status IN ('QUEUED','SENDING')) AS queued, SUM(status = 'FAILED') AS failed,
                              SUM(status = 'DEMO') AS demo, COUNT(*) AS total FROM mail_deliveries WHERE message_id = ?", [$id]) ?? [];
        return ['recipients' => $rows, 'rshow' => $show, 'rstats' => $st, 'estats' => $em];
    }

    public function reply(int $id): void
    {
        $nid = Mail::reply($id, (string) input('body', ''), $_FILES['attachments'] ?? []);
        Mail::pump(5, $nid);
        flash('success', 'Reply sent.');
        redirect('/mail/' . $id);
    }

    public function star(int $id): void
    {
        $this->flag($id, 'starred');
    }

    public function archive(int $id): void
    {
        $this->flag($id, 'archived');
        redirect('/mail', ['f' => 'inbox']);
    }

    private function flag(int $id, string $col): void
    {
        [$bt, $bi] = Mail::requireBox();
        Mail::visible($id);
        DB::run("UPDATE mail_recipients SET $col = 1 - $col WHERE message_id = ? AND box_type = ? AND box_id = ?", [$id, $bt, $bi]);
        if ($col === 'starred') {
            redirect('/mail/' . $id);
        }
    }

    public function markUnread(int $id): void
    {
        [$bt, $bi] = Mail::requireBox();
        Mail::visible($id);
        DB::run('UPDATE mail_recipients SET read_at = NULL, read_by = NULL WHERE message_id = ? AND box_type = ? AND box_id = ?', [$id, $bt, $bi]);
        redirect('/mail');
    }

    public function remind(int $id): void
    {
        $n = Mail::remind($id);
        Mail::pump(10, $id);
        flash('success', $n ? "Reminder e-mail queued to $n addresses of offices that have not opened the message." : 'Every office has opened the message (or has no e-mail address).');
        redirect('/mail/' . $id);
    }

    /** Called by the page while it is open: sends the next e-mails of this message (JSON). */
    public function pump(int $id): void
    {
        $m = Mail::visible($id);
        if (!$m['is_mine']) {
            throw new HttpException(404);
        }
        $r = Mail::pump(15, $id);
        $e = DB::one("SELECT SUM(status = 'SENT') AS sent, SUM(status IN ('QUEUED','SENDING')) AS queued, SUM(status = 'FAILED') AS failed, COUNT(*) AS total
                        FROM mail_deliveries WHERE message_id = ?", [$id]) ?? [];
        View::json(['sent' => (int) $e['sent'], 'queued' => (int) $e['queued'], 'failed' => (int) $e['failed'], 'total' => (int) $e['total'], 'note' => $r['reason']]);
    }

    public function export(int $id): void
    {
        $m = Mail::visible($id);
        if (!$m['is_mine']) {
            throw new HttpException(404);
        }
        $_GET['r'] = 'all';
        $rows = [['Office', 'ITI code', 'District', 'Type', 'E-mail addresses', 'E-mail status', 'Opened in portal', 'Opened by', 'Replied']];
        foreach (self::recipientStatus($m)['recipients'] as $r) {
            $em = array_filter(explode("\n", (string) $r['emails']));
            $rows[] = [(string) $r['name'], (string) $r['iti_code'], (string) $r['district'], label($r['box_type']),
                implode(', ', array_map(static fn ($x) => explode('|', $x)[0], $em)),
                $em ? implode(', ', array_map(static fn ($x) => label(explode('|', $x)[1]), $em)) : 'No e-mail address',
                $r['read_at'] ? fmt_date($r['read_at'], 'd-m-Y H:i') : 'Not opened', (string) $r['reader'], $r['replied_at'] ? fmt_date($r['replied_at'], 'd-m-Y H:i') : ''];
        }
        $bytes = Spreadsheet::xlsx(['Status' => $rows]);
        Audit::log('MAIL_EXPORT', 'mail_messages', $id);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="message-' . $id . '-status.xlsx"');
        header('Content-Length: ' . strlen($bytes));
        echo $bytes;
        exit;
    }

    public function attachment(int $id): void
    {
        $a = DB::one('SELECT * FROM mail_attachments WHERE id = ?', [$id]) ?? throw new HttpException(404);
        Mail::visible((int) $a['message_id']);
        $path = Mail::path($a);
        if (!is_file($path)) {
            throw new HttpException(404);
        }
        $type = Mail::serveType($a['original_filename']);
        $inline = in_array($type, ['application/pdf', 'image/jpeg', 'image/png'], true) && input('view') === '1';
        header('Content-Type: ' . $type);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $a['original_filename'])
            . "\"; filename*=UTF-8''" . rawurlencode($a['original_filename']));
        header('Cache-Control: private, no-store');
        header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox");
        readfile($path);
        exit;
    }

    /** Principal: the official e-mail address of the ITI (circulars are e-mailed there and to the Principal). */
    public function itiEmail(): void
    {
        if (!Auth::is('PRINCIPAL') || !Auth::itiId()) {
            throw new HttpException(403);
        }
        $e = strtolower(trim((string) input('email', '')));
        if ($e !== '' && !filter_var($e, FILTER_VALIDATE_EMAIL)) {
            throw new \DomainException('Enter a valid e-mail address.');
        }
        $old = DB::value('SELECT email FROM itis WHERE id = ?', [Auth::itiId()]);
        DB::run('UPDATE itis SET email = ?, updated_by = ? WHERE id = ?', [$e ?: null, Auth::id(), Auth::itiId()]);
        Audit::log('UPDATE', 'itis', Auth::itiId(), null, Auth::itiId(), ['email' => $old], ['email' => $e ?: null]);
        flash('success', $e ? "ITI e-mail saved: $e" : 'ITI e-mail removed.');
        redirect('/mail');
    }

    public function test(): void
    {
        if (!Auth::is('STATE_ADMIN')) {
            throw new HttpException(403);
        }
        try {
            Mail::test(trim((string) input('to', '')));
            flash('success', 'Test e-mail sent. Check the inbox (and the spam folder).');
        } catch (\App\Services\SmtpException $e) {
            flash('error', 'The mail server refused: ' . $e->getMessage());
        }
        redirect('/mail', ['f' => 'inbox']);
    }
}
