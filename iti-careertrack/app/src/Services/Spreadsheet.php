<?php
declare(strict_types=1);

namespace App\Services;

/** Minimal Excel (.xlsx) / CSV reader and .xlsx writer — needs only PHP's zip extension. */
final class Spreadsheet
{
    /** @return list<list<string>> rows of the first sheet */
    public static function read(string $path, string $originalName): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext === 'csv' || $ext === 'txt') {
            $fh = fopen($path, 'rb');
            $rows = [];
            while (($r = fgetcsv($fh, 0, ',', '"', '')) !== false) {
                if (isset($r[0])) {
                    $r[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $r[0]);
                }
                $rows[] = array_map(static fn ($v) => trim((string) $v), $r);
            }
            fclose($fh);
            return $rows;
        }
        if ($ext !== 'xlsx') {
            throw new \DomainException('Upload the Excel file (.xlsx) or a CSV file.');
        }
        if (!class_exists(\ZipArchive::class)) {
            throw new \DomainException('This server cannot read Excel files (PHP zip extension missing). Save the sheet as CSV and upload that.');
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \DomainException('The Excel file could not be opened.');
        }
        $strings = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            foreach (simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET)->si as $si) {
                $t = isset($si->t) ? (string) $si->t : '';
                foreach ($si->r as $run) {
                    $t .= (string) $run->t;
                }
                $strings[] = $t;
            }
        }
        // numeric formats that are dates (Excel stores a date as a day number)
        $dateStyles = [];
        if (($xml = $zip->getFromName('xl/styles.xml')) !== false) {
            $st = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET);
            $custom = [];
            foreach ($st->numFmts->numFmt ?? [] as $f) {
                $custom[(int) $f['numFmtId']] = (string) $f['formatCode'];
            }
            $i = 0;
            foreach ($st->cellXfs->xf ?? [] as $xf) {
                $id = (int) $xf['numFmtId'];
                $code = $custom[$id] ?? '';
                if (($id >= 14 && $id <= 22) || ($code !== '' && preg_match('/[dy]/i', preg_replace('/"[^"]*"|\[[^]]*]/', '', $code)))) {
                    $dateStyles[$i] = true;
                }
                $i++;
            }
        }
        $sheet = $zip->locateName('xl/worksheets/sheet1.xml') !== false ? 'xl/worksheets/sheet1.xml' : null;
        for ($i = 0; !$sheet && $i < $zip->numFiles; $i++) {
            if (preg_match('~^xl/worksheets/[^/]+\.xml$~', (string) $zip->getNameIndex($i))) {
                $sheet = (string) $zip->getNameIndex($i);
            }
        }
        $xml = $sheet ? $zip->getFromName($sheet) : false;
        $zip->close();
        if ($xml === false) {
            throw new \DomainException('The Excel file has no worksheet.');
        }
        $rows = [];
        foreach (simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET)->sheetData->row as $row) {
            $r = [];
            foreach ($row->c as $c) {
                $col = 0;
                foreach (str_split(preg_replace('/\d/', '', (string) $c['r'])) as $ch) {
                    $col = $col * 26 + (ord($ch) - 64);
                }
                $type = (string) $c['t'];
                $v = match ($type) {
                    's' => $strings[(int) $c->v] ?? '',
                    'inlineStr' => (string) $c->is->t,
                    'd' => substr((string) $c->v, 0, 10),   // ISO date
                    default => (string) $c->v,
                };
                if (($type === '' || $type === 'n') && $v !== '' && is_numeric($v)) {
                    if (isset($dateStyles[(int) $c['s']]) && (float) $v > 1000 && (float) $v < 100000) {
                        $v = gmdate('Y-m-d', (int) round(((float) $v - 25569) * 86400));   // Excel day number -> date
                    } elseif (str_contains(strtoupper($v), 'E') || (floor((float) $v) == (float) $v && strlen($v) > 6)) {
                        $v = sprintf('%.0f', (float) $v);                                  // 9.87654321E9 -> 9876543210
                    }
                }
                $r[$col - 1] = trim($v);
            }
            if ($r) {
                $rows[] = array_replace(array_fill(0, max(array_keys($r)) + 1, ''), $r);
            }
        }
        return $rows;
    }

    /**
     * Build an .xlsx file (all cells as text, first row bold and frozen).
     * @param array<string, list<list<string>>> $sheets sheet name => rows
     */
    public static function xlsx(array $sheets): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $names = array_keys($sheets);
        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $wbSheets = $wbRels = '';
        foreach ($names as $i => $n) {
            $k = $i + 1;
            $ct .= '<Override PartName="/xl/worksheets/sheet' . $k . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $wbSheets .= '<sheet name="' . self::x(mb_substr($n, 0, 31)) . '" sheetId="' . $k . '" r:id="rId' . $k . '"/>';
            $wbRels .= '<Relationship Id="rId' . $k . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $k . '.xml"/>';
        }
        $n = count($names) + 1;
        $wbRels .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $zip->addFromString('[Content_Types].xml', $ct . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>' . $wbSheets . '</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $wbRels . '</Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="49" formatCode="@"/></numFmts>'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0F3D8A"/></patternFill></fill></fills>'
            . '<borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs>'
            . '<cellXfs count="2"><xf numFmtId="49" applyNumberFormat="1"/><xf numFmtId="49" fontId="1" fillId="2" applyFont="1" applyFill="1" applyNumberFormat="1"/></cellXfs></styleSheet>');
        foreach (array_values($sheets) as $i => $rows) {
            $width = max(array_map('count', $rows ?: [[]])) ?: 1;
            $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
                . '<cols><col min="1" max="' . $width . '" width="22" customWidth="1" style="0"/></cols><sheetData>';
            foreach ($rows as $ri => $row) {
                $xml .= '<row r="' . ($ri + 1) . '">';
                foreach (array_values($row) as $ci => $v) {
                    $ref = self::col($ci) . ($ri + 1);
                    $xml .= '<c r="' . $ref . '" t="inlineStr" s="' . ($ri === 0 ? 1 : 0) . '"><is><t xml:space="preserve">' . self::x((string) $v) . '</t></is></c>';
                }
                $xml .= '</row>';
            }
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $xml . '</sheetData></worksheet>');
        }
        $zip->close();
        $bytes = (string) file_get_contents($tmp);
        @unlink($tmp);
        return $bytes;
    }

    private static function col(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }
        return $s;
    }

    private static function x(string $s): string
    {
        return htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
