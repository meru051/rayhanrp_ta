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
    sendMessage($rayhanRPChatId, "Pilihan Menu:\n1. /tugas - Untuk melihat tugas yang diberikan guru\n2. /jadwal - Untuk melihat jadwal pelajaran");
    exit;
}

if (rayhanRP_isCommand($rayhanRPText, '/tugas')) {
    if (!$rayhanRPAuthUser) {
        sendMessage($rayhanRPChatId, "Silakan login dulu dengan /start.");
        exit;
    }

    sendMessage($rayhanRPChatId, "Fitur tugas belum tersedia.");
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

    sendMessage($rayhanRPChatId, "Pilihan Menu:\n1. /tugas - Untuk melihat tugas yang diberikan guru\n2. /jadwal - Untuk melihat jadwal pelajaran");
    exit;
}

if (rayhanRP_isCommand($rayhanRPText, '/help')) {
    sendMessage($rayhanRPChatId, "Daftar Perintah:\n/start - Memulai interaksi dengan bot\n/stop - Menghentikan proses\n/help - Menampilkan daftar perintah\n/menu - Menampilkan menu utama\n/jadwal - Menampilkan jadwal pelajaran");
    exit;
}

sendMessage($rayhanRPChatId, "Perintah tidak dikenali. Ketik /help untuk daftar perintah.");
?>
