<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/student.php';

/* ---------- Safety for every request ---------- */

/** True when the visitor uses HTTPS (also behind a proxy / CDN that terminates SSL). */
function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
        || ($_SERVER['SERVER_PORT'] ?? '') === '443';
}

// Visitors never see PHP errors (they reveal server paths); they go to data/php-errors.log instead
if (PHP_SAPI !== 'cli' && !preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/', (string)($_SERVER['HTTP_HOST'] ?? ''))) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../data/php-errors.log');
}
/** Short code shown on the error page and in the admin's error list, so a visitor's screenshot points to the exact error. */
function error_code(string $msg, string $file, int $line): string { return strtoupper(substr(md5($msg . '|' . basename($file) . '|' . $line), 0, 6)); }

/** The "Something went wrong" page. Visitors see no details; a logged-in admin sees the cause, to fix it quickly. */
function error_page(string $msg, string $file, int $line): void
{
    if (!headers_sent()) { http_response_code(500); header('Content-Type: text/html; charset=utf-8'); }
    $admin = session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['admin']);
    $where = ltrim(str_replace('\\', '/', substr($file, strlen(dirname(__DIR__)))), '/');
    echo '<!DOCTYPE html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Something went wrong</title>'
        . '<div style="font:16px/1.5 system-ui,sans-serif;max-width:620px;margin:12vh auto;padding:24px;text-align:center"><h1 style="font-size:22px">Something went wrong</h1>'
        . '<p>Please go back and try again in a moment.</p><p><a href="/">Go to the homepage</a></p>'
        . '<p style="color:#94a3b8;font-size:13px">Error code: ' . error_code($msg, $file, $line) . '</p>'
        . ($admin ? '<div style="margin-top:22px;padding:14px 16px;border-radius:12px;background:#fff4ed;border:1px solid #fdcfae;text-align:left;font-size:14px;color:#7c2d12">'
            . '<b>Only you (admin) see this:</b><br>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '<br><code>' . htmlspecialchars($where, ENT_QUOTES, 'UTF-8') . ' line ' . $line . '</code>'
            . (str_contains($msg, 'undefined function') || str_contains($msg, 'not found') ? '<br><br>An old file is on the server. Upload the full website ZIP again and choose “Replace” for every file.' : '')
            . '</div>' : '')
        . '</div>';
}
set_exception_handler(function (Throwable $ex) {
    $msg = get_class($ex) . ': ' . $ex->getMessage();
    error_log('Uncaught ' . $msg . ' in ' . $ex->getFile() . ':' . $ex->getLine() . ' (code ' . error_code($msg, $ex->getFile(), $ex->getLine()) . ', ' . ($_SERVER['REQUEST_URI'] ?? '') . ')');
    error_page(get_class($ex) . ': ' . $ex->getMessage(), $ex->getFile(), $ex->getLine());
});
// Errors that stop PHP without an exception (e.g. a broken or half-uploaded file) get the same page
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) error_page($e['message'], $e['file'], $e['line']);
});

// Address-bar values are plain text, except the job filters that are lists (?type[]=…); anything else odd is dropped
foreach ($_GET as $k => $v) {
    if (is_array($v) && !in_array($k, ['type', 'qual', 'exp'], true)) unset($_GET[$k]);
}
foreach ($_COOKIE as $k => $v) if (is_array($v)) unset($_COOKIE[$k]);

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), payment=(), usb=(), interest-cohort=()');
    header('X-Frame-Options: SAMEORIGIN');                        // no clickjacking from other websites
    if (is_https()) header('Strict-Transport-Security: max-age=31536000');
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');                      // a session id is only accepted if this site made it
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => is_https()]);
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
/** A Google description of 110–158 characters: a short one is completed with the first sentences of the page's own text. */
function seo_desc(string $base, string $more, int $max = 158): string
{
    $base = trim(preg_replace('/\s+/', ' ', $base));
    if (mb_strlen($base) >= 110 || $more === '') return mb_strlen($base) > $max + 7 ? rtrim(mb_substr($base, 0, $max - 1), ' ,;:–-') . '…' : $base;
    $more = trim(preg_replace('/\s+/', ' ', $more));
    if ($base !== '' && str_starts_with(mb_strtolower($more), mb_strtolower(mb_substr($base, 0, 40)))) $more = trim(mb_substr($more, mb_strlen($base)));
    $out = $base;
    foreach (preg_split('/(?<=[.!?।])\s+/u', $more) as $sent) {
        if ($sent === '' || mb_stripos($out, mb_substr($sent, 0, 30)) !== false) continue;
        $try = trim($out . ' ' . $sent);
        if (mb_strlen($try) > $max) {
            if (mb_strlen($out) < 110) $out = rtrim(mb_substr($try, 0, mb_strrpos(mb_substr($try, 0, $max - 1), ' ') ?: $max - 1), ' ,;:–-') . '…';
            break;
        }
        $out = $try;
    }
    return $out;
}

