<?php

function rayhanRPGetConfig()
{
    static $rayhanRPConfig = null;

    if ($rayhanRPConfig === null) {
        $rayhanRPConfig = require dirname(__DIR__) . '/config_rayhanRP.php';
        $rayhanRPTimezone = (string)($rayhanRPConfig['app']['timezone'] ?? 'Asia/Jakarta');
        if ($rayhanRPTimezone !== '') {
            date_default_timezone_set($rayhanRPTimezone);
        }
    }

    return $rayhanRPConfig;
}

function rayhanRPGetConfigValue($rayhanRPPath, $rayhanRPDefault = null)
{
    $rayhanRPConfig = rayhanRPGetConfig();
    $rayhanRPValue = $rayhanRPConfig;
    foreach (explode('.', (string)$rayhanRPPath) as $rayhanRPKey) {
        if (!is_array($rayhanRPValue) || !array_key_exists($rayhanRPKey, $rayhanRPValue)) {
            return $rayhanRPDefault;
        }
        $rayhanRPValue = $rayhanRPValue[$rayhanRPKey];
    }

    return $rayhanRPValue;
}

function rayhanRPAddSystemIssue($rayhanRPMessage)
{
    static $rayhanRPIssues = [];

    $rayhanRPMessage = trim((string)$rayhanRPMessage);
    if ($rayhanRPMessage !== '' && !in_array($rayhanRPMessage, $rayhanRPIssues, true)) {
        $rayhanRPIssues[] = $rayhanRPMessage;
    }

    return $rayhanRPIssues;
}

function rayhanRPGetSystemIssues()
{
    return rayhanRPAddSystemIssue('');
}

function rayhanRPDbTableExists($databaseRayhanRP, $rayhanRPTableName)
{
    $rayhanRPStmt = mysqli_prepare(
        $databaseRayhanRP,
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1'
    );
    if (!$rayhanRPStmt) {
        return false;
    }

    mysqli_stmt_bind_param($rayhanRPStmt, 's', $rayhanRPTableName);
    mysqli_stmt_execute($rayhanRPStmt);
    mysqli_stmt_bind_result($rayhanRPStmt, $rayhanRPCount);
    $rayhanRPExists = mysqli_stmt_fetch($rayhanRPStmt) && (int)$rayhanRPCount > 0;
    mysqli_stmt_close($rayhanRPStmt);

    return $rayhanRPExists;
}

function rayhanRPDbColumnExists($databaseRayhanRP, $rayhanRPTableName, $rayhanRPColumnName)
{
    $rayhanRPStmt = mysqli_prepare(
        $databaseRayhanRP,
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
    );
    if (!$rayhanRPStmt) {
        return false;
    }

    mysqli_stmt_bind_param($rayhanRPStmt, 'ss', $rayhanRPTableName, $rayhanRPColumnName);
    mysqli_stmt_execute($rayhanRPStmt);
    mysqli_stmt_bind_result($rayhanRPStmt, $rayhanRPCount);
    $rayhanRPExists = mysqli_stmt_fetch($rayhanRPStmt) && (int)$rayhanRPCount > 0;
    mysqli_stmt_close($rayhanRPStmt);

    return $rayhanRPExists;
}

function rayhanRPDbIndexExists($databaseRayhanRP, $rayhanRPTableName, $rayhanRPIndexName)
{
    $rayhanRPStmt = mysqli_prepare(
        $databaseRayhanRP,
        'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1'
    );
    if (!$rayhanRPStmt) {
        return false;
    }

    mysqli_stmt_bind_param($rayhanRPStmt, 'ss', $rayhanRPTableName, $rayhanRPIndexName);
    mysqli_stmt_execute($rayhanRPStmt);
    mysqli_stmt_bind_result($rayhanRPStmt, $rayhanRPCount);
    $rayhanRPExists = mysqli_stmt_fetch($rayhanRPStmt) && (int)$rayhanRPCount > 0;
    mysqli_stmt_close($rayhanRPStmt);

    return $rayhanRPExists;
}

function rayhanRPDbHasUniqueIndexOnColumn($databaseRayhanRP, $rayhanRPTableName, $rayhanRPColumnName)
{
    $rayhanRPStmt = mysqli_prepare(
        $databaseRayhanRP,
        'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND NON_UNIQUE = 0 LIMIT 1'
    );
    if (!$rayhanRPStmt) {
        return false;
    }

    mysqli_stmt_bind_param($rayhanRPStmt, 'ss', $rayhanRPTableName, $rayhanRPColumnName);
    mysqli_stmt_execute($rayhanRPStmt);
    mysqli_stmt_bind_result($rayhanRPStmt, $rayhanRPCount);
    $rayhanRPExists = mysqli_stmt_fetch($rayhanRPStmt) && (int)$rayhanRPCount > 0;
    mysqli_stmt_close($rayhanRPStmt);

    return $rayhanRPExists;
}

