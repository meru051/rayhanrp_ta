<?php
require_once 'function/function_bot_rayhanRP.php';
require_once 'koneksi_rayhanRP.php';

$rayhanRPStateFile = (string)rayhanRPGetConfigValue('app.state_file', __DIR__ . '/data/state.json');

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
    return rayhanRPRunMigrations($databaseRayhanRP) && rayhanRPDbTableExists($databaseRayhanRP, 'tugas_pengumpulan');
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

function rayhanRP_getCommandArgs($rayhanRPText, $rayhanRPCommand)
{
    $rayhanRPPattern = '/^' . preg_quote($rayhanRPCommand, '/') . '(@[A-Za-z0-9_]+)?(?:\s+(.*))?\s*$/i';
    if (preg_match($rayhanRPPattern, trim($rayhanRPText), $rayhanRPMatches) !== 1) {
        return null;
    }
    return trim((string)($rayhanRPMatches[2] ?? ''));
}

function rayhanRP_isPreferensiCommand($rayhanRPText)
{
    return rayhanRP_isCommand($rayhanRPText, '/prefrensi') || rayhanRP_isCommand($rayhanRPText, '/preferensi');
}

function rayhanRP_getPreferensiArgs($rayhanRPText)
{
    if (rayhanRP_isCommand($rayhanRPText, '/prefrensi')) {
        return trim((string)rayhanRP_getCommandArgs($rayhanRPText, '/prefrensi'));
    }
    return trim((string)rayhanRP_getCommandArgs($rayhanRPText, '/preferensi'));
}

function rayhanRP_buildStartKeyboard()
{
    return [
        'keyboard' => [
            [['text' => '/start'], ['text' => '/panduan']],
            [['text' => '/stop']],
        ],
        'resize_keyboard' => true,
        'one_time_keyboard' => false,
        'is_persistent' => true,
    ];
}

function rayhanRP_buildMainKeyboard()
{
    return [
        'keyboard' => [
            [['text' => '/menu'], ['text' => '/tugas']],
            [['text' => '/kumpul'], ['text' => '/jadwal']],
            [['text' => '/preferensi']],
            [['text' => '/panduan'], ['text' => '/stop']],
        ],
        'resize_keyboard' => true,
        'one_time_keyboard' => false,
        'is_persistent' => true,
    ];
}

function rayhanRP_getMainMenuText()
{
    return "Menu Bot SiRey:\n"
        . "1. /tugas untuk lihat tugas\n"
        . "2. /kumpul untuk kirim tugas\n"
        . "3. /jadwal untuk lihat jadwal\n"
        . "4. /preferensi untuk atur pengingat\n\n"
        . "Bingung mulai dari mana? Ketik /panduan.\n"
        . "Butuh batal? Ketik /stop.";
}

function rayhanRP_getHelpText()
{
    return "Panduan singkat:\n"
        . "1. Ketik /start\n"
        . "2. Masukkan NIS/NIP\n"
        . "3. Masukkan password\n"
        . "4. Pilih menu dari keyboard\n\n"
        . "Perintah utama:\n"
        . "/menu - tampilkan menu\n"
        . "/tugas - daftar tugas Anda\n"
        . "/kumpul [id] - kirim tugas\n"
        . "/jadwal - jadwal grup Anda\n"
        . "/preferensi - atur pengingat\n"
        . "/stop - batalkan proses\n\n"
        . "Contoh:\n"
        . "- /kumpul 3\n"
        . "- /preferensi custom 45\n"
        . "- /preferensi off";
}

function rayhanRP_sendMainMenu($rayhanRPChatId)
{
    sendMessage($rayhanRPChatId, rayhanRP_getMainMenuText(), rayhanRP_buildMainKeyboard());
}

function rayhanRP_sendNeedLogin($rayhanRPChatId)
{
    sendMessage($rayhanRPChatId, "Anda belum masuk. Ketik /start lalu ikuti langkah login.", rayhanRP_buildStartKeyboard());
}

