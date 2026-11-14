<?php
/* Website visitors: counted from a small request each page sends (bots that do not run JavaScript are not counted).
   No cookies: a visitor is a hash of IP + browser + day, so the same person is one visitor per day.
   The IP is kept only until its city is found (ip-api.com), then removed. */

/** Visitor's IP address (the first public one a CDN / proxy passes on). */
function visitor_ip(): string
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR'] as $h) {
        foreach (explode(',', (string)($_SERVER[$h] ?? '')) as $ip) {
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return $ip;
        }
    }
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64);
}

/** Where the visitor came from, as a name the admin understands. */
function visit_source(string $ref, string $path, string $ua): array
{
    if (str_contains($ua, 'ITIExamApp/')) return ['Android app', ''];
    if (preg_match('/[?&]utm_source=([a-z0-9._-]{1,30})/i', $path, $m)) return [ucfirst(strtolower($m[1])), ''];
    $host = strtolower((string)parse_url($ref, PHP_URL_HOST));
    $host = preg_replace('/^(www|m|l|lm|mobile)\./', '', $host);
    if ($host === '') return ['Direct', ''];
    $own = array_map(fn($h) => preg_replace('/^www\./', '', $h), $GLOBALS['siteHosts'] ?? []);
    $own[] = strtolower((string)parse_url(SITE_URL, PHP_URL_HOST));
    if (in_array($host, $own, true)) return ['Internal', ''];
    $map = [
        'Google' => '/(^|\.)google\.|googleapis|android-app:\/\/com\.google|googlequicksearchbox/', 'Bing' => '/(^|\.)bing\.com$/',
        'Yahoo' => '/yahoo\./', 'DuckDuckGo' => '/duckduckgo\./', 'WhatsApp' => '/whatsapp|^wa\.me$/', 'Facebook' => '/facebook\.com$|^fb\.(com|me)$/',
        'Instagram' => '/instagram\.com$/', 'YouTube' => '/youtube\.com$|^youtu\.be$/', 'Telegram' => '/^t\.me$|telegram/', 'X (Twitter)' => '/^(x|twitter)\.com$|^t\.co$/',
        'LinkedIn' => '/linkedin\.com$|^lnkd\.in$/', 'ChatGPT' => '/chatgpt\.com$|openai\.com$/',
    ];
    foreach ($map as $name => $re) if (preg_match($re, $host)) return [$name, $host];
    return ['Other websites', $host];
}

function visit_device(string $ua): string
{
    if (str_contains($ua, 'ITIExamApp/')) return 'App';
    if (preg_match('/iPad|Tablet/i', $ua)) return 'Tablet';
    return preg_match('/Mobi|Android|iPhone/i', $ua) ? 'Mobile' : 'Computer';
}

