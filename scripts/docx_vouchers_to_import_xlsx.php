<?php

if ($argc < 3) {
    fwrite(STDERR, "Usage: php scripts/docx_vouchers_to_import_xlsx.php input.docx output.xlsx\n");
    exit(1);
}

$input = $argv[1];
$output = $argv[2];

if (!is_file($input)) {
    fwrite(STDERR, "Input DOCX not found: $input\n");
    exit(1);
}

$rows = extractVoucherRowsFromDocx($input);
if (!$rows) {
    fwrite(STDERR, "No voucher rows found in DOCX.\n");
    exit(1);
}

writeImportWorkbook($output, $rows);
echo "Created $output with " . count($rows) . " vouchers.\n";

function extractVoucherRowsFromDocx($path) {
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('Unable to open DOCX file.');
    }

    $xml = $zip->getFromName('word/document.xml');
    $zip->close();

    if ($xml === false) {
        throw new RuntimeException('Unable to read DOCX document XML.');
    }

    $doc = new DOMDocument();
    $doc->loadXML($xml);
    $xpath = new DOMXPath($doc);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

    $vouchers = [];
    foreach ($xpath->query('//w:tbl/w:tr') as $row) {
        $cells = [];
        foreach ($xpath->query('w:tc', $row) as $cell) {
            $parts = [];
            foreach ($xpath->query('.//w:t', $cell) as $text) {
                $parts[] = $text->textContent;
            }
            $cells[] = trim(preg_replace('/\s+/', ' ', implode('', $parts)));
        }

        if (count($cells) < 4 || !ctype_digit($cells[0])) {
            continue;
        }

        $evaId = trim($cells[1]);
        $clientName = trim($cells[2]);
        $amount = amountToNumber($cells[3]);

        if ($evaId === '' || $clientName === '' || $amount <= 0) {
            continue;
        }

        $vouchers[] = [
            'voucher_no' => $evaId,
            'client_name' => $clientName,
            'eva_id' => $evaId,
            'original_amount' => $amount,
            'balance' => $amount,
            'status' => 'active',
        ];
    }

    return $vouchers;
}

function amountToNumber($value) {
    return (float)preg_replace('/[^0-9.]/', '', $value);
}

function writeImportWorkbook($output, array $rows) {
    $zip = new ZipArchive();
    if ($zip->open($output, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Unable to create XLSX file.');
    }

    $zip->addFromString('[Content_Types].xml', contentTypesXml());
    $zip->addFromString('_rels/.rels', packageRelsXml());
    $zip->addFromString('xl/workbook.xml', workbookXml());
    $zip->addFromString('xl/_rels/workbook.xml.rels', workbookRelsXml());
    $zip->addFromString('xl/worksheets/sheet1.xml', worksheetXml($rows));
    $zip->addFromString('xl/styles.xml', stylesXml());
    $zip->addFromString('docProps/core.xml', corePropsXml());
    $zip->addFromString('docProps/app.xml', appPropsXml());
    $zip->close();
}

function xmlEscape($value) {
    return htmlspecialchars((string)$value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

function cellInline($ref, $value, $style = null) {
    $styleAttr = $style === null ? '' : ' s="' . (int)$style . '"';
    return '<c r="' . $ref . '" t="inlineStr"' . $styleAttr . '><is><t>' . xmlEscape($value) . '</t></is></c>';
}

function cellNumber($ref, $value) {
    return '<c r="' . $ref . '" s="2"><v>' . xmlEscape($value) . '</v></c>';
}

function worksheetXml(array $rows) {
    $headers = ['Voucher No', 'Client Name', 'EVA ID', 'Original Amount', 'Balance', 'Status'];
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
    $xml .= '<cols><col min="1" max="1" width="22" customWidth="1"/><col min="2" max="2" width="32" customWidth="1"/><col min="3" max="3" width="22" customWidth="1"/><col min="4" max="5" width="16" customWidth="1"/><col min="6" max="6" width="12" customWidth="1"/></cols>';
    $xml .= '<sheetData>';
    $xml .= '<row r="1">';
    foreach ($headers as $i => $header) {
        $xml .= cellInline(chr(65 + $i) . '1', $header, 1);
    }
    $xml .= '</row>';

    foreach ($rows as $index => $row) {
        $r = $index + 2;
        $xml .= '<row r="' . $r . '">';
        $xml .= cellInline('A' . $r, $row['voucher_no']);
        $xml .= cellInline('B' . $r, $row['client_name']);
        $xml .= cellInline('C' . $r, $row['eva_id']);
        $xml .= cellNumber('D' . $r, $row['original_amount']);
        $xml .= cellNumber('E' . $r, $row['balance']);
        $xml .= cellInline('F' . $r, $row['status']);
        $xml .= '</row>';
    }

    $xml .= '</sheetData></worksheet>';
    return $xml;
}

function contentTypesXml() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>';
}

function packageRelsXml() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
}

function workbookXml() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Master Register" sheetId="1" r:id="rId1"/></sheets></workbook>';
}

function workbookRelsXml() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
}

function stylesXml() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFFE699"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"/><right style="thin"/><top style="thin"/><bottom style="thin"/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1"/><xf numFmtId="4" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
}

function corePropsXml() {
    $now = gmdate('Y-m-d\TH:i:s\Z');
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator>N.HONEST Voucher System</dc:creator><cp:lastModifiedBy>N.HONEST Voucher System</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified></cp:coreProperties>';
}

function appPropsXml() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>N.HONEST Voucher System</Application></Properties>';
}