function rayhanRP_ensureAkunTelegramTable($databaseRayhanRP)
{
    return rayhanRPRunMigrations($databaseRayhanRP) && rayhanRPDbTableExists($databaseRayhanRP, 'akun_telegram');
}

function rayhanRP_dbColumnExists($databaseRayhanRP, $rayhanRPTableName, $rayhanRPColumnName)
{
    return rayhanRPDbColumnExists($databaseRayhanRP, $rayhanRPTableName, $rayhanRPColumnName);
}

function rayhanRP_ensurePreferensiSchema($databaseRayhanRP)
{
    return rayhanRPRunMigrations($databaseRayhanRP) && rayhanRPDbTableExists($databaseRayhanRP, 'prefrensi_user');
}

function rayhanRP_getLatestPreferensiByAkun($databaseRayhanRP, $rayhanRPAkunId)
{
    return rayhanRPFetchPreferensiByAkun($databaseRayhanRP, $rayhanRPAkunId);
}

function rayhanRP_ensurePreferensiRow($databaseRayhanRP, $rayhanRPAkunId)
{
    return rayhanRPEnsurePreferensiRowCommon($databaseRayhanRP, $rayhanRPAkunId);
}

function rayhanRP_linkAkunTelegram($databaseRayhanRP, $rayhanRPAkunId, $rayhanRPChatId)
{
    return rayhanRP_ensureAkunTelegramTable($databaseRayhanRP)
        && rayhanRPUpsertAkunTelegram($databaseRayhanRP, $rayhanRPAkunId, $rayhanRPChatId);
}

