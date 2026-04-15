<?php
require_once __DIR__ . '/../koneksi_rayhanRP.php';
require_once __DIR__ . '/../function/function_bot_rayhanRP.php';
require_once __DIR__ . '/includes/admin_layout_rayhanRP.php';
rayhanRPStartSession();

$rayhanRPAdmin = rayhanRPRequireAdminSession('loginAdmin_rayhanRP.php');
$rayhanRPAdminId = (int)$rayhanRPAdmin['akun_id'];
$rayhanRPAdminNisNip = (string)$rayhanRPAdmin['nis_nip'];
$rayhanRPAdminRole = (string)$rayhanRPAdmin['role'];
$rayhanRPAdminLabel = (string)$rayhanRPAdmin['label'];

$rayhanRPCanAccessAll = (bool)$rayhanRPAdmin['can_access_all'];
$rayhanRPError = '';
$rayhanRPSuccess = '';
$rayhanRPTargetType = 'all';
$rayhanRPSelectedGroup = 0;
$rayhanRPNisListRaw = '';
$rayhanRPPesan = '';
$rayhanRPSentCount = 0;
$rayhanRPFailedCount = 0;

function rayhanRPNotifEnsureAkunTelegramTable($db)
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

function rayhanRPNotifFetchGroups($db, $adminId, $canAll)
{
    if ($canAll) {
        $sql = "SELECT id_grup, nama_grup FROM grup ORDER BY nama_grup ASC";
        $result = mysqli_query($db, $sql);
        if (!$result) {
            return [];
        }

        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = [
                'id_grup' => (int)($row['id_grup'] ?? 0),
                'nama_grup' => (string)($row['nama_grup'] ?? '-'),
            ];
        }
        mysqli_free_result($result);
        return $rows;
    }

    $stmt = mysqli_prepare($db, 'SELECT id_grup, nama_grup FROM grup WHERE dibuat_oleh_akun_id = ? ORDER BY nama_grup ASC');
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, 'i', $adminId);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return [];
    }

    mysqli_stmt_bind_result($stmt, $idGrup, $namaGrup);
    $rows = [];
    while (mysqli_stmt_fetch($stmt)) {
        $rows[] = [
            'id_grup' => (int)$idGrup,
            'nama_grup' => (string)$namaGrup,
        ];
    }

    mysqli_stmt_close($stmt);
    return $rows;
}
function rayhanRPNotifCanUseGroup($groups, $groupId)
{
    foreach ($groups as $group) {
        if ((int)$group['id_grup'] === (int)$groupId) {
            return true;
        }
    }
    return false;
}

function rayhanRPNotifInsertLog($db, $akunId, $pesan)
{
    $jadwalId = null;
    $tugasId = null;
    $templateId = null;
    $stmt = mysqli_prepare(
        $db,
        'INSERT INTO notifikasi (akun_id, jadwal_id, tugas_id, template_id, waktu_kirim, pesan) VALUES (?, ?, ?, ?, NOW(), ?)'
    );
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'iiiis', $akunId, $jadwalId, $tugasId, $templateId, $pesan);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function rayhanRPNotifUserInGuruScope($db, $guruId, $akunId)
{
    $stmt = mysqli_prepare(
        $db,
        'SELECT ga.akun_id
         FROM grup_anggota ga
         INNER JOIN grup g ON g.id_grup = ga.grup_id
         WHERE ga.akun_id = ? AND ga.deleted_at IS NULL AND g.dibuat_oleh_akun_id = ?
         LIMIT 1'
    );
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'ii', $akunId, $guruId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $foundAkunId);
    $ok = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return (bool)$ok;
}

function rayhanRPNotifFindRecipientByNis($db, $nisNip)
{
    $stmt = mysqli_prepare(
        $db,
        'SELECT a.akun_id, a.nis_nip, at.telegram_chat_id
         FROM akun a
         INNER JOIN akun_telegram at ON at.akun_id = a.akun_id
         WHERE a.nis_nip = ?
         LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 's', $nisNip);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $akunId, $dbNis, $chatId);
    $data = null;
    if (mysqli_stmt_fetch($stmt)) {
        $data = [
            'akun_id' => (int)$akunId,
            'nis_nip' => (string)$dbNis,
            'telegram_chat_id' => (string)$chatId,
        ];
    }
    mysqli_stmt_close($stmt);
    return $data;
}

