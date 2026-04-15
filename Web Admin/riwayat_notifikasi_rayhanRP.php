<?php
require_once __DIR__ . '/../koneksi_rayhanRP.php';
require_once __DIR__ . '/includes/admin_layout_rayhanRP.php';
rayhanRPStartSession();

$rayhanRPAdmin = rayhanRPRequireAdminSession('loginAdmin_rayhanRP.php');
$rayhanRPAdminId = (int)$rayhanRPAdmin['akun_id'];
$rayhanRPAdminNisNip = (string)$rayhanRPAdmin['nis_nip'];
$rayhanRPAdminRole = (string)$rayhanRPAdmin['role'];
$rayhanRPAdminLabel = (string)$rayhanRPAdmin['label'];

$rayhanRPCanAccessAll = ($rayhanRPAdminRole === 'admin');
$rayhanRPError = '';

$rayhanRPJenis = trim((string)($_GET['jenis'] ?? 'all'));
$rayhanRPQ = trim((string)($_GET['q'] ?? ''));
$rayhanRPNis = trim((string)($_GET['nis_nip'] ?? ''));
$rayhanRPTanggalDari = trim((string)($_GET['tanggal_dari'] ?? ''));
$rayhanRPTanggalSampai = trim((string)($_GET['tanggal_sampai'] ?? ''));
$rayhanRPPage = (int)($_GET['page'] ?? 1);
if ($rayhanRPPage < 1) {
    $rayhanRPPage = 1;
}
$rayhanRPPerPage = 30;
$rayhanRPOffset = ($rayhanRPPage - 1) * $rayhanRPPerPage;

$rayhanRPAllowedJenis = ['all', 'manual', 'jadwal', 'tugas'];
if (!in_array($rayhanRPJenis, $rayhanRPAllowedJenis, true)) {
    $rayhanRPJenis = 'all';
}

$rayhanRPRows = [];
$rayhanRPTotalRows = 0;

