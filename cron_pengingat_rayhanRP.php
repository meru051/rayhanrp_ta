<?php
require_once __DIR__ . '/function/function_bot_rayhanRP.php';
require_once __DIR__ . '/koneksi_rayhanRP.php';

date_default_timezone_set('Asia/Jakarta');

function rayhanRPRemDbColumnExists($db, $table, $column)
{
    $stmt = mysqli_prepare(
        $db,
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
    );
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'ss', $table, $column);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    $exists = mysqli_stmt_fetch($stmt) && (int)$count > 0;
    mysqli_stmt_close($stmt);
    return $exists;
}

function rayhanRPRemEnsureAkunTelegramTable($db)
{
    $sql = "
        CREATE TABLE IF NOT EXISTS akun_telegram (
            akun_id INT NOT NULL,
            telegram_chat_id BIGINT NOT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (akun_id),
            UNIQUE KEY uniq_telegram_chat_id (telegram_chat_id),
            CONSTRAINT fk_akun_telegram_akun FOREIGN KEY (akun_id) REFERENCES akun (akun_id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";
    return mysqli_query($db, $sql) !== false;
}

function rayhanRPRemEnsurePreferensiSchema($db)
{
    $create = "
        CREATE TABLE IF NOT EXISTS prefrensi_user (
            id_preferensi INT AUTO_INCREMENT PRIMARY KEY,
            akun_id INT DEFAULT NULL,
            pengingat_aktif TINYINT(1) DEFAULT 1,
            waktu_default TIME DEFAULT '08:00:00',
            offset_custom_menit INT DEFAULT 30,
            snooze INT DEFAULT 10,
            snooze_sampai DATETIME DEFAULT NULL,
            zona_waktu VARCHAR(50) DEFAULT 'Asia/Jakarta',
            KEY idx_pref_akun (akun_id),
            CONSTRAINT fk_pref_akun FOREIGN KEY (akun_id) REFERENCES akun (akun_id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";
    if (!mysqli_query($db, $create)) {
        return false;
    }

    if (!rayhanRPRemDbColumnExists($db, 'prefrensi_user', 'offset_custom_menit')) {
        if (!mysqli_query($db, 'ALTER TABLE prefrensi_user ADD COLUMN offset_custom_menit INT DEFAULT NULL AFTER waktu_default')) {
            return false;
        }
    }

    if (!rayhanRPRemDbColumnExists($db, 'prefrensi_user', 'snooze_sampai')) {
        if (!mysqli_query($db, 'ALTER TABLE prefrensi_user ADD COLUMN snooze_sampai DATETIME DEFAULT NULL AFTER snooze')) {
            return false;
        }
    }

    return true;
}

function rayhanRPRemEnsurePengingatLogTable($db)
{
    $sql = "
        CREATE TABLE IF NOT EXISTS pengingat_terkirim (
            id_pengingat BIGINT AUTO_INCREMENT PRIMARY KEY,
            akun_id INT NOT NULL,
            jenis ENUM('jadwal','tugas') NOT NULL,
            ref_id INT NOT NULL,
            offset_menit INT NOT NULL,
            target_waktu DATETIME NOT NULL,
            dikirim_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_pengingat (akun_id, jenis, ref_id, offset_menit, target_waktu),
            KEY idx_pengingat_waktu (dikirim_pada)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";
    return mysqli_query($db, $sql) !== false;
}

function rayhanRPRemGetPreferensi($db, $akunId)
{
    $stmt = mysqli_prepare(
        $db,
        "SELECT id_preferensi, COALESCE(pengingat_aktif,1), COALESCE(offset_custom_menit,30)
         FROM prefrensi_user
         WHERE akun_id = ?
         ORDER BY id_preferensi DESC
         LIMIT 1"
    );
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $akunId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $idPref, $aktif, $offsetCustom);
    $data = null;
    if (mysqli_stmt_fetch($stmt)) {
        $data = [
            'id_preferensi' => (int)$idPref,
            'pengingat_aktif' => (int)$aktif,
            'offset_custom_menit' => (int)$offsetCustom,
        ];
    }
    mysqli_stmt_close($stmt);
    return $data;
}

function rayhanRPRemEnsurePreferensiRow($db, $akunId)
{
    $data = rayhanRPRemGetPreferensi($db, $akunId);
    if ($data !== null) {
        return $data;
    }

    $aktif = 1;
    $waktu = '08:00:00';
    $offset = 30;
    $stmt = mysqli_prepare(
        $db,
        'INSERT INTO prefrensi_user (akun_id, pengingat_aktif, waktu_default, offset_custom_menit) VALUES (?, ?, ?, ?)'
    );
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'iisi', $akunId, $aktif, $waktu, $offset);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if (!$ok) {
        return null;
    }

    return rayhanRPRemGetPreferensi($db, $akunId);
}

function rayhanRPRemOffsetLabel($offset)
{
    $offset = (int)$offset;
    if ($offset === 1440) {
        return 'H-1';
    }
    if ($offset === 60) {
        return 'H-1 jam';
    }
    return $offset . ' menit';
}

function rayhanRPRemMarkSent($db, $akunId, $jenis, $refId, $offset, $targetWaktu)
{
    $stmt = mysqli_prepare(
        $db,
        'INSERT IGNORE INTO pengingat_terkirim (akun_id, jenis, ref_id, offset_menit, target_waktu, dikirim_pada) VALUES (?, ?, ?, ?, ?, NOW())'
    );
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'isiis', $akunId, $jenis, $refId, $offset, $targetWaktu);
    $ok = mysqli_stmt_execute($stmt);
    $affected = $ok ? mysqli_stmt_affected_rows($stmt) : 0;
    mysqli_stmt_close($stmt);
    return $ok && $affected > 0;
}

function rayhanRPRemInsertNotifikasi($db, $akunId, $jadwalId, $tugasId, $pesan)
{
    $templateId = null;
    $stmt = mysqli_prepare(
        $db,
        'INSERT INTO notifikasi (akun_id, jadwal_id, tugas_id, template_id, waktu_kirim, pesan) VALUES (?, ?, ?, ?, NOW(), ?)'
    );
    if (!$stmt) {
        return;
    }

    mysqli_stmt_bind_param($stmt, 'iiiis', $akunId, $jadwalId, $tugasId, $templateId, $pesan);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function rayhanRPRemFetchDueJadwal($db, $akunId, $minWindow, $maxWindow)
{
    $sql = "
        SELECT
            j.id_jadwal,
            j.judul,
            j.tanggal,
            j.jam_mulai,
            COALESCE(g.nama_grup, '-') AS nama_grup,
            DATE_FORMAT(TIMESTAMP(j.tanggal, j.jam_mulai), '%Y-%m-%d %H:%i:%s') AS target_waktu
        FROM jadwal j
        INNER JOIN grup g ON g.id_grup = j.grup_id
        LEFT JOIN grup_anggota ga ON ga.grup_id = g.id_grup AND ga.akun_id = ? AND ga.deleted_at IS NULL
        WHERE j.tanggal IS NOT NULL
          AND j.jam_mulai IS NOT NULL
          AND (ga.akun_id IS NOT NULL OR g.dibuat_oleh_akun_id = ?)
          AND TIMESTAMP(j.tanggal, j.jam_mulai) >= NOW()
          AND TIMESTAMPDIFF(MINUTE, NOW(), TIMESTAMP(j.tanggal, j.jam_mulai)) BETWEEN ? AND ?
        ORDER BY j.tanggal ASC, j.jam_mulai ASC
        LIMIT 30
    ";

    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, 'iiii', $akunId, $akunId, $minWindow, $maxWindow);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return [];
    }

    mysqli_stmt_bind_result($stmt, $idJadwal, $judul, $tanggal, $jamMulai, $namaGrup, $targetWaktu);
    $rows = [];
    while (mysqli_stmt_fetch($stmt)) {
        $rows[] = [
            'id_jadwal' => (int)$idJadwal,
            'judul' => (string)$judul,
            'tanggal' => (string)$tanggal,
            'jam_mulai' => (string)$jamMulai,
            'nama_grup' => (string)$namaGrup,
            'target_waktu' => (string)$targetWaktu,
        ];
    }

    mysqli_stmt_close($stmt);
    return $rows;
}

function rayhanRPRemFetchDueTugas($db, $akunId, $minWindow, $maxWindow)
{
    $sql = "
        SELECT
            t.id_tugas,
            t.judul,
            DATE_FORMAT(t.tenggat, '%Y-%m-%d %H:%i:%s') AS tenggat,
            COALESCE(g.nama_grup, '-') AS nama_grup
        FROM tugas t
        INNER JOIN grup g ON g.id_grup = t.grup_id
        LEFT JOIN grup_anggota ga ON ga.grup_id = g.id_grup AND ga.akun_id = ? AND ga.deleted_at IS NULL
        LEFT JOIN tugas_pengumpulan tp ON tp.tugas_id = t.id_tugas AND tp.akun_id = ?
        WHERE t.tenggat IS NOT NULL
          AND t.tenggat <> '0000-00-00 00:00:00'
          AND tp.akun_id IS NULL
          AND (ga.akun_id IS NOT NULL OR g.dibuat_oleh_akun_id = ? OR t.dibuat_oleh_akun_id = ?)
          AND t.tenggat >= NOW()
          AND TIMESTAMPDIFF(MINUTE, NOW(), t.tenggat) BETWEEN ? AND ?
        ORDER BY t.tenggat ASC
        LIMIT 30
    ";

    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, 'iiiiii', $akunId, $akunId, $akunId, $akunId, $minWindow, $maxWindow);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return [];
    }

    mysqli_stmt_bind_result($stmt, $idTugas, $judul, $tenggat, $namaGrup);
    $rows = [];
    while (mysqli_stmt_fetch($stmt)) {
        $rows[] = [
            'id_tugas' => (int)$idTugas,
            'judul' => (string)$judul,
            'tenggat' => (string)$tenggat,
            'nama_grup' => (string)$namaGrup,
        ];
    }

    mysqli_stmt_close($stmt);
    return $rows;
}