/** Saves one page view ($kind 'v') or a "still here" ping ($kind 'p'). */
function visit_record(string $kind, string $path, string $title, string $ref): void
{
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua === '' || preg_match('/bot|crawl|spider|slurp|lighthouse|headless|preview|facebookexternalhit|whatsapp\//i', $ua)) return;
    if (!empty($_SESSION['admin'])) return;                                        // the admin's own clicks are not visitors
    $path = mb_substr('/' . ltrim(preg_replace('/[\x00-\x1f]/', '', $path), '/'), 0, 255);
    if (str_starts_with($path, '/admin')) return;
    $title = mb_substr(trim(preg_replace('/\s*[|–-]\s*ITI ?Exam.*$/iu', '', strip_tags($title))), 0, 160);
    $ip = visitor_ip();
    $salt = setting('visit_salt');
    if ($salt === '') { $salt = bin2hex(random_bytes(16)); setting_set('visit_salt', $salt); }
    $day = date('Y-m-d');
    $vid = substr(hash_hmac('sha256', $ip . '|' . $ua . '|' . $day, $salt), 0, 16);
    $pdo = db();
    $now = time();
    [$source, $host] = visit_source($ref, $path, $ua);
    $device = visit_device($ua);
    $country = strtoupper(substr((string)($_SERVER['HTTP_CF_IPCOUNTRY'] ?? $_SERVER['GEOIP_COUNTRY_CODE'] ?? ''), 0, 2));
    $country = preg_match('/^[A-Z]{2}$/', $country) && $country !== 'XX' ? $country : '';
    $keepIp = setting('visitor_geo', '1') === '1' ? $ip : '';
    if ($kind === 'v') {
        $n = $pdo->prepare('SELECT COUNT(*) FROM visits WHERE day = ? AND vid = ?');
        $n->execute([$day, $vid]);
        if ((int)$n->fetchColumn() < 300) {                                        // one person cannot fill the report
            // Where the visitor came from is decided by the day's first page view; the next pages keep it
            $f = $pdo->prepare('SELECT source, ref_host FROM visits WHERE day = ? AND vid = ? ORDER BY id LIMIT 1');
            $f->execute([$day, $vid]);
            if ($first = $f->fetch()) [$source, $host] = [$first['source'], $first['ref_host']];
            elseif ($source === 'Internal') $source = 'Direct';
            $pdo->prepare('INSERT INTO visits (day, at_time, vid, path, title, source, ref_host, device, ip, country) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$day, $now, $vid, $path, $title, $source, $host, $device, $keepIp, $country]);
        }
    }
    $old = $pdo->prepare('SELECT source, country, region, city FROM visitors_live WHERE vid = ?');
    $old->execute([$vid]);
    $o = $old->fetch() ?: [];
    if ($o || $source === 'Internal') $source = $o['source'] ?? 'Direct';
    $pdo->prepare('DELETE FROM visitors_live WHERE vid = ?')->execute([$vid]);
    $pdo->prepare('INSERT INTO visitors_live (vid, last_seen, path, title, source, device, ip, country, region, city) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([$vid, $now, $path, $title, $source, $device, ($o['city'] ?? '') === '' ? $keepIp : '', $o['country'] ?? $country, $o['region'] ?? '', $o['city'] ?? '']);
    if (random_int(1, 200) === 1) visits_cleanup();
}

/** Old rows go: page views after 400 days, IPs after 3 days (even without a city), live rows after an hour. */
function visits_cleanup(): void
{
    $pdo = db();
    $pdo->prepare('DELETE FROM visits WHERE day < ?')->execute([date('Y-m-d', strtotime('-400 days'))]);
    $pdo->prepare("UPDATE visits SET ip = '' WHERE ip <> '' AND at_time < ?")->execute([time() - 3 * 86400]);
    $pdo->prepare('DELETE FROM visitors_live WHERE last_seen < ?')->execute([time() - 3600]);
}

/** Finds country, state and city for IPs that do not have them yet (ip-api.com, up to 100 at a time), then forgets the IPs. */
function visits_geo_fill(int $max = 100): void
{
    if (setting('visitor_geo', '1') !== '1' || !function_exists('curl_init')) return;
    $pdo = db();
    $ips = array_values(array_unique(array_merge(
        $pdo->query("SELECT DISTINCT ip FROM visitors_live WHERE ip <> '' LIMIT $max")->fetchAll(PDO::FETCH_COLUMN),
        $pdo->query("SELECT DISTINCT ip FROM visits WHERE ip <> '' ORDER BY ip LIMIT $max")->fetchAll(PDO::FETCH_COLUMN)
    )));
    $ips = array_slice($ips, 0, $max);
    if (!$ips) return;
    $found = [];
    $public = array_values(array_filter($ips, fn($ip) => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)));
    if ($public) {
        $ch = curl_init('http://ip-api.com/batch?fields=status,country,regionName,city,query');
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($public), CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_HTTPHEADER => ['Content-Type: application/json']]);
        $res = json_decode((string)curl_exec($ch), true);
        curl_close($ch);
        if (!is_array($res)) return;                                               // service not reachable: try again later
        foreach ($res as $r) if (isset($r['query'])) $found[$r['query']] = ($r['status'] ?? '') === 'success'
            ? [mb_substr((string)$r['country'], 0, 60), mb_substr((string)$r['regionName'], 0, 80), mb_substr((string)$r['city'], 0, 80)] : ['Unknown', '', ''];
    }
    $v = $pdo->prepare("UPDATE visits SET country = ?, region = ?, city = ?, ip = '' WHERE ip = ?");
    $l = $pdo->prepare("UPDATE visitors_live SET country = ?, region = ?, city = ?, ip = '' WHERE ip = ?");
    foreach ($ips as $ip) {
        [$c, $r, $t] = $found[$ip] ?? (in_array($ip, $public, true) ? ['Unknown', '', ''] : ['Local network', '', '']);
        $v->execute([$c, $r, $t, $ip]);
        $l->execute([$c, $r, $t, $ip]);
    }
}
