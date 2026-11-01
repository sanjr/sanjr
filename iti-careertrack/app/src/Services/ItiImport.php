<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database as DB;

/**
 * Imports an ITI list (Excel .xlsx or .csv) in the DVET format:
 *   No. | NCVT MIS ITI Code | Institute Name | ITI Type | Taluka | District | Region
 * The sheet may hold several district blocks, each with its own header row.
 *
 * For every ITI: the ITI is added (or its name/type/district/taluka updated) and a Principal
 * login is created with User ID = NCVT MIS code. For every district a DVTO login
 * (DVTO-<DISTRICT CODE>) and for every region an RO login (RO-<REGION CODE>) is created if missing.
 */
final class ItiImport
{
    /** @return list<array{code:string,name:string,type:string,taluka:string,district:string,region:string,row:int}> */
    public static function readFile(string $path, string $originalName): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $rows = match ($ext) {
            'xlsx' => self::xlsxRows($path),
            'csv', 'txt' => self::csvRows($path),
            default => throw new \DomainException('Upload an Excel (.xlsx) or CSV file.'),
        };
        return self::parse($rows);
    }

    /** Header-driven parsing; works with one table or several district blocks in one sheet. */
    public static function parse(array $rows): array
    {
        $map = null;
        $out = [];
        foreach ($rows as $i => $r) {
            $norm = array_map(static fn ($v) => strtolower(preg_replace('/[^a-z]/i', '', (string) $v)), $r);
            $codeCol = self::find($norm, ['ncvtmisiticode', 'ncvtmiscode', 'iticode', 'ncvtcode', 'misitcode']);
            $nameCol = self::find($norm, ['institutename', 'itiname', 'nameofinstitute', 'name']);
            if ($codeCol !== null && $nameCol !== null) {
                $map = ['code' => $codeCol, 'name' => $nameCol, 'type' => self::find($norm, ['ititype', 'type', 'ownership']),
                        'taluka' => self::find($norm, ['taluka', 'tehsil']), 'district' => self::find($norm, ['district']),
                        'region' => self::find($norm, ['region', 'regionaloffice'])];
                continue;
            }
            if ($map === null) {
                continue;
            }
            $code = strtoupper(trim((string) ($r[$map['code']] ?? '')));
            $name = trim((string) ($r[$map['name']] ?? ''));
            if ($code === '' || $name === '' || !preg_match('/^[A-Z0-9]{4,20}$/', $code)) {
                continue;   // blank line or a "2. Satara District" title row
            }
            $get = static fn (?int $c) => $c === null ? '' : trim((string) ($r[$c] ?? ''));
            $taluka = $get($map['taluka']);
            // MSBSVET style: "GOVERNMENT INDUSTRIAL TRAINING INSTITUTE, DADAR, TALUKA: MUMBAI, DISTRICT: MUMBAI CITY"
            if (preg_match('/,?\s*TALUKA\s*:?\s*([^,]+?)\s*(?:,\s*DISTRICT\b.*)?$/i', $name, $m)) {
                $taluka = $taluka !== '' ? $taluka : self::title($m[1]);
                $name = trim(substr($name, 0, -strlen($m[0])), " ,");
            }
            $name = self::cleanName($name);
            $type = $get($map['type']);
            if ($type === '') {   // no type column: GR/GU codes and "Government" names are Government ITIs
                $type = preg_match('/^G[RU]\d/', $code) || stripos($name, 'govt') !== false || stripos($name, 'government') !== false ? 'Government' : 'Private';
            }
            $out[] = ['code' => $code, 'name' => mb_substr($name, 0, 200), 'type' => $type, 'taluka' => $taluka,
                      'district' => $get($map['district']), 'region' => $get($map['region']), 'row' => $i + 1];
        }
        if (!$out) {
            throw new \DomainException('No ITIs found. The sheet needs a header row with "NCVT MIS ITI Code", "Institute Name" and "District".');
        }
        return $out;
    }

    /** "GOVERNMENT INDUSTRIAL TRAINING INSTITUTE, (WOMAN), DADAR" -> "Govt. ITI (Woman), Dadar" */
    private static function cleanName(string $name): string
    {
        if (preg_match('/^GOVERNMENT\s+INDUSTRIAL\s+TRAINING\s+INSTITUTE\s*,?\s*(.*)$/i', $name, $m)) {
            return trim('Govt. ITI ' . self::title($m[1]));
        }
        return $name;
    }

    private static function title(string $s): string
    {
        return ucwords(strtolower(trim($s)), " \t(-/.");
    }

    private static function find(array $norm, array $names): ?int
    {
        foreach ($names as $n) {
            $k = array_search($n, $norm, true);
            if ($k !== false) {
                return (int) $k;
            }
        }
        return null;
    }

    private static function csvRows(string $path): array
    {
        $fh = fopen($path, 'rb');
        $rows = [];
        while (($r = fgetcsv($fh, 0, ',', '"', '')) !== false) {
            if (isset($r[0])) {
                $r[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $r[0]);
            }
            $rows[] = $r;
        }
        fclose($fh);
        return $rows;
    }

    /** Minimal .xlsx reader (first worksheet, shared + inline strings). Needs the zip extension. */
    private static function xlsxRows(string $path): array
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \DomainException('This server cannot read Excel files (PHP zip extension missing). Save the sheet as CSV and upload that.');
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \DomainException('The Excel file could not be opened.');
        }
        $strings = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $sx = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET);
            foreach ($sx->si as $si) {
                $t = '';
                if (isset($si->t)) {
                    $t = (string) $si->t;
                }
                foreach ($si->r as $run) {
                    $t .= (string) $run->t;
                }
                $strings[] = $t;
            }
        }
        $sheet = 'xl/worksheets/sheet1.xml';
        if ($zip->locateName($sheet) === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                if (preg_match('~^xl/worksheets/[^/]+\.xml$~', (string) $zip->getNameIndex($i))) {
                    $sheet = (string) $zip->getNameIndex($i);
                    break;
                }
            }
        }
        $xml = $zip->getFromName($sheet);
        $zip->close();
        if ($xml === false) {
            throw new \DomainException('The Excel file has no worksheet.');
        }
        $sx = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET);
        $rows = [];
        foreach ($sx->sheetData->row as $row) {
            $r = [];
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                $col = 0;
                foreach (str_split(preg_replace('/\d/', '', $ref)) as $ch) {
                    $col = $col * 26 + (ord($ch) - 64);
                }
                $type = (string) $c['t'];
                $v = match ($type) {
                    's' => $strings[(int) $c->v] ?? '',
                    'inlineStr' => (string) $c->is->t,
                    default => (string) $c->v,
                };
                $r[$col - 1] = $v;
            }
            if ($r) {
                $max = max(array_keys($r));
                $rows[] = array_replace(array_fill(0, $max + 1, ''), $r);
            }
        }
        return $rows;
    }

    /**
     * @return array{itis_added:int,itis_updated:int,principals:int,dvto:list<string>,ro:list<string>,warnings:list<string>}
     */
    public static function import(array $items, string $password, bool $mustChange, ?int $actor): array
    {
        $hash = password_hash($password, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT);
        $roles = array_column(DB::all("SELECT id, code FROM roles WHERE code IN ('PRINCIPAL','DVTO','RO_ADMIN')"), 'id', 'code');
        $districts = DB::all('SELECT d.id, d.code, d.name, d.region_id, r.code AS region_code, r.name AS region_name, r.hq_city
                                FROM districts d LEFT JOIN regions r ON r.id = d.region_id');
        $res = ['itis_added' => 0, 'itis_updated' => 0, 'principals' => 0, 'dvto' => [], 'ro' => [], 'warnings' => []];
        $today = date('Y-m-d');

        return DB::tx(function () use ($items, $hash, $mustChange, $actor, $roles, $districts, $res, $today) {
            $seenDistricts = [];
            $seenCodes = [];
            foreach ($items as $it) {
                if (isset($seenCodes[$it['code']])) {
                    $res['warnings'][] = "Row {$it['row']}: {$it['code']} is repeated (first in row {$seenCodes[$it['code']]}) — ignored.";
                    continue;
                }
                $seenCodes[$it['code']] = $it['row'];
                $d = self::matchDistrict($districts, $it['district']);
                if (!$d) {
                    $res['warnings'][] = "Row {$it['row']}: district \"{$it['district']}\" not found — {$it['code']} skipped.";
                    continue;
                }
                if ($it['region'] !== '' && $d['region_name'] !== null
                    && stripos($d['region_name'] . ' ' . $d['hq_city'], $it['region']) === false) {
                    $res['warnings'][] = "Row {$it['row']}: {$it['district']} belongs to {$d['region_name']}, not \"{$it['region']}\" — district region kept.";
                }
                $type = stripos($it['type'], 'priv') !== false ? 'PRIVATE' : 'GOVERNMENT';
                $talukaId = $it['taluka'] !== '' ? self::taluka((int) $d['id'], $it['taluka']) : null;

                $existing = DB::one('SELECT id FROM itis WHERE iti_code = ?', [$it['code']]);
                if ($existing) {
                    $itiId = (int) $existing['id'];
                    DB::run('UPDATE itis SET name = ?, ownership_type = ?, district_id = ?, taluka_id = ?, updated_by = ? WHERE id = ?',
                        [$it['name'], $type, $d['id'], $talukaId, $actor, $itiId]);
                    $res['itis_updated']++;
                } else {
                    $itiId = DB::insert('itis', ['iti_code' => $it['code'], 'name' => $it['name'], 'ownership_type' => $type,
                        'district_id' => $d['id'], 'taluka_id' => $talukaId, 'created_by' => $actor]);
                    $res['itis_added']++;
                }
                if (self::ensureUser($it['code'], 'Principal, ' . $it['name'], $hash, $mustChange, $actor, (int) $roles['PRINCIPAL'],
                        ['scope_type' => 'ITI', 'iti_id' => $itiId], $today)) {
                    $res['principals']++;
                }
                $seenDistricts[$d['id']] = $d;
            }
            $seenRegions = [];
            foreach ($seenDistricts as $d) {
                $u = 'DVTO-' . strtoupper(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $d['name']), '-'));   // DVTO-MUMBAI-SUBURBAN
                if (self::ensureUser($u, 'DVTO, ' . $d['name'], $hash, $mustChange, $actor, (int) $roles['DVTO'],
                        ['scope_type' => 'DISTRICT', 'district_id' => $d['id']], $today)) {
                    $res['dvto'][] = $u;
                }
                if ($d['region_id']) {
                    $seenRegions[$d['region_id']] = $d;
                }
            }
            foreach ($seenRegions as $d) {
                $u = 'RO-' . strtoupper($d['region_code']);
                if (self::ensureUser($u, 'RO Admin, ' . $d['region_name'], $hash, $mustChange, $actor, (int) $roles['RO_ADMIN'],
                        ['scope_type' => 'REGION', 'region_id' => $d['region_id']], $today)) {
                    $res['ro'][] = $u;
                }
            }
            return $res;
        });
    }

    private static function matchDistrict(array $districts, string $name): ?array
    {
        $n = strtolower(preg_replace('/[^a-z]/i', '', $name));
        foreach ($districts as $d) {
            if ($n !== '' && ($n === strtolower(preg_replace('/[^a-z]/i', '', $d['name'])) || $n === strtolower($d['code']))) {
                return $d;
            }
        }
        return null;
    }

    private static function taluka(int $districtId, string $name): int
    {
        $name = mb_substr($name, 0, 100);
        $id = DB::value('SELECT id FROM talukas WHERE district_id = ? AND name = ?', [$districtId, $name]);
        if ($id) {
            return (int) $id;
        }
        $slug = strtoupper(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-'));
        $code = substr('T' . $districtId . '-' . $slug, 0, 20);
        if (DB::value('SELECT 1 FROM talukas WHERE code = ?', [$code])) {
            $code = substr('T' . $districtId . '-' . substr(md5($name), 0, 8), 0, 20);
        }
        return DB::insert('talukas', ['district_id' => $districtId, 'code' => $code, 'name' => $name]);
    }

    /** Creates the login + role unless a user with this User ID already exists. Returns true if created. */
    private static function ensureUser(string $username, string $fullName, string $hash, bool $mustChange, ?int $actor,
                                       int $roleId, array $scope, string $today): bool
    {
        if (DB::value('SELECT 1 FROM users WHERE username = ?', [$username])) {
            return false;
        }
        $uid = DB::insert('users', ['full_name' => mb_substr($fullName, 0, 150), 'username' => $username, 'password_hash' => $hash,
            'must_change_password' => $mustChange ? 1 : 0, 'status' => 'ACTIVE', 'created_by' => $actor]);
        DB::insert('user_role_assignments', ['user_id' => $uid, 'role_id' => $roleId, 'valid_from' => $today, 'created_by' => $actor] + $scope);
        return true;
    }
}
