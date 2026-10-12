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
    return preg_replace_callback('/\[(trades|jobs|posts|contact|whatsapp)([^\]]*)\]/', function ($m) {
        if ($m[1] === 'contact') return contact_block();
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
                    . '<b class="tt">' . e($t['name']) . '</b><span class="go">' . icon('arrow') . '</span></a>';
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
function ads_active(): bool { return adsense_client() !== '' && empty($_SESSION['admin']); }

/** The <script> that loads AdSense (also runs Auto Ads when switched on in your AdSense account). */
function adsense_head(): string
{
    $c = adsense_client();
    if ($c === '') return '';
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
function favicon_url(): string { return ($f = setting('favicon_file')) ? upload_url($f) : '/assets/img/favicon-48.png'; }
function contact_email(): string { return setting('contact_email', 'contact@itiexam.com') ?: 'contact@itiexam.com'; }
function whatsapp_group(): string { $u = trim(setting('whatsapp_group')); return preg_match('~^https://~', $u) ? $u : ''; }
function telegram_channel(): string { $u = trim(setting('social_telegram')); return preg_match('~^https://~', $u) ? $u : ''; }

/** Logo used in header and footer. */
function site_logo(bool $light = false): string
{
    return '<a href="/" class="logo' . ($light ? ' light' : '') . '" aria-label="' . e(SITE_NAME) . ' home">'
        . '<img class="logo-img" src="' . e(logo_url()) . '" alt="" width="46" height="46" decoding="async">'
        . '<span class="logo-text"><strong><b class="l-i1">I</b><b class="l-t">T</b><b class="l-i2">I</b><span>Exam</span></strong><small>Learn • Test • Get Jobs</small></span></a>';
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
    if (preg_match('~^jobs/([a-z0-9-]+)$~', $p, $m)) {
        if (isset(JOB_TYPES[$m[1]])) return true;
        $st = db()->prepare("SELECT COUNT(*) FROM jobs WHERE slug = ? AND status = 'published'"); $st->execute([$m[1]]); return (bool)$st->fetchColumn();
    }
    $st = db()->prepare("SELECT COUNT(*) FROM pages WHERE slug = ? AND status = 'published'"); $st->execute([$p]);
    return (bool)$st->fetchColumn();
}