function rayhanRPNotifNormalizeNisList($raw)
{
    $parts = preg_split('/[\s,;]+/', trim((string)$raw));
    $out = [];
    foreach ($parts as $part) {
        $value = trim((string)$part);
        if ($value === '') {
            continue;
        }
        $out[] = $value;
    }
    return array_values(array_unique($out));
}

function rayhanRPNotifFetchRecipientsByGroup($db, $groupId)
{
    $stmt = mysqli_prepare(
        $db,
        'SELECT DISTINCT a.akun_id, a.nis_nip, at.telegram_chat_id
         FROM grup_anggota ga
         INNER JOIN akun a ON a.akun_id = ga.akun_id
         INNER JOIN akun_telegram at ON at.akun_id = a.akun_id
         WHERE ga.grup_id = ? AND ga.deleted_at IS NULL
         ORDER BY a.nis_nip ASC'
    );
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, 'i', $groupId);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return [];
    }

    mysqli_stmt_bind_result($stmt, $akunId, $nisNip, $chatId);
    $rows = [];
    while (mysqli_stmt_fetch($stmt)) {
        $rows[] = [
            'akun_id' => (int)$akunId,
            'nis_nip' => (string)$nisNip,
            'telegram_chat_id' => (string)$chatId,
        ];
    }

    mysqli_stmt_close($stmt);
    return $rows;
}
function rayhanRPNotifFetchRecipientsAll($db, $adminId, $canAll)
{
    if ($canAll) {
        $sql = 'SELECT a.akun_id, a.nis_nip, at.telegram_chat_id FROM akun a INNER JOIN akun_telegram at ON at.akun_id = a.akun_id ORDER BY a.nis_nip ASC';
        $result = mysqli_query($db, $sql);
        if (!$result) {
            return [];
        }

        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = [
                'akun_id' => (int)($row['akun_id'] ?? 0),
                'nis_nip' => (string)($row['nis_nip'] ?? ''),
                'telegram_chat_id' => (string)($row['telegram_chat_id'] ?? ''),
            ];
        }
        mysqli_free_result($result);
        return $rows;
    }

    $sql = "
        SELECT DISTINCT a.akun_id, a.nis_nip, at.telegram_chat_id
        FROM grup g
        INNER JOIN grup_anggota ga ON ga.grup_id = g.id_grup AND ga.deleted_at IS NULL
        INNER JOIN akun a ON a.akun_id = ga.akun_id
        INNER JOIN akun_telegram at ON at.akun_id = a.akun_id
        WHERE g.dibuat_oleh_akun_id = ?
        ORDER BY a.nis_nip ASC
    ";
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, 'i', $adminId);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return [];
    }

    mysqli_stmt_bind_result($stmt, $akunId, $nisNip, $chatId);
    $rows = [];
    while (mysqli_stmt_fetch($stmt)) {
        $rows[] = [
            'akun_id' => (int)$akunId,
            'nis_nip' => (string)$nisNip,
            'telegram_chat_id' => (string)$chatId,
        ];
    }

    mysqli_stmt_close($stmt);
    return $rows;
}
if ($databaseRayhanRP && !rayhanRPNotifEnsureAkunTelegramTable($databaseRayhanRP)) {
    $rayhanRPError = 'Gagal menyiapkan tabel akun_telegram.';
}

