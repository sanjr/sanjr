<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Audit;
use App\Core\Database as DB;

/**
 * WhatsApp bot for career status updates (WhatsApp Business Cloud API by Meta).
 *
 *  OUT  The ITI sends an approved template: "What are you doing now? 1 job · 2 apprenticeship ·
 *       3 studies · 4 own business · 5 looking for a job" (or the nightly job sends it as a reminder).
 *  IN   The student answers with the number, then one or two short replies (company, date …).
 *       The record is saved as source STUDENT, "pending verification" for the ITI, and the current
 *       career status is updated at once. "STOP" ends all messages to that number.
 *
 * Without the API settings in config.php the bot runs in DEMO mode: nothing is sent to a phone,
 * messages are only recorded, and the page offers "simulate the student's reply" for training and
 * presentation. The same code handles a real reply and a simulated one.
 */
final class WhatsApp
{
    public const CHOICES = ['1' => 'JOB', '2' => 'APPR', '3' => 'EDU', '4' => 'BIZ', '5' => 'SEEK'];

    public static function cfg(string $key, mixed $default = null): mixed
    {
        return config('whatsapp.' . $key, $default);
    }

    public static function isLive(): bool
    {
        return (string) self::cfg('phone_number_id', '') !== '' && (string) self::cfg('token', '') !== '';
    }

    public static function menu(string $name): string
    {
        return "नमस्कार {$name}! 🙏\nITI CareerTrack (DVET महाराष्ट्र): तुम्ही सध्या काय करत आहात? फक्त क्रमांक पाठवा:\n"
            . "1️⃣ नोकरी\n2️⃣ Apprenticeship\n3️⃣ पुढील शिक्षण\n4️⃣ स्वतःचा व्यवसाय\n5️⃣ नोकरी शोधत आहे\n"
            . "(संदेश बंद करण्यासाठी STOP पाठवा)";
    }

