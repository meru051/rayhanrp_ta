<?php
require_once 'function/function_bot_rayhanRP.php';
require_once 'koneksi_rayhanRP.php';

$rayhanRPStateFile = __DIR__ . '/data/state.json';

function rayhanRP_loadStates()
{
    global $rayhanRPStateFile;
    if (!file_exists($rayhanRPStateFile)) {
        return [];
    }

    $rayhanRPData = json_decode((string)file_get_contents($rayhanRPStateFile), true);
    return is_array($rayhanRPData) ? $rayhanRPData : [];
}

function rayhanRP_saveStates($rayhanRPData)
{
    global $rayhanRPStateFile;
    $rayhanRPDir = dirname($rayhanRPStateFile);
    if (!is_dir($rayhanRPDir)) {
        @mkdir($rayhanRPDir, 0755, true);
    }

    file_put_contents($rayhanRPStateFile, json_encode($rayhanRPData), LOCK_EX);
}

function rayhanRP_getState($rayhanRPChatId)
{
    $rayhanRPStates = rayhanRP_loadStates();
    return $rayhanRPStates[(string)$rayhanRPChatId] ?? null;
}

function rayhanRP_setState($rayhanRPChatId, $rayhanRPState)
{
    $rayhanRPStates = rayhanRP_loadStates();
    $rayhanRPKey = (string)$rayhanRPChatId;

    if ($rayhanRPState === null) {
        unset($rayhanRPStates[$rayhanRPKey]);
    } else {
        $rayhanRPStates[$rayhanRPKey] = $rayhanRPState;
    }

    rayhanRP_saveStates($rayhanRPStates);
}

function rayhanRP_isCommand($rayhanRPText, $rayhanRPCommand)
{
    $rayhanRPPattern = '/^' . preg_quote($rayhanRPCommand, '/') . '(@[A-Za-z0-9_]+)?(\s|$)/i';
    return preg_match($rayhanRPPattern, trim($rayhanRPText)) === 1;
}

function rayhanRP_ensureTugasPengumpulanTable($databaseRayhanRP)
{
    $rayhanRPSql = "CREATE TABLE IF NOT EXISTS tugas_pengumpulan (
        id_pengumpulan INT AUTO_INCREMENT PRIMARY KEY,
        tugas_id INT NOT NULL,
        akun_id INT NOT NULL,
        telegram_chat_id BIGINT DEFAULT NULL,
        file_type VARCHAR(20) NOT NULL DEFAULT 'document',
        telegram_file_id VARCHAR(255) NOT NULL,
        telegram_file_unique_id VARCHAR(255) DEFAULT NULL,
        telegram_file_path VARCHAR(255) DEFAULT NULL,
        nama_file_asli VARCHAR(255) DEFAULT NULL,
        file_mime VARCHAR(120) DEFAULT NULL,
        file_size BIGINT DEFAULT NULL,
        file_lokal VARCHAR(255) DEFAULT NULL,
        caption TEXT DEFAULT NULL,
        status ENUM('dikumpulkan','dinilai','revisi','terlambat') NOT NULL DEFAULT 'dikumpulkan',
        nilai DECIMAL(5,2) DEFAULT NULL,
        catatan_guru TEXT DEFAULT NULL,
        submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        graded_at DATETIME DEFAULT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_tugas_akun (tugas_id, akun_id),
        KEY idx_tp_akun (akun_id),
        CONSTRAINT fk_tp_tugas FOREIGN KEY (tugas_id) REFERENCES tugas (id_tugas) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT fk_tp_akun FOREIGN KEY (akun_id) REFERENCES akun (akun_id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    return mysqli_query($databaseRayhanRP, $rayhanRPSql) !== false;
}

function rayhanRP_parseKumpulCommandId($rayhanRPText)
{
    if (preg_match('/^\/kumpul(?:@[A-Za-z0-9_]+)?(?:\s+(\d+))?\s*$/i', trim($rayhanRPText), $rayhanRPMatches) !== 1) {
        return null;
    }
    if (!isset($rayhanRPMatches[1]) || $rayhanRPMatches[1] === '') {
        return 0;
    }
    return (int)$rayhanRPMatches[1];
}

function rayhanRP_fetchTaskForUser($databaseRayhanRP, $rayhanRPTaskId, $rayhanRPAkunId)
{
    $rayhanRPSql = "
        SELECT t.id_tugas, t.judul, t.tenggat, COALESCE(g.nama_grup, '-') AS nama_grup
        FROM tugas t
        INNER JOIN grup g ON g.id_grup = t.grup_id
        LEFT JOIN grup_anggota ga ON ga.grup_id = g.id_grup AND ga.akun_id = ? AND ga.deleted_at IS NULL
        WHERE t.id_tugas = ?
          AND (ga.akun_id IS NOT NULL OR g.dibuat_oleh_akun_id = ? OR t.dibuat_oleh_akun_id = ?)
        LIMIT 1
    ";

    $rayhanRPStmt = mysqli_prepare($databaseRayhanRP, $rayhanRPSql);
    if (!$rayhanRPStmt) {
        return null;
    }

    mysqli_stmt_bind_param($rayhanRPStmt, "iiii", $rayhanRPAkunId, $rayhanRPTaskId, $rayhanRPAkunId, $rayhanRPAkunId);
    mysqli_stmt_execute($rayhanRPStmt);
    mysqli_stmt_bind_result($rayhanRPStmt, $rayhanRPIdTugas, $rayhanRPJudul, $rayhanRPTenggat, $rayhanRPGrup);
    $rayhanRPData = null;
    if (mysqli_stmt_fetch($rayhanRPStmt)) {
        $rayhanRPData = [
            'id_tugas' => (int)$rayhanRPIdTugas,
            'judul' => (string)$rayhanRPJudul,
            'tenggat' => (string)$rayhanRPTenggat,
            'nama_grup' => (string)$rayhanRPGrup,
        ];
    }
    mysqli_stmt_close($rayhanRPStmt);
    return $rayhanRPData;
}

function rayhanRP_fetchTelegramFilePath($rayhanRPFileId)
{
    global $rayhanRPapiLink;

    $rayhanRPUrl = $rayhanRPapiLink . 'getFile?' . http_build_query(['file_id' => $rayhanRPFileId]);
    $rayhanRPContext = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 12
        ]
    ]);

    $rayhanRPResponse = @file_get_contents($rayhanRPUrl, false, $rayhanRPContext);
    if ($rayhanRPResponse === false) {
        return '';
    }

    $rayhanRPJson = json_decode((string)$rayhanRPResponse, true);
    if (!is_array($rayhanRPJson) || !($rayhanRPJson['ok'] ?? false)) {
        return '';
    }

    return (string)($rayhanRPJson['result']['file_path'] ?? '');
}