$rayhanRPGroups = $databaseRayhanRP ? rayhanRPNotifFetchGroups($databaseRayhanRP, $rayhanRPAdminId, $rayhanRPCanAccessAll) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rayhanRPTargetType = trim((string)($_POST['target_type'] ?? 'all'));
    $rayhanRPSelectedGroup = (int)($_POST['group_id'] ?? 0);
    $rayhanRPNisListRaw = trim((string)($_POST['nis_list'] ?? ''));
    $rayhanRPPesan = trim((string)($_POST['pesan'] ?? ''));

    if (!$databaseRayhanRP) {
        $rayhanRPError = 'Koneksi database gagal.';
    } elseif ($rayhanRPPesan === '') {
        $rayhanRPError = 'Pesan notifikasi wajib diisi.';
    } elseif (!in_array($rayhanRPTargetType, ['all', 'group', 'nis'], true)) {
        $rayhanRPError = 'Target notifikasi tidak valid.';
    }

    $rayhanRPRecipients = [];
    if ($rayhanRPError === '') {
        if ($rayhanRPTargetType === 'all') {
            $rayhanRPRecipients = rayhanRPNotifFetchRecipientsAll($databaseRayhanRP, $rayhanRPAdminId, $rayhanRPCanAccessAll);
        } elseif ($rayhanRPTargetType === 'group') {
            if ($rayhanRPSelectedGroup <= 0 || !rayhanRPNotifCanUseGroup($rayhanRPGroups, $rayhanRPSelectedGroup)) {
                $rayhanRPError = 'Grup target tidak valid atau tidak dapat diakses.';
            } else {
                $rayhanRPRecipients = rayhanRPNotifFetchRecipientsByGroup($databaseRayhanRP, $rayhanRPSelectedGroup);
            }
        } else {
            $rayhanRPNisList = rayhanRPNotifNormalizeNisList($rayhanRPNisListRaw);
            if (count($rayhanRPNisList) === 0) {
                $rayhanRPError = 'Daftar NIS/NIP untuk target manual masih kosong.';
            } else {
                foreach ($rayhanRPNisList as $rayhanRPNis) {
                    $recipient = rayhanRPNotifFindRecipientByNis($databaseRayhanRP, $rayhanRPNis);
                    if (!$recipient) {
                        continue;
                    }
                    if (!$rayhanRPCanAccessAll && !rayhanRPNotifUserInGuruScope($databaseRayhanRP, $rayhanRPAdminId, (int)$recipient['akun_id'])) {
                        continue;
                    }
                    $rayhanRPRecipients[] = $recipient;
                }
            }
        }
    }

    if ($rayhanRPError === '') {
        if (count($rayhanRPRecipients) === 0) {
            $rayhanRPError = 'Tidak ada penerima valid. Pastikan akun sudah login bot agar chat_id tersimpan.';
        } else {
            $unique = [];
            foreach ($rayhanRPRecipients as $recipient) {
                $key = (string)$recipient['akun_id'];
                if ($key === '' || isset($unique[$key])) {
                    continue;
                }
                $unique[$key] = $recipient;
            }

            foreach ($unique as $recipient) {
                $chatId = (string)($recipient['telegram_chat_id'] ?? '');
                $akunId = (int)($recipient['akun_id'] ?? 0);
                if ($chatId === '' || $akunId <= 0) {
                    $rayhanRPFailedCount++;
                    continue;
                }

                sendMessage($chatId, $rayhanRPPesan);
                if (rayhanRPNotifInsertLog($databaseRayhanRP, $akunId, $rayhanRPPesan)) {
                    $rayhanRPSentCount++;
                } else {
                    $rayhanRPFailedCount++;
                }
            }

            if ($rayhanRPSentCount > 0) {
                $rayhanRPSuccess = 'Notifikasi berhasil dikirim ke ' . $rayhanRPSentCount . ' akun.';
            }
            if ($rayhanRPSentCount === 0) {
                $rayhanRPError = 'Notifikasi gagal terkirim. Periksa koneksi bot atau data akun_telegram.';
            }
        }
    }
}

