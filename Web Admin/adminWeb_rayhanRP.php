<?php
require_once __DIR__ . '/../koneksi_rayhanRP.php';
require_once __DIR__ . '/includes/admin_layout_rayhanRP.php';

rayhanRPStartSession();

if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    rayhanRPLogoutAdmin('loginAdmin_rayhanRP.php');
}

$rayhanRPAdmin = rayhanRPRequireAdminSession('loginAdmin_rayhanRP.php');
$rayhanRPAdminId = (int)$rayhanRPAdmin['akun_id'];
$rayhanRPAdminNisNip = (string)$rayhanRPAdmin['nis_nip'];
$rayhanRPAdminLabel = (string)$rayhanRPAdmin['label'];
$rayhanRPAdminRole = (string)$rayhanRPAdmin['role'];

$rayhanRPStats = [
    'Total Akun' => '-',
    'Total Jadwal' => '-',
    'Total Tugas' => '-',
    'Total Notifikasi' => '-',
];

if ($databaseRayhanRP) {
    $rayhanRPQueryMap = [
        'Total Akun' => "SELECT COUNT(*) AS total FROM akun",
        'Total Jadwal' => "SELECT COUNT(*) AS total FROM jadwal",
        'Total Tugas' => "SELECT COUNT(*) AS total FROM tugas",
        'Total Notifikasi' => "SELECT COUNT(*) AS total FROM notifikasi",
    ];

    foreach ($rayhanRPQueryMap as $rayhanRPLabel => $rayhanRPSql) {
        $rayhanRPResult = mysqli_query($databaseRayhanRP, $rayhanRPSql);
        if ($rayhanRPResult) {
            $rayhanRPRow = mysqli_fetch_assoc($rayhanRPResult);
            $rayhanRPStats[$rayhanRPLabel] = (string)($rayhanRPRow['total'] ?? '0');
            mysqli_free_result($rayhanRPResult);
        }
    }
}

$rayhanRPRecentNotifikasi = [];
if ($databaseRayhanRP) {
    $rayhanRPNotifSql = "SELECT id_notifikasi, pesan, waktu_kirim FROM notifikasi ORDER BY id_notifikasi DESC LIMIT 5";
    $rayhanRPNotifResult = mysqli_query($databaseRayhanRP, $rayhanRPNotifSql);
    if ($rayhanRPNotifResult) {
        while ($rayhanRPNotifRow = mysqli_fetch_assoc($rayhanRPNotifResult)) {
            $rayhanRPRecentNotifikasi[] = $rayhanRPNotifRow;
        }
        mysqli_free_result($rayhanRPNotifResult);
    }
}

$rayhanRPPageTitle = 'Dashboard Overview';
$rayhanRPPageSubtitle = 'Selamat datang, ' . htmlspecialchars($rayhanRPAdminLabel, ENT_QUOTES, 'UTF-8') . ' | ID akun: ' . ($rayhanRPAdminId > 0 ? (int)$rayhanRPAdminId : '-') . ' | Ringkasan sistem saat ini';

rayhanRPRenderAdminLayoutStart([
    'title' => $rayhanRPPageTitle,
    'subtitle' => $rayhanRPPageSubtitle,
    'page_key' => 'dashboard',
    'admin' => $rayhanRPAdmin,
    'topbar_actions' => '<span class="overview-pill">' . htmlspecialchars(strtoupper($rayhanRPAdminRole), ENT_QUOTES, 'UTF-8') . ' | ' . htmlspecialchars($rayhanRPAdminNisNip, ENT_QUOTES, 'UTF-8') . '</span>',
]);
?>
<div class="page-stack">
    <section class="stats">
        <article class="stat-card stat-1">
            <p class="stat-title">Total Akun</p>
            <p class="stat-value"><?php echo htmlspecialchars($rayhanRPStats['Total Akun'], ENT_QUOTES, 'UTF-8'); ?></p>
        </article>
        <article class="stat-card stat-2">
            <p class="stat-title">Total Jadwal</p>
            <p class="stat-value"><?php echo htmlspecialchars($rayhanRPStats['Total Jadwal'], ENT_QUOTES, 'UTF-8'); ?></p>
        </article>
        <article class="stat-card stat-3">
            <p class="stat-title">Total Tugas</p>
            <p class="stat-value"><?php echo htmlspecialchars($rayhanRPStats['Total Tugas'], ENT_QUOTES, 'UTF-8'); ?></p>
        </article>
        <article class="stat-card stat-4">
            <p class="stat-title">Total Notifikasi</p>
            <p class="stat-value"><?php echo htmlspecialchars($rayhanRPStats['Total Notifikasi'], ENT_QUOTES, 'UTF-8'); ?></p>
        </article>
    </section>

    <section class="content-grid">
        <article class="panel">
            <h3>Aktivitas Notifikasi Terbaru</h3>
            <p class="panel-sub">Riwayat pengiriman notifikasi terakhir dari sistem.</p>

            <?php if (count($rayhanRPRecentNotifikasi) === 0): ?>
                <ul class="activity-list">
                    <li>
                        <p class="activity-title">Belum ada data notifikasi terbaru.</p>
                        <p class="activity-time">Coba lakukan pengiriman notifikasi dari modul terkait.</p>
                    </li>
                </ul>
            <?php else: ?>
                <ul class="activity-list">
                    <?php foreach ($rayhanRPRecentNotifikasi as $rayhanRPNotif): ?>
                        <li>
                            <p class="activity-title">#<?php echo (int)($rayhanRPNotif['id_notifikasi'] ?? 0); ?> - <?php echo htmlspecialchars((string)($rayhanRPNotif['pesan'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p class="activity-time"><?php echo htmlspecialchars((string)($rayhanRPNotif['waktu_kirim'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>

        <article class="panel">
            <h3>Panduan Pemula</h3>
            <p class="panel-sub">Urutan paling aman untuk penggunaan pertama kali.</p>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Langkah</th>
                            <th>Aksi</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td><a href="grup_rayhanRP.php">Buat Grup</a></td>
                            <td>Mulai dari grup kelas/kelompok sebelum membuat jadwal dan tugas.</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td><a href="jadwal_rayhanRP.php">Input Jadwal</a></td>
                            <td>Pastikan tanggal dan jam terisi benar agar pengingat otomatis tepat.</td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td><a href="tugas_rayhanRP.php">Buat Tugas</a></td>
                            <td>Tambahkan tenggat tugas, lalu cek pengumpulan siswa.</td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td><a href="notifikasi_rayhanRP.php">Kirim Notifikasi</a></td>
                            <td>Gunakan pesan manual untuk broadcast.</td>
                        </tr>
                        <tr>
                            <td>5</td>
                            <td><a href="riwayat_notifikasi_rayhanRP.php">Cek Riwayat</a></td>
                            <td>Verifikasi pesan terkirim dan audit aktivitas notifikasi.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</div>
<?php rayhanRPRenderAdminLayoutEnd(); ?>