if (!$databaseRayhanRP) {
    $rayhanRPError = 'Koneksi database gagal.';
} else {
    $rayhanRPWhere = ' WHERE 1=1 ';

    if (!$rayhanRPCanAccessAll) {
        $aid = (int)$rayhanRPAdminId;
        $rayhanRPWhere .= "
            AND (
                n.akun_id = {$aid}
                OR EXISTS (
                    SELECT 1
                    FROM grup_anggota ga
                    INNER JOIN grup g ON g.id_grup = ga.grup_id
                    WHERE ga.akun_id = n.akun_id
                      AND ga.deleted_at IS NULL
                      AND g.dibuat_oleh_akun_id = {$aid}
                )
                OR EXISTS (
                    SELECT 1
                    FROM jadwal j2
                    INNER JOIN grup g2 ON g2.id_grup = j2.grup_id
                    WHERE j2.id_jadwal = n.jadwal_id
                      AND g2.dibuat_oleh_akun_id = {$aid}
                )
                OR EXISTS (
                    SELECT 1
                    FROM tugas t2
                    INNER JOIN grup g3 ON g3.id_grup = t2.grup_id
                    WHERE t2.id_tugas = n.tugas_id
                      AND (t2.dibuat_oleh_akun_id = {$aid} OR g3.dibuat_oleh_akun_id = {$aid})
                )
            )
        ";
    }

    if ($rayhanRPJenis === 'manual') {
        $rayhanRPWhere .= ' AND n.jadwal_id IS NULL AND n.tugas_id IS NULL ';
    } elseif ($rayhanRPJenis === 'jadwal') {
        $rayhanRPWhere .= ' AND n.jadwal_id IS NOT NULL ';
    } elseif ($rayhanRPJenis === 'tugas') {
        $rayhanRPWhere .= ' AND n.tugas_id IS NOT NULL ';
    }

    if ($rayhanRPQ !== '') {
        $q = mysqli_real_escape_string($databaseRayhanRP, $rayhanRPQ);
        $rayhanRPWhere .= " AND n.pesan LIKE '%{$q}%' ";
    }

    if ($rayhanRPNis !== '') {
        $nis = mysqli_real_escape_string($databaseRayhanRP, $rayhanRPNis);
        $rayhanRPWhere .= " AND a.nis_nip LIKE '%{$nis}%' ";
    }

    if ($rayhanRPTanggalDari !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $rayhanRPTanggalDari) === 1) {
        $dari = mysqli_real_escape_string($databaseRayhanRP, $rayhanRPTanggalDari . ' 00:00:00');
        $rayhanRPWhere .= " AND n.waktu_kirim >= '{$dari}' ";
    }
    if ($rayhanRPTanggalSampai !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $rayhanRPTanggalSampai) === 1) {
        $sampai = mysqli_real_escape_string($databaseRayhanRP, $rayhanRPTanggalSampai . ' 23:59:59');
        $rayhanRPWhere .= " AND n.waktu_kirim <= '{$sampai}' ";
    }

    $countSql = "
        SELECT COUNT(*) AS total
        FROM notifikasi n
        LEFT JOIN akun a ON a.akun_id = n.akun_id
        {$rayhanRPWhere}
    ";
    $countRes = mysqli_query($databaseRayhanRP, $countSql);
    if ($countRes) {
        $countRow = mysqli_fetch_assoc($countRes);
        $rayhanRPTotalRows = (int)($countRow['total'] ?? 0);
        mysqli_free_result($countRes);
    }

    $listSql = "
        SELECT
            n.id_notifikasi,
            n.akun_id,
            COALESCE(a.nis_nip, '-') AS nis_nip,
            n.jadwal_id,
            n.tugas_id,
            n.waktu_kirim,
            n.pesan
        FROM notifikasi n
        LEFT JOIN akun a ON a.akun_id = n.akun_id
        {$rayhanRPWhere}
        ORDER BY n.id_notifikasi DESC
        LIMIT {$rayhanRPPerPage} OFFSET {$rayhanRPOffset}
    ";
    $listRes = mysqli_query($databaseRayhanRP, $listSql);
    if ($listRes) {
        while ($row = mysqli_fetch_assoc($listRes)) {
            $rayhanRPRows[] = $row;
        }
        mysqli_free_result($listRes);
    } else {
        $rayhanRPError = 'Gagal membaca riwayat notifikasi.';
    }
}

$rayhanRPTotalPages = $rayhanRPTotalRows > 0 ? (int)ceil($rayhanRPTotalRows / $rayhanRPPerPage) : 1;
if ($rayhanRPPage > $rayhanRPTotalPages) {
    $rayhanRPPage = $rayhanRPTotalPages;
}

function rayhanRPJenisLabel($jadwalId, $tugasId)
{
    if ((int)$jadwalId > 0) {
        return 'Jadwal';
    }
    if ((int)$tugasId > 0) {
        return 'Tugas';
    }
    return 'Manual';
}