$rayhanRPPageTitle = 'Notifikasi Manual';
$rayhanRPPageSubtitle = 'Login sebagai ' . htmlspecialchars($rayhanRPAdminNisNip, ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($rayhanRPAdminRole, ENT_QUOTES, 'UTF-8') . ') | Kirim broadcast ke akun, grup, atau daftar NIS/NIP tertentu.';
rayhanRPRenderAdminLayoutStart([
    'title' => $rayhanRPPageTitle,
    'subtitle' => $rayhanRPPageSubtitle,
    'page_key' => 'notifikasi',
    'admin' => $rayhanRPAdmin,
]);
?>
<div class="page-stack">
    <section class="panel">
        <h3>Kirim Broadcast / Notifikasi</h3>
        <p class="sub">Target dapat dipilih ke semua akun terhubung, per grup, atau daftar NIS/NIP tertentu.</p>

        <?php if ($rayhanRPError !== ''): ?>
            <div class="msg error"><?php echo htmlspecialchars($rayhanRPError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($rayhanRPSuccess !== ''): ?>
            <div class="msg ok"><?php echo htmlspecialchars($rayhanRPSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($rayhanRPFailedCount > 0): ?>
            <div class="msg warn">Sebagian gagal dikirim/log: <?php echo (int)$rayhanRPFailedCount; ?> akun.</div>
        <?php endif; ?>

        <form method="post">
            <div class="form-grid">
                <div class="field">
                    <label for="target_type">Target</label>
                    <select id="target_type" name="target_type" required>
                        <option value="all" <?php echo $rayhanRPTargetType === 'all' ? 'selected' : ''; ?>>Semua akun terhubung</option>
                        <option value="group" <?php echo $rayhanRPTargetType === 'group' ? 'selected' : ''; ?>>Berdasarkan grup</option>
                        <option value="nis" <?php echo $rayhanRPTargetType === 'nis' ? 'selected' : ''; ?>>Daftar NIS/NIP manual</option>
                    </select>
                </div>

                <div class="field" id="target-group-field">
                    <label for="group_id">Grup (jika target grup)</label>
                    <select id="group_id" name="group_id">
                        <option value="0">Pilih grup</option>
                        <?php foreach ($rayhanRPGroups as $group): ?>
                            <option value="<?php echo (int)$group['id_grup']; ?>" <?php echo (int)$group['id_grup'] === $rayhanRPSelectedGroup ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($group['nama_grup'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field full" id="target-nis-field">
                    <label for="nis_list">Daftar NIS/NIP (jika target manual)</label>
                    <textarea id="nis_list" name="nis_list" placeholder="Contoh: 10243313, 102306363, akun_tes"><?php echo htmlspecialchars($rayhanRPNisListRaw, ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="field full">
                    <label for="pesan">Pesan Notifikasi</label>
                    <textarea id="pesan" name="pesan" required placeholder="Tulis pesan notifikasi..."><?php echo htmlspecialchars($rayhanRPPesan, ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="field full field-actions">
                    <button class="btn-primary" type="submit">Kirim Notifikasi</button>
                </div>
            </div>
        </form>

        <div class="info-box">
            Catatan: akun penerima harus pernah login ke bot Telegram agar data <code>chat_id</code> tersimpan di tabel <code>akun_telegram</code>.
        </div>
    </section>
</div>
<script>
    (function () {
        var targetType = document.getElementById('target_type');
        var groupField = document.getElementById('target-group-field');
        var nisField = document.getElementById('target-nis-field');
        var groupInput = document.getElementById('group_id');
        var nisInput = document.getElementById('nis_list');

        function updateTargetFields() {
            if (!targetType) {
                return;
            }
            var mode = targetType.value;
            if (groupField) {
                groupField.style.display = mode === 'group' ? 'block' : 'none';
            }
            if (nisField) {
                nisField.style.display = mode === 'nis' ? 'block' : 'none';
            }
            if (groupInput) {
                groupInput.disabled = mode !== 'group';
            }
            if (nisInput) {
                nisInput.disabled = mode !== 'nis';
            }
        }

        if (targetType) {
            targetType.addEventListener('change', updateTargetFields);
            updateTargetFields();
        }
    })();
</script>
<?php rayhanRPRenderAdminLayoutEnd(); ?>
