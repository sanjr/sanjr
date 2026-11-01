<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;

/** Read a config value with dot notation, e.g. config('db.host'). */
function config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['CT_CONFIG'] ?? [];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

/** HTML-escape for output. Use for EVERY dynamic value printed in a view. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Base path when installed in a sub-folder (e.g. XAMPP htdocs/careertrack/public). */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $base = rtrim($dir, '/');
    }
    return $base;
}

function url(string $path = '/', array $query = []): string
{
    $u = base_path() . '/' . ltrim($path, '/');
    if ($query) {
        $u .= '?' . http_build_query($query);
    }
    return $u;
}

function asset(string $path): string
{
    $file = __DIR__ . '/../public/assets/' . $path;
    $v = is_file($file) ? (string) filemtime($file) : '1';
    return url('assets/' . $path) . '?v=' . $v;
}

function redirect(string $path, array $query = []): never
{
    header('Location: ' . url($path, $query), true, 303);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['old'][$key] ?? $default;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function can(string $permission): bool
{
    return Auth::can($permission);
}

function input(string $key, mixed $default = null): mixed
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function int_input(string $key, ?int $default = null): ?int
{
    $v = input($key);
    if ($v === null || $v === '' || !is_numeric($v)) {
        return $default;
    }
    return (int) $v;
}

function fmt_date(?string $d, string $format = 'd M Y'): string
{
    if (!$d) {
        return '—';
    }
    $ts = strtotime($d);
    return $ts ? date($format, $ts) : '—';
}

function money(mixed $amount, ?string $period = null): string
{
    if ($amount === null || $amount === '') {
        return '—';
    }
    $s = '₹' . number_format((float) $amount, 0, '.', ',');
    return $period ? $s . ' / ' . strtolower($period) : $s;
}

function num(mixed $n): string
{
    // Indian digit grouping: 4,98,320
    $n = (int) $n;
    $s = (string) abs($n);
    if (strlen($s) > 3) {
        $last3 = substr($s, -3);
        $rest = substr($s, 0, -3);
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        $s = $rest . ',' . $last3;
    }
    return ($n < 0 ? '-' : '') . $s;
}

function pct(float|int $part, float|int $whole, int $decimals = 1): string
{
    return $whole > 0 ? number_format($part * 100 / $whole, $decimals) . '%' : '—';
}

function label(?string $code): string
{
    if ($code === null || $code === '') {
        return '—';
    }
    $map = [
        'NOT_UPDATED' => 'Not Updated', 'NOT_CONTACTABLE' => 'Not Contactable', 'SEEKING_EMPLOYMENT' => 'Seeking Employment',
        'SELF_EMPLOYED' => 'Business / Self-employed', 'HIGHER_EDUCATION' => 'Higher Education', 'IN_TRAINING' => 'In Training',
        'TRANSFERRED_OUT' => 'Transferred Out', 'TRANSFER_IN' => 'Transfer In', 'COPA' => 'COPA',
    ];
    return $map[$code] ?? ucwords(strtolower(str_replace('_', ' ', $code)));
}

function badge(?string $status): string
{
    $s = strtoupper((string) $status);
    $cls = match (true) {
        in_array($s, ['VERIFIED', 'PASS', 'ACTIVE', 'COMPLETED', 'HIRED', 'EMPLOYED', 'ACCEPTED', 'PRESENT', 'OPEN'], true) => 'green',
        in_array($s, ['PENDING', 'APPLIED', 'REQUESTED', 'IN_PROGRESS', 'SUPPLEMENTARY', 'APPRENTICE', 'IN_TRAINING', 'LEAVE'], true) => 'amber',
        in_array($s, ['SHORTLISTED', 'INTERVIEW', 'OFFERED', 'HIGHER_EDUCATION', 'SELF_EMPLOYED', 'TRANSFER_IN'], true) => 'blue',
        in_array($s, ['REJECTED', 'FAIL', 'ABSENT', 'CANCELLED', 'DISCONTINUED', 'NOT_CONTACTABLE', 'TERMINATED'], true) => 'red',
        default => 'grey',
    };
    return '<span class="badge badge-' . $cls . '">' . e(label($status)) . '</span>';
}

/** Mask a name for duplicate warnings shown to other institutes: "Rohit Deshmukh" -> "R**** D*******" */
function mask_name(string $name): string
{
    return implode(' ', array_map(
        static fn ($w) => $w === '' ? '' : mb_substr($w, 0, 1) . str_repeat('*', max(1, mb_strlen($w) - 1)),
        explode(' ', $name)
    ));
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim(preg_replace('/[^\p{L} ]/u', '', $name) ?? '')) ?: [];
    $first = $parts[0] ?? '';
    $last = count($parts) > 1 ? $parts[count($parts) - 1] : '';
    return strtoupper(mb_substr($first, 0, 1) . mb_substr($last, 0, 1));
}