/** A php.ini size such as "64M" in bytes. */
function ini_bytes(string $v): int
{
    $n = (int)$v;
    return match (strtolower(substr(trim($v), -1))) { 'g' => $n << 30, 'm' => $n << 20, 'k' => $n << 10, default => $n };
}

function csrf_check(): void
{
    // A file bigger than the hosting's post_max_size arrives as an empty form: say so, instead of "form expired"
    if (!$_POST && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > ini_bytes((string)ini_get('post_max_size'))) {
        http_response_code(413);
        exit('The file is too big for this hosting (' . round((int)$_SERVER['CONTENT_LENGTH'] / 1048576, 1) . ' MB sent, the hosting allows ' . ini_get('post_max_size')
            . '). In hPanel → Advanced → PHP Configuration set upload_max_filesize and post_max_size to 64M, then try again.');
    }
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
    'home_quick'       => 'Homepage – Hero quick links (right box)',
    'home_career'      => 'Homepage – Jobs & Career list',
    'home_apprentice'  => 'Homepage – Apprenticeship list',
    'home_study'       => 'Homepage – Study Material tiles',
];
/** Menu locations whose links also have a subtitle and an icon. */
function menu_has_extras(string $loc): bool { return str_starts_with($loc, 'home_'); }

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
    $sql = "SELECT * FROM trades WHERE status = 'published' AND kind = 'trade'" . ($popularOnly ? ' AND is_popular = 1' : '') . ' ORDER BY sort_order, name';
    return db()->query($sql)->fetchAll();
}
/** Trades live at /trades/{slug}/; common subjects (Employability Skills …) at /{slug}/. */
function trade_url(array $t): string { return (($t['kind'] ?? 'trade') === 'subject' ? '/' : '/trades/') . $t['slug'] . '/'; }

/** Common subjects (Employability Skills, …): like trades, but not listed among the trades. */
function subjects(): array
{
    return db()->query("SELECT * FROM trades WHERE status = 'published' AND kind = 'subject' ORDER BY sort_order, name")->fetchAll();
}
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
        'videos'              => ['label' => 'Video Learning', 'tab' => 'Video Learning', 'icon' => 'play', 'fields' => ['subtitle' => true]],
        'resources'           => ['label' => 'Software & Resources', 'tab' => 'Resources', 'icon' => 'link', 'fields' => ['subtitle' => true]],
        'faq'                 => ['label' => 'FAQ', 'tab' => 'FAQ', 'icon' => 'help', 'fields' => ['subtitle' => true]],
    ];
}

/* ---------- Video Learning (trade_items with section 'videos': a YouTube link or an uploaded video file) ---------- */

if (!defined('MAX_VIDEO_BYTES')) define('MAX_VIDEO_BYTES', 1024 * 1024 * 1024);   // one video up to 1 GB (can be set in config.php)
const VIDEO_TYPES = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/ogg' => 'ogv', 'video/x-m4v' => 'm4v', 'video/quicktime' => 'mp4'];

/** The 11-character id of a YouTube video link (watch, youtu.be, shorts, embed, live), or ''. */
function youtube_id(string $url): string
{
    $url = trim($url);
    if (preg_match('~^[A-Za-z0-9_-]{11}$~', $url)) return $url;
    if (!preg_match('~^https?://(?:www\.|m\.|music\.)?(?:youtube\.com|youtube-nocookie\.com|youtu\.be)/~i', $url)) return '';
    if (preg_match('~youtu\.be/([A-Za-z0-9_-]{11})~', $url, $m)) return $m[1];
    if (preg_match('~/(?:shorts|embed|live|v)/([A-Za-z0-9_-]{11})~', $url, $m)) return $m[1];
    parse_str((string)parse_url($url, PHP_URL_QUERY), $q);
    return isset($q['v']) && preg_match('~^[A-Za-z0-9_-]{11}$~', $q['v']) ? $q['v'] : '';
}

