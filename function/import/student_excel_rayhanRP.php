<?php

function rayhanRPExcelArchivePath($rayhanRPFilePath, $rayhanRPEntryPath)
{
    return 'phar://' . str_replace('\\', '/', $rayhanRPFilePath) . '/' . ltrim((string)$rayhanRPEntryPath, '/');
}

function rayhanRPExcelLoadXml($rayhanRPFilePath, $rayhanRPEntryPath)
{
    $rayhanRPXmlPath = rayhanRPExcelArchivePath($rayhanRPFilePath, $rayhanRPEntryPath);
    if (!is_file($rayhanRPXmlPath)) {
        return null;
    }

    $rayhanRPXml = @simplexml_load_file($rayhanRPXmlPath);
    return $rayhanRPXml instanceof SimpleXMLElement ? $rayhanRPXml : null;
}

function rayhanRPExcelColumnToIndex($rayhanRPColumnRef)
{
    $rayhanRPColumnRef = strtoupper(trim((string)$rayhanRPColumnRef));
    $rayhanRPValue = 0;
    for ($rayhanRPIndex = 0, $rayhanRPLength = strlen($rayhanRPColumnRef); $rayhanRPIndex < $rayhanRPLength; $rayhanRPIndex++) {
        $rayhanRPValue = ($rayhanRPValue * 26) + (ord($rayhanRPColumnRef[$rayhanRPIndex]) - 64);
    }

    return max(0, $rayhanRPValue - 1);
}

function rayhanRPExcelNormalizeHeader($rayhanRPValue)
{
    $rayhanRPValue = strtoupper(trim((string)$rayhanRPValue));
    $rayhanRPValue = preg_replace('/\s+/', ' ', $rayhanRPValue);
    return str_replace(['.', ':'], '', $rayhanRPValue);
}

function rayhanRPExcelNormalizeGender($rayhanRPValue)
{
    $rayhanRPValue = strtoupper(trim((string)$rayhanRPValue));
    if ($rayhanRPValue === 'L' || $rayhanRPValue === 'LAKI-LAKI') {
        return 'L';
    }
    if ($rayhanRPValue === 'P' || $rayhanRPValue === 'PEREMPUAN') {
        return 'P';
    }

    return '';
}

function rayhanRPExcelNormalizeNis($rayhanRPValue)
{
    $rayhanRPValue = trim((string)$rayhanRPValue);
    if ($rayhanRPValue === '') {
        return '';
    }

    $rayhanRPValue = str_replace(["\t", ' '], '', $rayhanRPValue);
    if (preg_match('/^\d+(\.0+)?$/', $rayhanRPValue) === 1) {
        return preg_replace('/\.0+$/', '', $rayhanRPValue);
    }

    return trim($rayhanRPValue, "'");
}

function rayhanRPExcelParseSharedStrings($rayhanRPFilePath)
{
    $rayhanRPXml = rayhanRPExcelLoadXml($rayhanRPFilePath, 'xl/sharedStrings.xml');
    if (!$rayhanRPXml) {
        return [];
    }

    $rayhanRPNs = $rayhanRPXml->getNamespaces(true);
    $rayhanRPRootNs = $rayhanRPNs[''] ?? null;
    if ($rayhanRPRootNs) {
        $rayhanRPXml->registerXPathNamespace('main', $rayhanRPRootNs);
        $rayhanRPSiNodes = $rayhanRPXml->xpath('//main:si');
    } else {
        $rayhanRPSiNodes = $rayhanRPXml->si;
    }

    $rayhanRPStrings = [];
    foreach ($rayhanRPSiNodes as $rayhanRPSiNode) {
        $rayhanRPText = '';
        if ($rayhanRPRootNs) {
            $rayhanRPSiNode->registerXPathNamespace('main', $rayhanRPRootNs);
            $rayhanRPTNodes = $rayhanRPSiNode->xpath('.//main:t');
            if (!is_array($rayhanRPTNodes)) {
                $rayhanRPTNodes = [];
            }
        } else {
            $rayhanRPTNodes = $rayhanRPSiNode->t;
        }
        foreach ($rayhanRPTNodes as $rayhanRPTNode) {
            $rayhanRPText .= (string)$rayhanRPTNode;
        }
        $rayhanRPStrings[] = $rayhanRPText;
    }

    return $rayhanRPStrings;
}

