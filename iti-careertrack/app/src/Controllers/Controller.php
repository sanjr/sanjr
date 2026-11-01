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
    protected function paginate(string $sql, array $params, int $perPage = 25): array
    {
        $page = max(1, (int) input('page', 1));
        $total = (int) DB::value("SELECT COUNT(*) FROM ($sql) x", $params);
        $rows = DB::all($sql . ' LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage), $params);
        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
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