function rayhanRPFetchDuplicates($databaseRayhanRP, $rayhanRPTableName, $rayhanRPColumnName)
{
    $rayhanRPDuplicates = [];
    $rayhanRPSql = sprintf(
        'SELECT `%1$s`, COUNT(*) AS total_duplikat FROM `%2$s` WHERE `%1$s` IS NOT NULL AND TRIM(`%1$s`) <> \'\' GROUP BY `%1$s` HAVING COUNT(*) > 1 ORDER BY `%1$s` ASC',
        str_replace('`', '', $rayhanRPColumnName),
        str_replace('`', '', $rayhanRPTableName)
    );

    $rayhanRPResult = mysqli_query($databaseRayhanRP, $rayhanRPSql);
    if (!$rayhanRPResult) {
        return $rayhanRPDuplicates;
    }

    while ($rayhanRPRow = mysqli_fetch_assoc($rayhanRPResult)) {
        $rayhanRPDuplicates[] = [
            'value' => (string)($rayhanRPRow[$rayhanRPColumnName] ?? ''),
            'total' => (int)($rayhanRPRow['total_duplikat'] ?? 0),
        ];
    }
    mysqli_free_result($rayhanRPResult);

    return $rayhanRPDuplicates;
}

function rayhanRPMigrationTableReady($databaseRayhanRP)
{
    $rayhanRPSql = "
        CREATE TABLE IF NOT EXISTS rayhanrp_schema_migrations (
            version INT NOT NULL PRIMARY KEY,
            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";

    return mysqli_query($databaseRayhanRP, $rayhanRPSql) !== false;
}

function rayhanRPMigrationApplied($databaseRayhanRP, $rayhanRPVersion)
{
    $rayhanRPStmt = mysqli_prepare(
        $databaseRayhanRP,
        'SELECT version FROM rayhanrp_schema_migrations WHERE version = ? LIMIT 1'
    );
    if (!$rayhanRPStmt) {
        return false;
    }

    mysqli_stmt_bind_param($rayhanRPStmt, 'i', $rayhanRPVersion);
    mysqli_stmt_execute($rayhanRPStmt);
    mysqli_stmt_bind_result($rayhanRPStmt, $rayhanRPFoundVersion);
    $rayhanRPApplied = mysqli_stmt_fetch($rayhanRPStmt);
    mysqli_stmt_close($rayhanRPStmt);

    return (bool)$rayhanRPApplied;
}

function rayhanRPMarkMigrationApplied($databaseRayhanRP, $rayhanRPVersion)
{
    $rayhanRPStmt = mysqli_prepare(
        $databaseRayhanRP,
        'INSERT INTO rayhanrp_schema_migrations (version, applied_at) VALUES (?, NOW())'
    );
    if (!$rayhanRPStmt) {
        return false;
    }

    mysqli_stmt_bind_param($rayhanRPStmt, 'i', $rayhanRPVersion);
    $rayhanRPOk = mysqli_stmt_execute($rayhanRPStmt);
    mysqli_stmt_close($rayhanRPStmt);

    return $rayhanRPOk;
}

function rayhanRPMigrateProfileColumnsAndIndexes($databaseRayhanRP)
{
    if (!rayhanRPDbColumnExists($databaseRayhanRP, 'akun', 'nama_lengkap')) {
        if (!mysqli_query($databaseRayhanRP, "ALTER TABLE akun ADD COLUMN nama_lengkap VARCHAR(120) DEFAULT NULL AFTER password")) {
            rayhanRPAddSystemIssue('Gagal menambah kolom nama_lengkap pada tabel akun.');
            return false;
        }
    }

    if (!rayhanRPDbColumnExists($databaseRayhanRP, 'akun', 'kelas_label')) {
        if (!mysqli_query($databaseRayhanRP, "ALTER TABLE akun ADD COLUMN kelas_label VARCHAR(100) DEFAULT NULL AFTER nama_lengkap")) {
            rayhanRPAddSystemIssue('Gagal menambah kolom kelas_label pada tabel akun.');
            return false;
        }
    }

    if (!rayhanRPDbColumnExists($databaseRayhanRP, 'akun', 'jenis_kelamin')) {
        if (!mysqli_query($databaseRayhanRP, "ALTER TABLE akun ADD COLUMN jenis_kelamin ENUM('L','P') DEFAULT NULL AFTER kelas_label")) {
            rayhanRPAddSystemIssue('Gagal menambah kolom jenis_kelamin pada tabel akun.');
            return false;
        }
    }

    if (!rayhanRPDbHasUniqueIndexOnColumn($databaseRayhanRP, 'akun', 'nis_nip')) {
        $rayhanRPDuplicates = rayhanRPFetchDuplicates($databaseRayhanRP, 'akun', 'nis_nip');
        if (count($rayhanRPDuplicates) > 0) {
            $rayhanRPIssueLines = [];
            foreach ($rayhanRPDuplicates as $rayhanRPDuplicate) {
                $rayhanRPIssueLines[] = $rayhanRPDuplicate['value'] . ' (' . $rayhanRPDuplicate['total'] . ' data)';
            }
            rayhanRPAddSystemIssue('Ada duplikat nis_nip di tabel akun: ' . implode(', ', $rayhanRPIssueLines) . '.');
            return false;
        }

        if (!mysqli_query($databaseRayhanRP, 'ALTER TABLE akun ADD UNIQUE KEY uniq_akun_nis_nip (nis_nip)')) {
            rayhanRPAddSystemIssue('Gagal membuat unique key pada akun.nis_nip.');
            return false;
        }
    }

    if (!rayhanRPDbIndexExists($databaseRayhanRP, 'grup', 'uniq_grup_owner_name')) {
        $rayhanRPSql = "
            SELECT nama_grup, dibuat_oleh_akun_id, COUNT(*) AS total_duplikat
            FROM grup
            WHERE nama_grup IS NOT NULL
            GROUP BY nama_grup, dibuat_oleh_akun_id
            HAVING COUNT(*) > 1
        ";
        $rayhanRPResult = mysqli_query($databaseRayhanRP, $rayhanRPSql);
        $rayhanRPDuplicateGroups = [];
        if ($rayhanRPResult) {
            while ($rayhanRPRow = mysqli_fetch_assoc($rayhanRPResult)) {
                $rayhanRPDuplicateGroups[] = (string)($rayhanRPRow['nama_grup'] ?? '-') . ' / owner ' . (int)($rayhanRPRow['dibuat_oleh_akun_id'] ?? 0);
            }
            mysqli_free_result($rayhanRPResult);
        }
        if (count($rayhanRPDuplicateGroups) > 0) {
            rayhanRPAddSystemIssue('Ada nama grup ganda untuk pemilik yang sama: ' . implode(', ', $rayhanRPDuplicateGroups) . '.');
            return false;
        }

        if (!mysqli_query($databaseRayhanRP, 'ALTER TABLE grup ADD UNIQUE KEY uniq_grup_owner_name (nama_grup, dibuat_oleh_akun_id)')) {
            rayhanRPAddSystemIssue('Gagal membuat unique key nama grup per pemilik.');
            return false;
        }
    }

    return true;
}

function rayhanRPMigrateSupportTables($databaseRayhanRP)
{
    $rayhanRPSqlList = [
        "
            CREATE TABLE IF NOT EXISTS akun_telegram (
                akun_id INT NOT NULL,
                telegram_chat_id BIGINT NOT NULL,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (akun_id),
                UNIQUE KEY uniq_telegram_chat_id (telegram_chat_id),
                CONSTRAINT fk_akun_telegram_akun FOREIGN KEY (akun_id) REFERENCES akun (akun_id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ",
        "
            CREATE TABLE IF NOT EXISTS tugas_pengumpulan (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ",
        "
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
        ",
    ];

    foreach ($rayhanRPSqlList as $rayhanRPSql) {
        if (!mysqli_query($databaseRayhanRP, $rayhanRPSql)) {
            rayhanRPAddSystemIssue('Gagal menyiapkan tabel pendukung sistem.');
            return false;
        }
    }

    return true;
}

function rayhanRPMigratePreferensiTable($databaseRayhanRP)
{
    $rayhanRPCreateSql = "
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
    if (!mysqli_query($databaseRayhanRP, $rayhanRPCreateSql)) {
        rayhanRPAddSystemIssue('Gagal menyiapkan tabel preferensi.');
        return false;
    }

    if (!rayhanRPDbColumnExists($databaseRayhanRP, 'prefrensi_user', 'offset_custom_menit')) {
        if (!mysqli_query($databaseRayhanRP, 'ALTER TABLE prefrensi_user ADD COLUMN offset_custom_menit INT DEFAULT 30 AFTER waktu_default')) {
            rayhanRPAddSystemIssue('Gagal menambah kolom offset_custom_menit.');
            return false;
        }
    }

    if (!rayhanRPDbColumnExists($databaseRayhanRP, 'prefrensi_user', 'snooze_sampai')) {
        if (!mysqli_query($databaseRayhanRP, 'ALTER TABLE prefrensi_user ADD COLUMN snooze_sampai DATETIME DEFAULT NULL AFTER snooze')) {
            rayhanRPAddSystemIssue('Gagal menambah kolom snooze_sampai.');
            return false;
        }
    }

    if (!mysqli_query(
        $databaseRayhanRP,
        'DELETE pu_old FROM prefrensi_user pu_old INNER JOIN prefrensi_user pu_new ON pu_old.akun_id = pu_new.akun_id AND pu_old.id_preferensi < pu_new.id_preferensi WHERE pu_old.akun_id IS NOT NULL'
    )) {
        rayhanRPAddSystemIssue('Gagal merapikan data preferensi ganda.');
        return false;
    }

    if (!rayhanRPDbIndexExists($databaseRayhanRP, 'prefrensi_user', 'uniq_prefrensi_akun')) {
        if (!mysqli_query($databaseRayhanRP, 'ALTER TABLE prefrensi_user ADD UNIQUE KEY uniq_prefrensi_akun (akun_id)')) {
            rayhanRPAddSystemIssue('Gagal membuat unique key pada preferensi akun.');
            return false;
        }
    }

    return true;
}

function rayhanRPRunMigrations($databaseRayhanRP)
{
    static $rayhanRPMigrationsDone = false;
    static $rayhanRPMigrationsOk = false;

    if ($rayhanRPMigrationsDone) {
        return $rayhanRPMigrationsOk;
    }

    $rayhanRPMigrationsDone = true;
    if (!$databaseRayhanRP) {
        $rayhanRPMigrationsOk = false;
        return false;
    }

    if (!rayhanRPMigrationTableReady($databaseRayhanRP)) {
        rayhanRPAddSystemIssue('Gagal menyiapkan tabel migrasi.');
        $rayhanRPMigrationsOk = false;
        return false;
    }

    $rayhanRPMigrationMap = [
        1 => 'rayhanRPMigrateProfileColumnsAndIndexes',
        2 => 'rayhanRPMigrateSupportTables',
        3 => 'rayhanRPMigratePreferensiTable',
    ];

    foreach ($rayhanRPMigrationMap as $rayhanRPVersion => $rayhanRPCallback) {
        if (rayhanRPMigrationApplied($databaseRayhanRP, $rayhanRPVersion)) {
            continue;
        }

        if (!call_user_func($rayhanRPCallback, $databaseRayhanRP)) {
            $rayhanRPMigrationsOk = false;
            return false;
        }

        if (!rayhanRPMarkMigrationApplied($databaseRayhanRP, $rayhanRPVersion)) {
            rayhanRPAddSystemIssue('Gagal mencatat migrasi schema versi ' . $rayhanRPVersion . '.');
            $rayhanRPMigrationsOk = false;
            return false;
        }
    }

    $rayhanRPMigrationsOk = true;
    return true;
}

function rayhanRPGetDatabase()
{
    static $databaseRayhanRP = null;
    static $rayhanRPAttempted = false;

    if ($rayhanRPAttempted) {
        return $databaseRayhanRP;
    }

    $rayhanRPAttempted = true;
    if (function_exists('mysqli_report')) {
        mysqli_report(MYSQLI_REPORT_OFF);
    }

    if (!function_exists('mysqli_connect')) {
        rayhanRPAddSystemIssue('Ekstensi mysqli tidak tersedia di PHP.');
        return null;
    }

    $rayhanRPDbConfig = (array)rayhanRPGetConfigValue('db', []);
    $databaseRayhanRP = @mysqli_connect(
        (string)($rayhanRPDbConfig['host'] ?? 'localhost'),
        (string)($rayhanRPDbConfig['username'] ?? 'root'),
        (string)($rayhanRPDbConfig['password'] ?? ''),
        (string)($rayhanRPDbConfig['database'] ?? '')
    );
    if (!$databaseRayhanRP) {
        rayhanRPAddSystemIssue('Koneksi database gagal: ' . mysqli_connect_error());
        return null;
    }

    mysqli_set_charset($databaseRayhanRP, (string)($rayhanRPDbConfig['charset'] ?? 'utf8mb4'));
    rayhanRPRunMigrations($databaseRayhanRP);

    return $databaseRayhanRP;
}

function rayhanRPStartSession()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function rayhanRPNormalizeRole($rayhanRPRole)
{
    return strtolower(trim((string)$rayhanRPRole));
}

function rayhanRPFetchAccountById($databaseRayhanRP, $rayhanRPAkunId)
{
    $rayhanRPStmt = mysqli_prepare(
        $databaseRayhanRP,
        "SELECT akun_id, nis_nip, password, role, COALESCE(NULLIF(nama_lengkap, ''), '') AS nama_lengkap, COALESCE(NULLIF(kelas_label, ''), '') AS kelas_label, COALESCE(NULLIF(jenis_kelamin, ''), '') AS jenis_kelamin
         FROM akun
         WHERE akun_id = ?
         LIMIT 1"
    );
    if (!$rayhanRPStmt) {
        return null;
    }

    mysqli_stmt_bind_param($rayhanRPStmt, 'i', $rayhanRPAkunId);
    mysqli_stmt_execute($rayhanRPStmt);
    mysqli_stmt_bind_result(
        $rayhanRPStmt,
        $rayhanRPDbAkunId,
        $rayhanRPDbNisNip,
        $rayhanRPDbPassword,
        $rayhanRPDbRole,
        $rayhanRPDbNama,
        $rayhanRPDbKelas,
        $rayhanRPDbJenisKelamin
    );

    $rayhanRPAkun = null;
    if (mysqli_stmt_fetch($rayhanRPStmt)) {
        $rayhanRPAkun = [
            'akun_id' => (int)$rayhanRPDbAkunId,
            'nis_nip' => (string)$rayhanRPDbNisNip,
            'password' => (string)$rayhanRPDbPassword,
            'role' => rayhanRPNormalizeRole($rayhanRPDbRole),
            'nama_lengkap' => (string)$rayhanRPDbNama,
            'kelas_label' => (string)$rayhanRPDbKelas,
            'jenis_kelamin' => (string)$rayhanRPDbJenisKelamin,
        ];
    }
    mysqli_stmt_close($rayhanRPStmt);

    return $rayhanRPAkun;
}

function rayhanRPFetchAccountByNisNip($databaseRayhanRP, $rayhanRPNisNip)
{
    $rayhanRPStmt = mysqli_prepare(
        $databaseRayhanRP,
        "SELECT akun_id, nis_nip, password, role, COALESCE(NULLIF(nama_lengkap, ''), '') AS nama_lengkap, COALESCE(NULLIF(kelas_label, ''), '') AS kelas_label, COALESCE(NULLIF(jenis_kelamin, ''), '') AS jenis_kelamin
         FROM akun
         WHERE nis_nip = ?
         LIMIT 1"
    );
    if (!$rayhanRPStmt) {
        return null;
    }

    mysqli_stmt_bind_param($rayhanRPStmt, 's', $rayhanRPNisNip);
    mysqli_stmt_execute($rayhanRPStmt);
    mysqli_stmt_bind_result(
        $rayhanRPStmt,
        $rayhanRPDbAkunId,
        $rayhanRPDbNisNip,
        $rayhanRPDbPassword,
        $rayhanRPDbRole,
        $rayhanRPDbNama,
        $rayhanRPDbKelas,
        $rayhanRPDbJenisKelamin
    );

    $rayhanRPAkun = null;
    if (mysqli_stmt_fetch($rayhanRPStmt)) {
        $rayhanRPAkun = [
            'akun_id' => (int)$rayhanRPDbAkunId,
            'nis_nip' => (string)$rayhanRPDbNisNip,
            'password' => (string)$rayhanRPDbPassword,
            'role' => rayhanRPNormalizeRole($rayhanRPDbRole),
            'nama_lengkap' => (string)$rayhanRPDbNama,
            'kelas_label' => (string)$rayhanRPDbKelas,
            'jenis_kelamin' => (string)$rayhanRPDbJenisKelamin,
        ];
    }
    mysqli_stmt_close($rayhanRPStmt);

    return $rayhanRPAkun;
}

function rayhanRPVerifyAccountPassword($databaseRayhanRP, $rayhanRPAkun, $rayhanRPPasswordInput)
{
    $rayhanRPStoredPassword = (string)($rayhanRPAkun['password'] ?? '');
    $rayhanRPPasswordInput = (string)$rayhanRPPasswordInput;
    $rayhanRPIsValid = false;
    $rayhanRPNeedsUpdate = false;
    $rayhanRPNewHash = '';

    if ($rayhanRPStoredPassword !== '' && hash_equals($rayhanRPStoredPassword, $rayhanRPPasswordInput)) {
        $rayhanRPIsValid = true;
        $rayhanRPNeedsUpdate = true;
        $rayhanRPNewHash = (string)password_hash($rayhanRPPasswordInput, PASSWORD_DEFAULT);
    } elseif ($rayhanRPStoredPassword !== '' && function_exists('password_verify') && password_verify($rayhanRPPasswordInput, $rayhanRPStoredPassword)) {
        $rayhanRPIsValid = true;
        if (function_exists('password_needs_rehash') && password_needs_rehash($rayhanRPStoredPassword, PASSWORD_DEFAULT)) {
            $rayhanRPNeedsUpdate = true;
            $rayhanRPNewHash = (string)password_hash($rayhanRPPasswordInput, PASSWORD_DEFAULT);
        }
    }

    if ($rayhanRPIsValid && $rayhanRPNeedsUpdate && $rayhanRPNewHash !== '') {
        $rayhanRPUpdateStmt = mysqli_prepare($databaseRayhanRP, 'UPDATE akun SET password = ? WHERE akun_id = ? LIMIT 1');
        if ($rayhanRPUpdateStmt) {
            $rayhanRPAkunId = (int)($rayhanRPAkun['akun_id'] ?? 0);
            mysqli_stmt_bind_param($rayhanRPUpdateStmt, 'si', $rayhanRPNewHash, $rayhanRPAkunId);
            mysqli_stmt_execute($rayhanRPUpdateStmt);
            mysqli_stmt_close($rayhanRPUpdateStmt);
        }
    }

    return $rayhanRPIsValid;
}

function rayhanRPFormatAccountLabel($rayhanRPNamaLengkap, $rayhanRPNisNip, $rayhanRPRole = '')
{
    $rayhanRPNamaLengkap = trim((string)$rayhanRPNamaLengkap);
    $rayhanRPNisNip = trim((string)$rayhanRPNisNip);
    $rayhanRPRole = trim((string)$rayhanRPRole);

    if ($rayhanRPNamaLengkap !== '' && $rayhanRPNisNip !== '') {
        $rayhanRPLabel = $rayhanRPNamaLengkap . ' (' . $rayhanRPNisNip . ')';
    } elseif ($rayhanRPNamaLengkap !== '') {
        $rayhanRPLabel = $rayhanRPNamaLengkap;
    } else {
        $rayhanRPLabel = $rayhanRPNisNip;
    }

    if ($rayhanRPRole !== '') {
        $rayhanRPLabel .= ' - ' . $rayhanRPRole;
    }

    return $rayhanRPLabel;
}

function rayhanRPAccountLabelFromRow($rayhanRPAkun, $rayhanRPIncludeRole = false)
{
    $rayhanRPRole = $rayhanRPIncludeRole ? (string)($rayhanRPAkun['role'] ?? '') : '';
    return rayhanRPFormatAccountLabel(
        (string)($rayhanRPAkun['nama_lengkap'] ?? ''),
        (string)($rayhanRPAkun['nis_nip'] ?? ''),
        $rayhanRPRole
    );
}

function rayhanRPGetAdminSession($rayhanRPRefreshFromDb = true)
{
    rayhanRPStartSession();

    if (empty($_SESSION['rayhanRP_admin_login'])) {
        return null;
    }

    $rayhanRPAkunId = (int)($_SESSION['rayhanRP_admin_id'] ?? 0);
    if ($rayhanRPAkunId <= 0) {
        return null;
    }

    $rayhanRPAdmin = [
        'akun_id' => $rayhanRPAkunId,
        'nis_nip' => (string)($_SESSION['rayhanRP_admin_nis_nip'] ?? ''),
        'nama_lengkap' => (string)($_SESSION['rayhanRP_admin_nama'] ?? ''),
        'role' => rayhanRPNormalizeRole($_SESSION['rayhanRP_admin_role'] ?? ''),
    ];

    if ($rayhanRPRefreshFromDb) {
        $databaseRayhanRP = rayhanRPGetDatabase();
        if ($databaseRayhanRP) {
            $rayhanRPAkunDb = rayhanRPFetchAccountById($databaseRayhanRP, $rayhanRPAkunId);
            if (!$rayhanRPAkunDb) {
                return null;
            }

            $rayhanRPAdmin['nis_nip'] = (string)$rayhanRPAkunDb['nis_nip'];
            $rayhanRPAdmin['nama_lengkap'] = (string)$rayhanRPAkunDb['nama_lengkap'];
            $rayhanRPAdmin['role'] = (string)$rayhanRPAkunDb['role'];

            $_SESSION['rayhanRP_admin_nis_nip'] = $rayhanRPAdmin['nis_nip'];
            $_SESSION['rayhanRP_admin_nama'] = $rayhanRPAdmin['nama_lengkap'];
            $_SESSION['rayhanRP_admin_role'] = $rayhanRPAdmin['role'];
        }
    }

    if ($rayhanRPAdmin['role'] !== 'admin' && $rayhanRPAdmin['role'] !== 'guru') {
        return null;
    }

    $rayhanRPAdmin['can_access_all'] = ($rayhanRPAdmin['role'] === 'admin');
    $rayhanRPAdmin['label'] = rayhanRPFormatAccountLabel($rayhanRPAdmin['nama_lengkap'], $rayhanRPAdmin['nis_nip'], $rayhanRPAdmin['role']);

    return $rayhanRPAdmin;
}

function rayhanRPLogoutAdmin($rayhanRPLoginPath = 'loginAdmin_rayhanRP.php')
{
    rayhanRPStartSession();
    $_SESSION = [];
    session_unset();
    session_destroy();
    header('Location: ' . $rayhanRPLoginPath);
    exit;
}

function rayhanRPRequireAdminSession($rayhanRPLoginPath = 'loginAdmin_rayhanRP.php')
{
    $rayhanRPAdmin = rayhanRPGetAdminSession(true);
    if ($rayhanRPAdmin === null) {
        rayhanRPLogoutAdmin($rayhanRPLoginPath);
    }

    return $rayhanRPAdmin;
}

function rayhanRPFetchOwnerAccounts($databaseRayhanRP)
{
    $rayhanRPAkunList = [];
    if (!$databaseRayhanRP) {
        return $rayhanRPAkunList;
    }

    $rayhanRPSql = "
        SELECT akun_id, nis_nip, COALESCE(NULLIF(nama_lengkap, ''), '') AS nama_lengkap, role
        FROM akun
        WHERE role IN ('admin', 'guru')
        ORDER BY role DESC, nama_lengkap ASC, nis_nip ASC
    ";
    $rayhanRPResult = mysqli_query($databaseRayhanRP, $rayhanRPSql);
    if (!$rayhanRPResult) {
        return $rayhanRPAkunList;
    }

    while ($rayhanRPRow = mysqli_fetch_assoc($rayhanRPResult)) {
        $rayhanRPRow['akun_id'] = (int)($rayhanRPRow['akun_id'] ?? 0);
        $rayhanRPRow['nis_nip'] = (string)($rayhanRPRow['nis_nip'] ?? '');
        $rayhanRPRow['nama_lengkap'] = (string)($rayhanRPRow['nama_lengkap'] ?? '');
        $rayhanRPRow['role'] = rayhanRPNormalizeRole($rayhanRPRow['role'] ?? '');
        $rayhanRPAkunList[] = $rayhanRPRow;
    }
    mysqli_free_result($rayhanRPResult);

    return $rayhanRPAkunList;
}

function rayhanRPFetchPreferensiByAkun($databaseRayhanRP, $rayhanRPAkunId)
{
    $rayhanRPStmt = mysqli_prepare(
        $databaseRayhanRP,
        "SELECT id_preferensi, COALESCE(pengingat_aktif, 1), COALESCE(waktu_default, '08:00:00'), COALESCE(offset_custom_menit, 30)
         FROM prefrensi_user
         WHERE akun_id = ?
         LIMIT 1"
    );
    if (!$rayhanRPStmt) {
        return null;
    }

    mysqli_stmt_bind_param($rayhanRPStmt, 'i', $rayhanRPAkunId);
    mysqli_stmt_execute($rayhanRPStmt);
    mysqli_stmt_bind_result(
        $rayhanRPStmt,
        $rayhanRPIdPreferensi,
        $rayhanRPPengingatAktif,
        $rayhanRPWaktuDefault,
        $rayhanRPOffsetCustomMenit
    );

    $rayhanRPData = null;
    if (mysqli_stmt_fetch($rayhanRPStmt)) {
        $rayhanRPData = [
            'id_preferensi' => (int)$rayhanRPIdPreferensi,
            'akun_id' => (int)$rayhanRPAkunId,
            'pengingat_aktif' => (int)$rayhanRPPengingatAktif,
            'waktu_default' => (string)$rayhanRPWaktuDefault,
            'offset_custom_menit' => (int)$rayhanRPOffsetCustomMenit,
        ];
    }
    mysqli_stmt_close($rayhanRPStmt);

    return $rayhanRPData;
}

function rayhanRPEnsurePreferensiRowCommon($databaseRayhanRP, $rayhanRPAkunId)
{
    $rayhanRPData = rayhanRPFetchPreferensiByAkun($databaseRayhanRP, $rayhanRPAkunId);
    if ($rayhanRPData !== null) {
        return $rayhanRPData;
    }

    $rayhanRPPengingatAktif = 1;
    $rayhanRPWaktuDefault = '08:00:00';
    $rayhanRPOffset = 30;
    $rayhanRPStmt = mysqli_prepare(
        $databaseRayhanRP,
        'INSERT INTO prefrensi_user (akun_id, pengingat_aktif, waktu_default, offset_custom_menit) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE akun_id = VALUES(akun_id)'
    );
    if (!$rayhanRPStmt) {
        return null;
    }

    mysqli_stmt_bind_param(
        $rayhanRPStmt,
        'iisi',
        $rayhanRPAkunId,
        $rayhanRPPengingatAktif,
        $rayhanRPWaktuDefault,
        $rayhanRPOffset
    );
    $rayhanRPOk = mysqli_stmt_execute($rayhanRPStmt);
    mysqli_stmt_close($rayhanRPStmt);

    if (!$rayhanRPOk) {
        return null;
    }

    return rayhanRPFetchPreferensiByAkun($databaseRayhanRP, $rayhanRPAkunId);
}

function rayhanRPUpsertAkunTelegram($databaseRayhanRP, $rayhanRPAkunId, $rayhanRPChatId)
{
    $rayhanRPChatId = (string)$rayhanRPChatId;
    $rayhanRPStmt = mysqli_prepare(
        $databaseRayhanRP,
        'INSERT INTO akun_telegram (akun_id, telegram_chat_id, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE telegram_chat_id = VALUES(telegram_chat_id), updated_at = NOW()'
    );
    if (!$rayhanRPStmt) {
        return false;
    }

    mysqli_stmt_bind_param($rayhanRPStmt, 'is', $rayhanRPAkunId, $rayhanRPChatId);
    $rayhanRPOk = mysqli_stmt_execute($rayhanRPStmt);
    mysqli_stmt_close($rayhanRPStmt);

    return $rayhanRPOk;
}

function rayhanRPGetTelegramBotToken()
{
    return (string)rayhanRPGetConfigValue('telegram.bot_token', '');
}

function rayhanRPGetTelegramTimeout()
{
    return (int)rayhanRPGetConfigValue('telegram.timeout', 15);
}

function rayhanRPCallTelegramApi($rayhanRPMethod, $rayhanRPParams = [])
{
    $rayhanRPToken = rayhanRPGetTelegramBotToken();
    if ($rayhanRPToken === '') {
        return false;
    }

    $rayhanRPUrl = 'https://api.telegram.org/bot' . $rayhanRPToken . '/' . ltrim((string)$rayhanRPMethod, '/');
    $rayhanRPPostFields = http_build_query($rayhanRPParams);
    $rayhanRPTimeout = max(5, rayhanRPGetTelegramTimeout());
    $rayhanRPResponse = false;

    if (function_exists('curl_init')) {
        $rayhanRPCurl = curl_init($rayhanRPUrl);
        if ($rayhanRPCurl !== false) {
            curl_setopt_array($rayhanRPCurl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $rayhanRPPostFields,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $rayhanRPTimeout,
                CURLOPT_CONNECTTIMEOUT => min(10, $rayhanRPTimeout),
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $rayhanRPResponse = curl_exec($rayhanRPCurl);
            curl_close($rayhanRPCurl);
        }
    }

    if ($rayhanRPResponse === false) {
        $rayhanRPContext = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $rayhanRPPostFields,
                'timeout' => $rayhanRPTimeout,
                'ignore_errors' => true,
            ],
        ]);
        $rayhanRPResponse = @file_get_contents($rayhanRPUrl, false, $rayhanRPContext);
    }

    if (!is_string($rayhanRPResponse) || $rayhanRPResponse === '') {
        return false;
    }

    $rayhanRPDecoded = json_decode($rayhanRPResponse, true);
    return (is_array($rayhanRPDecoded) && !empty($rayhanRPDecoded['ok'])) ? $rayhanRPDecoded : false;
}

function rayhanRPGetTelegramFileInfo($rayhanRPFileId)
{
    $rayhanRPResponse = rayhanRPCallTelegramApi('getFile', ['file_id' => (string)$rayhanRPFileId]);
    if (!$rayhanRPResponse || !isset($rayhanRPResponse['result']) || !is_array($rayhanRPResponse['result'])) {
        return null;
    }

    return $rayhanRPResponse['result'];
}

function rayhanRPDownloadTelegramFileBinary($rayhanRPFilePath)
{
    $rayhanRPToken = rayhanRPGetTelegramBotToken();
    if ($rayhanRPToken === '') {
        return false;
    }

    $rayhanRPUrl = 'https://api.telegram.org/file/bot' . $rayhanRPToken . '/' . ltrim((string)$rayhanRPFilePath, '/');
    $rayhanRPTimeout = max(5, rayhanRPGetTelegramTimeout());

    if (function_exists('curl_init')) {
        $rayhanRPCurl = curl_init($rayhanRPUrl);
        if ($rayhanRPCurl !== false) {
            curl_setopt_array($rayhanRPCurl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $rayhanRPTimeout,
                CURLOPT_CONNECTTIMEOUT => min(10, $rayhanRPTimeout),
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $rayhanRPBinary = curl_exec($rayhanRPCurl);
            curl_close($rayhanRPCurl);
            if (is_string($rayhanRPBinary) && $rayhanRPBinary !== '') {
                return $rayhanRPBinary;
            }
        }
    }

    $rayhanRPContext = stream_context_create([
        'http' => [
            'timeout' => $rayhanRPTimeout,
            'ignore_errors' => true,
        ],
    ]);

    return @file_get_contents($rayhanRPUrl, false, $rayhanRPContext);
}