function rayhanRPExcelGetFirstWorksheetPath($rayhanRPFilePath)
{
    $rayhanRPWorkbookXml = rayhanRPExcelLoadXml($rayhanRPFilePath, 'xl/workbook.xml');
    $rayhanRPRelsXml = rayhanRPExcelLoadXml($rayhanRPFilePath, 'xl/_rels/workbook.xml.rels');
    if (!$rayhanRPWorkbookXml || !$rayhanRPRelsXml) {
        return null;
    }

    $rayhanRPWorkbookNs = $rayhanRPWorkbookXml->getNamespaces(true);
    $rayhanRPRootNs = $rayhanRPWorkbookNs[''] ?? null;
    $rayhanRPRelNs = $rayhanRPWorkbookNs['r'] ?? 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    if ($rayhanRPRootNs) {
        $rayhanRPWorkbookXml->registerXPathNamespace('main', $rayhanRPRootNs);
        $rayhanRPWorkbookXml->registerXPathNamespace('r', $rayhanRPRelNs);
        $rayhanRPSheets = $rayhanRPWorkbookXml->xpath('//main:sheets/main:sheet');
    } else {
        $rayhanRPSheets = $rayhanRPWorkbookXml->sheets->sheet;
    }

    if (!is_array($rayhanRPSheets) || count($rayhanRPSheets) === 0) {
        return null;
    }

    $rayhanRPFirstSheet = $rayhanRPSheets[0];
    $rayhanRPSheetRid = (string)$rayhanRPFirstSheet->attributes($rayhanRPRelNs)['id'];
    if ($rayhanRPSheetRid === '') {
        return null;
    }

    $rayhanRPRelsNs = $rayhanRPRelsXml->getNamespaces(true);
    $rayhanRPRelsRootNs = $rayhanRPRelsNs[''] ?? null;
    if ($rayhanRPRelsRootNs) {
        $rayhanRPRelsXml->registerXPathNamespace('rel', $rayhanRPRelsRootNs);
        $rayhanRPRelationships = $rayhanRPRelsXml->xpath('//rel:Relationship');
    } else {
        $rayhanRPRelationships = $rayhanRPRelsXml->Relationship;
    }

    foreach ($rayhanRPRelationships as $rayhanRPRelationship) {
        if ((string)$rayhanRPRelationship['Id'] === $rayhanRPSheetRid) {
            $rayhanRPTarget = ltrim((string)$rayhanRPRelationship['Target'], '/');
            return strpos($rayhanRPTarget, 'xl/') === 0 ? $rayhanRPTarget : 'xl/' . $rayhanRPTarget;
        }
    }

    return null;
}

