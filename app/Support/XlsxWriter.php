<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * نویسنده سبک فایل اکسل (xlsx) بدون وابستگی بیرونی.
 *
 * ساختار فایل بر پایه استاندارد OpenXML ساخته می‌شود: هر برگه یک «worksheet»،
 * متن‌ها در «sharedStrings» و یک «styles.xml» کوچک برای سرستون‌ها.
 */
class XlsxWriter
{
    /** @var list<array{name: string, rows: list<list<mixed>>, header: bool}> */
    private array $sheets = [];

    /**
     * افزودن یک برگه. اگر header=true باشد، سطر اول به شکل سرستون درمی‌آید.
     *
     * @param  array<int, array<int, mixed>|array<string, mixed>>  $rows
     */
    public function addSheet(string $name, array $rows, bool $header = true): static
    {
        $normalized = [];

        foreach ($rows as $row) {
            $normalized[] = array_values((array) $row);
        }

        $this->sheets[] = [
            'name' => $this->sheetName($name),
            'rows' => $normalized,
            'header' => $header && $normalized !== [],
        ];

        return $this;
    }

    /**
     * ساخت فایل روی دیسک.
     */
    public function save(string $path): string
    {
        if (@file_put_contents($path, $this->toString()) === false) {
            throw new RuntimeException('ساخت فایل اکسل ناموفق بود.');
        }

        return $path;
    }