function rayhanRP_downloadTelegramFile($rayhanRPFilePath, $rayhanRPTargetPath)
{
    global $rayhanRPToken;

    if (trim($rayhanRPFilePath) === '' || trim($rayhanRPTargetPath) === '') {
        return false;
    }

    $rayhanRPUrl = 'https://api.telegram.org/file/bot' . $rayhanRPToken . '/' . ltrim($rayhanRPFilePath, '/');
    $rayhanRPContext = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 20
        ]
    ]);
    $rayhanRPBinary = @file_get_contents($rayhanRPUrl, false, $rayhanRPContext);
    if ($rayhanRPBinary === false) {
        return false;
    }

    return @file_put_contents($rayhanRPTargetPath, $rayhanRPBinary) !== false;
}

function rayhanRP_guessExtension($rayhanRPFileName, $rayhanRPMime, $rayhanRPType)
{
    $rayhanRPExt = strtolower((string)pathinfo((string)$rayhanRPFileName, PATHINFO_EXTENSION));
    if ($rayhanRPExt !== '') {
        return preg_replace('/[^a-z0-9]/', '', $rayhanRPExt);
    }

    $rayhanRPMimeMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
        'application/zip' => 'zip',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/msword' => 'doc',
    ];
    if (isset($rayhanRPMimeMap[$rayhanRPMime])) {
        return $rayhanRPMimeMap[$rayhanRPMime];
    }

    return $rayhanRPType === 'photo' ? 'jpg' : 'bin';
}

$rayhanRPUpdate = json_decode((string)file_get_contents('php://input'), true);
$rayhanRPMessage = $rayhanRPUpdate['message'] ?? null;
if (!is_array($rayhanRPMessage)) {
    exit;
}

$rayhanRPChatId = $rayhanRPMessage['chat']['id'] ?? null;
if ($rayhanRPChatId === null) {
    exit;
}

$rayhanRPTextRaw = (string)($rayhanRPMessage['text'] ?? '');
$rayhanRPText = trim($rayhanRPTextRaw);
$rayhanRPChatName = $rayhanRPMessage['chat']['first_name'] ?? 'Pengguna';
$rayhanRPDocument = isset($rayhanRPMessage['document']) && is_array($rayhanRPMessage['document']) ? $rayhanRPMessage['document'] : null;
$rayhanRPPhotoList = isset($rayhanRPMessage['photo']) && is_array($rayhanRPMessage['photo']) ? $rayhanRPMessage['photo'] : [];