    /** Current mobile (10 digits) of the student, WhatsApp number first. */
    public static function phoneOf(int $sid): ?string
    {
        $p = DB::value("SELECT value_normalized FROM student_contacts WHERE student_id = ? AND contact_type IN ('WHATSAPP','MOBILE') AND valid_to IS NULL
                         ORDER BY contact_type = 'WHATSAPP' DESC, is_primary DESC LIMIT 1", [$sid]);
        return $p && preg_match('/^[6-9]\d{9}$/', (string) $p) ? (string) $p : null;
    }

    // ------------------------------------------------------------------ sending

    /** Ask one student. Returns DEMO / SENT / FAILED / SKIPPED:<reason>. */
    public static function ask(int $sid, ?int $by, ?int $itiId, string $kind = 'TEMPLATE'): string
    {
        $phone = self::phoneOf($sid);
        if (!$phone) {
            return 'SKIPPED:no mobile';
        }
        $sess = DB::one('SELECT * FROM whatsapp_sessions WHERE phone = ?', [$phone]);
        if ($sess && (int) $sess['opted_out'] === 1) {
            return 'SKIPPED:stopped';
        }
        $name = (string) DB::value('SELECT first_name FROM students WHERE id = ?', [$sid]);
        $body = self::menu($name);
        $status = 'DEMO';
        $waId = null;
        $error = null;
        if (self::isLive()) {
            [$ok, $waId, $error] = self::post(['messaging_product' => 'whatsapp', 'to' => '91' . $phone, 'type' => 'template',
                'template' => ['name' => (string) self::cfg('template', 'career_status_update'), 'language' => ['code' => (string) self::cfg('language', 'mr')],
                    'components' => [['type' => 'body', 'parameters' => [['type' => 'text', 'text' => $name ?: 'विद्यार्थी']]]]]]);
            $status = $ok ? 'SENT' : 'FAILED';
        }
        DB::insert('whatsapp_messages', ['student_id' => $sid, 'phone' => $phone, 'direction' => 'OUT', 'kind' => $kind, 'body' => $body,
            'wa_message_id' => $waId, 'status' => $status, 'error' => $error, 'sent_by' => $by, 'iti_id' => $itiId]);
        DB::run("INSERT INTO whatsapp_sessions (phone, student_id, step) VALUES (?, ?, 'ASK')
                 ON DUPLICATE KEY UPDATE student_id = VALUES(student_id), step = 'ASK', data = NULL", [$phone, $sid]);
        return $status;
    }

    /** A free text answer inside the 24-hour window opened by the student's message. */
    private static function reply(string $phone, ?int $sid, string $body): void
    {
        $status = 'DEMO';
        $waId = null;
        $error = null;
        if (self::isLive()) {
            [$ok, $waId, $error] = self::post(['messaging_product' => 'whatsapp', 'to' => '91' . $phone, 'type' => 'text', 'text' => ['body' => $body]]);
            $status = $ok ? 'SENT' : 'FAILED';
        }
        DB::insert('whatsapp_messages', ['student_id' => $sid, 'phone' => $phone, 'direction' => 'OUT', 'kind' => 'TEXT', 'body' => mb_substr($body, 0, 1000),
            'wa_message_id' => $waId, 'status' => $status, 'error' => $error]);
    }

    /** @return array{0:bool, 1:?string, 2:?string} ok, message id, error */
    private static function post(array $payload): array
    {
        $url = 'https://graph.facebook.com/' . self::cfg('api_version', 'v21.0') . '/' . rawurlencode((string) self::cfg('phone_number_id')) . '/messages';
        $ctx = stream_context_create(['http' => ['method' => 'POST', 'timeout' => 15, 'ignore_errors' => true,
            'header' => "Content-Type: application/json\r\nAuthorization: Bearer " . self::cfg('token') . "\r\n", 'content' => json_encode($payload, JSON_UNESCAPED_UNICODE)]]);
        $t0 = microtime(true);
        $res = @file_get_contents($url, false, $ctx);
        $code = isset($http_response_header[0]) && preg_match('/\s(\d{3})\b/', $http_response_header[0], $m) ? (int) $m[1] : 0;
        $data = is_string($res) ? json_decode($res, true) : null;
        $ok = $code === 200 && !empty($data['messages'][0]['id']);
        DB::insert('integration_logs', ['integration' => 'WHATSAPP', 'operation' => 'send', 'status' => $ok ? 'OK' : 'FAILED', 'http_status' => $code ?: null,
            'duration_ms' => (int) ((microtime(true) - $t0) * 1000), 'error_code' => $ok ? null : mb_substr((string) ($data['error']['code'] ?? 'no_response'), 0, 60)]);
        return [$ok, $ok ? (string) $data['messages'][0]['id'] : null, $ok ? null : mb_substr((string) ($data['error']['message'] ?? 'No answer from WhatsApp (HTTP ' . $code . ')'), 0, 255)];
    }

    // ------------------------------------------------------------------ receiving

    /** Webhook body from Meta: messages from students and delivery statuses. */
    public static function webhook(string $raw, ?string $signature): int
    {
        // only calls signed with the Meta app secret are accepted (otherwise anyone could post "replies")
        $secret = (string) self::cfg('app_secret', '');
        if ($secret === '' || !hash_equals('sha256=' . hash_hmac('sha256', $raw, $secret), (string) $signature)) {
            return 403;
        }
        $data = json_decode($raw, true);
        foreach ((array) ($data['entry'] ?? []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $ch) {
                $v = (array) ($ch['value'] ?? []);
                foreach ((array) ($v['statuses'] ?? []) as $st) {
                    $map = ['sent' => 'SENT', 'delivered' => 'DELIVERED', 'read' => 'READ', 'failed' => 'FAILED'];
                    if (!empty($st['id']) && isset($map[$st['status'] ?? ''])) {
                        DB::run('UPDATE whatsapp_messages SET status = ?, error = COALESCE(?, error) WHERE wa_message_id = ?',
                            [$map[$st['status']], isset($st['errors'][0]['title']) ? mb_substr((string) $st['errors'][0]['title'], 0, 255) : null, $st['id']]);
                    }
                }
                foreach ((array) ($v['messages'] ?? []) as $msg) {
                    $phone = substr(preg_replace('/\D/', '', (string) ($msg['from'] ?? '')) ?? '', -10);
                    $text = match ($msg['type'] ?? '') {
                        'text' => (string) ($msg['text']['body'] ?? ''),
                        'button' => (string) ($msg['button']['payload'] ?? $msg['button']['text'] ?? ''),
                        'interactive' => (string) ($msg['interactive']['button_reply']['id'] ?? $msg['interactive']['list_reply']['id'] ?? ''),
                        default => '',
                    };
                    if (strlen($phone) === 10 && !DB::value('SELECT 1 FROM whatsapp_messages WHERE wa_message_id = ? AND direction = ?', [(string) ($msg['id'] ?? ''), 'IN'])) {
                        self::incoming($phone, $text, (string) ($msg['id'] ?? '') ?: null);
                    }
                }
            }
        }
        return 200;
    }

    /** One message from a student (real or simulated). Returns the bot's answer. */
    public static function incoming(string $phone, string $text, ?string $waId = null): string
    {
        $text = trim($text);
        $sess = DB::one('SELECT * FROM whatsapp_sessions WHERE phone = ?', [$phone]);
        $sid = $sess['student_id'] ?? null;
        if (!$sid) {
            $ids = DB::all("SELECT DISTINCT student_id FROM student_contacts WHERE contact_type IN ('MOBILE','WHATSAPP') AND value_normalized = ? AND valid_to IS NULL", [$phone]);
            $sid = count($ids) === 1 ? (int) $ids[0]['student_id'] : null;
        }
        $sid = $sid ? (int) $sid : null;
        DB::insert('whatsapp_messages', ['student_id' => $sid, 'phone' => $phone, 'direction' => 'IN', 'kind' => 'TEXT', 'body' => mb_substr($text, 0, 1000),
            'wa_message_id' => $waId, 'status' => 'RECEIVED']);
        DB::run("INSERT INTO whatsapp_sessions (phone, student_id, step, last_in_at) VALUES (?, ?, 'IDLE', NOW())
                 ON DUPLICATE KEY UPDATE last_in_at = NOW(), student_id = COALESCE(student_id, VALUES(student_id))", [$phone, $sid]);
        $sess = DB::one('SELECT * FROM whatsapp_sessions WHERE phone = ?', [$phone]);
        $answer = self::converse($phone, $sid, $sess, $text);
        self::reply($phone, $sid, $answer);
        return $answer;
    }

    private static function setStep(string $phone, string $step, ?array $data = null): void
    {
        DB::run('UPDATE whatsapp_sessions SET step = ?, data = ? WHERE phone = ?', [$step, $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : null, $phone]);
    }

    private static function converse(string $phone, ?int $sid, array $sess, string $text): string
    {
        $u = mb_strtoupper($text);
        if (in_array($u, ['STOP', 'थांबा', 'बंद', 'UNSUBSCRIBE'], true)) {
            DB::run("UPDATE whatsapp_sessions SET opted_out = 1, step = 'IDLE', data = NULL WHERE phone = ?", [$phone]);
            return 'ठीक आहे, यापुढे संदेश पाठवले जाणार नाहीत. पुन्हा सुरू करण्यासाठी START पाठवा.';
        }
        if (in_array($u, ['START', 'सुरू'], true)) {
            DB::run("UPDATE whatsapp_sessions SET opted_out = 0 WHERE phone = ?", [$phone]);
            $u = 'MENU';
        }
        if (!$sid) {
            return 'हा मोबाईल क्रमांक ITI CareerTrack मध्ये नोंदलेला नाही. कृपया तुमच्या ITI शी संपर्क करा.';
        }
        $name = (string) DB::value('SELECT first_name FROM students WHERE id = ?', [$sid]);
        $step = (string) $sess['step'];
        $data = json_decode((string) ($sess['data'] ?? ''), true) ?: [];
        $choice = self::CHOICES[preg_replace('/\D/', '', strtr($text, ['१' => '1', '२' => '2', '३' => '3', '४' => '4', '५' => '5'])) ?? ''] ?? (in_array($u, self::CHOICES, true) ? $u : null);
        if (!$choice) {   // a word instead of the number
            foreach (['JOB' => ['नोकरी', 'JOB', 'NAUKRI', 'NOKRI'], 'APPR' => ['APPRENTICE', 'अप्रेंटिस', 'शिकाऊ'], 'EDU' => ['शिक्षण', 'DIPLOMA', 'COLLEGE', 'STUDY'],
                      'BIZ' => ['व्यवसाय', 'BUSINESS', 'धंदा'], 'SEEK' => ['शोधत', 'LOOKING', 'बेरोजगार']] as $c => $words) {
                foreach ($words as $w) {
                    if (mb_strlen($u) <= 25 && str_contains($u, mb_strtoupper($w))) {
                        $choice = $c;
                        break 2;
                    }
                }
            }
        }
        if ($choice && in_array($step, ['IDLE', 'ASK', 'DONE'], true)) {
            return self::start($phone, $sid, $choice, $name);
        }
        switch ($step) {
            case 'JOB_CO': case 'APPR_CO':
                if (mb_strlen($text) < 2) {
                    return 'कृपया कंपनीचे नाव लिहा.';
                }
                self::setStep($phone, $step === 'JOB_CO' ? 'JOB_DATE' : 'APPR_DATE', ['company' => mb_substr($text, 0, 150)]);
                return 'कधीपासून? तारीख पाठवा (उदा. 01-10-2026) किंवा "आज".';
            case 'JOB_DATE': case 'APPR_DATE':
                $date = self::parseDate($text);
                if (!$date) {
                    return 'तारीख समजली नाही. उदा. 01-10-2026 किंवा 10-2026 किंवा "आज" असे पाठवा.';
                }
                $what = $step === 'JOB_DATE' ? self::saveJob($sid, (string) $data['company'], $date) : self::saveApprenticeship($sid, (string) $data['company'], $date);
                self::setStep($phone, 'DONE');
                return "धन्यवाद {$name}! ✅ तुमची नोंद झाली: {$what}. ITI ती पडताळेल. काही बदल झाला तर कधीही 1-5 पैकी क्रमांक पाठवा.";
            case 'EDU':
                if (mb_strlen($text) < 3) {
                    return 'कॉलेज आणि अभ्यासक्रम लिहा (उदा. Govt Polytechnic Pune, Diploma Mechanical).';
                }
                $what = self::saveStudy($sid, $text);
                self::setStep($phone, 'DONE');
                return "धन्यवाद {$name}! ✅ नोंद झाली: {$what}.";
            case 'BIZ':
                if (mb_strlen($text) < 3) {
                    return 'व्यवसायाचा प्रकार आणि गाव लिहा (उदा. मोटर रिवाइंडिंग, शिरूर).';
                }
                $what = self::saveBusiness($sid, $text);
                self::setStep($phone, 'DONE');
                return "धन्यवाद {$name}! ✅ नोंद झाली: {$what}. शुभेच्छा!";
        }
        self::setStep($phone, 'ASK');
        return self::menu($name);
    }

    private static function start(string $phone, int $sid, string $choice, string $name): string
    {
        switch ($choice) {
            case 'JOB':
                self::setStep($phone, 'JOB_CO');
                return 'छान! 👍 कोणत्या कंपनीत नोकरी करता? कंपनीचे नाव व गाव लिहा.';
            case 'APPR':
                self::setStep($phone, 'APPR_CO');
                return 'छान! 👍 Apprenticeship कोणत्या कंपनीत? कंपनीचे नाव व गाव लिहा.';
            case 'EDU':
                self::setStep($phone, 'EDU');
                return 'छान! 📘 कॉलेज आणि अभ्यासक्रम लिहा (उदा. Govt Polytechnic Pune, Diploma Mechanical).';
            case 'BIZ':
                self::setStep($phone, 'BIZ');
                return 'छान! 🏪 व्यवसायाचा प्रकार आणि गाव लिहा (उदा. मोटर रिवाइंडिंग, शिरूर).';
            default:   // SEEK
                CareerService::setStatus($sid, 'SEEKING_EMPLOYMENT', date('Y-m-d'), [], 'WhatsApp: looking for a job', 'STUDENT');
                Audit::log('WHATSAPP_UPDATE', 'career_status_history', null, $sid, null, null, ['status' => 'SEEKING_EMPLOYMENT']);
                self::setStep($phone, 'DONE');
                return "नोंद झाली {$name}. 🔎 तुमच्या ITI ला कळवले आहे. नवीन नोकऱ्या: " . absolute_url('/careers');
        }
    }

    private static function parseDate(string $t): ?string
    {
        $t = trim(mb_strtolower($t));
        if (in_array($t, ['आज', 'today', 'aaj', 'aj'], true)) {
            return date('Y-m-d');
        }
        $t = strtr($t, ['०' => '0', '१' => '1', '२' => '2', '३' => '3', '४' => '4', '५' => '5', '६' => '6', '७' => '7', '८' => '8', '९' => '9']);
        if (preg_match('/^(\d{1,2})[-\/.](\d{4})$/', $t, $m) && checkdate((int) $m[1], 1, (int) $m[2])) {
            $d = sprintf('%04d-%02d-01', $m[2], $m[1]);
        } else {
            $d = AutoLink::date($t);
        }
        return $d && $d <= date('Y-m-d', strtotime('+60 days')) && $d >= '2000-01-01' ? $d : null;
    }

    /** a company typed by a student waits for the ITI's approval */
    private static function company(string $name): int
    {
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');
        $id = DB::value("SELECT id FROM companies WHERE LOWER(legal_name) = LOWER(?) AND status IN ('ACTIVE','PENDING_APPROVAL') ORDER BY status = 'ACTIVE' DESC LIMIT 1", [$name]);
        return $id ? (int) $id : DB::insert('companies', ['legal_name' => mb_substr($name, 0, 200), 'display_name' => mb_substr($name, 0, 200), 'status' => 'PENDING_APPROVAL']);
    }

    private static function saveJob(int $sid, string $company, string $date): string
    {
        return DB::tx(function () use ($sid, $company, $date) {
            $cid = self::company($company);
            $id = DB::insert('employments', ['student_id' => $sid, 'company_id' => $cid, 'designation' => 'As told on WhatsApp', 'employment_type' => 'FULL_TIME',
                'employer_sector' => 'PRIVATE', 'joining_date' => $date, 'status' => 'ACTIVE', 'source' => 'STUDENT']);
            CareerService::setStatus($sid, 'EMPLOYED', $date, ['employment_id' => $id], 'WhatsApp reply', 'STUDENT');
            Audit::log('WHATSAPP_UPDATE', 'employments', $id, $sid, null, null, ['company' => $company, 'from' => $date]);
            return 'नोकरी — ' . $company . ' (' . date('d-m-Y', strtotime($date)) . ' पासून)';
        });
    }

    private static function saveApprenticeship(int $sid, string $company, string $date): string
    {
        return DB::tx(function () use ($sid, $company, $date) {
            $cid = self::company($company);
            $id = DB::insert('apprenticeships', ['student_id' => $sid, 'company_id' => $cid, 'scheme' => 'OTHER', 'start_date' => $date, 'status' => 'ACTIVE', 'source' => 'STUDENT']);
            CareerService::setStatus($sid, 'APPRENTICE', $date, ['apprenticeship_id' => $id], 'WhatsApp reply', 'STUDENT');
            Audit::log('WHATSAPP_UPDATE', 'apprenticeships', $id, $sid, null, null, ['company' => $company, 'from' => $date]);
            return 'Apprenticeship — ' . $company . ' (' . date('d-m-Y', strtotime($date)) . ' पासून)';
        });
    }

    private static function saveStudy(int $sid, string $text): string
    {
        return DB::tx(function () use ($sid, $text) {
            [$college, $course] = array_map('trim', array_pad(explode(',', $text, 2), 2, ''));
            $course = $course !== '' ? $course : $college;
            $l = mb_strtoupper($course);
            $level = str_contains($l, 'DIPLOMA') || str_contains($l, 'पदविका') ? 'DIPLOMA' : (str_contains($l, 'DEGREE') || str_contains($l, 'B.') || str_contains($l, 'पदवी') ? 'DEGREE' : 'OTHER');
            $id = DB::insert('higher_education_records', ['student_id' => $sid, 'college_id' => CareerService::collegeIdByName($college), 'course_name' => mb_substr($course, 0, 150),
                'course_level' => $level, 'study_mode' => 'FULL_TIME', 'admission_year' => (int) date('Y'), 'source' => 'STUDENT']);
            CareerService::setStatus($sid, 'HIGHER_EDUCATION', date('Y-m-d'), ['higher_education_record_id' => $id], 'WhatsApp reply', 'STUDENT');
            Audit::log('WHATSAPP_UPDATE', 'higher_education_records', $id, $sid, null, null, ['text' => $text]);
            return 'पुढील शिक्षण — ' . $course . ($course !== $college ? ', ' . $college : '');
        });
    }

    private static function saveBusiness(int $sid, string $text): string
    {
        return DB::tx(function () use ($sid, $text) {
            [$type, $place] = array_map('trim', array_pad(explode(',', $text, 2), 2, ''));
            $id = DB::insert('business_records', ['student_id' => $sid, 'business_name' => mb_substr($type, 0, 150), 'business_type' => mb_substr($type, 0, 100),
                'registration_type' => 'UNREGISTERED', 'location' => $place !== '' ? mb_substr($place, 0, 150) : null, 'start_date' => date('Y-m-d'), 'source' => 'STUDENT']);
            CareerService::setStatus($sid, 'SELF_EMPLOYED', date('Y-m-d'), ['business_record_id' => $id], 'WhatsApp reply', 'STUDENT');
            Audit::log('WHATSAPP_UPDATE', 'business_records', $id, $sid, null, null, ['text' => $text]);
            return 'व्यवसाय — ' . $type . ($place !== '' ? ', ' . $place : '');
        });
    }

    // ------------------------------------------------------------------ nightly reminders

    /**
     * Pass-outs whose status is "not updated" or older than N days, not asked in the last 30 days and
     * not stopped, get the question again. Only with a live connection (DEMO mode sends nothing).
     */
    public static function reminders(int $limit = 500): array
    {
        $days = (int) self::cfg('reminder_days', 180);
        if (!self::isLive() || $days <= 0) {
            return ['sent' => 0, 'skipped' => 0, 'reason' => self::isLive() ? 'reminders off' : 'demo mode'];
        }
        $rows = DB::all("SELECT DISTINCT a.student_id, a.iti_id FROM admissions a
                           JOIN students s ON s.id = a.student_id AND s.profile_status = 'ACTIVE'
                           LEFT JOIN career_status_history c ON c.student_id = a.student_id AND c.current_flag = 1
                          WHERE a.status = 'COMPLETED' AND (c.id IS NULL OR c.status IN ('NOT_UPDATED','NOT_CONTACTABLE') OR c.effective_from < CURDATE() - INTERVAL ? DAY)
                            AND NOT EXISTS (SELECT 1 FROM whatsapp_messages w WHERE w.student_id = a.student_id AND w.direction = 'OUT' AND w.created_at > NOW() - INTERVAL 30 DAY)
                          LIMIT " . (int) $limit, [$days]);
        $sent = $skipped = 0;
        foreach ($rows as $r) {
            $res = self::ask((int) $r['student_id'], null, (int) $r['iti_id'], 'REMINDER');
            str_starts_with($res, 'SKIPPED') || $res === 'FAILED' ? $skipped++ : $sent++;
        }
        return ['sent' => $sent, 'skipped' => $skipped, 'reason' => ''];
    }
}
