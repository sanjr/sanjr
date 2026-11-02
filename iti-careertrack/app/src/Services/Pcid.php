<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database as DB;

/** PCID helpers: format/check-digit validation and minimal public verification. */
final class Pcid
{
    public static function luhn(string $digits): string
    {
        $sum = 0;
        $double = true;
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $d = (int) $digits[$i];
            if ($double) { $d *= 2; if ($d > 9) { $d -= 9; } }
            $sum += $d;
            $double = !$double;
        }
        return (string) ((10 - $sum % 10) % 10);
    }

    /**
     * A PCID typed in any way -> the stored form. "itic mh 2026 001080 4", "ITICMH20260010804" and
     * "ITIC-MH-2026-001080-4" all become "ITIC-MH-2026-001080-4" (like an Aadhaar number, typed with or
     * without spaces / hyphens). Anything that is not shaped like a PCID is returned trimmed and in capitals.
     */
    public static function normalize(string $s): string
    {
        $s = strtoupper(trim($s));
        $compact = preg_replace('/[^A-Z0-9]/', '', $s) ?? '';
        if (preg_match('/^([A-Z]{2,6})([A-Z]{2})(\d{4})(\d{6})(\d)$/', $compact, $m)) {
            return "$m[1]-$m[2]-$m[3]-$m[4]-$m[5]";
        }
        return $s;
    }

    /** LIKE pattern for searching PCIDs whatever the user typed (compare with REPLACE(pcid, '-', '')). */
    public static function searchLike(string $q): string
    {
        $c = preg_replace('/[^A-Z0-9]/', '', strtoupper($q)) ?? '';
        return $c === '' ? '' : "%$c%";   // '' matches no PCID (only punctuation was typed)
    }

    /** True if the text looks like a PCID AND its last digit matches (catches typing mistakes). */
    public static function isValid(string $pcid): bool
    {
        if (!preg_match('/^[A-Z]{2,6}-[A-Z]{2}-(\d{4})-(\d{6})-(\d)$/', $pcid, $m)) {
            return false;
        }
        return self::luhn($m[1] . $m[2]) === $m[3];
    }

    /**
     * Public verification. Returns only: valid or not, masked name, issue year, ITI, trade, and training/certificate state.
     * Never returns date of birth, contacts, results, employer or salary.
     */
    public static function verify(string $pcid): array
    {
        if (!self::isValid($pcid)) {
            return ['state' => 'invalid'];
        }
        $r = DB::one('SELECT p.*, s.first_name, s.last_name, s.profile_status FROM pcid_registry p JOIN students s ON s.id = p.student_id WHERE p.pcid = ?', [$pcid]);
        if (!$r) {
            return ['state' => 'not_found'];
        }
        if ($r['status'] === 'RETIRED_MERGED') {
            $new = $r['redirect_to_pcid_id'] ? DB::value('SELECT pcid FROM pcid_registry WHERE id = ?', [$r['redirect_to_pcid_id']]) : null;
            return ['state' => 'merged', 'new_pcid' => $new];
        }
        if ($r['status'] !== 'ACTIVE') {
            return ['state' => 'void'];
        }
        $adm = DB::one('SELECT a.status, i.name AS iti, t.name AS trade FROM admissions a JOIN itis i ON i.id = a.iti_id JOIN trades t ON t.id = a.trade_id
                         WHERE a.student_id = ? ORDER BY a.admission_date DESC, a.id DESC LIMIT 1', [$r['student_id']]);
        $passed = (bool) DB::value("SELECT 1 FROM exam_results WHERE student_id = ? AND result = 'PASS' LIMIT 1", [$r['student_id']]);
        return ['state' => 'valid', 'student_id' => (int) $r['student_id'], 'pcid' => $pcid, 'name' => mask_name(trim($r['first_name'] . ' ' . $r['last_name'])),
                'issue_year' => (int) $r['issue_year'], 'iti' => $adm['iti'] ?? '—', 'trade' => $adm['trade'] ?? '—',
                'training' => $adm ? ($adm['status'] === 'COMPLETED' ? 'Course completed' : (in_array($adm['status'], ['ACCEPTED', 'ACTIVE'], true) ? 'Currently in training' : label($adm['status']))) : '—',
                'certificate' => $passed ? 'Trade examination passed' : 'Not yet passed / awaited'];
    }
}
