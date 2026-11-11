<?php
/* Theory Notes: modules (per trade and year) with lesson pages, written in Admin → Theory Notes.
   Read at /trades/<slug>/notes/ (or /<subject>/notes/ for a common subject such as Employability Skills). */

/** Address of a trade's notes, or of one lesson. */
function notes_url(array $trade, string $lessonSlug = ''): string
{
    return trade_url($trade) . 'notes/' . ($lessonSlug !== '' ? rawurlencode($lessonSlug) . '/' : '');
}

/** Modules of a trade with their lessons, grouped by year: [year => [module + ['lessons' => [...]]]]. */
function notes_tree(int $tradeId, bool $withDrafts = false): array
{
    $pub = $withDrafts ? '' : " AND status = 'published'";
    $st = db()->prepare("SELECT * FROM note_modules WHERE trade_id = ?$pub ORDER BY year, sort_order, id");
    $st->execute([$tradeId]);
    $mods = [];
    foreach ($st as $m) { $m['lessons'] = []; $mods[(int)$m['id']] = $m; }
    if ($mods) {
        $st = db()->prepare("SELECT id, module_id, num, title, slug, summary, image, minutes, status, sort_order, updated_at FROM note_lessons
            WHERE trade_id = ?$pub ORDER BY sort_order, id");
        $st->execute([$tradeId]);
        foreach ($st as $l) if (isset($mods[(int)$l['module_id']])) $mods[(int)$l['module_id']]['lessons'][] = $l;
    }
    $tree = [];
    foreach ($mods as $m) if ($withDrafts || $m['lessons']) $tree[(int)$m['year']][] = $m;
    ksort($tree);
    return $tree;
}

/** How many published lessons a trade has (0 = no notes yet). */
function notes_count(int $tradeId): int
{
    $st = db()->prepare("SELECT COUNT(*) FROM note_lessons l JOIN note_modules m ON m.id = l.module_id
        WHERE l.trade_id = ? AND l.status = 'published' AND m.status = 'published'");
    $st->execute([$tradeId]);
    return (int)$st->fetchColumn();
}

/** Minutes to read a lesson body (about 100 words a minute – students read technical notes slowly; at least 1). */
function notes_minutes(string $html): int
{
    $words = str_word_count(strip_tags(str_replace('<', ' <', $html)));
    return max(1, (int)round($words / 100));
}

function notes_year_label(int $y): string { return $y === 2 ? '2nd Year' : '1st Year'; }

/** Languages students can read the notes in (Google Translate codes) – English plus the languages of India. */
function notes_langs(): array
{
    return [
        'en' => ['English', 'English'], 'hi' => ['हिन्दी', 'Hindi'], 'mr' => ['मराठी', 'Marathi'], 'bn' => ['বাংলা', 'Bengali'],
        'gu' => ['ગુજરાતી', 'Gujarati'], 'ta' => ['தமிழ்', 'Tamil'], 'te' => ['తెలుగు', 'Telugu'], 'kn' => ['ಕನ್ನಡ', 'Kannada'],
        'ml' => ['മലയാളം', 'Malayalam'], 'pa' => ['ਪੰਜਾਬੀ', 'Punjabi'], 'or' => ['ଓଡ଼ିଆ', 'Odia'], 'as' => ['অসমীয়া', 'Assamese'],
        'ur' => ['اردو', 'Urdu'], 'ne' => ['नेपाली', 'Nepali'], 'gom' => ['कोंकणी', 'Konkani'], 'mai' => ['मैथिली', 'Maithili'],
        'sd' => ['سنڌي', 'Sindhi'], 'doi' => ['डोगरी', 'Dogri'], 'bho' => ['भोजपुरी', 'Bhojpuri'], 'sa' => ['संस्कृतम्', 'Sanskrit'],
        'mni-Mtei' => ['ꯃꯤꯇꯩꯂꯣꯟ', 'Manipuri'],
    ];
}

/** The "Read in" language button with its list. Translation runs in the student's browser (Google Translate). */
function notes_lang_picker(): string
{
    $o = '';
    foreach (notes_langs() as $c => [$native, $en]) {
        $o .= '<li><button type="button" data-lang="' . e($c) . '" lang="' . e($c) . '"><b>' . e($native) . '</b>'
            . ($c !== 'en' ? '<small>' . e($en) . '</small>' : '') . '</button></li>';
    }
    return '<div class="nlang notranslate" translate="no" data-nlang>'
        . '<button type="button" class="nlang-btn" aria-haspopup="true" aria-expanded="false">' . icon('globe')
        . '<span><small>Read in</small><b data-nlang-cur>English</b></span>' . icon('chev') . '</button>'
        . '<div class="nlang-pop" role="menu" hidden><p class="nlang-h">Choose your language <span>भाषा निवडा · भाषा चुनें</span></p>'
        . '<ul>' . $o . '</ul><p class="nlang-note">Notes are translated automatically. Technical words may stay in English.</p></div>'
        . '<div id="gt-el" hidden></div></div>';
}

/** Read a lesson body for students: only safe tags, and images/links with good defaults (the admin writes it). */
function notes_body(string $html): string
{
    // images load lazily and keep their shape; external links open in a new tab
    $html = preg_replace('~<img(?![^>]*\bloading=)~i', '<img loading="lazy" decoding="async"', $html);
    return preg_replace_callback('~<a\s+([^>]*href="https?://[^"]+"[^>]*)>~i', fn($m) => str_contains($m[1], 'target=') ? $m[0] : '<a ' . $m[1] . ' target="_blank" rel="noopener">', $html);
}
