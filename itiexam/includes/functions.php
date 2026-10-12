<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
    session_start();
}

/** Escape for HTML output. */
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function now(): string { return date('Y-m-d H:i:s'); }

/** "Hello World / Jobs" -> "hello-world/jobs" (slashes allowed for nested page URLs). */
function slugify(string $s, bool $allowSlash = false): string
{
    $s = strtolower(trim($s));
    $s = preg_replace($allowSlash ? '~[^a-z0-9/]+~' : '~[^a-z0-9]+~', '-', $s);
    if ($allowSlash) $s = preg_replace('~-*/-*~', '/', $s);
    return trim(preg_replace('~/+~', '/', $s), '-/');
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Form expired. Go back, refresh the page and try again.');
    }
}

/* ---------- Settings ---------- */
function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT skey, svalue FROM settings') as $r) $cache[$r['skey']] = $r['svalue'];
    }
    return $cache[$key] ?? $default;
}
function setting_set(string $key, string $value): void
{
    $pdo = db();
    $pdo->prepare('DELETE FROM settings WHERE skey = ?')->execute([$key]);
    $pdo->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?)')->execute([$key, $value]);
}

/* ---------- Menus ---------- */
const MENU_LOCATIONS = [
    'header'           => 'Header menu',
    'footer_quick'     => 'Footer – Quick Links',
    'footer_resources' => 'Footer – Useful Resources',
    'footer_legal'     => 'Footer – Bottom links',
];

/** Returns menu items as a tree: each item has 'children'. */
function menu(string $location): array
{
    $st = db()->prepare('SELECT * FROM menu_items WHERE location = ? ORDER BY sort_order, id');
    $st->execute([$location]);
    $all = $st->fetchAll();
    $tree = [];
    foreach ($all as $m) if (!$m['parent_id']) { $m['children'] = []; $tree[$m['id']] = $m; }
    foreach ($all as $m) if ($m['parent_id'] && isset($tree[$m['parent_id']])) $tree[$m['parent_id']]['children'][] = $m;
    return array_values($tree);
}

function is_external(string $url): bool { return (bool)preg_match('~^https?://~i', $url); }
function link_attrs(string $url): string { return is_external($url) ? ' target="_blank" rel="noopener"' : ''; }

/* ---------- Trades ---------- */
function trades(bool $popularOnly = false): array
{
    $sql = "SELECT * FROM trades WHERE status = 'published'" . ($popularOnly ? ' AND is_popular = 1' : '') . ' ORDER BY sort_order, name';
    return db()->query($sql)->fetchAll();
}
function trade_url(array $t): string { return '/trades/' . $t['slug'] . '/'; }
function upload_url(string $file): string { return $file ? UPLOAD_URL . '/' . rawurlencode($file) : ''; }

/**
 * Content sections of a trade page. Admin adds items to each section; empty sections are hidden.
 * fields = which item fields the admin form shows for that section.
 */
function trade_sections(): array
{
    return [
        'quick_links'         => ['label' => 'Quick Links (hero box)', 'tab' => '', 'icon' => 'link', 'fields' => ['subtitle' => false]],
        'features'            => ['label' => 'Feature Tiles (top cards)', 'tab' => '', 'icon' => 'grid', 'fields' => ['subtitle' => true]],
        'mock_tests'          => ['label' => 'Mock Tests', 'tab' => 'Mock Tests', 'icon' => 'clip', 'fields' => ['subtitle' => false, 'questions' => true, 'minutes' => true, 'is_new' => true]],
        'syllabus'            => ['label' => 'Syllabus & Modules', 'tab' => 'Syllabus', 'icon' => 'open', 'fields' => ['subtitle' => true]],
        'study_material'      => ['label' => 'Study Material', 'tab' => 'Theory Notes', 'icon' => 'pdf', 'fields' => ['subtitle' => true]],
        'practical'           => ['label' => 'Practical Exercises', 'tab' => 'Practical', 'icon' => 'flask', 'fields' => ['subtitle' => true]],
        'previous_papers'     => ['label' => 'Previous Year Papers', 'tab' => 'Previous Papers', 'icon' => 'doc', 'fields' => ['subtitle' => true, 'label' => 'Year (e.g. 2024)']],
        'important_questions' => ['label' => 'Important Questions', 'tab' => 'Important Questions', 'icon' => 'help', 'fields' => ['subtitle' => true]],
        'videos'              => ['label' => 'Video Tutorials', 'tab' => 'Video Tutorials', 'icon' => 'play', 'fields' => ['subtitle' => true]],
        'resources'           => ['label' => 'Software & Resources', 'tab' => 'Resources', 'icon' => 'link', 'fields' => ['subtitle' => true]],
        'faq'                 => ['label' => 'FAQ', 'tab' => 'FAQ', 'icon' => 'help', 'fields' => ['subtitle' => true]],
    ];
}

/** Items of a trade grouped by section. */
function trade_items(int $tradeId): array
{
    $st = db()->prepare('SELECT * FROM trade_items WHERE trade_id = ? ORDER BY sort_order, id');
    $st->execute([$tradeId]);
    $out = array_fill_keys(array_keys(trade_sections()), []);
    foreach ($st as $r) $out[$r['section']][] = $r;
    return $out;
}

/** Icon names available in the SVG sprite (for admin dropdowns). */
const ICONS = ['doc','clip','list','book','pdf','help','flask','open','layout','grid','monitor','code','globe','js','db','cloud','shield',
    'bulb','tool','gear','chat','target','cap','brief','users','bank','building','cal','clock','play','link','home','star','alert','download'];

/** Cycling chip colours so lists look like the design without the admin picking colours. */
function chip_color(int $i): string
{
    $c = ['c-teal','c-red','c-purple','c-navy','c-orange','c-green','c-blue','c-pink','c-indigo','c-cyan'];
    return $c[$i % count($c)];
}

function icon(string $name, string $class = 'ic'): string
{
    $name = in_array($name, ICONS, true) || in_array($name, ['arrow','chev','chev-r','search','menu','close','yt','fb','tg','wa','chev-l'], true) ? $name : 'doc';
    return '<svg class="' . e($class) . '" aria-hidden="true"><use href="#i-' . $name . '"/></svg>';
}

/** Pretty date like "12 Oct 2026". */
function nice_date(string $d): string { return $d ? date('d M Y', strtotime($d)) : ''; }

function not_found(): void
{
    http_response_code(404);
    require __DIR__ . '/../404.php';
    exit;
}
