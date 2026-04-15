<?php
require_once __DIR__ . '/koneksi_rayhanRP.php';
require_once __DIR__ . '/function/function_bot_rayhanRP.php';
rayhanRPStartSession();

$rayhanRPAdmin = rayhanRPRequireAdminSession('Web Admin/loginAdmin_rayhanRP.php');
$rayhanRPAdminId = (int)$rayhanRPAdmin['akun_id'];
$rayhanRPAdminRole = (string)$rayhanRPAdmin['role'];
$rayhanRPIdPengumpulan = (int)($_GET['id'] ?? 0);

if ($rayhanRPIdPengumpulan <= 0) {
    http_response_code(400);
    echo 'ID pengumpulan tidak valid.';
    exit;
}

if (!$databaseRayhanRP) {
    http_response_code(500);
    echo 'Koneksi database gagal.';
    exit;
}

if ($rayhanRPAdminRole !== 'admin' && $rayhanRPAdminRole !== 'guru') {
    http_response_code(403);
    echo 'Akses ditolak.';
    exit;
}

$rayhanRPStmt = mysqli_prepare(
    $databaseRayhanRP,
    "SELECT
        tp.file_lokal,
        tp.nama_file_asli,
        tp.file_mime,
        tp.telegram_file_path,
        t.dibuat_oleh_akun_id
     FROM tugas_pengumpulan tp
     INNER JOIN tugas t ON t.id_tugas = tp.tugas_id
     WHERE tp.id_pengumpulan = ?
     LIMIT 1"
);

if (!$rayhanRPStmt) {
    http_response_code(500);
    echo 'Gagal menyiapkan query file.';
    exit;
}

mysqli_stmt_bind_param($rayhanRPStmt, 'i', $rayhanRPIdPengumpulan);
mysqli_stmt_execute($rayhanRPStmt);
mysqli_stmt_bind_result(
    $rayhanRPStmt,
    $rayhanRPFileLokal,
    $rayhanRPNamaFileAsli,
    $rayhanRPFileMime,
    $rayhanRPTelegramFilePath,
    $rayhanRPPembuatAkunId
);
$rayhanRPFound = mysqli_stmt_fetch($rayhanRPStmt);
mysqli_stmt_close($rayhanRPStmt);

if (!$rayhanRPFound) {
    http_response_code(404);
    echo 'Data pengumpulan tidak ditemukan.';
    exit;
}

if ($rayhanRPAdminRole === 'guru' && (int)$rayhanRPPembuatAkunId !== $rayhanRPAdminId) {
    http_response_code(403);
    echo 'Anda tidak memiliki akses ke file ini.';
    exit;
}

$rayhanRPFileName = trim((string)$rayhanRPNamaFileAsli);
if ($rayhanRPFileName === '') {
    $rayhanRPFileName = 'tugas_' . $rayhanRPIdPengumpulan . '.bin';
}
$rayhanRPFileName = preg_replace('/[^A-Za-z0-9._-]/', '_', $rayhanRPFileName);
$rayhanRPMime = trim((string)$rayhanRPFileMime);
if ($rayhanRPMime === '') {
    $rayhanRPMime = 'application/octet-stream';
}

$rayhanRPAbsolutePath = '';
$rayhanRPRelativePath = trim((string)$rayhanRPFileLokal);
if ($rayhanRPRelativePath !== '') {
    $rayhanRPRelativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rayhanRPRelativePath);
    $rayhanRPPathCandidate = __DIR__ . DIRECTORY_SEPARATOR . $rayhanRPRelativePath;
    $rayhanRPResolved = realpath($rayhanRPPathCandidate);
    $rayhanRPRoot = realpath(__DIR__);
    if ($rayhanRPResolved !== false && $rayhanRPRoot !== false && strpos($rayhanRPResolved, $rayhanRPRoot) === 0 && is_file($rayhanRPResolved)) {
        $rayhanRPAbsolutePath = $rayhanRPResolved;
    }
}

if ($rayhanRPAbsolutePath !== '') {
    header('Content-Type: ' . $rayhanRPMime);
    header('Content-Disposition: attachment; filename="' . $rayhanRPFileName . '"');
    header('Content-Length: ' . (string)filesize($rayhanRPAbsolutePath));
    readfile($rayhanRPAbsolutePath);
    exit;
}

$rayhanRPTelegramFilePath = trim((string)$rayhanRPTelegramFilePath);
if ($rayhanRPTelegramFilePath !== '' && isset($rayhanRPToken)) {
    $rayhanRPUrl = 'https://api.telegram.org/file/bot' . $rayhanRPToken . '/' . ltrim($rayhanRPTelegramFilePath, '/');
    $rayhanRPContext = stream_context_create(['http' => ['timeout' => 20]]);
    $rayhanRPBinary = @file_get_contents($rayhanRPUrl, false, $rayhanRPContext);
    if ($rayhanRPBinary !== false) {
        header('Content-Type: ' . $rayhanRPMime);
        header('Content-Disposition: attachment; filename="' . $rayhanRPFileName . '"');
        header('Content-Length: ' . (string)strlen($rayhanRPBinary));
        echo $rayhanRPBinary;
        exit;
    }
}

http_response_code(404);
echo 'File tidak ditemukan.';
