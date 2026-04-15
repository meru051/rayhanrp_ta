<?php
require_once __DIR__ . '/../koneksi_rayhanRP.php';
require_once __DIR__ . '/../function/import/student_excel_rayhanRP.php';
require_once __DIR__ . '/includes/admin_layout_rayhanRP.php';

rayhanRPStartSession();

$rayhanRPAdmin = rayhanRPRequireAdminSession('loginAdmin_rayhanRP.php');
$rayhanRPAdminId = (int)$rayhanRPAdmin['akun_id'];
$rayhanRPAdminLabel = (string)$rayhanRPAdmin['label'];
$rayhanRPAdminNisNip = (string)$rayhanRPAdmin['nis_nip'];
$rayhanRPAdminRole = (string)$rayhanRPAdmin['role'];
$rayhanRPError = '';
$rayhanRPSuccess = '';
$rayhanRPFileResults = [];
$rayhanRPBatchSummary = [
    'files_total' => 0,
    'files_processed' => 0,
    'files_failed' => 0,
    'groups_created' => 0,
    'accounts_inserted' => 0,
    'accounts_updated' => 0,
    'members_added' => 0,
    'skipped' => 0,
    'errors' => 0,
];

function rayhanRPExcelAddResultError(&$rayhanRPResult, $rayhanRPMessage)
{
    $rayhanRPMessage = trim((string)$rayhanRPMessage);
    if ($rayhanRPMessage !== '' && !in_array($rayhanRPMessage, $rayhanRPResult['errors'], true)) {
        $rayhanRPResult['errors'][] = $rayhanRPMessage;
    }
}

function rayhanRPExcelUploadErrorMessage($rayhanRPErrorCode)
{
    switch ((int)$rayhanRPErrorCode) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'Ukuran file melebihi batas upload server.';
        case UPLOAD_ERR_PARTIAL:
            return 'Upload file tidak selesai.';
        case UPLOAD_ERR_NO_FILE:
            return 'Tidak ada file yang diupload.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Folder temporary upload tidak tersedia.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Server gagal menyimpan file upload.';
        case UPLOAD_ERR_EXTENSION:
            return 'Upload dibatalkan oleh ekstensi PHP.';
        default:
            return 'Terjadi kesalahan upload file.';
    }
}

function rayhanRPExcelCollectUploads($rayhanRPFiles)
{
    if (!is_array($rayhanRPFiles) || !isset($rayhanRPFiles['name'])) {
        return [];
    }

    if (!is_array($rayhanRPFiles['name'])) {
        return [[
            'name' => (string)($rayhanRPFiles['name'] ?? ''),
            'tmp_name' => (string)($rayhanRPFiles['tmp_name'] ?? ''),
            'error' => (int)($rayhanRPFiles['error'] ?? UPLOAD_ERR_NO_FILE),
        ]];
    }

    $rayhanRPList = [];
    foreach ($rayhanRPFiles['name'] as $rayhanRPIndex => $rayhanRPName) {
        $rayhanRPList[] = [
            'name' => (string)$rayhanRPName,
            'tmp_name' => (string)($rayhanRPFiles['tmp_name'][$rayhanRPIndex] ?? ''),
            'error' => (int)($rayhanRPFiles['error'][$rayhanRPIndex] ?? UPLOAD_ERR_NO_FILE),
        ];
    }

    return $rayhanRPList;
}