$rayhanRPState = rayhanRP_getState($rayhanRPChatId);
$rayhanRPAuthUser = null;
if (is_array($rayhanRPState) && isset($rayhanRPState['auth']) && is_array($rayhanRPState['auth'])) {
    $rayhanRPAuthUser = $rayhanRPState['auth'];
}

if (rayhanRP_isCommand($rayhanRPText, '/stop')) {
    rayhanRP_setState($rayhanRPChatId, null);
    sendMessage($rayhanRPChatId, "Proses dihentikan.\nKetik /start untuk memulai lagi.");
    exit;
}

if (rayhanRP_isCommand($rayhanRPText, '/start')) {
    rayhanRP_setState($rayhanRPChatId, ['step' => 'awaiting_id']);
    sendMessage($rayhanRPChatId, "Halo, $rayhanRPChatName! Selamat datang di Bot SiRey.");
    sendMessage($rayhanRPChatId, "Masukkan NIS/NIP untuk login.\nKetik /stop kapan saja untuk membatalkan.");
    exit;
}

$rayhanRPStep = is_array($rayhanRPState) ? ($rayhanRPState['step'] ?? null) : $rayhanRPState;
if ($rayhanRPStep === 'awaiting_nis') {
    $rayhanRPStep = 'awaiting_id';
}

if ($rayhanRPStep === 'awaiting_id') {
    $rayhanRPLoginId = $rayhanRPText;

    if ($rayhanRPLoginId === '') {
        sendMessage($rayhanRPChatId, "NIS/NIP tidak boleh kosong.");
        exit;
    }

    if (!preg_match('/^[A-Za-z0-9._-]{3,25}$/', $rayhanRPLoginId)) {
        sendMessage($rayhanRPChatId, "Format NIS/NIP tidak valid.");
        exit;
    }

    if (!$databaseRayhanRP) {
        sendMessage($rayhanRPChatId, "Koneksi database gagal. Coba lagi nanti.");
        exit;
    }

    $rayhanRPStmt = mysqli_prepare($databaseRayhanRP, "SELECT akun_id, nis_nip, role FROM akun WHERE nis_nip = ? LIMIT 1");
    if (!$rayhanRPStmt) {
        sendMessage($rayhanRPChatId, "Terjadi kesalahan sistem. Coba lagi.");
        exit;
    }

    mysqli_stmt_bind_param($rayhanRPStmt, "s", $rayhanRPLoginId);
    $rayhanRPExecuted = mysqli_stmt_execute($rayhanRPStmt);
    mysqli_stmt_bind_result($rayhanRPStmt, $rayhanRPDbAkunId, $rayhanRPDbNisNip, $rayhanRPDbRole);
    $rayhanRPFound = $rayhanRPExecuted && mysqli_stmt_fetch($rayhanRPStmt);
    mysqli_stmt_close($rayhanRPStmt);

    if (!$rayhanRPFound) {
        sendMessage($rayhanRPChatId, "NIS/NIP tidak ditemukan.");
        exit;
    }

    rayhanRP_setState($rayhanRPChatId, [
        'step' => 'awaiting_password',
        'nis_nip' => $rayhanRPDbNisNip
    ]);
    sendMessage($rayhanRPChatId, "NIS/NIP ditemukan.\nMasukkan password.");
    exit;
}