function icon(string $name, string $class = 'ic'): string
{
    return App\Core\Icons::svg($name, $class);
}

function current_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_path();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    return '/' . trim($uri, '/');
}

// ---------------------------------------------------------------------------
// View helpers
// ---------------------------------------------------------------------------
function kpi(string $label, string $value, string $icon, string $tone = 'blue', string $sub = ''): string
{
    return '<div class="kpi tone-' . e($tone) . '"><div class="kpi-ic">' . icon($icon) . '</div><div><div class="kpi-label">' . e($label)
        . '</div><div class="kpi-value">' . e($value) . '</div>' . ($sub !== '' ? '<div class="kpi-sub">' . e($sub) . '</div>' : '') . '</div></div>';
}

function field_error(string $name): string
{
    $err = $_SESSION['errors'][$name] ?? null;
    return $err ? '<div class="error">' . e($err) . '</div>' : '';
}

function has_error(string $name): string
{
    return isset($_SESSION['errors'][$name]) ? ' has-error' : '';
}

function options(array $rows, string $valueKey, string $labelKey, mixed $selected = null, string $placeholder = 'Select'): string
{
    $h = $placeholder !== '' ? '<option value="">' . e($placeholder) . '</option>' : '';
    foreach ($rows as $r) {
        $v = (string) $r[$valueKey];
        $h .= '<option value="' . e($v) . '"' . ((string) $selected === $v ? ' selected' : '') . '>' . e($r[$labelKey]) . '</option>';
    }
    return $h;
}

function enum_options(array $codes, mixed $selected = null, string $placeholder = 'Select'): string
{
    $h = $placeholder !== '' ? '<option value="">' . e($placeholder) . '</option>' : '';
    foreach ($codes as $c) {
        $h .= '<option value="' . e($c) . '"' . ((string) $selected === (string) $c ? ' selected' : '') . '>' . e(label($c)) . '</option>';
    }
    return $h;
}

function pager(array $p): string
{
    if ($p['pages'] <= 1) {
        return '<div class="pager muted">' . num($p['total']) . ' records</div>';
    }
    $q = $_GET;
    $h = '<div class="pager"><span class="muted">' . num($p['total']) . ' records · page ' . $p['page'] . ' of ' . $p['pages'] . '</span>';
    if ($p['page'] > 1) {
        $q['page'] = $p['page'] - 1;
        $h .= '<a class="btn btn-light btn-sm" href="' . e(url(current_path(), $q)) . '">‹ Prev</a>';
    }
    if ($p['page'] < $p['pages']) {
        $q['page'] = $p['page'] + 1;
        $h .= '<a class="btn btn-light btn-sm" href="' . e(url(current_path(), $q)) . '">Next ›</a>';
    }
    return $h . '</div>';
}

function chart(array $spec, string $class = 'chart'): string
{
    return '<div class="' . e($class) . '" data-chart="' . e(json_encode($spec, JSON_UNESCAPED_UNICODE)) . '"></div>';
}

/** Absolute URL (used inside the QR code on the PCID card). */
function absolute_url(string $path, array $query = []): string
{
    $https = ($_SERVER['HTTPS'] ?? '') === 'on' || (bool) config('app.force_https_cookies');
    $host = preg_replace('/[^A-Za-z0-9.:\-\[\]]/', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    return ($https ? 'https://' : 'http://') . $host . url($path, $query);
}


/**
 * Password hash: Argon2id with the OWASP-recommended cost (19 MiB, 2 passes). Strong against
 * cracking, yet hundreds of people can log in at the same moment without exhausting the server.
 */
function pw_hash(string $password): string
{
    return defined('PASSWORD_ARGON2ID')
        ? password_hash($password, PASSWORD_ARGON2ID, PW_OPTIONS)
        : password_hash($password, PASSWORD_DEFAULT);
}

const PW_OPTIONS = ['memory_cost' => 19456, 'time_cost' => 2, 'threads' => 1];