/** How to play one video item: type youtube|file|link, plus thumbnail and play address. */
function video_info(array $it): array
{
    $url = trim($it['url']);
    $thumb = !empty($it['image']) ? upload_url($it['image']) : '';
    if ($id = youtube_id($url)) {
        return ['type' => 'youtube', 'id' => $id, 'src' => 'https://www.youtube-nocookie.com/embed/' . $id . '?autoplay=1&rel=0&modestbranding=1&playsinline=1',
            'watch' => 'https://www.youtube.com/watch?v=' . $id, 'thumb' => $thumb ?: 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg'];
    }
    if (str_starts_with($url, UPLOAD_URL . '/videos/') || preg_match('~\.(mp4|webm|ogv|m4v)(\?|$)~i', $url)) {
        return ['type' => 'file', 'id' => '', 'src' => $url, 'watch' => '', 'thumb' => $thumb];
    }
    return ['type' => 'link', 'id' => '', 'src' => $url, 'watch' => $url, 'thumb' => $thumb];
}

/** Title of a YouTube video (YouTube oEmbed), or '' when it cannot be fetched. */
function youtube_title(string $id): string
{
    $u = 'https://www.youtube.com/oembed?format=json&url=' . rawurlencode('https://www.youtube.com/watch?v=' . $id);
    $out = @file_get_contents($u, false, stream_context_create(['http' => ['timeout' => 6, 'ignore_errors' => true], 'ssl' => ['verify_peer' => true]]));
    $j = is_string($out) ? json_decode($out, true) : null;
    return is_array($j) ? mb_substr(trim((string)($j['title'] ?? '')), 0, 255) : '';
}

/** Deletes an uploaded video (and its thumbnail) once no trade uses it any more. */
function video_cleanup(string $url, string $image = ''): void
{
    $chk = db()->prepare('SELECT COUNT(*) FROM trade_items WHERE url = ?');
    if (str_starts_with($url, UPLOAD_URL . '/videos/')) {
        $chk->execute([$url]);
        $f = UPLOAD_DIR . '/videos/' . basename(rawurldecode($url));
        if (!(int)$chk->fetchColumn() && is_file($f)) @unlink($f);
    }
    if ($image !== '') {
        $q = db()->prepare('SELECT COUNT(*) FROM trade_items WHERE image = ?');
        $q->execute([$image]);
        if (!(int)$q->fetchColumn() && is_file(UPLOAD_DIR . '/' . $image)) @unlink(UPLOAD_DIR . '/' . $image);
    }
}

/** How many years a trade runs, from its Duration ("1 Year", "2 Years", "6 Months"); 2 when it is not filled in. */
function trade_years(string $duration): int
{
    if (preg_match('/(\d+(?:\.\d+)?)\s*(?:years?|yrs?|वर्ष)/iu', $duration, $m)) return (float)$m[1] > 1 ? 2 : 1;
    if (preg_match('/\d+\s*months?|\b(one|single)\s*years?\b/i', $duration)) return 1;
    return 2;
}

/** A test's Year/Semester as students see it: one-year trades have no "1st Year" (semesters still show). */
function shown_level(?string $level, string $duration): string
{
    $level = trim((string)$level);
    return trade_years($duration) === 1 && preg_match('/^(1st|first)\s*year$/i', $level) ? '' : $level;
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
    'bulb','tool','gear','chat','target','cap','brief','users','bank','building','cal','clock','play','link','home','star','alert','download','pin','filter','share','rupee','sort'];

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

/* =========================================================
   Jobs
   ========================================================= */
const JOB_TYPES = [
    'government' => ['Government Jobs', 'bank', 'c-red', 'Government'],
    'private'    => ['Private Company Jobs', 'brief', 'c-purple', 'Private'],
    'campus'     => ['Campus Placement', 'users', 'c-green', 'Campus'],
    'walk-in'    => ['Walk-in Interview', 'users', 'c-orange', 'Walk-in'],
    'apprentice' => ['Apprentice / Trainee', 'cap', 'c-blue', 'Apprentice'],
    'contract'   => ['Contract Jobs', 'doc', 'c-teal', 'Contract'],
    'fresher'    => ['Fresher Jobs', 'star', 'c-pink', 'Fresher'],
];
const JOB_EXPERIENCE = ['fresher' => 'Fresher (0-1 Year)', '1-3' => '1-3 Years', '3-5' => '3-5 Years', '5+' => '5+ Years'];
const JOB_QUALIFICATIONS = ['ITI Pass', 'ITI Appearing', '10th Pass', '12th Pass', 'Diploma'];
const JOB_SALARY = ['' => 'Any Salary', 'u10' => 'Up to ₹10,000', '10-20' => '₹10,000 – ₹20,000', '20-30' => '₹20,000 – ₹30,000', '30+' => '₹30,000+'];

function job_url(array $j): string { return '/jobs/' . $j['slug'] . '/'; }
function job_expired(array $j): bool { return $j['last_date'] !== '' && $j['last_date'] < date('Y-m-d'); }
function job_is_new(array $j): bool { return $j['mark_new'] && strtotime($j['posted_at']) > strtotime('-10 days') && !job_expired($j); }
function job_salary(array $j): string
{
    if ($j['salary_text'] !== '') return $j['salary_text'];
    $f = fn($n) => '₹' . number_format($n);
    if ($j['salary_min'] && $j['salary_max']) return $f($j['salary_min']) . ' – ' . $f($j['salary_max']) . ' / month';
    if ($j['salary_min']) return $f($j['salary_min']) . '+ / month';
    return '';
}

/** SQL fragment for jobs that are still open. */
function job_active_sql(string $alias = 'j'): string
{
    return "$alias.status = 'published' AND ($alias.last_date = '' OR $alias.last_date >= '" . date('Y-m-d') . "')";
}

/** Latest open jobs with employer info. Optional trade name or job type filter. */
function latest_jobs(int $limit = 4, string $trade = '', string $type = ''): array
{
    $sql = 'SELECT j.*, e.name AS emp_name, e.short_name, e.logo AS emp_logo, e.color AS emp_color FROM jobs j LEFT JOIN employers e ON e.id = j.employer_id WHERE ' . job_active_sql();
    $args = [];
    if ($trade !== '') { $sql .= " AND (j.trades LIKE ? OR j.trades LIKE '%Multiple%' OR j.trades LIKE '%All Trades%')"; $args[] = '%' . $trade . '%'; }
    if ($type !== '') { $sql .= ' AND j.job_type = ?'; $args[] = $type; }
    $sql .= ' ORDER BY j.is_featured DESC, j.posted_at DESC LIMIT ' . (int)$limit;
    $st = db()->prepare($sql);
    $st->execute($args);
    return $st->fetchAll();
}

/** Company logo: uploaded image, or a coloured badge with the short name. */
function employer_logo(array $j, string $class = 'jl'): string
{
    $name = $j['emp_name'] ?? $j['name'] ?? $j['company'] ?? '';
    $logo = $j['emp_logo'] ?? $j['logo'] ?? '';
    if ($logo) return '<span class="' . $class . ' img"><img src="' . e(upload_url($logo)) . '" alt="' . e($name) . ' logo" loading="lazy"></span>';
    $short = ($j['short_name'] ?? '') ?: strtoupper(implode('', array_map(fn($w) => $w[0] ?? '', array_slice(preg_split('/\s+/', preg_replace('/[^A-Za-z ]/', '', $name)), 0, 3))));
    $color = ($j['emp_color'] ?? $j['color'] ?? '') ?: '#2563eb';
    return '<span class="' . $class . '" style="--c:' . e($color) . '">' . e($short ?: '?') . '</span>';
}

function company_name(array $j): string { return ($j['emp_name'] ?? '') ?: $j['company']; }

/* =========================================================
   Page categories (news / articles) and shortcodes
   ========================================================= */
const PAGE_CATEGORIES = ['' => 'Normal page', 'exam-update' => 'Exam Update (shows on homepage)', 'article' => 'Article / Blog (shows on homepage)'];

function latest_posts(string $category, int $limit = 4): array
{
    $st = db()->prepare("SELECT * FROM pages WHERE status = 'published' AND category = ? ORDER BY created_at DESC LIMIT " . (int)$limit);
    $st->execute([$category]);
    return $st->fetchAll();
}

/**
 * Admin can place these inside page content:
 *   [trades]                       grid of all trades
 *   [trades section="mock-tests"]  same, linking to that section of each trade page
 *   [jobs type="apprentice" limit="6"]  open jobs (type/trade optional)
 *   [posts category="article" limit="10"]  list of articles / exam updates
 */
function render_shortcodes(string $html): string
{
    return preg_replace_callback('/\[(trades|jobs|posts|contact|whatsapp|tests)([^\]]*)\]/', function ($m) {
        if ($m[1] === 'contact') return contact_block();
        if ($m[1] === 'tests') {
            preg_match_all('/(\w+)="([^"]*)"/', $m[2], $kv, PREG_SET_ORDER);
            $a = []; foreach ($kv as $p) $a[$p[1]] = $p[2];
            return cbt_list((int)($a['limit'] ?? 20), $a['trade'] ?? '');
        }
        if ($m[1] === 'whatsapp') return whatsapp_cta('inline');
        preg_match_all('/(\w+)="([^"]*)"/', $m[2], $kv, PREG_SET_ORDER);
        $a = [];
        foreach ($kv as $p) $a[$p[1]] = $p[2];
        ob_start();
        if ($m[1] === 'trades') {
            $anchor = isset($a['section']) ? '#' . slugify($a['section']) : '';
            echo '<div class="trades sc">';
            foreach (trades() as $t) {
                echo '<a class="trade" href="' . e(trade_url($t) . $anchor) . '" style="--tbg:' . e($t['card_color']) . '">'
                    . ($t['image'] ? '<img class="timg" src="' . e(upload_url($t['image'])) . '" alt="" loading="lazy">' : '<span class="emo" aria-hidden="true">' . e($t['emoji'] ?: '🛠️') . '</span>')
                    . '<b class="tt">' . e($t['name']) . '</b>' . ($t['short_desc'] !== '' ? '<small class="td">' . e($t['short_desc']) . '</small>' : '') . '<span class="go">' . icon('arrow') . '</span></a>';
            }
            echo '</div>';
        } elseif ($m[1] === 'jobs') {
            $list = latest_jobs((int)($a['limit'] ?? 6), $a['trade'] ?? '', $a['type'] ?? '');
            echo $list ? '<div class="job-list sc">' . implode('', array_map('job_card', $list)) . '</div>' : '<p>No open jobs right now. Check again soon.</p>';
        } else {
            $list = latest_posts($a['category'] ?? 'article', (int)($a['limit'] ?? 10));
            echo '<ul class="rows arts sc">';
            foreach ($list as $i => $p) echo post_row($p, $i);
            echo '</ul>';
        }
        return ob_get_clean();
    }, $html);
}

function post_row(array $p, int $i): string
{
    $thumb = $p['image'] ? '<img class="thumb" src="' . e(upload_url($p['image'])) . '" alt="" loading="lazy">' : '<span class="thumb th' . ($i % 4 + 1) . '" aria-hidden="true">' . ['📝', '📚', '📋', '🎯'][$i % 4] . '</span>';
    return '<li><a href="/' . e($p['slug']) . '/">' . $thumb . '<span class="rt"><b>' . e($p['title']) . '</b><small>' . icon('cal')
        . '<time datetime="' . e(substr($p['created_at'], 0, 10)) . '">' . e(nice_date($p['created_at'])) . '</time></small></span>' . icon('chev-r', 'ic chev') . '</a></li>';
}

/** Job card used on the jobs list, homepage, trade pages and shortcodes. */
function job_card(array $j): string
{
    $type = JOB_TYPES[$j['job_type']] ?? JOB_TYPES['private'];
    $meta = [];
    if ($j['trades'] !== '') $meta[] = icon('tool') . e($j['trades']);
    if ($j['qualification'] !== '') $meta[] = icon('cap') . e($j['qualification']);
    if ($j['location'] !== '') $meta[] = icon('pin') . e($j['location']);
    $last = $j['last_date'] ? '<p class="last">Last Date: <b>' . e(nice_date($j['last_date'])) . '</b></p>' : '';
    return '<article class="jcard' . (job_expired($j) ? ' expired' : '') . '">' . employer_logo($j)
        . '<div class="jbody"><p class="jco"><b>' . e(company_name($j)) . '</b> <span class="tag t-' . e($j['job_type']) . '">' . e($type[3]) . '</span>'
        . (job_is_new($j) ? '<span class="tag new">New</span>' : '') . (job_expired($j) ? '<span class="tag off">Closed</span>' : '') . '</p>'
        . '<h3><a href="' . e(job_url($j)) . '">' . e($j['title']) . '</a></h3>'
        . ($meta ? '<p class="jmeta">' . implode('', array_map(fn($m) => "<span>$m</span>", $meta)) . '</p>' : '') . $last . '</div>'
        . '<a class="btn-out" href="' . e(job_url($j)) . '" aria-label="View details: ' . e($j['title']) . '">View Details ' . icon('arrow') . '</a></article>';
}

/* =========================================================
   Google AdSense (managed in Admin → Ads)
   ========================================================= */
const AD_PLACEMENTS = [
    'home_top'    => ['Homepage – below the hero', 'Shown under the search banner on the homepage'],
    'home_middle' => ['Homepage – before Latest Jobs', 'Between Study Material and Latest Job Opportunities'],
    'trade_top'   => ['Trade page – below overview tiles', 'Under the 6 coloured tiles on every trade page'],
    'trade_middle'=> ['Trade page – middle', 'After Mock Tests & Syllabus on every trade page'],
    'jobs_list'   => ['Jobs list – between jobs', 'After every 4th job on /jobs/ and category pages'],
    'job_detail'  => ['Job details – below highlights', 'Under the Job Highlights box on each job page'],
    'trades_list' => ['All Trades page – between cards', 'After the first 8 trade cards on /trades/'],
    'syllabus'    => ['Syllabus page – below the list', 'Under the syllabus table on /syllabus/'],
    'article'     => ['Articles & pages – inside content', 'After the first paragraph of pages and articles'],
    'sidebar'     => ['Sidebar', 'Right sidebar on jobs, job details and articles'],
    'footer_top'  => ['All pages – above footer', 'At the bottom of every page'],
];

/** AdSense publisher ID like "ca-pub-1234567890123456", or '' when ads are off. */
function adsense_client(): string
{
    $c = trim(setting('adsense_client'));
    if ($c !== '' && !str_starts_with($c, 'ca-')) $c = 'ca-' . $c;
    return setting('adsense_enabled') === '1' && preg_match('/^ca-pub-\d{10,20}$/', $c) ? $c : '';
}

/** Ads are hidden for the logged-in admin so you never click (or inflate) your own ads. */
function ads_active(): bool { return adsense_client() !== '' && empty($_SESSION['admin']) && !in_app(); }

/** Page opened inside the ITI Exam Android app (its web view adds "ITIExamApp/…" to the user agent).
    AdSense is not shown there: Google allows ads inside apps only through its mobile ads SDK. */
function in_app(): bool { return str_contains((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 'ITIExamApp/'); }

/** The <script> that loads AdSense (also runs Auto Ads when switched on in your AdSense account). */
function adsense_head(): string
{
    $c = adsense_client();
    if ($c === '' || in_app()) return '';
    $out = '<meta name="google-adsense-account" content="' . e($c) . '">' . "\n";
    if (ads_active()) $out .= '  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' . e($c) . '" crossorigin="anonymous"></script>' . "\n";
    return $out;
}

/** A manual ad unit for one placement ('' when that placement has no slot ID). */
function ad(string $placement, string $class = ''): string
{
    if (!ads_active() || !isset(AD_PLACEMENTS[$placement])) return '';
    $slot = preg_replace('/\D/', '', setting('ad_slot_' . $placement));
    if ($slot === '') return '';
    $inArticle = $placement === 'article' && setting('ad_article_fluid') === '1';
    $fmt = $inArticle ? ' style="display:block; text-align:center;" data-ad-layout="in-article" data-ad-format="fluid"'
                      : ' style="display:block" data-ad-format="auto" data-full-width-responsive="true"';
    return '<div class="ad-box ' . e($class) . '" aria-label="Advertisement"><span class="ad-label">Advertisement</span>'
        . '<ins class="adsbygoogle"' . $fmt . ' data-ad-client="' . e(adsense_client()) . '" data-ad-slot="' . $slot . '"></ins>'
        . '<script>(adsbygoogle = window.adsbygoogle || []).push({});</script></div>';
}

/** Puts the 'article' ad after the first paragraph of admin-written HTML. */
function insert_article_ad(string $html): string
{
    $ad = ad('article', 'in-content');
    if ($ad === '') return $html;
    $pos = stripos($html, '</p>');
    return $pos === false ? $ad . $html : substr($html, 0, $pos + 4) . $ad . substr($html, $pos + 4);
}

/** Contents of /ads.txt */
function ads_txt(): string
{
    $out = '';
    if (preg_match('/(pub-\d{10,20})/', setting('adsense_client'), $m)) $out .= "google.com, {$m[1]}, DIRECT, f08c47fec0942fa0\n";
    $extra = trim(setting('ads_txt_extra'));
    return $out . ($extra !== '' ? $extra . "\n" : '');
}

/* =========================================================
   Brand: logo, favicon, contact, WhatsApp group (Admin → Settings)
   ========================================================= */
function logo_url(): string { return ($f = setting('logo_file')) ? upload_url($f) : '/assets/img/logo-mark.webp'; }
function favicon_url(): string { return ($f = setting('favicon_file')) ? upload_url($f) : '/assets/img/favicon-48.png?v=2'; }
function app_store_url(): string { $u = trim(setting('app_url')); return preg_match('~^https://play\.google\.com/~', $u) ? $u : ''; }
function contact_email(): string { return setting('contact_email', 'contact@itiexam.com') ?: 'contact@itiexam.com'; }
function whatsapp_group(): string { $u = trim(setting('whatsapp_group')); return preg_match('~^https://~', $u) ? $u : ''; }
function telegram_channel(): string { $u = trim(setting('social_telegram')); return preg_match('~^https://~', $u) ? $u : ''; }

/** Logo used in header and footer. */
function site_logo(bool $light = false): string
{
    $alt = 'ITIExam – Online Mock Test, Study Material, Syllabus, Jobs, All Trades';
    $open = '<a href="/" class="logo' . ($light ? ' light' : '') . '" aria-label="' . e(SITE_NAME) . ' home">';
    if ($f = setting('logo_file')) return $open . '<img class="logo-full" src="' . e(upload_url($f)) . '" alt="' . e($alt) . '" decoding="async"></a>';
    // Small laptops get the round emblem only, so the whole menu fits next to it
    return $open . '<picture>' . ($light ? '' : '<source media="(min-width: 1024px) and (max-width: 1179px)" srcset="/assets/img/logo-mark.webp" width="160" height="160">')
        . '<img class="logo-full" src="/assets/img/logo-full.webp" alt="' . e($alt) . '" width="700" height="224" decoding="async"></picture></a>';
}

/** "Join our WhatsApp group" card ('' when the admin has not added a group link). */
function whatsapp_cta(string $class = ''): string
{
    $wa = whatsapp_group();
    if ($wa === '') return '';
    $tg = telegram_channel();
    return '<section class="wa-cta ' . e($class) . '" aria-label="Join WhatsApp group"><span class="wa-ic">' . icon('wa') . '</span>'
        . '<div class="wa-txt"><b>' . e(setting('whatsapp_title') ?: 'Get daily ITI job & exam alerts') . '</b><small>' . e(setting('whatsapp_subtitle') ?: 'Join our free WhatsApp group – new jobs, results and mock tests every day.') . '</small></div>'
        . '<span class="wa-btns"><a class="wa-btn" href="' . e($wa) . '" target="_blank" rel="noopener">' . icon('wa') . ' Join WhatsApp Group</a>'
        . ($tg ? '<a class="tg-btn" href="' . e($tg) . '" target="_blank" rel="noopener">' . icon('tg') . ' Telegram</a>' : '') . '</span></section>';
}

/** Contact details block for the Contact page: [contact] */
function contact_block(): string
{
    $mail = contact_email();
    $out = '<div class="contact-grid"><a class="cbox" href="mailto:' . e($mail) . '"><span class="ri c-blue">' . icon('chat') . '</span><span><small>Email us</small><b>' . e($mail) . '</b></span></a>';
    if ($wa = whatsapp_group()) $out .= '<a class="cbox" href="' . e($wa) . '" target="_blank" rel="noopener"><span class="ri c-green">' . icon('wa') . '</span><span><small>WhatsApp group</small><b>Join for daily updates</b></span></a>';
    if ($tg = telegram_channel()) $out .= '<a class="cbox" href="' . e($tg) . '" target="_blank" rel="noopener"><span class="ri c-cyan">' . icon('tg') . '</span><span><small>Telegram</small><b>Join our channel</b></span></a>';
    return $out . '</div>';
}


/* =========================================================
   404 log + internal link checking (Admin → Link Checker)
   ========================================================= */
function log_not_found(): void
{
    $path = mb_substr(strtok($_SERVER['REQUEST_URI'] ?? '', '?') ?: '', 0, 290);
    if ($path === '' || preg_match('~\.(php|env|git|sql|bak|zip)|wp-|xmlrpc~i', $path)) return;   // ignore hacker bots
    $ref = mb_substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 290);
    try {
        $u = db()->prepare('UPDATE not_found_log SET hits = hits + 1, last_seen = ?, referrer = CASE WHEN ? <> \'\' THEN ? ELSE referrer END WHERE path = ?');
        $u->execute([now(), $ref, $ref, $path]);
        if (!$u->rowCount()) db()->prepare('INSERT INTO not_found_log (path, referrer, last_seen) VALUES (?, ?, ?)')->execute([$path, $ref, now()]);
    } catch (PDOException $e) { /* logging must never break the page */ }
}

/** True when an internal URL (starting with /) opens a real page on this site. */
function internal_url_ok(string $url): bool
{
    $path = strtok(strtok($url, '#') ?: '/', '?') ?: '/';
    if (in_array($path, ['/', '/trades/', '/jobs/', '/search/', '/sitemap.xml', '/ads.txt', '/robots.txt', '/admin/'], true)) return true;
    $root = dirname(__DIR__);
    if (is_file($root . $path) && !str_contains($path, '..')) return true;
    $p = trim($path, '/');
    if (preg_match('~^trades/([a-z0-9-]+)$~', $p, $m)) {
        $st = db()->prepare("SELECT COUNT(*) FROM trades WHERE slug = ? AND status = 'published'"); $st->execute([$m[1]]); return (bool)$st->fetchColumn();
    }
    if (preg_match('~^cbt/([a-z0-9-]+)$~', $p, $m)) {
        $st = db()->prepare("SELECT COUNT(*) FROM cbt_tests WHERE folder = ? AND status = 'published'"); $st->execute([$m[1]]); return (bool)$st->fetchColumn();
    }
    if (preg_match('~^jobs/([a-z0-9-]+)$~', $p, $m)) {
        if (isset(JOB_TYPES[$m[1]])) return true;
        $st = db()->prepare("SELECT COUNT(*) FROM jobs WHERE slug = ? AND status = 'published'"); $st->execute([$m[1]]); return (bool)$st->fetchColumn();
    }
    $st = db()->prepare("SELECT COUNT(*) FROM pages WHERE slug = ? AND status = 'published'"); $st->execute([$p]);
    return (bool)$st->fetchColumn();
}


/* =========================================================
   Ready-made HTML CBT tests (Admin → CBT Tests)
   ========================================================= */
const CBT_TYPES = ['html' => 'text/html; charset=utf-8', 'htm' => 'text/html; charset=utf-8', 'css' => 'text/css', 'js' => 'application/javascript',
    'json' => 'application/json', 'txt' => 'text/plain; charset=utf-8', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif',
    'webp' => 'image/webp', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon', 'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf',
    'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'mp4' => 'video/mp4', 'pdf' => 'application/pdf'];

function cbt_url(array $t): string { return '/cbt/' . $t['folder'] . '/'; }
function cbt_play_url(array $t): string
{
    return '/cbt/' . $t['folder'] . '/play/k-' . cbt_play_key($t['folder']) . '/' . implode('/', array_map('rawurlencode', explode('/', $t['entry'])));
}

/** Short daily key in the test's address. The sandboxed test frame sends no cookies, so this key is what lets
    a logged-in student (or the admin, for a draft) move between the test's own pages. */
function cbt_play_key(string $folder, int $daysAgo = 0): string
{
    return substr(hash_hmac('sha256', 'play|' . $folder . '|' . date('Y-m-d', strtotime("-$daysAgo day")), app_secret()), 0, 16);
}

/** Random secret made once per website, used to sign keys and the student's login cookie. */
function app_secret(): string
{
    static $k = null;
    if ($k === null) {
        $k = setting('app_secret');
        if ($k === '') { $k = bin2hex(random_bytes(32)); setting_set('app_secret', $k); }
    }
    return $k;
}

/** Sandbox rules for a test page: scripts run, but the test cannot reach the website, its cookies or the admin panel. */
function cbt_sandbox(): string
{
    return 'allow-scripts allow-forms allow-popups allow-modals allow-downloads' . (setting('cbt_allow_storage') === '1' ? ' allow-same-origin' : '');
}


/** List of uploaded HTML tests (newest first), optionally for one trade name. */
function cbt_list(int $limit = 20, string $trade = ''): string
{
    $sql = "SELECT c.*, t.name AS tname FROM cbt_tests c LEFT JOIN trades t ON t.id = c.trade_id WHERE c.status = 'published'";
    $args = [];
    if ($trade !== '') { $sql .= ' AND t.name = ?'; $args[] = $trade; }
    $st = db()->prepare($sql . ' ORDER BY c.created_at DESC LIMIT ' . max(1, $limit));
    $st->execute($args);
    $out = '';
    foreach ($st as $i => $c) {
        $info = trim(($c['questions'] ? (int)$c['questions'] . ' Questions' : '') . ($c['questions'] && $c['minutes'] ? ' · ' : '') . ($c['minutes'] ? (int)$c['minutes'] . ' Minutes' : ''));
        $out .= '<li><a href="' . e(cbt_url($c)) . '" class="row-mock"><span class="ri ' . chip_color($i) . ' solid">' . icon('clip') . '</span><span class="rt"><b>'
            . ($c['tname'] ? e($c['tname']) . ' – ' : '') . e($c['title']) . '</b>' . ($info ? '<small>' . e($info) . '</small>' : '') . '</span>' . start_label() . '</a></li>';
    }
    return $out ? '<ul class="rows sc">' . $out . '</ul>' : '<p>New online tests are coming soon.</p>';
}

/* =========================================================
   Right sidebar for pages and articles (fills the space next to the content)
   ========================================================= */
function page_sidebar(array $more = [], string $moreTitle = '', string $moreUrl = '', string $skipCategory = '', bool $showWa = true): string
{
    ob_start();
    echo '<aside class="post-side">', ad('sidebar', 'side');
    if ($more) {
        echo '<section class="card"><h2 class="side-h"><span class="h-ic">', icon('book'), '</span>More ', e($moreTitle), '</h2><ul class="rows arts">';
        foreach ($more as $i => $m) echo post_row($m, $i);
        echo '</ul><a class="view-all" href="', e($moreUrl), '">View all ', icon('arrow'), '</a></section>';
    }
    // Popular trades
    $tr = array_slice(trades(true), 0, 8);
    if ($tr) {
        echo '<section class="card"><h2 class="side-h"><span class="h-ic">', icon('tool'), '</span>Popular ITI Trades</h2><div class="side-trades">';
        foreach ($tr as $t) echo '<a href="', e(trade_url($t)), '" style="--tbg:', e($t['card_color']), '"><span aria-hidden="true">', e($t['emoji'] ?: '🛠️'), '</span>', e($t['name']), '</a>';
        echo '</div><a class="view-all" href="/trades/">All trades ', icon('arrow'), '</a></section>';
    }
    // Latest jobs
    $jobs = latest_jobs(4);
    if ($jobs) {
        echo '<section class="card"><h2 class="side-h"><span class="h-ic">', icon('brief'), '</span>Latest ITI Jobs</h2><ul class="rows">';
        foreach ($jobs as $j) {
            echo '<li><a href="', e(job_url($j)), '">', employer_logo($j, 'jl sm'), '<span class="rt"><b>', e(company_name($j)), '</b><small>', e($j['title']),
                $j['last_date'] ? ' · ' . e(nice_date($j['last_date'])) : '', '</small></span>', icon('chev-r', 'ic chev'), '</a></li>';
        }
        echo '</ul><a class="view-all" href="/jobs/">All jobs ', icon('arrow'), '</a></section>';
    }
    // Latest articles (when this page is not already listing them)
    if ($skipCategory !== 'article' && ($arts = latest_posts('article', 3))) {
        echo '<section class="card"><h2 class="side-h"><span class="h-ic">', icon('doc'), '</span>Recent Articles</h2><ul class="rows arts">';
        foreach ($arts as $i => $a) echo post_row($a, $i);
        echo '</ul></section>';
    }
    if ($showWa) echo whatsapp_cta('side');
    echo '</aside>';
    return ob_get_clean();
}