function rayhanRPExcelPersistUploadTempFile($rayhanRPUploadTmpPath, &$rayhanRPErrorMessage = '')
{
    $rayhanRPErrorMessage = '';
    $rayhanRPUploadTmpPath = trim((string)$rayhanRPUploadTmpPath);
    if ($rayhanRPUploadTmpPath === '' || !is_file($rayhanRPUploadTmpPath)) {
        $rayhanRPErrorMessage = 'File upload tidak ditemukan di server.';
        return '';
    }

    $rayhanRPTempBase = tempnam(sys_get_temp_dir(), 'rayhanrp_xlsx_');
    if ($rayhanRPTempBase === false) {
        $rayhanRPErrorMessage = 'Gagal menyiapkan file temporary untuk import.';
        return '';
    }

    $rayhanRPTempPath = $rayhanRPTempBase . '.xlsx';
    @unlink($rayhanRPTempBase);

    $rayhanRPOk = false;
    if (is_uploaded_file($rayhanRPUploadTmpPath)) {
        $rayhanRPOk = @move_uploaded_file($rayhanRPUploadTmpPath, $rayhanRPTempPath);
    }
    if (!$rayhanRPOk) {
        $rayhanRPOk = @copy($rayhanRPUploadTmpPath, $rayhanRPTempPath);
    }

    if (!$rayhanRPOk) {
        $rayhanRPErrorMessage = 'Gagal menyalin file upload untuk diproses.';
        @unlink($rayhanRPTempPath);
        return '';
    }

    return $rayhanRPTempPath;
}

function rayhanRPExcelFindGroupId($databaseRayhanRP, $rayhanRPNamaGrup, $rayhanRPOwnerAkunId)
{
    $rayhanRPStmt = mysqli_prepare($databaseRayhanRP, 'SELECT id_grup FROM grup WHERE nama_grup = ? AND dibuat_oleh_akun_id = ? LIMIT 1');
    if (!$rayhanRPStmt) {
        return 0;
    }

    mysqli_stmt_bind_param($rayhanRPStmt, 'si', $rayhanRPNamaGrup, $rayhanRPOwnerAkunId);
    mysqli_stmt_execute($rayhanRPStmt);
    mysqli_stmt_bind_result($rayhanRPStmt, $rayhanRPIdGrup);
    $rayhanRPFound = mysqli_stmt_fetch($rayhanRPStmt);
    mysqli_stmt_close($rayhanRPStmt);

    return $rayhanRPFound ? (int)$rayhanRPIdGrup : 0;
}

