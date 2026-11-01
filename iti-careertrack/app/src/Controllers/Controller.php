<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database as DB;
use App\Core\View;

abstract class Controller
{
    protected function render(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    /** Store validation errors + old input, then go back to the form. */
    protected function backWithErrors(string $path, array $errors, array $query = []): never
    {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = array_diff_key($_POST, array_flip(['_csrf', 'password', 'aadhaar']));
        flash('error', 'Please correct the highlighted fields.');
        redirect($path, $query);
    }

    protected function require(array $fields): array
    {
        $errors = [];
        foreach ($fields as $k => $label) {
            if (trim((string) ($_POST[$k] ?? '')) === '') {
                $errors[$k] = "$label is required.";
            }
        }
        return $errors;
    }

    protected function date(string $key): string
    {
        $v = (string) input($key, '');
        $d = \DateTime::createFromFormat('Y-m-d', $v);
        if (!$d || $d->format('Y-m-d') !== $v) {
            throw new \DomainException('Please enter a valid date.');
        }
        return $v;
    }

    /** Small pagination helper. */
    protected function paginate(string $sql, array $params, int $perPage = 25, ?string $countSql = null): array
    {
        $page = max(1, (int) input('page', 1));
        // The total is cached for a minute (same query + same scope parameters): paging through a big
        // list does not count lakhs of rows again on every click.
        $cq = $countSql ?? self::countSql($sql);
        $total = (int) \App\Core\Cache::remember('count:' . md5($cq . json_encode($params)), (int) config('cache.count_ttl', 60),
            static fn () => DB::value($cq, $params));
        $rows = DB::all($sql . ' LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage), $params);
        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    /**
     * COUNT query for a list: when the top level is a plain SELECT … FROM … WHERE … ORDER BY (no DISTINCT,
     * GROUP BY, HAVING or UNION), count over the FROM/WHERE part only — the display columns
     * (and their sub-queries) are not computed for every row. Otherwise fall back to a derived table.
     */
    public static function countSql(string $sql): string
    {
        $fallback = "SELECT COUNT(*) FROM ($sql) x";
        $depth = 0;
        $from = $order = null;
        $len = strlen($sql);
        $quote = null;
        for ($i = 0; $i < $len; $i++) {
            $c = $sql[$i];
            if ($quote) { if ($c === $quote) { $quote = null; } continue; }
            if ($c === "'" || $c === '"') { $quote = $c; continue; }
            if ($c === '(') { $depth++; continue; }
            if ($c === ')') { $depth--; continue; }
            if ($depth !== 0 || !ctype_space($c)) { continue; }
            $rest = substr($sql, $i + 1, 12);
            if ($from === null && preg_match('/^FROM\s/i', $rest)) { $from = $i + 1; }
            elseif ($from !== null && preg_match('/^(GROUP\s+BY|HAVING|UNION)\b/i', $rest)) { return $fallback; }
            elseif ($from !== null && preg_match('/^ORDER\s+BY\b/i', $rest)) { $order = $i; }
        }
        if ($from === null || preg_match('/^\s*SELECT\s+(DISTINCT|STRAIGHT_JOIN)\b/i', $sql) || str_contains(substr($sql, 0, $from), '?')) {
            return $fallback;
        }
        return 'SELECT COUNT(*) ' . substr($sql, $from, ($order ?? $len) - $from);
    }

    protected function academicYears(): array
    {
        return DB::all('SELECT * FROM academic_years ORDER BY start_date DESC');
    }

    protected function currentYearId(): int
    {
        return (int) (DB::value("SELECT id FROM academic_years WHERE CURDATE() BETWEEN start_date AND end_date")
            ?? DB::value('SELECT id FROM academic_years ORDER BY start_date DESC LIMIT 1'));
    }
}