if ($rayhanRPStep === 'awaiting_password') {
    if (trim($rayhanRPTextRaw) === '') {
        sendMessage($rayhanRPChatId, "Password tidak boleh kosong.");
        exit;
    }

    $rayhanRPLoginId = is_array($rayhanRPState) ? (string)($rayhanRPState['nis_nip'] ?? ($rayhanRPState['nis'] ?? '')) : '';
    if ($rayhanRPLoginId === '') {
        rayhanRP_setState($rayhanRPChatId, ['step' => 'awaiting_id']);
        sendMessage($rayhanRPChatId, "Sesi login tidak valid. Masukkan NIS/NIP lagi.");
        exit;
    }

    if (!$databaseRayhanRP) {
        sendMessage($rayhanRPChatId, "Koneksi database gagal. Coba lagi nanti.");
        exit;
    }

    $rayhanRPStmt = mysqli_prepare($databaseRayhanRP, "SELECT akun_id, nis_nip, role, password FROM akun WHERE nis_nip = ? LIMIT 1");
    if (!$rayhanRPStmt) {
        sendMessage($rayhanRPChatId, "Terjadi kesalahan sistem. Coba lagi.");
        exit;
    }

    mysqli_stmt_bind_param($rayhanRPStmt, "s", $rayhanRPLoginId);
    $rayhanRPExecuted = mysqli_stmt_execute($rayhanRPStmt);
    mysqli_stmt_bind_result($rayhanRPStmt, $rayhanRPDbAkunId, $rayhanRPDbNisNip, $rayhanRPDbRole, $rayhanRPDbPassword);
    $rayhanRPFound = $rayhanRPExecuted && mysqli_stmt_fetch($rayhanRPStmt);
    mysqli_stmt_close($rayhanRPStmt);

    if (!$rayhanRPFound) {
        rayhanRP_setState($rayhanRPChatId, ['step' => 'awaiting_id']);
        sendMessage($rayhanRPChatId, "Data akun tidak ditemukan. Mulai lagi dengan /start.");
        exit;
    }

    $rayhanRPDbPassword = (string)$rayhanRPDbPassword;
    $rayhanRPValid = hash_equals($rayhanRPDbPassword, $rayhanRPTextRaw);
    if (!$rayhanRPValid && function_exists('password_verify')) {
        $rayhanRPValid = password_verify($rayhanRPTextRaw, $rayhanRPDbPassword);
    }

    if (!$rayhanRPValid) {
        sendMessage($rayhanRPChatId, "Password salah. Coba lagi atau ketik /stop.");
        exit;
    }

    $rayhanRPDbRole = $rayhanRPDbRole ?: '-';
    rayhanRP_setState($rayhanRPChatId, [
        'step' => 'authenticated',
        'auth' => [
            'akun_id' => (int)$rayhanRPDbAkunId,
            'nis_nip' => $rayhanRPDbNisNip,
            'role' => $rayhanRPDbRole
        ]
    ]);

    sendMessage($rayhanRPChatId, "Login berhasil.");
    sendMessage($rayhanRPChatId, "Pilihan Menu:\n1. /tugas - Lihat daftar tugas\n2. /kumpul <id_tugas> - Kumpulkan tugas\n3. /jadwal - Lihat jadwal pelajaran");
    exit;
}

