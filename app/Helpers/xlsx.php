<?php

function xlsxSharedStrings(ZipArchive $zip) {
    $xml = $zip->getFromName('xl/sharedStrings.xml');
    if ($xml === false) {
        return [];
    }

    $shared = [];
    $sx = simplexml_load_string($xml);
    $sx->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

    foreach ($sx->xpath('//m:si') as $si) {
        $si->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $parts = [];
        foreach ($si->xpath('.//m:t') as $text) {
            $parts[] = (string)$text;
        }
        $shared[] = implode('', $parts);
    }

    return $shared;
}

function xlsxSheetPath(ZipArchive $zip, $sheetName) {
    $workbookXml = $zip->getFromName('xl/workbook.xml');
    $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

    if ($workbookXml === false || $relsXml === false) {
        throw new Exception('Invalid XLSX workbook structure.');
    }

    $workbook = simplexml_load_string($workbookXml);
    $workbook->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

    $relationId = null;
    foreach ($workbook->xpath('//m:sheet') as $sheet) {
        $attrs = $sheet->attributes();
        if ((string)$attrs['name'] === $sheetName) {
            $relAttrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $relationId = (string)$relAttrs['id'];
            break;
        }
    }

    if (!$relationId) {
        throw new Exception("Sheet '$sheetName' not found in workbook.");
    }

    $rels = simplexml_load_string($relsXml);
    foreach ($rels->Relationship as $rel) {
        $attrs = $rel->attributes();
        if ((string)$attrs['Id'] === $relationId) {
            $target = (string)$attrs['Target'];
            return 'xl/' . ltrim($target, '/');
        }
    }

    throw new Exception("Sheet relation for '$sheetName' not found.");
}

function xlsxCellValue($cell, array $sharedStrings) {
    $attrs = $cell->attributes();
    $type = (string)($attrs['t'] ?? '');

    if ($type === 'inlineStr') {
        $cell->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $texts = $cell->xpath('.//m:t');
        return trim(implode('', array_map('strval', $texts)));
    }

    $value = isset($cell->v) ? (string)$cell->v : '';
    if ($type === 's') {
        return trim($sharedStrings[(int)$value] ?? '');
    }

    return trim($value);
}

function xlsxColumnName($cellRef) {
    return preg_replace('/[^A-Z]/', '', strtoupper($cellRef));
}

function xlsxRows($path, $sheetName) {
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new Exception('Unable to open XLSX file.');
    }

    $sharedStrings = xlsxSharedStrings($zip);
    $sheetPath = xlsxSheetPath($zip, $sheetName);
    $sheetXml = $zip->getFromName($sheetPath);
    $zip->close();

    if ($sheetXml === false) {
        throw new Exception("Unable to read sheet '$sheetName'.");
    }

    $sheet = simplexml_load_string($sheetXml);
    $sheet->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

    $rows = [];
    foreach ($sheet->xpath('//m:sheetData/m:row') as $row) {
        $row->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $cells = [];
        foreach ($row->xpath('m:c') as $cell) {
            $attrs = $cell->attributes();
            $column = xlsxColumnName((string)$attrs['r']);
            $cells[$column] = xlsxCellValue($cell, $sharedStrings);
        }
        $rows[] = $cells;
    }

    return $rows;
}

function parseManualVoucherRegister($path) {
    $rows = xlsxRows($path, 'Master Register');
    $headerMap = [];
    $dataRows = [];

    foreach ($rows as $row) {
        $normalized = array_map(function ($value) {
            return strtolower(trim((string)$value));
        }, $row);

        if (in_array('voucher no', $normalized, true) && in_array('client name', $normalized, true)) {
            foreach ($row as $column => $label) {
                $headerMap[strtolower(trim($label))] = $column;
            }
            continue;
        }

        if (!$headerMap) {
            continue;
        }

        $voucherNo = trim($row[$headerMap['voucher no'] ?? ''] ?? '');
        $clientName = trim($row[$headerMap['client name'] ?? ''] ?? '');

        if ($voucherNo === '' || $clientName === '') {
            continue;
        }

        $dataRows[] = [
            'voucher_no' => $voucherNo,
            'client_name' => $clientName,
            'eva_id' => trim($row[$headerMap['eva id'] ?? ''] ?? ''),
            'original_amount' => parseAmount($row[$headerMap['original amount'] ?? ''] ?? 0),
            'balance' => parseAmount($row[$headerMap['balance'] ?? ''] ?? 0),
            'status' => strtolower(trim($row[$headerMap['status'] ?? ''] ?? 'active')) ?: 'active',
        ];
    }

    return $dataRows;
}
