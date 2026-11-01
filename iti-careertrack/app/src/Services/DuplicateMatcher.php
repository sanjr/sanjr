<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database as DB;

/**
 * "Does this student already exist?" — runs BEFORE a new student is created.
 *
 * Score bands:
 *   100    certain  (same SSC board+seat+year, or same Aadhaar fingerprint) -> must use existing student
 *   70-95  strong   -> blocked unless the user gives an override reason (audited)
 *   50-69  possible -> allowed, logged in duplicate_candidates for later review
 * Soft signals: first name similar +25 | surname similar +10 | whole name very similar +15
 *               same date of birth +30 | same mobile +25
 * First name is scored separately so twins/siblings (same surname, DOB, family mobile) are not blocked.
 */
final class DuplicateMatcher
{
    public const BLOCK = 70;
    public const FLAG = 50;

    public static function normalize(?string $s): string
    {
        $s = strtolower(preg_replace('/[^A-Za-z ]/', '', (string) $s) ?? '');
        return trim(preg_replace('/\s+/', ' ', $s) ?? '');
    }

    public static function mobile(?string $s): ?string
    {
        $d = preg_replace('/\D/', '', (string) $s) ?? '';
        return $d === '' ? null : substr($d, -10);
    }

    public static function similarity(string $a, string $b): float
    {
        $a = self::normalize($a);
        $b = self::normalize($b);
        if ($a === '' || $b === '') {
            return 0.0;
        }
        if ($a === $b) {
            return 1.0;
        }
        $max = max(strlen($a), strlen($b));
        $lev = 1 - levenshtein($a, $b) / $max;
        $sound = metaphone($a) === metaphone($b) ? 0.85 : 0.0;
        return max($lev, $sound);
    }

    /** @return list<array{student_id:int,score:int,reasons:list<string>,row:array}> */
    public static function find(array $in): array
    {
        $first = (string) ($in['first_name'] ?? '');
        $last = (string) ($in['last_name'] ?? '');
        $dob = (string) ($in['date_of_birth'] ?? '');
        $mob = self::mobile($in['mobile'] ?? null);
        $sscBoard = $in['ssc_board'] ?? null;
        $sscSeat = $in['ssc_seat_number'] ?? null;
        $sscYear = $in['ssc_passing_year'] ?? null;
        $aHash = $in['aadhaar_hash'] ?? null;

        $ids = [];
        if ($aHash) {
            $ids = array_merge($ids, array_column(DB::all('SELECT id FROM students WHERE aadhaar_hash = ?', [$aHash]), 'id'));
        }
        if ($sscSeat && $sscBoard && $sscYear) {
            $ids = array_merge($ids, array_column(DB::all('SELECT id FROM students WHERE ssc_board = ? AND ssc_seat_number = ? AND ssc_passing_year = ?',
                [$sscBoard, $sscSeat, (int) $sscYear]), 'id'));
        }
        if ($dob) {
            $ids = array_merge($ids, array_column(DB::all('SELECT id FROM students WHERE date_of_birth = ? LIMIT 500', [$dob]), 'id'));
        }
        if ($mob) {
            $ids = array_merge($ids, array_column(DB::all("SELECT DISTINCT student_id AS id FROM student_contacts WHERE value_normalized = ?
                AND valid_to IS NULL AND contact_type IN ('MOBILE','WHATSAPP','GUARDIAN_MOBILE')", [$mob]), 'id'));
        }
        if ($last !== '' && $dob) {
            $ids = array_merge($ids, array_column(DB::all('SELECT id FROM students WHERE SOUNDEX(last_name) = SOUNDEX(?) AND YEAR(date_of_birth) = YEAR(?) LIMIT 500',
                [$last, $dob]), 'id'));
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) {
            return [];
        }
        $rows = DB::all('SELECT s.*, p.pcid,
                                (SELECT GROUP_CONCAT(value_normalized) FROM student_contacts c WHERE c.student_id = s.id AND c.valid_to IS NULL
                                    AND c.contact_type IN (\'MOBILE\',\'WHATSAPP\',\'GUARDIAN_MOBILE\')) AS mobiles,
                                (SELECT i.name FROM institute_affiliations ia JOIN itis i ON i.id = ia.iti_id WHERE ia.student_id = s.id
                                    ORDER BY ia.start_date DESC LIMIT 1) AS iti_name
                           FROM students s LEFT JOIN pcid_registry p ON p.student_id = s.id AND p.status = \'ACTIVE\'
                          WHERE s.profile_status = \'ACTIVE\' AND s.id IN (' . DB::in($ids) . ')', $ids);

        $out = [];
        $fullIn = trim("$first " . ($in['middle_name'] ?? '') . " $last");
        foreach ($rows as $r) {
            $reasons = [];
            if ($aHash && $r['aadhaar_hash'] !== null && hash_equals($r['aadhaar_hash'], $aHash)) {
                $reasons[] = 'Aadhaar';
            }
            if ($sscSeat && $r['ssc_seat_number'] === $sscSeat && $r['ssc_board'] === $sscBoard && (int) $r['ssc_passing_year'] === (int) $sscYear) {
                $reasons[] = 'SSC seat number';
            }
            $score = $reasons ? 100 : 0;
            if (!$reasons) {
                if (self::similarity($r['first_name'], $first) >= 0.75) { $score += 25; $reasons[] = 'First name'; }
                if ($last !== '' && self::similarity((string) $r['last_name'], $last) >= 0.6) { $score += 10; $reasons[] = 'Surname'; }
                $full = trim($r['first_name'] . ' ' . $r['middle_name'] . ' ' . $r['last_name']);
                if (self::similarity($full, $fullIn) >= 0.85) { $score += 15; $reasons[] = 'Full name'; }
                if ($dob && $r['date_of_birth'] === $dob) { $score += 30; $reasons[] = 'Date of birth'; }
                if ($mob && in_array($mob, explode(',', (string) $r['mobiles']), true)) { $score += 25; $reasons[] = 'Mobile'; }
                $score = min(95, $score);
            }
            if ($score >= self::FLAG) {
                $out[] = ['student_id' => (int) $r['id'], 'score' => $score, 'reasons' => $reasons, 'row' => $r];
            }
        }
        usort($out, static fn ($a, $b) => $b['score'] <=> $a['score']);
        return $out;
    }

    public static function aadhaarHash(?string $aadhaar): ?string
    {
        $d = preg_replace('/\D/', '', (string) $aadhaar) ?? '';
        if ($d === '') {
            return null;
        }
        return hash_hmac('sha256', $d, (string) config('app.secret'), true);
    }
}