function rayhanRP_formatPreferensiMessage($rayhanRPPreferensi)
{
    $rayhanRPStatus = ((int)($rayhanRPPreferensi['pengingat_aktif'] ?? 1) === 1) ? 'Aktif' : 'Nonaktif';
    $rayhanRPOffset = (int)($rayhanRPPreferensi['offset_custom_menit'] ?? 30);
    if ($rayhanRPOffset <= 0) {
        $rayhanRPOffset = 30;
    }

    return "Preferensi pengingat Anda:\n"
        . "Status: {$rayhanRPStatus}\n"
        . "Waktu pengingat: {$rayhanRPOffset} menit sebelum jadwal atau tenggat\n\n"
        . "Gunakan salah satu perintah ini:\n"
        . "/preferensi on - aktifkan pengingat\n"
        . "/preferensi off - matikan pengingat\n"
        . "/preferensi custom <menit> - atur menit pengingat\n"
        . "/preferensi reset - kembali ke 30 menit";
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

function rayhanRP_startKumpulByTask($databaseRayhanRP, $rayhanRPChatId, $rayhanRPAuthUser, $rayhanRPTaskId)
{
    if (!$databaseRayhanRP) {
        sendMessage($rayhanRPChatId, "Koneksi database gagal. Coba lagi nanti.");
        return false;
    }

    if (!rayhanRP_ensureTugasPengumpulanTable($databaseRayhanRP)) {
        sendMessage($rayhanRPChatId, "Tabel pengumpulan tugas belum siap.");
        return false;
    }

    $rayhanRPAkunId = isset($rayhanRPAuthUser['akun_id']) ? (int)$rayhanRPAuthUser['akun_id'] : 0;
    if ($rayhanRPAkunId <= 0) {
        sendMessage($rayhanRPChatId, "Sesi login tidak lengkap. Silakan login ulang dengan /start.");
        return false;
    }

    $rayhanRPTaskData = rayhanRP_fetchTaskForUser($databaseRayhanRP, $rayhanRPTaskId, $rayhanRPAkunId);
    if (!$rayhanRPTaskData) {
        sendMessage($rayhanRPChatId, "ID tugas tidak ditemukan atau tidak termasuk grup Anda.");
        return false;
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
    return true;
}

function rayhanRP_fetchTelegramFilePath($rayhanRPFileId)
{
    $rayhanRPFileInfo = rayhanRPGetTelegramFileInfo($rayhanRPFileId);
    return is_array($rayhanRPFileInfo) ? (string)($rayhanRPFileInfo['file_path'] ?? '') : '';
}

function rayhanRP_downloadTelegramFile($rayhanRPFilePath, $rayhanRPTargetPath)
{
    if (trim($rayhanRPFilePath) === '' || trim($rayhanRPTargetPath) === '') {
        return false;
    }

    $rayhanRPBinary = rayhanRPDownloadTelegramFileBinary($rayhanRPFilePath);
    if (!is_string($rayhanRPBinary) || $rayhanRPBinary === '') {
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

$rayhanRPDefaultKeyboard = $rayhanRPAuthUser ? rayhanRP_buildMainKeyboard() : rayhanRP_buildStartKeyboard();

if (rayhanRP_isCommand($rayhanRPText, '/stop')) {
    rayhanRP_setState($rayhanRPChatId, null);
    sendMessage($rayhanRPChatId, "Proses dihentikan.\nKetik /start untuk memulai lagi.", rayhanRP_buildStartKeyboard());
    exit;
}

if (rayhanRP_isCommand($rayhanRPText, '/start')) {
    if ($rayhanRPAuthUser) {
        $rayhanRPAuthLabel = rayhanRPFormatAccountLabel(
            (string)($rayhanRPAuthUser['nama_lengkap'] ?? ''),
            (string)($rayhanRPAuthUser['nis_nip'] ?? '-')
        );
        sendMessage($rayhanRPChatId, "Anda sudah masuk sebagai {$rayhanRPAuthLabel}.\nKetik /menu untuk lanjut atau /stop untuk ganti akun.");
        rayhanRP_sendMainMenu($rayhanRPChatId);
        exit;
    }

    rayhanRP_setState($rayhanRPChatId, ['step' => 'awaiting_id']);
    sendMessage($rayhanRPChatId, "Halo, $rayhanRPChatName. Bot SiRey siap membantu jadwal dan tugas Anda.", rayhanRP_buildStartKeyboard());
    sendMessage($rayhanRPChatId, "Langkah 1 dari 2: kirim NIS/NIP Anda.\nKetik /panduan jika perlu bantuan.\nKetik /stop jika ingin batal.", rayhanRP_buildStartKeyboard());
    exit;
}

$rayhanRPStep = is_array($rayhanRPState) ? ($rayhanRPState['step'] ?? null) : $rayhanRPState;
if ($rayhanRPStep === 'awaiting_nis') {
    $rayhanRPStep = 'awaiting_id';
}

if ($rayhanRPStep === 'awaiting_id') {
    if ($rayhanRPText !== '' && preg_match('/^\//', $rayhanRPText) === 1) {
        // Biarkan command standar diproses di bawah.
    } else {
        $rayhanRPLoginId = $rayhanRPText;

        if ($rayhanRPLoginId === '') {
            sendMessage($rayhanRPChatId, "NIS/NIP tidak boleh kosong.");
            exit;
        }

    if (!preg_match('/^[A-Za-z0-9._-]{3,25}$/', $rayhanRPLoginId)) {
        sendMessage($rayhanRPChatId, "Format NIS/NIP tidak valid. Gunakan huruf/angka (3-25 karakter).\nContoh: 102306363");
        exit;
    }

    if (!$databaseRayhanRP) {
        sendMessage($rayhanRPChatId, "Koneksi database gagal. Coba lagi nanti.");
        exit;
    }

    $rayhanRPAkun = rayhanRPFetchAccountByNisNip($databaseRayhanRP, $rayhanRPLoginId);
    if (!$rayhanRPAkun) {
        sendMessage($rayhanRPChatId, "NIS/NIP tidak ditemukan. Periksa lagi, lalu kirim ulang NIS/NIP.\nKetik /panduan jika perlu bantuan.");
        exit;
    }

    $rayhanRPDbNisNip = (string)$rayhanRPAkun['nis_nip'];
    rayhanRP_setState($rayhanRPChatId, [
        'step' => 'awaiting_password',
        'nis_nip' => $rayhanRPDbNisNip,
        'nama_lengkap' => (string)($rayhanRPAkun['nama_lengkap'] ?? ''),
    ]);
    sendMessage($rayhanRPChatId, "Langkah 2 dari 2: kirim password untuk {$rayhanRPDbNisNip}.\nKetik /stop jika ingin batal login.");
    exit;
    }
}

if ($rayhanRPStep === 'awaiting_password') {
    if ($rayhanRPText !== '' && preg_match('/^\//', $rayhanRPText) === 1) {
        // Biarkan command standar diproses di bawah.
    } else {
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

    $rayhanRPAkun = rayhanRPFetchAccountByNisNip($databaseRayhanRP, $rayhanRPLoginId);
    if (!$rayhanRPAkun) {
        rayhanRP_setState($rayhanRPChatId, ['step' => 'awaiting_id']);
        sendMessage($rayhanRPChatId, "Data akun tidak ditemukan. Mulai lagi dengan /start.");
        exit;
    }

    if (!rayhanRPVerifyAccountPassword($databaseRayhanRP, $rayhanRPAkun, $rayhanRPTextRaw)) {
        sendMessage($rayhanRPChatId, "Password salah. Silakan coba lagi.\nKetik /stop untuk membatalkan login.");
        exit;
    }

    $rayhanRPAkunIdInt = (int)($rayhanRPAkun['akun_id'] ?? 0);
    $rayhanRPDbNisNip = (string)($rayhanRPAkun['nis_nip'] ?? '');
    $rayhanRPDbRole = (string)($rayhanRPAkun['role'] ?? '-');
    $rayhanRPNamaLengkap = (string)($rayhanRPAkun['nama_lengkap'] ?? '');
    rayhanRP_linkAkunTelegram($databaseRayhanRP, $rayhanRPAkunIdInt, $rayhanRPChatId);
    rayhanRP_ensurePreferensiRow($databaseRayhanRP, $rayhanRPAkunIdInt);

    rayhanRP_setState($rayhanRPChatId, [
        'step' => 'authenticated',
        'auth' => [
            'akun_id' => $rayhanRPAkunIdInt,
            'nis_nip' => $rayhanRPDbNisNip,
            'nama_lengkap' => $rayhanRPNamaLengkap,
            'role' => $rayhanRPDbRole
        ]
    ]);

    $rayhanRPLabel = rayhanRPFormatAccountLabel($rayhanRPNamaLengkap, $rayhanRPDbNisNip);
    sendMessage($rayhanRPChatId, "Login berhasil. Selamat datang {$rayhanRPLabel}.", rayhanRP_buildMainKeyboard());
    rayhanRP_sendMainMenu($rayhanRPChatId);
    exit;
}
}

if ($rayhanRPStep === 'awaiting_tugas_id') {
    if ($rayhanRPText !== '' && preg_match('/^\//', $rayhanRPText) === 1) {
        // Biarkan command standar diproses di bawah.
    } else {
        if (!$rayhanRPAuthUser && is_array($rayhanRPState) && isset($rayhanRPState['auth']) && is_array($rayhanRPState['auth'])) {
            $rayhanRPAuthUser = $rayhanRPState['auth'];
        }

        if (!$rayhanRPAuthUser) {
            rayhanRP_setState($rayhanRPChatId, ['step' => 'awaiting_id']);
            sendMessage($rayhanRPChatId, "Sesi login tidak ditemukan. Mulai lagi dengan /start.");
            exit;
        }

        $rayhanRPTaskIdInput = (int)trim($rayhanRPText);
        if ($rayhanRPTaskIdInput <= 0) {
            sendMessage($rayhanRPChatId, "Masukkan ID tugas berupa angka.\nContoh: 1\nKetik /stop untuk batal.");
            exit;
        }

        if (rayhanRP_startKumpulByTask($databaseRayhanRP, $rayhanRPChatId, $rayhanRPAuthUser, $rayhanRPTaskIdInput)) {
            exit;
        }
        exit;
    }
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
        sendMessage($rayhanRPChatId, "Data tugas tidak valid. Gunakan /tugas lalu /kumpul.");
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

if (rayhanRP_isPreferensiCommand($rayhanRPText)) {
    if (!$rayhanRPAuthUser) {
        rayhanRP_sendNeedLogin($rayhanRPChatId);
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

    $rayhanRPPreferensi = rayhanRP_ensurePreferensiRow($databaseRayhanRP, $rayhanRPAkunId);
    if (!$rayhanRPPreferensi) {
        sendMessage($rayhanRPChatId, "Gagal membaca preferensi pengingat.");
        exit;
    }

    $rayhanRPArgs = strtolower((string)rayhanRP_getPreferensiArgs($rayhanRPText));
    if ($rayhanRPArgs === '' || $rayhanRPArgs === 'status') {
        sendMessage($rayhanRPChatId, rayhanRP_formatPreferensiMessage($rayhanRPPreferensi));
        exit;
    }

    $rayhanRPUpdated = false;
    if ($rayhanRPArgs === 'on' || $rayhanRPArgs === 'off') {
        $rayhanRPValue = $rayhanRPArgs === 'on' ? 1 : 0;
        $rayhanRPStmt = mysqli_prepare($databaseRayhanRP, "UPDATE prefrensi_user SET pengingat_aktif = ? WHERE id_preferensi = ? LIMIT 1");
        if ($rayhanRPStmt) {
            $rayhanRPIdPref = (int)$rayhanRPPreferensi['id_preferensi'];
            mysqli_stmt_bind_param($rayhanRPStmt, 'ii', $rayhanRPValue, $rayhanRPIdPref);
            $rayhanRPUpdated = mysqli_stmt_execute($rayhanRPStmt);
            mysqli_stmt_close($rayhanRPStmt);
        }
    } elseif ($rayhanRPArgs === 'reset') {
        $rayhanRPDefaultAktif = 1;
        $rayhanRPDefaultWaktu = '08:00:00';
        $rayhanRPDefaultOffset = 30;
        $rayhanRPStmt = mysqli_prepare($databaseRayhanRP, "UPDATE prefrensi_user SET pengingat_aktif = ?, waktu_default = ?, offset_custom_menit = ? WHERE id_preferensi = ? LIMIT 1");
        if ($rayhanRPStmt) {
            $rayhanRPIdPref = (int)$rayhanRPPreferensi['id_preferensi'];
            mysqli_stmt_bind_param($rayhanRPStmt, 'isii', $rayhanRPDefaultAktif, $rayhanRPDefaultWaktu, $rayhanRPDefaultOffset, $rayhanRPIdPref);
            $rayhanRPUpdated = mysqli_stmt_execute($rayhanRPStmt);
            mysqli_stmt_close($rayhanRPStmt);
        }
    } elseif (preg_match('/^custom\s+(\d{1,4})$/', $rayhanRPArgs, $rayhanRPMatches) === 1) {
        $rayhanRPValue = (int)$rayhanRPMatches[1];
        if ($rayhanRPValue < 1 || $rayhanRPValue > 1440) {
            sendMessage($rayhanRPChatId, "Nilai custom harus 1 sampai 1440 menit.");
            exit;
        }
        $rayhanRPStmt = mysqli_prepare($databaseRayhanRP, "UPDATE prefrensi_user SET offset_custom_menit = ? WHERE id_preferensi = ? LIMIT 1");
        if ($rayhanRPStmt) {
            $rayhanRPIdPref = (int)$rayhanRPPreferensi['id_preferensi'];
            mysqli_stmt_bind_param($rayhanRPStmt, 'ii', $rayhanRPValue, $rayhanRPIdPref);
            $rayhanRPUpdated = mysqli_stmt_execute($rayhanRPStmt);
            mysqli_stmt_close($rayhanRPStmt);
        }
    } else {
        sendMessage($rayhanRPChatId, "Format /preferensi belum tepat.\nCoba salah satu:\n/preferensi\n/preferensi on\n/preferensi off\n/preferensi custom 45\n/preferensi reset");
        exit;
    }

    if (!$rayhanRPUpdated) {
        sendMessage($rayhanRPChatId, "Gagal memperbarui preferensi.");
        exit;
    }

    $rayhanRPPreferensiBaru = rayhanRP_getLatestPreferensiByAkun($databaseRayhanRP, $rayhanRPAkunId);
    if (!$rayhanRPPreferensiBaru) {
        $rayhanRPPreferensiBaru = $rayhanRPPreferensi;
    }

    sendMessage($rayhanRPChatId, "Preferensi berhasil diperbarui.\n\n" . rayhanRP_formatPreferensiMessage($rayhanRPPreferensiBaru));
    exit;
}

if (rayhanRP_isCommand($rayhanRPText, '/tugas')) {
    if (!$rayhanRPAuthUser) {
        rayhanRP_sendNeedLogin($rayhanRPChatId);
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
    $rayhanRPTugasLines[] = "Kumpulkan tugas: tekan /kumpul di keyboard atau /kumpul <id_tugas>";
    $rayhanRPTugasLines[] = "Contoh: /kumpul 1";
    sendMessage($rayhanRPChatId, implode("\n", $rayhanRPTugasLines));
    exit;
}

if (rayhanRP_isCommand($rayhanRPText, '/kumpul')) {
    if (!$rayhanRPAuthUser) {
        rayhanRP_sendNeedLogin($rayhanRPChatId);
        exit;
    }

    $rayhanRPTaskId = rayhanRP_parseKumpulCommandId($rayhanRPText);
    if ($rayhanRPTaskId === null) {
        sendMessage($rayhanRPChatId, "Gunakan tombol /kumpul di keyboard atau format: /kumpul <id_tugas>\nContoh: /kumpul 1");
        exit;
    }

    if ($rayhanRPTaskId === 0) {
        rayhanRP_setState($rayhanRPChatId, [
            'step' => 'awaiting_tugas_id',
            'auth' => $rayhanRPAuthUser,
        ]);
        sendMessage($rayhanRPChatId, "Masukkan ID tugas yang ingin dikumpulkan.\nContoh: 1\nKetik /stop untuk batal.");
        exit;
    }

    if (rayhanRP_startKumpulByTask($databaseRayhanRP, $rayhanRPChatId, $rayhanRPAuthUser, $rayhanRPTaskId)) {
        exit;
    }
    exit;
}
if (rayhanRP_isCommand($rayhanRPText, '/jadwal')) {
    if (!$rayhanRPAuthUser) {
        rayhanRP_sendNeedLogin($rayhanRPChatId);
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
        rayhanRP_sendNeedLogin($rayhanRPChatId);
        exit;
    }

    rayhanRP_sendMainMenu($rayhanRPChatId);
    exit;
}

if (rayhanRP_isCommand($rayhanRPText, '/help') || rayhanRP_isCommand($rayhanRPText, '/panduan')) {
    sendMessage(
        $rayhanRPChatId,
        rayhanRP_getHelpText(),
        $rayhanRPAuthUser ? rayhanRP_buildMainKeyboard() : rayhanRP_buildStartKeyboard()
    );
    exit;
}

sendMessage($rayhanRPChatId, "Perintah tidak dikenali. Gunakan tombol keyboard atau ketik /panduan.");
?>