function rayhanRPExcelGetOrCreateGroupId($databaseRayhanRP, $rayhanRPNamaGrup, $rayhanRPOwnerAkunId, &$rayhanRPGroupCreated, &$rayhanRPErrorMessage)
{
    $rayhanRPGroupCreated = false;
    $rayhanRPErrorMessage = '';
    $rayhanRPNamaGrup = trim((string)$rayhanRPNamaGrup);
    if ($rayhanRPNamaGrup === '') {
        $rayhanRPErrorMessage = 'Nama grup hasil import tidak valid.';
        return 0;
    }

    $rayhanRPIdGrup = rayhanRPExcelFindGroupId($databaseRayhanRP, $rayhanRPNamaGrup, $rayhanRPOwnerAkunId);
    if ($rayhanRPIdGrup > 0) {
        return $rayhanRPIdGrup;
    }

    $rayhanRPStmt = mysqli_prepare($databaseRayhanRP, 'INSERT INTO grup (nama_grup, dibuat_oleh_akun_id) VALUES (?, ?)');
    if (!$rayhanRPStmt) {
        $rayhanRPErrorMessage = 'Gagal menyiapkan query pembuatan grup.';
        return 0;
    }

    mysqli_stmt_bind_param($rayhanRPStmt, 'si', $rayhanRPNamaGrup, $rayhanRPOwnerAkunId);
    $rayhanRPOk = mysqli_stmt_execute($rayhanRPStmt);
    mysqli_stmt_close($rayhanRPStmt);

    if (!$rayhanRPOk) {
        $rayhanRPIdGrup = rayhanRPExcelFindGroupId($databaseRayhanRP, $rayhanRPNamaGrup, $rayhanRPOwnerAkunId);
        if ($rayhanRPIdGrup > 0) {
            return $rayhanRPIdGrup;
        }
        $rayhanRPErrorMessage = 'Gagal membuat grup kelas untuk file ini.';
        return 0;
    }

    $rayhanRPGroupCreated = true;
    return (int)mysqli_insert_id($databaseRayhanRP);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$databaseRayhanRP) {
        $rayhanRPError = 'Koneksi database gagal.';
    } else {
        $rayhanRPUploads = rayhanRPExcelCollectUploads($_FILES['excel_files'] ?? null);
        if (count($rayhanRPUploads) === 0) {
            $rayhanRPError = 'Minimal satu file Excel wajib diupload.';
        } else {
            $rayhanRPBatchSummary['files_total'] = count($rayhanRPUploads);
            foreach ($rayhanRPUploads as $rayhanRPUpload) {
                $rayhanRPResult = [
                    'file_name' => (string)($rayhanRPUpload['name'] ?? ''),
                    'class_label' => '',
                    'group_name' => '',
                    'group_created' => 0,
                    'accounts_inserted' => 0,
                    'accounts_updated' => 0,
                    'members_added' => 0,
                    'skipped' => 0,
                    'errors' => [],
                ];

                $rayhanRPUploadError = (int)($rayhanRPUpload['error'] ?? UPLOAD_ERR_NO_FILE);
                if ($rayhanRPUploadError !== UPLOAD_ERR_OK) {
                    rayhanRPExcelAddResultError($rayhanRPResult, rayhanRPExcelUploadErrorMessage($rayhanRPUploadError));
                } elseif (strtolower(pathinfo((string)$rayhanRPUpload['name'], PATHINFO_EXTENSION)) !== 'xlsx') {
                    rayhanRPExcelAddResultError($rayhanRPResult, 'Format file harus .xlsx.');
                } else {
                    $rayhanRPTempPath = rayhanRPExcelPersistUploadTempFile((string)($rayhanRPUpload['tmp_name'] ?? ''), $rayhanRPTempError);
                    if ($rayhanRPTempPath === '') {
                        rayhanRPExcelAddResultError($rayhanRPResult, $rayhanRPTempError);
                    } else {
                        $rayhanRPParsed = rayhanRPExcelParseStudentWorkbook($rayhanRPTempPath, (string)$rayhanRPUpload['name']);
                        @unlink($rayhanRPTempPath);

                        $rayhanRPResult['class_label'] = (string)($rayhanRPParsed['class_label'] ?? '');
                        $rayhanRPResult['group_name'] = $rayhanRPResult['class_label'];
                        $rayhanRPResult['skipped'] += (int)($rayhanRPParsed['skipped'] ?? 0);
                        foreach ((array)($rayhanRPParsed['errors'] ?? []) as $rayhanRPParseError) {
                            rayhanRPExcelAddResultError($rayhanRPResult, $rayhanRPParseError);
                        }

                        $rayhanRPRows = is_array($rayhanRPParsed['rows'] ?? null) ? $rayhanRPParsed['rows'] : [];
                        if (count($rayhanRPRows) > 0) {
                            $rayhanRPGroupId = rayhanRPExcelGetOrCreateGroupId($databaseRayhanRP, $rayhanRPResult['group_name'], $rayhanRPAdminId, $rayhanRPGroupCreated, $rayhanRPGroupError);
                            if ($rayhanRPGroupId <= 0) {
                                rayhanRPExcelAddResultError($rayhanRPResult, $rayhanRPGroupError);
                            } else {
                                if ($rayhanRPGroupCreated) {
                                    $rayhanRPResult['group_created'] = 1;
                                }

                                $rayhanRPSelectAccountStmt = mysqli_prepare($databaseRayhanRP, 'SELECT akun_id FROM akun WHERE nis_nip = ? LIMIT 1');
                                $rayhanRPUpdateAccountStmt = mysqli_prepare($databaseRayhanRP, "UPDATE akun SET nama_lengkap = ?, kelas_label = ?, jenis_kelamin = NULLIF(?, '') WHERE akun_id = ? LIMIT 1");
                                $rayhanRPInsertAccountStmt = mysqli_prepare($databaseRayhanRP, "INSERT INTO akun (nis_nip, password, nama_lengkap, kelas_label, jenis_kelamin, role, created_at) VALUES (?, ?, ?, ?, NULLIF(?, ''), 'siswa', NOW())");
                                $rayhanRPSelectMemberStmt = mysqli_prepare($databaseRayhanRP, 'SELECT deleted_at FROM grup_anggota WHERE grup_id = ? AND akun_id = ? LIMIT 1');
                                $rayhanRPInsertMemberStmt = mysqli_prepare($databaseRayhanRP, 'INSERT INTO grup_anggota (grup_id, akun_id, joined_at, deleted_at) VALUES (?, ?, NOW(), NULL)');
                                $rayhanRPReactivateMemberStmt = mysqli_prepare($databaseRayhanRP, 'UPDATE grup_anggota SET deleted_at = NULL, joined_at = NOW() WHERE grup_id = ? AND akun_id = ? LIMIT 1');

                                if (!$rayhanRPSelectAccountStmt || !$rayhanRPUpdateAccountStmt || !$rayhanRPInsertAccountStmt || !$rayhanRPSelectMemberStmt || !$rayhanRPInsertMemberStmt || !$rayhanRPReactivateMemberStmt) {
                                    rayhanRPExcelAddResultError($rayhanRPResult, 'Gagal menyiapkan query import akun.');
                                } else {
                                    foreach ($rayhanRPRows as $rayhanRPRow) {
                                        $rayhanRPNisNip = trim((string)($rayhanRPRow['nis_nip'] ?? ''));
                                        $rayhanRPNamaLengkap = trim((string)($rayhanRPRow['nama_lengkap'] ?? ''));
                                        $rayhanRPKelasLabel = trim((string)($rayhanRPRow['kelas_label'] ?? $rayhanRPResult['class_label']));
                                        $rayhanRPJenisKelamin = trim((string)($rayhanRPRow['jenis_kelamin'] ?? ''));
                                        if ($rayhanRPNisNip === '' || $rayhanRPNamaLengkap === '') {
                                            $rayhanRPResult['skipped']++;
                                            continue;
                                        }

                                        mysqli_stmt_bind_param($rayhanRPSelectAccountStmt, 's', $rayhanRPNisNip);
                                        if (!mysqli_stmt_execute($rayhanRPSelectAccountStmt)) {
                                            rayhanRPExcelAddResultError($rayhanRPResult, 'Gagal mengecek akun untuk NIS ' . $rayhanRPNisNip . '.');
                                            continue;
                                        }
                                        $rayhanRPSelectAccountResult = mysqli_stmt_get_result($rayhanRPSelectAccountStmt);
                                        $rayhanRPExistingAccount = $rayhanRPSelectAccountResult ? mysqli_fetch_assoc($rayhanRPSelectAccountResult) : null;
                                        if ($rayhanRPSelectAccountResult) {
                                            mysqli_free_result($rayhanRPSelectAccountResult);
                                        }

                                        if ($rayhanRPExistingAccount) {
                                            $rayhanRPAkunId = (int)($rayhanRPExistingAccount['akun_id'] ?? 0);
                                            mysqli_stmt_bind_param($rayhanRPUpdateAccountStmt, 'sssi', $rayhanRPNamaLengkap, $rayhanRPKelasLabel, $rayhanRPJenisKelamin, $rayhanRPAkunId);
                                            if (!mysqli_stmt_execute($rayhanRPUpdateAccountStmt)) {
                                                rayhanRPExcelAddResultError($rayhanRPResult, 'Gagal memperbarui akun untuk NIS ' . $rayhanRPNisNip . '.');
                                                continue;
                                            }
                                            $rayhanRPResult['accounts_updated']++;
                                        } else {
                                            $rayhanRPPasswordHash = password_hash($rayhanRPNisNip, PASSWORD_DEFAULT);
                                            if (!$rayhanRPPasswordHash) {
                                                rayhanRPExcelAddResultError($rayhanRPResult, 'Gagal membuat password awal untuk NIS ' . $rayhanRPNisNip . '.');
                                                continue;
                                            }
                                            mysqli_stmt_bind_param($rayhanRPInsertAccountStmt, 'sssss', $rayhanRPNisNip, $rayhanRPPasswordHash, $rayhanRPNamaLengkap, $rayhanRPKelasLabel, $rayhanRPJenisKelamin);
                                            if (!mysqli_stmt_execute($rayhanRPInsertAccountStmt)) {
                                                rayhanRPExcelAddResultError($rayhanRPResult, 'Gagal menambahkan akun untuk NIS ' . $rayhanRPNisNip . '.');
                                                continue;
                                            }
                                            $rayhanRPAkunId = (int)mysqli_insert_id($databaseRayhanRP);
                                            if ($rayhanRPAkunId <= 0) {
                                                rayhanRPExcelAddResultError($rayhanRPResult, 'ID akun baru tidak ditemukan untuk NIS ' . $rayhanRPNisNip . '.');
                                                continue;
                                            }
                                            $rayhanRPResult['accounts_inserted']++;
                                        }

                                        mysqli_stmt_bind_param($rayhanRPSelectMemberStmt, 'ii', $rayhanRPGroupId, $rayhanRPAkunId);
                                        if (!mysqli_stmt_execute($rayhanRPSelectMemberStmt)) {
                                            rayhanRPExcelAddResultError($rayhanRPResult, 'Gagal mengecek anggota grup untuk NIS ' . $rayhanRPNisNip . '.');
                                            continue;
                                        }
                                        $rayhanRPSelectMemberResult = mysqli_stmt_get_result($rayhanRPSelectMemberStmt);
                                        $rayhanRPMembership = $rayhanRPSelectMemberResult ? mysqli_fetch_assoc($rayhanRPSelectMemberResult) : null;
                                        if ($rayhanRPSelectMemberResult) {
                                            mysqli_free_result($rayhanRPSelectMemberResult);
                                        }

                                        if (!$rayhanRPMembership) {
                                            mysqli_stmt_bind_param($rayhanRPInsertMemberStmt, 'ii', $rayhanRPGroupId, $rayhanRPAkunId);
                                            if (!mysqli_stmt_execute($rayhanRPInsertMemberStmt)) {
                                                rayhanRPExcelAddResultError($rayhanRPResult, 'Gagal menambahkan anggota grup untuk NIS ' . $rayhanRPNisNip . '.');
                                                continue;
                                            }
                                            $rayhanRPResult['members_added']++;
                                        } elseif (($rayhanRPMembership['deleted_at'] ?? null) !== null) {
                                            mysqli_stmt_bind_param($rayhanRPReactivateMemberStmt, 'ii', $rayhanRPGroupId, $rayhanRPAkunId);
                                            if (!mysqli_stmt_execute($rayhanRPReactivateMemberStmt)) {
                                                rayhanRPExcelAddResultError($rayhanRPResult, 'Gagal mengaktifkan ulang anggota grup untuk NIS ' . $rayhanRPNisNip . '.');
                                                continue;
                                            }
                                            $rayhanRPResult['members_added']++;
                                        }
                                    }
                                }

                                if ($rayhanRPSelectAccountStmt) mysqli_stmt_close($rayhanRPSelectAccountStmt);
                                if ($rayhanRPUpdateAccountStmt) mysqli_stmt_close($rayhanRPUpdateAccountStmt);
                                if ($rayhanRPInsertAccountStmt) mysqli_stmt_close($rayhanRPInsertAccountStmt);
                                if ($rayhanRPSelectMemberStmt) mysqli_stmt_close($rayhanRPSelectMemberStmt);
                                if ($rayhanRPInsertMemberStmt) mysqli_stmt_close($rayhanRPInsertMemberStmt);
                                if ($rayhanRPReactivateMemberStmt) mysqli_stmt_close($rayhanRPReactivateMemberStmt);
                            }
                        }
                    }
                }

                $rayhanRPHasWork = $rayhanRPResult['group_created'] > 0 || $rayhanRPResult['accounts_inserted'] > 0 || $rayhanRPResult['accounts_updated'] > 0 || $rayhanRPResult['members_added'] > 0;
                if ($rayhanRPHasWork) {
                    $rayhanRPBatchSummary['files_processed']++;
                } else {
                    $rayhanRPBatchSummary['files_failed']++;
                }

                $rayhanRPBatchSummary['groups_created'] += (int)$rayhanRPResult['group_created'];
                $rayhanRPBatchSummary['accounts_inserted'] += (int)$rayhanRPResult['accounts_inserted'];
                $rayhanRPBatchSummary['accounts_updated'] += (int)$rayhanRPResult['accounts_updated'];
                $rayhanRPBatchSummary['members_added'] += (int)$rayhanRPResult['members_added'];
                $rayhanRPBatchSummary['skipped'] += (int)$rayhanRPResult['skipped'];
                $rayhanRPBatchSummary['errors'] += count($rayhanRPResult['errors']);
                $rayhanRPFileResults[] = $rayhanRPResult;
            }

            if ($rayhanRPBatchSummary['files_processed'] > 0) {
                $rayhanRPSuccess = 'Import Excel selesai diproses.';
            } elseif ($rayhanRPError === '') {
                $rayhanRPError = 'Tidak ada file Excel yang berhasil diproses.';
            }
        }
    }
}

