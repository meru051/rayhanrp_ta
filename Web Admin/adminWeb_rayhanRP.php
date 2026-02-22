<?php
require_once __DIR__ . '/../koneksi_rayhanRP.php';
session_start();

if (empty($_SESSION['rayhanRP_admin_login'])) {
    header('Location: loginAdmin_rayhanRP.php');
    exit;
}

if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    session_unset();
    session_destroy();
    header('Location: loginAdmin_rayhanRP.php');
    exit;
}

$rayhanRPAdminId = (int)($_SESSION['rayhanRP_admin_id'] ?? 0);
$rayhanRPAdminNisNip = (string)($_SESSION['rayhanRP_admin_nis_nip'] ?? 'admin');

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
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Bot SiRey</title>
    <style>
        :root {
            --bg-main: #f3f6fb;
            --bg-side-a: #0f172a;
            --bg-side-b: #1e293b;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-sub: #64748b;
            --line: #e2e8f0;
            --blue: #2563eb;
            --blue-dark: #1d4ed8;
            --cyan: #0891b2;
            --green: #15803d;
            --orange: #d97706;
            --red: #dc2626;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Poppins", "Segoe UI", sans-serif;
            color: var(--text-main);
            background: linear-gradient(180deg, #eef3ff 0%, var(--bg-main) 45%);
        }

        .layout {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 270px 1fr;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--bg-side-a), var(--bg-side-b));
            color: #e2e8f0;
            padding: 24px 18px;
            border-right: 1px solid rgba(148, 163, 184, 0.25);
        }

        .brand {
            margin: 0 0 24px;
            padding: 0 10px 20px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
        }

        .brand h1 {
            margin: 0;
            font-size: 1.2rem;
            color: #f8fafc;
            font-weight: 700;
        }

        .brand p {
            margin: 8px 0 0;
            font-size: 0.88rem;
            color: #cbd5e1;
        }

        .menu {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 8px;
        }

        .menu a {
            display: block;
            text-decoration: none;
            padding: 11px 12px;
            border-radius: 10px;
            color: #dbeafe;
            font-size: 0.95rem;
            transition: background 0.15s ease;
        }

        .menu a:hover,
        .menu a.active {
            background: rgba(37, 99, 235, 0.28);
        }

        .main {
            padding: 22px;
        }

        .topbar {
            background: var(--card-bg);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 16px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }

        .topbar h2 {
            margin: 0;
            font-size: 1.2rem;
        }

        .topbar p {
            margin: 4px 0 0;
            color: var(--text-sub);
            font-size: 0.9rem;
        }

        .logout-btn {
            border: 0;
            text-decoration: none;
            background: var(--blue);
            color: #fff;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .logout-btn:hover {
            background: var(--blue-dark);
        }

        .stats {
            margin-top: 18px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 14px;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.04);
        }

        .stat-title {
            margin: 0;
            color: var(--text-sub);
            font-size: 0.85rem;
            font-weight: 600;
        }

        .stat-value {
            margin: 8px 0 0;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .stat-1 .stat-value {
            color: var(--blue);
        }

        .stat-2 .stat-value {
            color: var(--cyan);
        }

        .stat-3 .stat-value {
            color: var(--green);
        }

        .stat-4 .stat-value {
            color: var(--orange);
        }

        .content-grid {
            margin-top: 18px;
            display: grid;
            grid-template-columns: 1.35fr 1fr;
            gap: 14px;
        }

        .panel {
            background: var(--card-bg);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.04);
        }

        .panel h3 {
            margin: 0;
            font-size: 1.02rem;
        }

        .panel-sub {
            margin: 6px 0 0;
            color: var(--text-sub);
            font-size: 0.86rem;
        }

        .activity-list {
            margin: 14px 0 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 10px;
        }

        .activity-list li {
            border: 1px solid var(--line);
            border-left: 4px solid var(--blue);
            border-radius: 10px;
            padding: 10px 11px;
        }

        .activity-title {
            margin: 0;
            font-size: 0.92rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .activity-time {
            margin: 5px 0 0;
            font-size: 0.82rem;
            color: var(--text-sub);
        }

        .table-wrap {
            margin-top: 12px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 500px;
        }

        th,
        td {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 1px solid var(--line);
            font-size: 0.88rem;
        }

        th {
            font-size: 0.82rem;
            text-transform: uppercase;
            color: var(--text-sub);
            letter-spacing: 0.03em;
        }

        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 0.77rem;
            font-weight: 600;
            color: #fff;
        }

        .status-ok {
            background: var(--green);
        }

        .status-wait {
            background: var(--orange);
        }

        .status-alert {
            background: var(--red);
        }

        @media (max-width: 1080px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                border-right: 0;
                border-bottom: 1px solid rgba(148, 163, 184, 0.25);
            }

            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .main {
                padding: 14px;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="brand">
                <h1>Bot SiRey Admin</h1>
                <p>Panel monitoring dan pengelolaan sistem.</p>
            </div>
            <ul class="menu">
                <li><a class="active" href="#">Dashboard</a></li>
                <li><a href="grup_rayhanRP.php">Grup</a></li>
                <li><a href="jadwal_rayhanRP.php">Jadwal</a></li>
                <li><a href="tugas_rayhanRP.php">Tugas</a></li>
                <li><a href="#">Notifikasi</a></li>
                <li><a href="#">Pengaturan</a></li>
            </ul>
        </aside>

        <main class="main">
            <section class="topbar">
                <div>
                    <h2>Selamat datang, <?php echo htmlspecialchars($rayhanRPAdminNisNip, ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p>ID Admin: <?php echo $rayhanRPAdminId > 0 ? $rayhanRPAdminId : '-'; ?> | Ringkasan sistem hari ini</p>
                </div>
                <a class="logout-btn" href="?logout=1">Logout</a>
            </section>

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
                    <h3>Status Modul Admin</h3>
                    <p class="panel-sub">Ringkasan cepat kondisi fitur utama.</p>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Modul</th>
                                    <th>Status</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Autentikasi</td>
                                    <td><span class="status status-ok">Aktif</span></td>
                                    <td>Login hash berjalan normal.</td>
                                </tr>
                                <tr>
                                    <td>Jadwal</td>
                                    <td><span class="status status-wait">Pantau</span></td>
                                    <td>Periksa sinkronisasi data harian.</td>
                                </tr>
                                <tr>
                                    <td>Notifikasi</td>
                                    <td><span class="status status-ok">Aktif</span></td>
                                    <td>Log notifikasi tersedia.</td>
                                </tr>
                                <tr>
                                    <td>Backup</td>
                                    <td><span class="status status-alert">Manual</span></td>
                                    <td>Jadwalkan backup database rutin.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>
            </section>
        </main>
    </div>
</body>

</html>
