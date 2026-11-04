<?php
declare(strict_types=1);

/**
 * Nightly automatic data linking — run once a day by cron, e.g. on Hostinger
 * (hPanel → Advanced → Cron Jobs):   php /home/USER/public_html/app/app/bin/auto_import.php
 *
 *  1. Imports every file in storage/imports/inbox/<SOURCE>/  (SOURCE = NCVT, NAPS, EPFO, UDYAM,
 *     HIGHER_EDU, ID_LINK; .xlsx or .csv) and moves it to storage/imports/done/ (or failed/).
 *  2. Fetches every API configured in config.php → 'integrations' (when an MoU gives one):
 *        'integrations' => [
 *            'EPFO' => [
 *                'url'     => 'https://…/members?from={since}',     // {since} = date of the last fetch (YYYY-MM-DD)
 *                'token'   => '…',                                   // optional: sent as "Authorization: Bearer …"
 *                'headers' => ['X-APISETU-CLIENTID' => '…', 'X-APISETU-APIKEY' => '…'],   // optional, any extra headers
 *                'method'  => 'GET',                                 // or POST
 *                'body'    => ['fromDate' => '{since}'],             // optional JSON body for POST
 *                'rows'    => 'data.records',                        // optional: where the list is in the answer
 *                'map'     => ['memberUan' => 'UAN', 'doj' => 'Date of Joining'],   // optional: their field => our column
 *            ],
 *        ]
 *     Without 'rows' the answer may be a list, or {"rows": [...]}, {"data": [...]}, {"records": [...]}.
 *  3. Tries all kept (not yet matched) rows again.
 *
 * Options: --source=EPFO  (only that source)   --no-api   --no-retry
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database as DB;
use App\Core\Migrator;
use App\Services\AutoLink;
use App\Services\Spreadsheet;

$opt = getopt('', ['source:', 'no-api', 'no-retry']);
$only = isset($opt['source']) ? strtoupper((string) $opt['source']) : null;
$say = static fn (string $m) => fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . "] $m\n");

Migrator::run();   // database up to date (also after an update of the files)
$lock = fopen(AutoLink::inboxDir() . '.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    $say('Another run is still busy — stopping.');
    exit(0);
}

$base = AutoLink::inboxDir();
foreach (['inbox', 'done', 'failed'] as $d) {
    foreach (array_keys(AutoLink::SOURCES) as $s) {
        if ($d === 'inbox' && !is_dir("$base/$d/$s")) {
            @mkdir("$base/$d/$s", 0770, true);
        }
    }
    if (!is_dir("$base/$d")) {
        @mkdir("$base/$d", 0770, true);
    }
}
if (!is_file("$base/.htaccess")) {
    @file_put_contents("$base/.htaccess", "Require all denied\nDeny from all\n");
}

$total = 0;
// 1. inbox folders
foreach (array_keys(AutoLink::SOURCES) as $source) {
    if ($only && $only !== $source) {
        continue;
    }
    foreach (glob("$base/inbox/$source/*.{xlsx,csv,XLSX,CSV}", GLOB_BRACE) ?: [] as $file) {
        $name = basename($file);
        try {
            $rows = AutoLink::rowsFromSheet(Spreadsheet::read($file, $name));
            $imp = AutoLink::run($source, $rows, 'INBOX', $name, null);
            $say("$source $name: {$imp['rows_total']} rows, {$imp['matched']} matched ({$imp['created']} new, {$imp['updated']} updated), {$imp['unmatched']} waiting");
            rename($file, "$base/done/" . date('Ymd-His') . "-$source-$name");
            $total++;
        } catch (Throwable $e) {
            $say("$source $name FAILED: " . $e->getMessage());
            DB::insert('integration_logs', ['integration' => $source, 'operation' => 'import:inbox', 'status' => 'FAILED', 'error_code' => mb_substr($e->getMessage(), 0, 60)]);
            rename($file, "$base/failed/" . date('Ymd-His') . "-$source-$name");
        }
    }
}

// 2. APIs (only when configured)
if (!isset($opt['no-api'])) {
    foreach ((array) config('integrations', []) as $source => $c) {
        $source = strtoupper((string) $source);
        if (empty($c['url']) || !isset(AutoLink::SOURCES[$source]) || ($only && $only !== $source)) {
            continue;
        }
        $since = (string) (DB::value("SELECT DATE(MAX(started_at)) FROM data_imports WHERE source = ? AND channel = 'API'", [$source]) ?: '2000-01-01');
        $url = str_replace('{since}', rawurlencode($since), (string) $c['url']);
        $method = strtoupper((string) ($c['method'] ?? 'GET')) === 'POST' ? 'POST' : 'GET';
        $headers = "Accept: application/json\r\n" . (!empty($c['token']) ? 'Authorization: Bearer ' . $c['token'] . "\r\n" : '');
        foreach ((array) ($c['headers'] ?? []) as $hk => $hv) {
            $headers .= preg_replace('/[\r\n:]/', '', (string) $hk) . ': ' . preg_replace('/[\r\n]/', '', (string) $hv) . "\r\n";
        }
        $body = null;
        if ($method === 'POST') {
            $payload = json_decode(str_replace('{since}', $since, (string) json_encode($c['body'] ?? [])), true);
            $body = json_encode($payload ?: new stdClass());
            $headers .= "Content-Type: application/json\r\n";
        }
        $t0 = microtime(true);
        $ctx = stream_context_create(['http' => ['method' => $method, 'timeout' => 120, 'ignore_errors' => true, 'header' => $headers] + ($body !== null ? ['content' => $body] : [])]);
        $answer = @file_get_contents($url, false, $ctx);
        $code = isset($http_response_header[0]) && preg_match('/\s(\d{3})\b/', $http_response_header[0], $m) ? (int) $m[1] : 0;
        $data = is_string($answer) ? json_decode($answer, true) : null;
        $items = null;
        if (is_array($data)) {
            if (!empty($c['rows'])) {
                $items = $data;
                foreach (explode('.', (string) $c['rows']) as $part) {
                    $items = is_array($items) && array_key_exists($part, $items) ? $items[$part] : null;
                }
            } else {
                $items = $data['rows'] ?? $data['data'] ?? $data['records'] ?? (array_is_list($data) ? $data : null);
            }
        }
        if ($code !== 200 || !is_array($items)) {
            $say("$source API failed (HTTP $code)");
            DB::insert('integration_logs', ['integration' => $source, 'operation' => 'import:api', 'status' => 'FAILED', 'http_status' => $code ?: null,
                'duration_ms' => (int) ((microtime(true) - $t0) * 1000), 'error_code' => 'bad_response']);
            continue;
        }
        if (!empty($c['map'])) {   // their field names -> our column names
            $items = array_map(static function ($it) use ($c) {
                if (!is_array($it)) {
                    return $it;
                }
                foreach ((array) $c['map'] as $from => $to) {
                    if (array_key_exists($from, $it)) {
                        $it[$to] = $it[$from];
                        unset($it[$from]);
                    }
                }
                return $it;
            }, $items);
        }
        $imp = AutoLink::run($source, AutoLink::rowsFromJson($items), 'API', parse_url($url, PHP_URL_HOST) ?: 'api', null);
        $say("$source API: {$imp['rows_total']} rows, {$imp['matched']} matched ({$imp['created']} new), {$imp['unmatched']} waiting");
        $total++;
    }
}

// 3. kept rows again
if (!isset($opt['no-retry'])) {
    $r = AutoLink::retryOpen(null);
    $say("Retry: {$r['tried']} waiting rows tried, {$r['matched']} matched now");
}
$say("Done ($total files / feeds).");
flock($lock, LOCK_UN);