function rayhanRPBuildPageUrl($page)
{
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

$rayhanRPPageTitle = 'Riwayat Notifikasi';
$rayhanRPPageSubtitle = htmlspecialchars($rayhanRPAdminNisNip, ENT_QUOTES, 'UTF-8') . ' | Total data: ' . (int)$rayhanRPTotalRows;
rayhanRPRenderAdminLayoutStart([
    'title' => $rayhanRPPageTitle,
    'subtitle' => $rayhanRPPageSubtitle,
    'page_key' => 'riwayat',
    'admin' => $rayhanRPAdmin,
]);
?>
<div class="page-stack">
    <section class="panel">
        <form method="get" class="filters">
            <div class="field">
                <label for="jenis">Jenis</label>
                <select id="jenis" name="jenis">
                    <option value="all" <?php echo $rayhanRPJenis === 'all' ? 'selected' : ''; ?>>Semua</option>
                    <option value="manual" <?php echo $rayhanRPJenis === 'manual' ? 'selected' : ''; ?>>Manual</option>
                    <option value="jadwal" <?php echo $rayhanRPJenis === 'jadwal' ? 'selected' : ''; ?>>Jadwal</option>
                    <option value="tugas" <?php echo $rayhanRPJenis === 'tugas' ? 'selected' : ''; ?>>Tugas</option>
                </select>
            </div>
            <div class="field">
                <label for="nis_nip">NIS/NIP</label>
                <input id="nis_nip" name="nis_nip" value="<?php echo htmlspecialchars($rayhanRPNis, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Cari NIS/NIP">
            </div>
            <div class="field wide">
                <label for="q">Pesan</label>
                <input id="q" name="q" value="<?php echo htmlspecialchars($rayhanRPQ, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Cari isi pesan">
            </div>
            <div class="field">
                <label for="tanggal_dari">Dari</label>
                <input type="date" id="tanggal_dari" name="tanggal_dari" value="<?php echo htmlspecialchars($rayhanRPTanggalDari, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="field">
                <label for="tanggal_sampai">Sampai</label>
                <input type="date" id="tanggal_sampai" name="tanggal_sampai" value="<?php echo htmlspecialchars($rayhanRPTanggalSampai, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="field full">
                <label>&nbsp;</label>
                <div class="filter-actions">
                    <button class="btn" type="submit">Filter</button>
                    <a class="btn secondary" href="riwayat_notifikasi_rayhanRP.php">Reset</a>
                </div>
            </div>
        </form>

        <?php if ($rayhanRPError !== ''): ?>
            <div class="msg error"><?php echo htmlspecialchars($rayhanRPError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Jenis</th>
                    <th>NIS/NIP</th>
                    <th>Ref</th>
                    <th>Waktu Kirim</th>
                    <th>Pesan</th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($rayhanRPRows) === 0): ?>
                    <tr>
                        <td colspan="6" class="muted">Belum ada data pada filter ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rayhanRPRows as $row): ?>
                        <?php
                        $jenisLabel = rayhanRPJenisLabel($row['jadwal_id'] ?? null, $row['tugas_id'] ?? null);
                        $jenisTagClass = '';
                        if (strtolower($jenisLabel) === 'manual') {
                            $jenisTagClass = 'warn';
                        } elseif (strtolower($jenisLabel) === 'tugas') {
                            $jenisTagClass = 'ok';
                        }
                        ?>
                        <tr>
                            <td>#<?php echo (int)($row['id_notifikasi'] ?? 0); ?></td>
                            <td><span class="tag <?php echo htmlspecialchars($jenisTagClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($jenisLabel, ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo htmlspecialchars((string)($row['nis_nip'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php if ((int)($row['jadwal_id'] ?? 0) > 0): ?>
                                    Jadwal #<?php echo (int)$row['jadwal_id']; ?>
                                <?php elseif ((int)($row['tugas_id'] ?? 0) > 0): ?>
                                    Tugas #<?php echo (int)$row['tugas_id']; ?>
                                <?php else: ?>
                                    Manual
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars((string)($row['waktu_kirim'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo nl2br(htmlspecialchars((string)($row['pesan'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <?php if ($rayhanRPPage > 1): ?>
                <a href="<?php echo htmlspecialchars(rayhanRPBuildPageUrl($rayhanRPPage - 1), ENT_QUOTES, 'UTF-8'); ?>">Prev</a>
            <?php endif; ?>
            <?php
            $start = max(1, $rayhanRPPage - 2);
            $end = min($rayhanRPTotalPages, $rayhanRPPage + 2);
            for ($p = $start; $p <= $end; $p++):
            ?>
                <a class="<?php echo $p === $rayhanRPPage ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(rayhanRPBuildPageUrl($p), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $p; ?></a>
            <?php endfor; ?>
            <?php if ($rayhanRPPage < $rayhanRPTotalPages): ?>
                <a href="<?php echo htmlspecialchars(rayhanRPBuildPageUrl($rayhanRPPage + 1), ENT_QUOTES, 'UTF-8'); ?>">Next</a>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php rayhanRPRenderAdminLayoutEnd(); ?>