if (!$databaseRayhanRP) {
    echo "Koneksi database gagal.\n";
    exit(1);
}

if (!rayhanRPRemEnsureAkunTelegramTable($databaseRayhanRP)) {
    echo "Gagal menyiapkan tabel akun_telegram.\n";
    exit(1);
}

if (!rayhanRPRemEnsurePreferensiSchema($databaseRayhanRP)) {
    echo "Gagal menyiapkan skema preferensi user.\n";
    exit(1);
}

if (!rayhanRPRemEnsurePengingatLogTable($databaseRayhanRP)) {
    echo "Gagal menyiapkan tabel log pengingat.\n";
    exit(1);
}

$recipientSql = 'SELECT at.akun_id, at.telegram_chat_id, a.nis_nip FROM akun_telegram at INNER JOIN akun a ON a.akun_id = at.akun_id ORDER BY at.akun_id ASC';
$recipientResult = mysqli_query($databaseRayhanRP, $recipientSql);
if (!$recipientResult) {
    echo "Gagal membaca penerima pengingat.\n";
    exit(1);
}

$totalAkun = 0;
$totalKirim = 0;
$totalSkipNonaktif = 0;
$totalError = 0;

while ($row = mysqli_fetch_assoc($recipientResult)) {
    $akunId = (int)($row['akun_id'] ?? 0);
    $chatId = (string)($row['telegram_chat_id'] ?? '');
    $nisNip = (string)($row['nis_nip'] ?? '-');

    if ($akunId <= 0 || $chatId === '') {
        continue;
    }

    $totalAkun++;
    $pref = rayhanRPRemEnsurePreferensiRow($databaseRayhanRP, $akunId);
    if (!$pref) {
        $totalError++;
        continue;
    }

    if ((int)($pref['pengingat_aktif'] ?? 1) !== 1) {
        $totalSkipNonaktif++;
        continue;
    }

    $offsetCustom = (int)($pref['offset_custom_menit'] ?? 30);
    $offsets = array_values(array_unique([1440, 60, $offsetCustom]));

    foreach ($offsets as $offset) {
        $offset = (int)$offset;
        if ($offset <= 0 || $offset > 10080) {
            continue;
        }

        $minWindow = $offset > 0 ? $offset - 1 : 0;
        $maxWindow = $offset;
        $label = rayhanRPRemOffsetLabel($offset);

        $jadwalRows = rayhanRPRemFetchDueJadwal($databaseRayhanRP, $akunId, $minWindow, $maxWindow);
        foreach ($jadwalRows as $jadwal) {
            $targetWaktu = (string)$jadwal['target_waktu'];
            if (!rayhanRPRemMarkSent($databaseRayhanRP, $akunId, 'jadwal', (int)$jadwal['id_jadwal'], $offset, $targetWaktu)) {
                continue;
            }

            $pesan = "Pengingat Jadwal ({$label})\n"
                . "NIS/NIP: {$nisNip}\n"
                . "Judul: " . (string)$jadwal['judul'] . "\n"
                . "Grup: " . (string)$jadwal['nama_grup'] . "\n"
                . "Waktu: {$targetWaktu}";

            sendMessage($chatId, $pesan);
            rayhanRPRemInsertNotifikasi($databaseRayhanRP, $akunId, (int)$jadwal['id_jadwal'], null, $pesan);
            $totalKirim++;
        }

        $tugasRows = rayhanRPRemFetchDueTugas($databaseRayhanRP, $akunId, $minWindow, $maxWindow);
        foreach ($tugasRows as $tugas) {
            $targetWaktu = (string)$tugas['tenggat'];
            if (!rayhanRPRemMarkSent($databaseRayhanRP, $akunId, 'tugas', (int)$tugas['id_tugas'], $offset, $targetWaktu)) {
                continue;
            }

            $pesan = "Pengingat Tugas ({$label})\n"
                . "NIS/NIP: {$nisNip}\n"
                . "Judul: " . (string)$tugas['judul'] . "\n"
                . "Grup: " . (string)$tugas['nama_grup'] . "\n"
                . "Tenggat: {$targetWaktu}\n"
                . "Kumpulkan via bot: /tugas lalu /kumpul";

            sendMessage($chatId, $pesan);
            rayhanRPRemInsertNotifikasi($databaseRayhanRP, $akunId, null, (int)$tugas['id_tugas'], $pesan);
            $totalKirim++;
        }
    }
}

mysqli_free_result($recipientResult);

echo "Selesai menjalankan pengingat otomatis.\n";
echo "Total akun diproses: {$totalAkun}\n";
echo "Total notifikasi terkirim: {$totalKirim}\n";
echo "Skip (pengingat nonaktif): {$totalSkipNonaktif}\n";
echo "Error akun: {$totalError}\n";