function rayhanRPExcelReadRows($rayhanRPFilePath)
{
    $rayhanRPSheetPath = rayhanRPExcelGetFirstWorksheetPath($rayhanRPFilePath);
    if ($rayhanRPSheetPath === null) {
        return [];
    }

    $rayhanRPXml = rayhanRPExcelLoadXml($rayhanRPFilePath, $rayhanRPSheetPath);
    if (!$rayhanRPXml) {
        return [];
    }

    $rayhanRPSharedStrings = rayhanRPExcelParseSharedStrings($rayhanRPFilePath);
    $rayhanRPNs = $rayhanRPXml->getNamespaces(true);
    $rayhanRPRootNs = $rayhanRPNs[''] ?? null;

    if ($rayhanRPRootNs) {
        $rayhanRPXml->registerXPathNamespace('main', $rayhanRPRootNs);
        $rayhanRPRows = $rayhanRPXml->xpath('//main:sheetData/main:row');
    } else {
        $rayhanRPRows = $rayhanRPXml->sheetData->row;
    }

    $rayhanRPParsedRows = [];
    foreach ($rayhanRPRows as $rayhanRPRow) {
        $rayhanRPCells = [];
        if ($rayhanRPRootNs) {
            $rayhanRPRow->registerXPathNamespace('main', $rayhanRPRootNs);
            $rayhanRPCellNodes = $rayhanRPRow->xpath('./main:c');
            if (!is_array($rayhanRPCellNodes)) {
                $rayhanRPCellNodes = [];
            }
        } else {
            $rayhanRPCellNodes = $rayhanRPRow->c;
        }

        foreach ($rayhanRPCellNodes as $rayhanRPCell) {
            $rayhanRPCellRef = (string)$rayhanRPCell['r'];
            $rayhanRPColumnRef = preg_replace('/\d+/', '', $rayhanRPCellRef);
            $rayhanRPColumnIndex = rayhanRPExcelColumnToIndex($rayhanRPColumnRef);
            $rayhanRPType = (string)$rayhanRPCell['t'];
            $rayhanRPValue = '';

            if ($rayhanRPType === 's') {
                $rayhanRPSharedIndex = (int)$rayhanRPCell->v;
                $rayhanRPValue = (string)($rayhanRPSharedStrings[$rayhanRPSharedIndex] ?? '');
            } elseif ($rayhanRPType === 'inlineStr') {
                if ($rayhanRPRootNs) {
                    $rayhanRPCell->registerXPathNamespace('main', $rayhanRPRootNs);
                    $rayhanRPTNodes = $rayhanRPCell->xpath('.//main:t');
                    if (!is_array($rayhanRPTNodes)) {
                        $rayhanRPTNodes = [];
                    }
                } else {
                    $rayhanRPTNodes = $rayhanRPCell->is->t;
                }
                foreach ($rayhanRPTNodes as $rayhanRPTNode) {
                    $rayhanRPValue .= (string)$rayhanRPTNode;
                }
            } else {
                $rayhanRPValue = (string)$rayhanRPCell->v;
            }

            $rayhanRPCells[$rayhanRPColumnIndex] = trim($rayhanRPValue);
        }

        if (count($rayhanRPCells) === 0) {
            $rayhanRPParsedRows[] = [];
            continue;
        }

        ksort($rayhanRPCells);
        $rayhanRPParsedRows[] = $rayhanRPCells;
    }

    return $rayhanRPParsedRows;
}

function rayhanRPExcelFindHeaderMap($rayhanRPRows)
{
    foreach ($rayhanRPRows as $rayhanRPRowIndex => $rayhanRPRow) {
        $rayhanRPHeaderMap = [];
        foreach ($rayhanRPRow as $rayhanRPColumnIndex => $rayhanRPValue) {
            $rayhanRPHeader = rayhanRPExcelNormalizeHeader($rayhanRPValue);
            if ($rayhanRPHeader !== '') {
                $rayhanRPHeaderMap[$rayhanRPHeader] = (int)$rayhanRPColumnIndex;
            }
        }

        $rayhanRPNamaIndex = null;
        $rayhanRPNisIndex = null;
        $rayhanRPGenderIndex = null;
        foreach ($rayhanRPHeaderMap as $rayhanRPHeader => $rayhanRPColumnIndex) {
            if ($rayhanRPNamaIndex === null && strpos($rayhanRPHeader, 'NAMA') !== false) {
                $rayhanRPNamaIndex = $rayhanRPColumnIndex;
            }
            if ($rayhanRPNisIndex === null && strpos($rayhanRPHeader, 'NIS') !== false) {
                $rayhanRPNisIndex = $rayhanRPColumnIndex;
            }
            if ($rayhanRPGenderIndex === null && (strpos($rayhanRPHeader, 'L/P') !== false || strpos($rayhanRPHeader, 'JENIS KELAMIN') !== false || strpos($rayhanRPHeader, 'JK') !== false)) {
                $rayhanRPGenderIndex = $rayhanRPColumnIndex;
            }
        }

        if ($rayhanRPNamaIndex !== null && $rayhanRPNisIndex !== null) {
            return [
                'header_row_index' => (int)$rayhanRPRowIndex,
                'nama_index' => (int)$rayhanRPNamaIndex,
                'nis_index' => (int)$rayhanRPNisIndex,
                'gender_index' => $rayhanRPGenderIndex === null ? null : (int)$rayhanRPGenderIndex,
            ];
        }
    }

    return null;
}

function rayhanRPExcelClassLabelFromFileName($rayhanRPFileName)
{
    $rayhanRPBaseName = pathinfo((string)$rayhanRPFileName, PATHINFO_FILENAME);
    $rayhanRPBaseName = str_replace(['_', '-'], ' ', $rayhanRPBaseName);
    $rayhanRPBaseName = preg_replace('/\s+/', ' ', trim($rayhanRPBaseName));
    return strtoupper($rayhanRPBaseName);
}