$rayhanRPPageTitle = 'Import Excel Siswa';
$rayhanRPPageSubtitle = htmlspecialchars($rayhanRPAdminLabel, ENT_QUOTES, 'UTF-8') . ' | Upload multi-file .xlsx';
rayhanRPRenderAdminLayoutStart([
    'title' => $rayhanRPPageTitle,
    'subtitle' => $rayhanRPPageSubtitle,
    'page_key' => 'import_excel',
    'admin' => $rayhanRPAdmin,
]);
?>
<div class="page-stack">
    <section class="panel">
        <form method="post" enctype="multipart/form-data" class="form-grid">
            <div class="field full">
                <label for="excel_files">File Excel Siswa</label>
                <input type="file" id="excel_files" name="excel_files[]" accept=".xlsx" multiple required>
            </div>
            <div class="field full field-actions">
                <button class="btn-primary" type="submit">Import Excel Sekarang</button>
            </div>
        </form>

        <?php if ($rayhanRPError !== ''): ?>
            <div class="msg error"><?php echo htmlspecialchars($rayhanRPError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($rayhanRPSuccess !== ''): ?>
            <div class="msg ok"><?php echo htmlspecialchars($rayhanRPSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <div class="msg info">Akun baru dibuat sebagai <code>siswa</code> dengan password awal = NIS. Akun lama hanya diupdate profilnya, lalu dimasukkan ke grup kelas dari nama file.</div>
    </section>

    <section class="panel">
        <h3>Aturan Import</h3>
        <ul class="hint-list">
            <li>Format file harus <code>.xlsx</code> dan minimal memiliki kolom <code>NAMA</code> dan <code>NIS</code>.</li>
            <li>Kolom <code>L/P</code>, <code>JK</code>, atau <code>Jenis Kelamin</code> akan dibaca bila tersedia.</li>
            <li>Nama file akan dipakai sebagai <code>kelas_label</code> dan nama grup, misalnya <code>XI_PPLG_B.xlsx</code> menjadi <code>XI PPLG B</code>.</li>
            <li>Import ulang bersifat additive: anggota grup yang nonaktif akan aktif lagi, dan siswa yang tidak ada di file tidak dikeluarkan dari grup.</li>
        </ul>
    </section>

    <?php if ($rayhanRPBatchSummary['files_total'] > 0): ?>
        <section class="panel">
            <h3>Ringkasan Batch</h3>
            <div class="stats">
                <div class="stat"><div class="label">File Diproses</div><div class="value blue"><?php echo (int)$rayhanRPBatchSummary['files_processed']; ?> / <?php echo (int)$rayhanRPBatchSummary['files_total']; ?></div></div>
                <div class="stat"><div class="label">Akun Baru</div><div class="value green"><?php echo (int)$rayhanRPBatchSummary['accounts_inserted']; ?></div></div>
                <div class="stat"><div class="label">Akun Diperbarui</div><div class="value blue"><?php echo (int)$rayhanRPBatchSummary['accounts_updated']; ?></div></div>
                <div class="stat"><div class="label">Anggota Grup Aktif</div><div class="value amber"><?php echo (int)$rayhanRPBatchSummary['members_added']; ?></div></div>
                <div class="stat"><div class="label">Grup Baru</div><div class="value blue"><?php echo (int)$rayhanRPBatchSummary['groups_created']; ?></div></div>
                <div class="stat"><div class="label">Baris Dilewati</div><div class="value amber"><?php echo (int)$rayhanRPBatchSummary['skipped']; ?></div></div>
                <div class="stat"><div class="label">File Gagal</div><div class="value red"><?php echo (int)$rayhanRPBatchSummary['files_failed']; ?></div></div>
                <div class="stat"><div class="label">Jumlah Error</div><div class="value red"><?php echo (int)$rayhanRPBatchSummary['errors']; ?></div></div>
            </div>
        </section>
    <?php endif; ?>

    <?php if (count($rayhanRPFileResults) > 0): ?>
        <section class="panel">
            <h3>Hasil Per File</h3>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>File</th>
                        <th>Kelas / Grup</th>
                        <th>Status</th>
                        <th>Akun Baru</th>
                        <th>Akun Update</th>
                        <th>Anggota Grup</th>
                        <th>Skip</th>
                        <th>Error</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rayhanRPFileResults as $rayhanRPRow): ?>
                        <?php $rayhanRPHasWork = ((int)$rayhanRPRow['group_created'] > 0 || (int)$rayhanRPRow['accounts_inserted'] > 0 || (int)$rayhanRPRow['accounts_updated'] > 0 || (int)$rayhanRPRow['members_added'] > 0); ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars((string)$rayhanRPRow['file_name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td>
                                <div><?php echo htmlspecialchars((string)($rayhanRPRow['class_label'] !== '' ? $rayhanRPRow['class_label'] : '-'), ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="muted">
                                    Grup: <?php echo htmlspecialchars((string)($rayhanRPRow['group_name'] !== '' ? $rayhanRPRow['group_name'] : '-'), ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if ((int)$rayhanRPRow['group_created'] > 0): ?><span class="tag ok">Grup baru</span><?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($rayhanRPHasWork && count($rayhanRPRow['errors']) === 0): ?>
                                    <span class="tag ok">Berhasil</span>
                                <?php elseif ($rayhanRPHasWork): ?>
                                    <span class="tag warn">Parsial</span>
                                <?php else: ?>
                                    <span class="tag err">Gagal</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo (int)$rayhanRPRow['accounts_inserted']; ?></td>
                            <td><?php echo (int)$rayhanRPRow['accounts_updated']; ?></td>
                            <td><?php echo (int)$rayhanRPRow['members_added']; ?></td>
                            <td><?php echo (int)$rayhanRPRow['skipped']; ?></td>
                            <td>
                                <?php if (count($rayhanRPRow['errors']) === 0): ?>
                                    <span class="muted">-</span>
                                <?php else: ?>
                                    <ul class="error-list">
                                        <?php foreach ($rayhanRPRow['errors'] as $rayhanRPItemError): ?>
                                            <li><?php echo htmlspecialchars((string)$rayhanRPItemError, ENT_QUOTES, 'UTF-8'); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <section class="panel">
        <h3>Contoh Workbook</h3>
        <div class="msg info">Cocok untuk file seperti <code>X_PPLG_A.xlsx</code>, <code>X_PPLG_B.xlsx</code>, <code>XI_PPLG_A.xlsx</code>, dan <code>XI_PPLG_B.xlsx</code>.</div>
        <pre>NO | NAMA | NIS | L/P
1  | ADI FADLY SHAADIQIN | 102306363 | L
2  | AGISTI NUR ANDINI   | 102306400 | P</pre>
    </section>
</div>
<?php rayhanRPRenderAdminLayoutEnd(); ?>