    /**
     * محتوای فایل اکسل به صورت باینری.
     */
    public function toString(): string
    {
        if ($this->sheets === []) {
            throw new RuntimeException('فایل اکسل بدون برگه ساخته نمی‌شود.');
        }

        [$sharedStrings, $sharedIndex] = $this->collectStrings();

        $zip = new ZipArchive;
        $temp = tempnam(sys_get_temp_dir(), 'xlsx');

        if ($temp === false) {
            throw new RuntimeException('فایل موقت برای اکسل ساخته نشد.');
        }

        if ($zip->open($temp, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('باز کردن بسته اکسل ناموفق بود.');
        }

        $sheetCount = count($this->sheets);

        $overrides = '';

        for ($i = 1; $i <= $sheetCount; $i++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        $zip->addFromString('[Content_Types].xml', $this->xml(
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .$overrides
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            .'</Types>'
        ));

        $zip->addFromString('_rels/.rels', $this->xml(
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>'
        ));

        $sheets = '';
        $workbookRels = '';

        foreach ($this->sheets as $i => $sheet) {
            $number = $i + 1;

            $sheets .= '<sheet name="'.$this->escape($sheet['name']).'" sheetId="'.$number.'" r:id="rId'.$number.'"/>';
            $workbookRels .= '<Relationship Id="rId'.$number.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$number.'.xml"/>';

            $zip->addFromString('xl/worksheets/sheet'.$number.'.xml', $this->sheetXml($sheet, $sharedIndex));
        }

        $workbookRels .= '<Relationship Id="rId'.($sheetCount + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $workbookRels .= '<Relationship Id="rId'.($sheetCount + 2).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>';

        $zip->addFromString('xl/workbook.xml', $this->xml(
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<bookViews><workbookView activeTab="0"/></bookViews>'
            .'<sheets>'.$sheets.'</sheets>'
            .'</workbook>'
        ));

        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xml(
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$workbookRels.'</Relationships>'
        ));

        $zip->addFromString('xl/styles.xml', $this->xml($this->stylesXml()));

        $zip->addFromString('xl/sharedStrings.xml', $this->xml(
            '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.count($sharedStrings).'" uniqueCount="'.count($sharedStrings).'">'
            .implode('', array_map(
                fn (string $value) => '<si><t xml:space="preserve">'.$this->escape($value).'</t></si>',
                $sharedStrings
            ))
            .'</sst>'
        ));

        $zip->close();

        $binary = file_get_contents($temp);
        unlink($temp);

        if ($binary === false) {
            throw new RuntimeException('خواندن فایل اکسل ناموفق بود.');
        }

        return $binary;
    }

    /**
     * @return array{0: list<string>, 1: array<string, int>}
     */
    private function collectStrings(): array
    {
        $shared = [];
        $index = [];

        foreach ($this->sheets as $sheet) {
            foreach ($sheet['rows'] as $row) {
                foreach ($row as $value) {
                    if (! is_string($value) || $value === '') {
                        continue;
                    }

                    $text = $this->clean($value);

                    if (! isset($index['s:'.$text])) {
                        $index['s:'.$text] = count($shared);
                        $shared[] = $text;
                    }
                }
            }
        }

        return [$shared, $index];
    }

    /**
     * @param  array{name: string, rows: list<list<mixed>>, header: bool}  $sheet
     * @param  array<string, int>  $sharedIndex
     */
    private function sheetXml(array $sheet, array $sharedIndex): string
    {
        $rows = [];
        $widths = [];

        foreach ($sheet['rows'] as $rowIndex => $row) {
            $cells = '';

            foreach ($row as $columnIndex => $value) {
                $reference = $this->columnLetter($columnIndex).($rowIndex + 1);
                $style = $sheet['header'] && $rowIndex === 0 ? 1 : 0;

                $cells .= $this->cellXml($reference, $value, $style, $sharedIndex);

                $length = mb_strlen($this->clean((string) (is_bool($value) ? 'بله' : $value)), 'UTF-8');
                $widths[$columnIndex] = max($widths[$columnIndex] ?? 0, $length);
            }

            $rows[] = '<row r="'.($rowIndex + 1).'">'.$cells.'</row>';
        }

        $columns = '';

        foreach ($widths as $columnIndex => $length) {
            $width = max(10, min(46, $length + 2));
            $number = $columnIndex + 1;

            $columns .= '<col min="'.$number.'" max="'.$number.'" width="'.$width.'" customWidth="1"/>';
        }

        return $this->xml(
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView rightToLeft="1" workbookViewId="0"/></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="16"/>'
            .($columns === '' ? '' : '<cols>'.$columns.'</cols>')
            .'<sheetData>'.implode('', $rows).'</sheetData>'
            .'</worksheet>'
        );
    }

    /**
     * @param  array<string, int>  $sharedIndex
     */
    private function cellXml(string $reference, mixed $value, int $style, array $sharedIndex): string
    {
        if (is_bool($value)) {
            $value = $value ? 'بله' : 'خیر';
        }

        if ($value === null || $value === '') {
            return '<c r="'.$reference.'"/>';
        }

        if (is_int($value) || is_float($value)) {
            return '<c r="'.$reference.'" s="'.$style.'"><v>'.$value.'</v></c>';
        }

        $text = $this->clean((string) $value);
        $index = $sharedIndex['s:'.$text] ?? 0;

        return '<c r="'.$reference.'" s="'.$style.'" t="s"><v>'.$index.'</v></c>';
    }

    private function stylesXml(): string
    {
        return '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="3">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF254D3A"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    private function columnLetter(int $index): string
    {
        $letters = '';
        $index++;

        while ($index > 0) {
            $index--;
            $letters = chr(65 + ($index % 26)).$letters;
            $index = intdiv($index, 26);
        }

        return $letters;
    }

    /**
     * نام برگه‌ها در اکسل محدودیت دارد: ۳۱ کاراکتر و بدون []:*?/\
     */
    private function sheetName(string $name): string
    {
        $name = trim(str_replace(['[', ']', ':', '*', '?', '/', '\\'], ' ', $name));
        $name = mb_substr($name === '' ? 'برگه' : $name, 0, 31, 'UTF-8');

        $existing = array_column($this->sheets, 'name');
        $base = $name;
        $counter = 2;

        while (in_array($name, $existing, true)) {
            $suffix = ' '.$counter;
            $name = mb_substr($base, 0, 31 - mb_strlen($suffix, 'UTF-8'), 'UTF-8').$suffix;
            $counter++;
        }

        return $name;
    }

    private function clean(string $value): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '';
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        return trim($value);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function xml(string $body): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.$body;
    }
}