function rayhanRPExcelDeduplicateStudentRows($rayhanRPRows, &$rayhanRPSkipped = 0, &$rayhanRPErrors = [])
{
    $rayhanRPSeenNis = [];
    $rayhanRPUniqueRows = [];

    foreach ($rayhanRPRows as $rayhanRPRow) {
        $rayhanRPNis = trim((string)($rayhanRPRow['nis_nip'] ?? ''));
        if ($rayhanRPNis === '') {
            $rayhanRPSkipped++;
            continue;
        }

        if (isset($rayhanRPSeenNis[$rayhanRPNis])) {
            $rayhanRPSkipped++;
            $rayhanRPError = 'NIS duplikat dalam file Excel: ' . $rayhanRPNis . '.';
            if (!in_array($rayhanRPError, $rayhanRPErrors, true)) {
                $rayhanRPErrors[] = $rayhanRPError;
            }
            continue;
        }

        $rayhanRPSeenNis[$rayhanRPNis] = true;
        $rayhanRPUniqueRows[] = $rayhanRPRow;
    }

    return $rayhanRPUniqueRows;
}

function rayhanRPExcelParseStudentWorkbook($rayhanRPFilePath, $rayhanRPOriginalName = '')
{
    $rayhanRPResult = [
        'class_label' => rayhanRPExcelClassLabelFromFileName($rayhanRPOriginalName !== '' ? $rayhanRPOriginalName : basename($rayhanRPFilePath)),
        'rows' => [],
        'skipped' => 0,
        'errors' => [],
    ];

    if (!class_exists('PharData')) {
        $rayhanRPResult['errors'][] = 'PHP belum mendukung pembacaan file Excel di server ini.';
        return $rayhanRPResult;
    }

    try {
        new PharData($rayhanRPFilePath);
    } catch (Throwable $rayhanRPThrowable) {
        $rayhanRPResult['errors'][] = 'File Excel tidak bisa dibaca.';
        return $rayhanRPResult;
    }

    $rayhanRPRows = rayhanRPExcelReadRows($rayhanRPFilePath);
    $rayhanRPHeaderMap = rayhanRPExcelFindHeaderMap($rayhanRPRows);
    if ($rayhanRPHeaderMap === null) {
        $rayhanRPResult['errors'][] = 'Header Excel tidak ditemukan. Minimal harus ada kolom NAMA dan NIS.';
        return $rayhanRPResult;
    }

    foreach ($rayhanRPRows as $rayhanRPRowIndex => $rayhanRPRow) {
        if ($rayhanRPRowIndex <= $rayhanRPHeaderMap['header_row_index']) {
            continue;
        }

        $rayhanRPNama = trim((string)($rayhanRPRow[$rayhanRPHeaderMap['nama_index']] ?? ''));
        $rayhanRPNis = rayhanRPExcelNormalizeNis($rayhanRPRow[$rayhanRPHeaderMap['nis_index']] ?? '');
        $rayhanRPJenisKelamin = '';
        if ($rayhanRPHeaderMap['gender_index'] !== null) {
            $rayhanRPJenisKelamin = rayhanRPExcelNormalizeGender($rayhanRPRow[$rayhanRPHeaderMap['gender_index']] ?? '');
        }

        if ($rayhanRPNama === '' && $rayhanRPNis === '') {
            continue;
        }

        if ($rayhanRPNama === '' || $rayhanRPNis === '') {
            $rayhanRPResult['skipped']++;
            continue;
        }

        $rayhanRPResult['rows'][] = [
            'nama_lengkap' => $rayhanRPNama,
            'nis_nip' => $rayhanRPNis,
            'jenis_kelamin' => $rayhanRPJenisKelamin,
            'kelas_label' => $rayhanRPResult['class_label'],
        ];
    }

    $rayhanRPResult['rows'] = rayhanRPExcelDeduplicateStudentRows(
        $rayhanRPResult['rows'],
        $rayhanRPResult['skipped'],
        $rayhanRPResult['errors']
    );

    if (count($rayhanRPResult['rows']) === 0) {
        $rayhanRPResult['errors'][] = 'Tidak ada data siswa yang valid pada file Excel.';
    }

    return $rayhanRPResult;
}