if ($rayhanRPStep === 'awaiting_tugas_file') {
    if (!$rayhanRPAuthUser && is_array($rayhanRPState) && isset($rayhanRPState['auth']) && is_array($rayhanRPState['auth'])) {
        $rayhanRPAuthUser = $rayhanRPState['auth'];
    }

    if (!$rayhanRPAuthUser) {
        rayhanRP_setState($rayhanRPChatId, ['step' => 'awaiting_id']);
        sendMessage($rayhanRPChatId, "Sesi login tidak ditemukan. Mulai lagi dengan /start.");
        exit;
    }

    $rayhanRPAkunId = isset($rayhanRPAuthUser['akun_id']) ? (int)$rayhanRPAuthUser['akun_id'] : 0;
    $rayhanRPTaskId = is_array($rayhanRPState) ? (int)($rayhanRPState['tugas_id'] ?? 0) : 0;
    if ($rayhanRPAkunId <= 0 || $rayhanRPTaskId <= 0) {
        rayhanRP_setState($rayhanRPChatId, [
            'step' => 'authenticated',
            'auth' => $rayhanRPAuthUser
        ]);
        sendMessage($rayhanRPChatId, "Data tugas tidak valid. Gunakan /tugas lalu /kumpul <id_tugas>.");
        exit;
    }

    if (!$databaseRayhanRP) {
        sendMessage($rayhanRPChatId, "Koneksi database gagal. Coba lagi nanti.");
        exit;
    }

    if (!rayhanRP_ensureTugasPengumpulanTable($databaseRayhanRP)) {
        sendMessage($rayhanRPChatId, "Tabel pengumpulan tugas belum siap.");
        exit;
    }

    $rayhanRPTaskData = rayhanRP_fetchTaskForUser($databaseRayhanRP, $rayhanRPTaskId, $rayhanRPAkunId);
    if (!$rayhanRPTaskData) {
        rayhanRP_setState($rayhanRPChatId, [
            'step' => 'authenticated',
            'auth' => $rayhanRPAuthUser
        ]);
        sendMessage($rayhanRPChatId, "Tugas tidak ditemukan atau Anda tidak punya akses.");
        exit;
    }

    if (!$rayhanRPDocument && count($rayhanRPPhotoList) === 0) {
        sendMessage($rayhanRPChatId, "Kirim file atau foto tugas sekarang.\nKetik /stop untuk membatalkan.");
        exit;
    }

    $rayhanRPFileType = '';
    $rayhanRPFileId = '';
    $rayhanRPFileUniqueId = '';
    $rayhanRPNamaFileAsli = '';
    $rayhanRPMime = '';
    $rayhanRPFileSize = 0;

    if ($rayhanRPDocument) {
        $rayhanRPFileType = 'document';
        $rayhanRPFileId = (string)($rayhanRPDocument['file_id'] ?? '');
        $rayhanRPFileUniqueId = (string)($rayhanRPDocument['file_unique_id'] ?? '');
        $rayhanRPNamaFileAsli = (string)($rayhanRPDocument['file_name'] ?? '');
        $rayhanRPMime = (string)($rayhanRPDocument['mime_type'] ?? '');
        $rayhanRPFileSize = (int)($rayhanRPDocument['file_size'] ?? 0);
    } else {
        $rayhanRPFileType = 'photo';
        $rayhanRPLastPhoto = end($rayhanRPPhotoList);
        if (is_array($rayhanRPLastPhoto)) {
            $rayhanRPFileId = (string)($rayhanRPLastPhoto['file_id'] ?? '');
            $rayhanRPFileUniqueId = (string)($rayhanRPLastPhoto['file_unique_id'] ?? '');
            $rayhanRPFileSize = (int)($rayhanRPLastPhoto['file_size'] ?? 0);
        }
        $rayhanRPNamaFileAsli = 'foto_tugas.jpg';
        $rayhanRPMime = 'image/jpeg';
    }

    if ($rayhanRPFileId === '') {
        sendMessage($rayhanRPChatId, "Gagal membaca file Telegram. Coba kirim ulang.");
        exit;
    }

    $rayhanRPTelegramPath = rayhanRP_fetchTelegramFilePath($rayhanRPFileId);
    if ($rayhanRPTelegramPath === '') {
        sendMessage($rayhanRPChatId, "Gagal mengambil file dari Telegram. Coba kirim ulang.");
        exit;
    }

    $rayhanRPUploadDir = __DIR__ . '/data/tugas_uploads';
    if (!is_dir($rayhanRPUploadDir)) {
        @mkdir($rayhanRPUploadDir, 0755, true);
    }

    $rayhanRPExt = rayhanRP_guessExtension($rayhanRPNamaFileAsli, $rayhanRPMime, $rayhanRPFileType);
    if ($rayhanRPExt === '') {
        $rayhanRPExt = 'bin';
    }
    $rayhanRPLocalName = 'tugas_' . $rayhanRPTaskId . '_akun_' . $rayhanRPAkunId . '_' . date('Ymd_His') . '_' . substr(sha1($rayhanRPFileUniqueId . microtime(true)), 0, 8) . '.' . $rayhanRPExt;
    $rayhanRPRelativePath = 'data/tugas_uploads/' . $rayhanRPLocalName;
    $rayhanRPAbsolutePath = __DIR__ . '/data/tugas_uploads/' . $rayhanRPLocalName;
    $rayhanRPDownloadOk = rayhanRP_downloadTelegramFile($rayhanRPTelegramPath, $rayhanRPAbsolutePath);
    if (!$rayhanRPDownloadOk) {
        $rayhanRPRelativePath = '';
    }

    $rayhanRPTenggat = (string)($rayhanRPTaskData['tenggat'] ?? '');
    $rayhanRPStatus = 'dikumpulkan';
    if ($rayhanRPTenggat !== '' && $rayhanRPTenggat !== '0000-00-00 00:00:00') {
        $rayhanRPTenggatTs = strtotime($rayhanRPTenggat);
        if ($rayhanRPTenggatTs !== false && time() > $rayhanRPTenggatTs) {
            $rayhanRPStatus = 'terlambat';
        }
    }

    $rayhanRPCaption = trim((string)($rayhanRPMessage['caption'] ?? ''));
    $rayhanRPChatIdString = (string)$rayhanRPChatId;
    $rayhanRPSubmitSql = "
        INSERT INTO tugas_pengumpulan
        (tugas_id, akun_id, telegram_chat_id, file_type, telegram_file_id, telegram_file_unique_id, telegram_file_path, nama_file_asli, file_mime, file_size, file_lokal, caption, status, submitted_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            telegram_chat_id = VALUES(telegram_chat_id),
            file_type = VALUES(file_type),
            telegram_file_id = VALUES(telegram_file_id),
            telegram_file_unique_id = VALUES(telegram_file_unique_id),
            telegram_file_path = VALUES(telegram_file_path),
            nama_file_asli = VALUES(nama_file_asli),
            file_mime = VALUES(file_mime),
            file_size = VALUES(file_size),
            file_lokal = VALUES(file_lokal),
            caption = VALUES(caption),
            status = VALUES(status),
            nilai = NULL,
            catatan_guru = NULL,
            submitted_at = NOW(),
            graded_at = NULL
    ";
    $rayhanRPSubmitStmt = mysqli_prepare($databaseRayhanRP, $rayhanRPSubmitSql);
    if (!$rayhanRPSubmitStmt) {
        sendMessage($rayhanRPChatId, "Gagal menyimpan pengumpulan tugas.");
        exit;
    }

    mysqli_stmt_bind_param(
        $rayhanRPSubmitStmt,
        "iisssssssisss",
        $rayhanRPTaskId,
        $rayhanRPAkunId,
        $rayhanRPChatIdString,
        $rayhanRPFileType,
        $rayhanRPFileId,
        $rayhanRPFileUniqueId,
        $rayhanRPTelegramPath,
        $rayhanRPNamaFileAsli,
        $rayhanRPMime,
        $rayhanRPFileSize,
        $rayhanRPRelativePath,
        $rayhanRPCaption,
        $rayhanRPStatus
    );
    $rayhanRPSubmitOk = mysqli_stmt_execute($rayhanRPSubmitStmt);
    mysqli_stmt_close($rayhanRPSubmitStmt);

    if (!$rayhanRPSubmitOk) {
        sendMessage($rayhanRPChatId, "Pengumpulan gagal disimpan. Coba ulangi.");
        exit;
    }

    rayhanRP_setState($rayhanRPChatId, [
        'step' => 'authenticated',
        'auth' => $rayhanRPAuthUser
    ]);

    $rayhanRPSubmitMsg = "Tugas berhasil dikumpulkan.\nID Tugas: {$rayhanRPTaskId}\nStatus: {$rayhanRPStatus}";
    if (!$rayhanRPDownloadOk) {
        $rayhanRPSubmitMsg .= "\nCatatan: file tidak tersimpan lokal, tapi data Telegram sudah tercatat.";
    }
    sendMessage($rayhanRPChatId, $rayhanRPSubmitMsg);
    exit;
}

if (rayhanRP_isCommand($rayhanRPText, '/tugas')) {
    if (!$rayhanRPAuthUser) {
        sendMessage($rayhanRPChatId, "Silakan login dulu dengan /start.");
        exit;
    }

    if (!$databaseRayhanRP) {
        sendMessage($rayhanRPChatId, "Koneksi database gagal. Coba lagi nanti.");
        exit;
    }

    if (!rayhanRP_ensureTugasPengumpulanTable($databaseRayhanRP)) {
        sendMessage($rayhanRPChatId, "Tabel pengumpulan tugas belum siap.");
        exit;
    }

    $rayhanRPAkunId = isset($rayhanRPAuthUser['akun_id']) ? (int)$rayhanRPAuthUser['akun_id'] : 0;
    if ($rayhanRPAkunId <= 0) {
        sendMessage($rayhanRPChatId, "Sesi login tidak lengkap. Silakan login ulang dengan /start.");
        exit;
    }

    $rayhanRPTugasSql = "
        SELECT
            t.id_tugas,
            t.judul,
            t.tenggat,
            COALESCE(g.nama_grup, '-') AS nama_grup,
            tp.status,
            tp.nilai,
            tp.submitted_at
        FROM tugas t
        INNER JOIN grup g ON g.id_grup = t.grup_id
        LEFT JOIN grup_anggota ga ON ga.grup_id = g.id_grup AND ga.akun_id = ? AND ga.deleted_at IS NULL
        LEFT JOIN tugas_pengumpulan tp ON tp.tugas_id = t.id_tugas AND tp.akun_id = ?
        WHERE ga.akun_id IS NOT NULL OR g.dibuat_oleh_akun_id = ? OR t.dibuat_oleh_akun_id = ?
        ORDER BY t.tenggat ASC, t.id_tugas ASC
        LIMIT 15
    ";
    $rayhanRPTugasStmt = mysqli_prepare($databaseRayhanRP, $rayhanRPTugasSql);
    if (!$rayhanRPTugasStmt) {
        sendMessage($rayhanRPChatId, "Gagal mengambil data tugas.");
        exit;
    }

    mysqli_stmt_bind_param($rayhanRPTugasStmt, "iiii", $rayhanRPAkunId, $rayhanRPAkunId, $rayhanRPAkunId, $rayhanRPAkunId);
    if (!mysqli_stmt_execute($rayhanRPTugasStmt)) {
        mysqli_stmt_close($rayhanRPTugasStmt);
        sendMessage($rayhanRPChatId, "Gagal mengambil data tugas.");
        exit;
    }

    mysqli_stmt_bind_result(
        $rayhanRPTugasStmt,
        $rayhanRPIdTugas,
        $rayhanRPJudulTugas,
        $rayhanRPTenggatTugas,
        $rayhanRPNamaGrupTugas,
        $rayhanRPStatusTugas,
        $rayhanRPNilaiTugas,
        $rayhanRPSubmittedAtTugas
    );

    $rayhanRPTugasLines = ["Daftar Tugas Anda:"];
    $rayhanRPTugasNo = 1;
    $rayhanRPTugasFound = false;
    while (mysqli_stmt_fetch($rayhanRPTugasStmt)) {
        $rayhanRPTugasFound = true;
        $rayhanRPStatusLabel = "Belum dikumpulkan";
        if ((string)$rayhanRPSubmittedAtTugas !== '' && $rayhanRPSubmittedAtTugas !== null) {
            $rayhanRPStatusLabel = ucfirst((string)($rayhanRPStatusTugas ?: 'dikumpulkan'));
            if ($rayhanRPNilaiTugas !== null && $rayhanRPNilaiTugas !== '') {
                $rayhanRPStatusLabel .= " | Nilai: " . number_format((float)$rayhanRPNilaiTugas, 2, '.', '');
            }
        }

        $rayhanRPTugasLines[] = $rayhanRPTugasNo . ". [ID " . (int)$rayhanRPIdTugas . "] " . (string)$rayhanRPJudulTugas;
        $rayhanRPTugasLines[] = "   Grup: " . (string)$rayhanRPNamaGrupTugas;
        $rayhanRPTugasLines[] = "   Tenggat: " . (string)$rayhanRPTenggatTugas;
        $rayhanRPTugasLines[] = "   Status: " . $rayhanRPStatusLabel;
        $rayhanRPTugasNo++;
    }
    mysqli_stmt_close($rayhanRPTugasStmt);

    if (!$rayhanRPTugasFound) {
        sendMessage($rayhanRPChatId, "Belum ada tugas di grup Anda.");
        exit;
    }

    $rayhanRPTugasLines[] = "";
    $rayhanRPTugasLines[] = "Kumpulkan tugas: /kumpul <id_tugas>";
    $rayhanRPTugasLines[] = "Contoh: /kumpul 1";
    sendMessage($rayhanRPChatId, implode("\n", $rayhanRPTugasLines));
    exit;
}

if (rayhanRP_isCommand($rayhanRPText, '/kumpul')) {
    if (!$rayhanRPAuthUser) {
        sendMessage($rayhanRPChatId, "Silakan login dulu dengan /start.");
        exit;
    }

    $rayhanRPTaskId = rayhanRP_parseKumpulCommandId($rayhanRPText);
    if ($rayhanRPTaskId === null || $rayhanRPTaskId <= 0) {
        sendMessage($rayhanRPChatId, "Format perintah:\n/kumpul <id_tugas>\nContoh: /kumpul 1");
        exit;
    }

    if (!$databaseRayhanRP) {
        sendMessage($rayhanRPChatId, "Koneksi database gagal. Coba lagi nanti.");
        exit;
    }

    if (!rayhanRP_ensureTugasPengumpulanTable($databaseRayhanRP)) {
        sendMessage($rayhanRPChatId, "Tabel pengumpulan tugas belum siap.");
        exit;
    }

    $rayhanRPAkunId = isset($rayhanRPAuthUser['akun_id']) ? (int)$rayhanRPAuthUser['akun_id'] : 0;
    if ($rayhanRPAkunId <= 0) {
        sendMessage($rayhanRPChatId, "Sesi login tidak lengkap. Silakan login ulang dengan /start.");
        exit;
    }

    $rayhanRPTaskData = rayhanRP_fetchTaskForUser($databaseRayhanRP, $rayhanRPTaskId, $rayhanRPAkunId);
    if (!$rayhanRPTaskData) {
        sendMessage($rayhanRPChatId, "ID tugas tidak ditemukan atau tidak termasuk grup Anda.");
        exit;
    }

    rayhanRP_setState($rayhanRPChatId, [
        'step' => 'awaiting_tugas_file',
        'auth' => $rayhanRPAuthUser,
        'tugas_id' => $rayhanRPTaskId
    ]);

    sendMessage(
        $rayhanRPChatId,
        "Siap mengumpulkan tugas:\n[ID {$rayhanRPTaskId}] " . (string)$rayhanRPTaskData['judul'] . "\nGrup: " . (string)$rayhanRPTaskData['nama_grup'] . "\nKirim file atau foto sekarang.\nKetik /stop untuk membatalkan."
    );
    exit;
}

if (rayhanRP_isCommand($rayhanRPText, '/jadwal')) {
    if (!$rayhanRPAuthUser) {
        sendMessage($rayhanRPChatId, "Silakan login dulu dengan /start.");
        exit;
    }

    if (!$databaseRayhanRP) {
        sendMessage($rayhanRPChatId, "Koneksi database gagal. Coba lagi nanti.");
        exit;
    }

    $rayhanRPAkunId = isset($rayhanRPAuthUser['akun_id']) ? (int)$rayhanRPAuthUser['akun_id'] : 0;
    if ($rayhanRPAkunId <= 0) {
        sendMessage($rayhanRPChatId, "Sesi login tidak lengkap. Silakan login ulang dengan /start.");
        exit;
    }

    $rayhanRPJadwalSql = "
        SELECT j.judul, j.deskripsi, j.tanggal, j.jam_mulai, j.jam_selesai, COALESCE(g.nama_grup, '-') AS nama_grup
        FROM jadwal j
        INNER JOIN grup g ON g.id_grup = j.grup_id
        LEFT JOIN grup_anggota ga
            ON ga.grup_id = g.id_grup
            AND ga.akun_id = ?
            AND ga.deleted_at IS NULL
        WHERE g.dibuat_oleh_akun_id = ?
           OR ga.akun_id IS NOT NULL
        ORDER BY j.tanggal ASC, j.jam_mulai ASC
        LIMIT 10
    ";

    $rayhanRPJadwalStmt = mysqli_prepare($databaseRayhanRP, $rayhanRPJadwalSql);
    if (!$rayhanRPJadwalStmt) {
        sendMessage($rayhanRPChatId, "Gagal mengambil data jadwal.");
        exit;
    }
    mysqli_stmt_bind_param($rayhanRPJadwalStmt, "ii", $rayhanRPAkunId, $rayhanRPAkunId);
    if (!mysqli_stmt_execute($rayhanRPJadwalStmt)) {
        mysqli_stmt_close($rayhanRPJadwalStmt);
        sendMessage($rayhanRPChatId, "Gagal mengambil data jadwal.");
        exit;
    }

    mysqli_stmt_bind_result(
        $rayhanRPJadwalStmt,
        $rayhanRPJudul,
        $rayhanRPDeskripsi,
        $rayhanRPTanggal,
        $rayhanRPJamMulai,
        $rayhanRPJamSelesai,
        $rayhanRPGrup
    );

    $rayhanRPMessageLines = ["Jadwal Grup Anda:"];
    $rayhanRPNo = 1;
    $rayhanRPHasRows = false;

    while (mysqli_stmt_fetch($rayhanRPJadwalStmt)) {
        $rayhanRPHasRows = true;
        $rayhanRPJudul = (string)$rayhanRPJudul;
        $rayhanRPGrup = (string)$rayhanRPGrup;
        $rayhanRPTanggal = (string)$rayhanRPTanggal;
        $rayhanRPJamMulai = substr((string)$rayhanRPJamMulai, 0, 5);
        $rayhanRPJamSelesai = substr((string)$rayhanRPJamSelesai, 0, 5);

        $rayhanRPMessageLines[] = "{$rayhanRPNo}. {$rayhanRPJudul}";
        $rayhanRPMessageLines[] = "   Grup: {$rayhanRPGrup}";
        $rayhanRPMessageLines[] = "   Tanggal: {$rayhanRPTanggal}";
        $rayhanRPMessageLines[] = "   Jam: {$rayhanRPJamMulai} - {$rayhanRPJamSelesai}";
        $rayhanRPNo++;
    }

    mysqli_stmt_close($rayhanRPJadwalStmt);

    if (!$rayhanRPHasRows) {
        sendMessage($rayhanRPChatId, "Belum ada jadwal di grup Anda.");
        exit;
    }

    sendMessage($rayhanRPChatId, implode("\n", $rayhanRPMessageLines));
    exit;
}

if (rayhanRP_isCommand($rayhanRPText, '/menu')) {
    if (!$rayhanRPAuthUser) {
        sendMessage($rayhanRPChatId, "Silakan login dulu dengan /start.");
        exit;
    }

    sendMessage($rayhanRPChatId, "Pilihan Menu:\n1. /tugas - Lihat daftar tugas\n2. /kumpul <id_tugas> - Kumpulkan tugas\n3. /jadwal - Lihat jadwal pelajaran");
    exit;
}

if (rayhanRP_isCommand($rayhanRPText, '/help')) {
    sendMessage($rayhanRPChatId, "Daftar Perintah:\n/start - Memulai interaksi dengan bot\n/stop - Menghentikan proses\n/help - Menampilkan daftar perintah\n/menu - Menampilkan menu utama\n/tugas - Menampilkan daftar tugas\n/kumpul <id_tugas> - Mulai kirim tugas (file/foto)\n/jadwal - Menampilkan jadwal pelajaran");
    exit;
}

sendMessage($rayhanRPChatId, "Perintah tidak dikenali. Ketik /help untuk daftar perintah.");
