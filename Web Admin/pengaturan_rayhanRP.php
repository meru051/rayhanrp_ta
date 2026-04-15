<?php
require_once __DIR__ . '/../koneksi_rayhanRP.php';
rayhanRPStartSession();

$rayhanRPAdmin = rayhanRPRequireAdminSession('loginAdmin_rayhanRP.php');
$rayhanRPAdminId = (int)$rayhanRPAdmin['akun_id'];
$rayhanRPAdminNisNip = (string)$rayhanRPAdmin['nis_nip'];
$rayhanRPAdminRole = (string)$rayhanRPAdmin['role'];
$rayhanRPAdminLabel = (string)$rayhanRPAdmin['label'];

$rayhanRPError = '';
$rayhanRPSuccess = '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Bot SiRey</title>
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
            --red: #dc2626;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Poppins", "Segoe UI", sans-serif;
            color: var(--text-main);
            background: linear-gradient(180deg, #eef3ff 0%, var(--bg-main) 45%);
        }
        .layout { min-height: 100vh; display: grid; grid-template-columns: 270px 1fr; }
        .sidebar {
            background: linear-gradient(180deg, var(--bg-side-a), var(--bg-side-b));
            color: #e2e8f0;
            padding: 24px 18px;
            border-right: 1px solid rgba(148, 163, 184, 0.25);
        }
        .brand { margin: 0 0 24px; padding: 0 10px 20px; border-bottom: 1px solid rgba(148, 163, 184, 0.25); }
        .brand h1 { margin: 0; font-size: 1.2rem; color: #f8fafc; font-weight: 700; }
        .brand p { margin: 8px 0 0; font-size: 0.88rem; color: #cbd5e1; }
        .menu { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
        .menu a { display: block; text-decoration: none; padding: 11px 12px; border-radius: 10px; color: #dbeafe; font-size: 0.95rem; }
        .menu a:hover, .menu a.active { background: rgba(37, 99, 235, 0.28); }
        .main { padding: 22px; }
        .topbar {
            background: var(--card-bg); border: 1px solid var(--line); border-radius: 14px;
            padding: 16px 18px; display: flex; justify-content: space-between; align-items: center;
            gap: 12px; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }
        .topbar h2 { margin: 0; font-size: 1.2rem; }
        .topbar p { margin: 4px 0 0; color: var(--text-sub); font-size: 0.9rem; }
        .logout-btn {
            border: 0; text-decoration: none; background: var(--blue); color: #fff;
            padding: 10px 14px; border-radius: 10px; font-size: 0.9rem; font-weight: 600;
        }
        .panel {
            margin-top: 18px; background: var(--card-bg); border: 1px solid var(--line); border-radius: 14px;
            padding: 16px; box-shadow: 0 10px 20px rgba(15, 23, 42, 0.04);
        }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .field { display: grid; gap: 6px; }
        .field label { font-size: 0.84rem; color: #334155; font-weight: 600; }
        .field input, .field textarea {
            width: 100%; border: 1px solid #cbd5e1; border-radius: 9px; padding: 10px; font: inherit;
        }
        .field textarea { min-height: 110px; resize: vertical; }
        .field.full { grid-column: 1 / -1; }
        .btn {
            border: 0; background: var(--blue); color: #fff; border-radius: 9px;
            padding: 10px 14px; font-weight: 600; cursor: pointer;
        }
        .btn.secondary { background: #64748b; }
        .btn.danger { background: var(--red); }
        .msg { margin-top: 10px; padding: 10px 12px; border-radius: 10px; font-size: 0.9rem; }
        .error { background: #fee2e2; color: #991b1b; }
        .ok { background: #dcfce7; color: #166534; }
        .info { background: #f8fafc; border: 1px dashed #cbd5e1; color: #475569; }
        .table-wrap { margin-top: 12px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid var(--line); font-size: 0.88rem; vertical-align: top; }
        th { font-size: 0.8rem; text-transform: uppercase; color: #64748b; }
        .actions { display: flex; gap: 8px; }
        .actions form { margin: 0; }
        code { background: #f1f5f9; padding: 2px 4px; border-radius: 4px; }
        @media (max-width: 1080px) { .layout { grid-template-columns: 1fr; } .sidebar { border-right: 0; border-bottom: 1px solid rgba(148,163,184,0.25);} }
        @media (max-width: 780px) { .grid { grid-template-columns: 1fr; } .main { padding: 14px; } .topbar { flex-direction: column; align-items: flex-start; } }
    </style>
    <link rel="stylesheet" href="assets/admin_theme_rayhanRP.css">
</head>
<body class="admin-app">
<div class="layout">
    <aside class="sidebar">
        <div class="brand">
            <h1>Bot SiRey Admin</h1>
            <p>Panel monitoring dan pengelolaan sistem.</p>
        </div>
        <div class="sidebar-profile">
            <div class="sidebar-avatar"><?php echo htmlspecialchars(strtoupper(substr($rayhanRPAdminLabel !== '' ? $rayhanRPAdminLabel : $rayhanRPAdminNisNip, 0, 1)), ENT_QUOTES, 'UTF-8'); ?></div>
            <div>
                <strong><?php echo htmlspecialchars($rayhanRPAdminLabel, ENT_QUOTES, 'UTF-8'); ?></strong>
                <span><?php echo htmlspecialchars(strtoupper($rayhanRPAdminRole), ENT_QUOTES, 'UTF-8'); ?> | <?php echo htmlspecialchars($rayhanRPAdminNisNip, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </div>
        <ul class="menu">
            <li><a href="adminWeb_rayhanRP.php">Dashboard</a></li>
            <li><a href="grup_rayhanRP.php">Grup</a></li>
            <li><a href="jadwal_rayhanRP.php">Jadwal</a></li>
            <li><a href="tugas_rayhanRP.php">Tugas</a></li>
            <li><a href="notifikasi_rayhanRP.php">Notifikasi</a></li>
            <li><a href="riwayat_notifikasi_rayhanRP.php">Riwayat</a></li>
            <li><a href="import_excel_rayhanRP.php">Import Excel</a></li>
            <li><a class="active" href="pengaturan_rayhanRP.php">Pengaturan</a></li>
        </ul>
    </aside>

    <main class="main">
        <section class="topbar">
            <div>
                <h2>Pengaturan Sistem</h2>
                <p><?php echo htmlspecialchars($rayhanRPAdminNisNip, ENT_QUOTES, 'UTF-8'); ?> | Kelola operasional cron dan catatan sistem</p>
            </div>
            <a class="logout-btn" href="adminWeb_rayhanRP.php?logout=1">Logout</a>
        </section>

        <section class="panel">
            <h3>Fitur Template Dinonaktifkan</h3>
            <div class="msg info">
                Template pada modul notifikasi dan pengaturan sudah dihapus. Pengiriman notifikasi sekarang menggunakan pesan manual langsung dari halaman notifikasi.
            </div>

            <?php if ($rayhanRPError !== ''): ?>
                <div class="msg error"><?php echo htmlspecialchars($rayhanRPError, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($rayhanRPSuccess !== ''): ?>
                <div class="msg ok"><?php echo htmlspecialchars($rayhanRPSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
        </section>

        <section class="panel">
            <h3>Operasional Pengingat Otomatis</h3>
            <div class="msg info">
                Jalankan script cron ini setiap 1 menit menggunakan Task Scheduler Windows:<br>
                <code>D:\APLIKASI\xampp\php\php.exe D:\APLIKASI\xampp\htdocs\bot_sirey\cron_pengingat_rayhanRP.php</code><br><br>
                Bot membaca preferensi user dari <code>prefrensi_user</code>, menghormati <code>snooze_sampai</code>, lalu kirim notifikasi jadwal/tugas dan mencatat log ke tabel <code>notifikasi</code>.
            </div>
        </section>
    </main>
</div>
</body>
</html>

