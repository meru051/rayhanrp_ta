<?php
require_once __DIR__ . '/../koneksi_rayhanRP.php';
session_start();

if (empty($_SESSION['rayhanRP_admin_login'])) {
    header('Location: loginAdmin_rayhanRP.php');
    exit;
}

$rayhanRPAdminId = (int)($_SESSION['rayhanRP_admin_id'] ?? 0);
$rayhanRPAdminNisNip = (string)($_SESSION['rayhanRP_admin_nis_nip'] ?? 'admin');

$rayhanRPError = '';
$rayhanRPSuccess = '';
$rayhanRPAdminRole = 'guru';

if ($databaseRayhanRP && $rayhanRPAdminId > 0) {
    $rayhanRPRoleStmt = mysqli_prepare($databaseRayhanRP, "SELECT role FROM akun WHERE akun_id = ? LIMIT 1");
    if ($rayhanRPRoleStmt) {
        mysqli_stmt_bind_param($rayhanRPRoleStmt, "i", $rayhanRPAdminId);
        mysqli_stmt_execute($rayhanRPRoleStmt);
        mysqli_stmt_bind_result($rayhanRPRoleStmt, $rayhanRPDbRole);
        if (mysqli_stmt_fetch($rayhanRPRoleStmt)) {
            $rayhanRPAdminRole = (string)$rayhanRPDbRole;
        }
        mysqli_stmt_close($rayhanRPRoleStmt);
    }
}

$rayhanRPCanAccessAll = ($rayhanRPAdminRole === 'admin');

function rayhanRP_fetchAvailableGrup($databaseRayhanRP, $rayhanRPAdminId, $rayhanRPCanAccessAll)
{
    $rayhanRPGrupList = [];

    if (!$databaseRayhanRP) {
        return $rayhanRPGrupList;
    }

    if ($rayhanRPCanAccessAll) {
        $rayhanRPGrupSql = "SELECT id_grup, nama_grup FROM grup ORDER BY nama_grup ASC";
        $rayhanRPGrupResult = mysqli_query($databaseRayhanRP, $rayhanRPGrupSql);
        if ($rayhanRPGrupResult) {
            while ($rayhanRPRow = mysqli_fetch_assoc($rayhanRPGrupResult)) {
                $rayhanRPGrupList[] = $rayhanRPRow;
            }
            mysqli_free_result($rayhanRPGrupResult);
        }
    } else {
        $rayhanRPGrupStmt = mysqli_prepare(
            $databaseRayhanRP,
            "SELECT id_grup, nama_grup FROM grup WHERE dibuat_oleh_akun_id = ? ORDER BY nama_grup ASC"
        );
        if ($rayhanRPGrupStmt) {
            mysqli_stmt_bind_param($rayhanRPGrupStmt, "i", $rayhanRPAdminId);
            mysqli_stmt_execute($rayhanRPGrupStmt);
            mysqli_stmt_bind_result($rayhanRPGrupStmt, $rayhanRPIdGrup, $rayhanRPNamaGrup);
            while (mysqli_stmt_fetch($rayhanRPGrupStmt)) {
                $rayhanRPGrupList[] = [
                    'id_grup' => $rayhanRPIdGrup,
                    'nama_grup' => $rayhanRPNamaGrup,
                ];
            }
            mysqli_stmt_close($rayhanRPGrupStmt);
        }
    }

    return $rayhanRPGrupList;
}

function rayhanRP_isGrupAllowed($databaseRayhanRP, $rayhanRPAdminId, $rayhanRPCanAccessAll, $rayhanRPGrupId)
{
    if ($rayhanRPCanAccessAll) {
        $rayhanRPCheckStmt = mysqli_prepare($databaseRayhanRP, "SELECT id_grup FROM grup WHERE id_grup = ? LIMIT 1");
        if (!$rayhanRPCheckStmt) {
            return false;
        }
        mysqli_stmt_bind_param($rayhanRPCheckStmt, "i", $rayhanRPGrupId);
    } else {
        $rayhanRPCheckStmt = mysqli_prepare(
            $databaseRayhanRP,
            "SELECT id_grup FROM grup WHERE id_grup = ? AND dibuat_oleh_akun_id = ? LIMIT 1"
        );
        if (!$rayhanRPCheckStmt) {
            return false;
        }
        mysqli_stmt_bind_param($rayhanRPCheckStmt, "ii", $rayhanRPGrupId, $rayhanRPAdminId);
    }

    mysqli_stmt_execute($rayhanRPCheckStmt);
    mysqli_stmt_bind_result($rayhanRPCheckStmt, $rayhanRPFoundId);
    $rayhanRPExists = mysqli_stmt_fetch($rayhanRPCheckStmt);
    mysqli_stmt_close($rayhanRPCheckStmt);
    return (bool)$rayhanRPExists;
}

$rayhanRPFormData = [
    'id_jadwal' => 0,
    'grup_id' => '',
    'judul' => '',
    'deskripsi' => '',
    'tanggal' => '',
    'jam_mulai' => '',
    'jam_selesai' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$databaseRayhanRP) {
        $rayhanRPError = 'Koneksi database gagal.';
    } else {
        $rayhanRPAction = (string)($_POST['action'] ?? '');

        if ($rayhanRPAction === 'create' || $rayhanRPAction === 'update') {
            $rayhanRPFormData['id_jadwal'] = (int)($_POST['id_jadwal'] ?? 0);
            $rayhanRPFormData['grup_id'] = trim((string)($_POST['grup_id'] ?? ''));
            $rayhanRPFormData['judul'] = trim((string)($_POST['judul'] ?? ''));
            $rayhanRPFormData['deskripsi'] = trim((string)($_POST['deskripsi'] ?? ''));
            $rayhanRPFormData['tanggal'] = trim((string)($_POST['tanggal'] ?? ''));
            $rayhanRPFormData['jam_mulai'] = trim((string)($_POST['jam_mulai'] ?? ''));
            $rayhanRPFormData['jam_selesai'] = trim((string)($_POST['jam_selesai'] ?? ''));

            $rayhanRPGrupId = (int)$rayhanRPFormData['grup_id'];
            $rayhanRPIdJadwal = (int)$rayhanRPFormData['id_jadwal'];

            if ($rayhanRPGrupId <= 0 || $rayhanRPFormData['judul'] === '' || $rayhanRPFormData['tanggal'] === '' || $rayhanRPFormData['jam_mulai'] === '' || $rayhanRPFormData['jam_selesai'] === '') {
                $rayhanRPError = 'Semua field wajib harus diisi.';
            } elseif (!rayhanRP_isGrupAllowed($databaseRayhanRP, $rayhanRPAdminId, $rayhanRPCanAccessAll, $rayhanRPGrupId)) {
                $rayhanRPError = 'Anda tidak memiliki akses ke grup tersebut.';
            } else {
                if ($rayhanRPAction === 'create') {
                    $rayhanRPInsertStmt = mysqli_prepare(
                        $databaseRayhanRP,
                        "INSERT INTO jadwal (grup_id, judul, deskripsi, tanggal, jam_mulai, jam_selesai) VALUES (?, ?, ?, ?, ?, ?)"
                    );
                    if (!$rayhanRPInsertStmt) {
                        $rayhanRPError = 'Gagal menyiapkan query tambah jadwal.';
                    } else {
                        mysqli_stmt_bind_param(
                            $rayhanRPInsertStmt,
                            "isssss",
                            $rayhanRPGrupId,
                            $rayhanRPFormData['judul'],
                            $rayhanRPFormData['deskripsi'],
                            $rayhanRPFormData['tanggal'],
                            $rayhanRPFormData['jam_mulai'],
                            $rayhanRPFormData['jam_selesai']
                        );
                        if (mysqli_stmt_execute($rayhanRPInsertStmt)) {
                            $rayhanRPSuccess = 'Jadwal berhasil ditambahkan.';
                            $rayhanRPFormData = [
                                'id_jadwal' => 0,
                                'grup_id' => '',
                                'judul' => '',
                                'deskripsi' => '',
                                'tanggal' => '',
                                'jam_mulai' => '',
                                'jam_selesai' => '',
                            ];
                        } else {
                            $rayhanRPError = 'Gagal menambahkan jadwal.';
                        }
                        mysqli_stmt_close($rayhanRPInsertStmt);
                    }
                } else {
                    if ($rayhanRPIdJadwal <= 0) {
                        $rayhanRPError = 'ID jadwal tidak valid.';
                    } else {
                        if ($rayhanRPCanAccessAll) {
                            $rayhanRPUpdateStmt = mysqli_prepare(
                                $databaseRayhanRP,
                                "UPDATE jadwal SET grup_id = ?, judul = ?, deskripsi = ?, tanggal = ?, jam_mulai = ?, jam_selesai = ? WHERE id_jadwal = ? LIMIT 1"
                            );
                            if ($rayhanRPUpdateStmt) {
                                mysqli_stmt_bind_param(
                                    $rayhanRPUpdateStmt,
                                    "isssssi",
                                    $rayhanRPGrupId,
                                    $rayhanRPFormData['judul'],
                                    $rayhanRPFormData['deskripsi'],
                                    $rayhanRPFormData['tanggal'],
                                    $rayhanRPFormData['jam_mulai'],
                                    $rayhanRPFormData['jam_selesai'],
                                    $rayhanRPIdJadwal
                                );
                            }
                        } else {
                            $rayhanRPUpdateStmt = mysqli_prepare(
                                $databaseRayhanRP,
                                "UPDATE jadwal j INNER JOIN grup g ON g.id_grup = j.grup_id SET j.grup_id = ?, j.judul = ?, j.deskripsi = ?, j.tanggal = ?, j.jam_mulai = ?, j.jam_selesai = ? WHERE j.id_jadwal = ? AND g.dibuat_oleh_akun_id = ?"
                            );
                            if ($rayhanRPUpdateStmt) {
                                mysqli_stmt_bind_param(
                                    $rayhanRPUpdateStmt,
                                    "isssssii",
                                    $rayhanRPGrupId,
                                    $rayhanRPFormData['judul'],
                                    $rayhanRPFormData['deskripsi'],
                                    $rayhanRPFormData['tanggal'],
                                    $rayhanRPFormData['jam_mulai'],
                                    $rayhanRPFormData['jam_selesai'],
                                    $rayhanRPIdJadwal,
                                    $rayhanRPAdminId
                                );
                            }
                        }

                        if (!$rayhanRPUpdateStmt) {
                            $rayhanRPError = 'Gagal menyiapkan query edit jadwal.';
                        } else {
                            if (mysqli_stmt_execute($rayhanRPUpdateStmt) && mysqli_stmt_affected_rows($rayhanRPUpdateStmt) >= 0) {
                                $rayhanRPSuccess = 'Jadwal berhasil diperbarui.';
                            } else {
                                $rayhanRPError = 'Gagal memperbarui jadwal.';
                            }
                            mysqli_stmt_close($rayhanRPUpdateStmt);
                        }
                    }
                }
            }
        } elseif ($rayhanRPAction === 'delete') {
            $rayhanRPIdJadwal = (int)($_POST['id_jadwal'] ?? 0);
            if ($rayhanRPIdJadwal <= 0) {
                $rayhanRPError = 'ID jadwal tidak valid.';
            } else {
                if ($rayhanRPCanAccessAll) {
                    $rayhanRPDeleteStmt = mysqli_prepare($databaseRayhanRP, "DELETE FROM jadwal WHERE id_jadwal = ? LIMIT 1");
                    if ($rayhanRPDeleteStmt) {
                        mysqli_stmt_bind_param($rayhanRPDeleteStmt, "i", $rayhanRPIdJadwal);
                    }
                } else {
                    $rayhanRPDeleteStmt = mysqli_prepare(
                        $databaseRayhanRP,
                        "DELETE j FROM jadwal j INNER JOIN grup g ON g.id_grup = j.grup_id WHERE j.id_jadwal = ? AND g.dibuat_oleh_akun_id = ?"
                    );
                    if ($rayhanRPDeleteStmt) {
                        mysqli_stmt_bind_param($rayhanRPDeleteStmt, "ii", $rayhanRPIdJadwal, $rayhanRPAdminId);
                    }
                }

                if (!$rayhanRPDeleteStmt) {
                    $rayhanRPError = 'Gagal menyiapkan query hapus jadwal.';
                } else {
                    if (mysqli_stmt_execute($rayhanRPDeleteStmt) && mysqli_stmt_affected_rows($rayhanRPDeleteStmt) > 0) {
                        $rayhanRPSuccess = 'Jadwal berhasil dihapus.';
                    } else {
                        $rayhanRPError = 'Jadwal tidak ditemukan atau tidak bisa dihapus.';
                    }
                    mysqli_stmt_close($rayhanRPDeleteStmt);
                }
            }
        }
    }
}

$rayhanRPEditId = (int)($_GET['edit'] ?? 0);
if ($rayhanRPEditId > 0 && $databaseRayhanRP) {
    if ($rayhanRPCanAccessAll) {
        $rayhanRPEditStmt = mysqli_prepare(
            $databaseRayhanRP,
            "SELECT j.id_jadwal, j.grup_id, j.judul, j.deskripsi, j.tanggal, j.jam_mulai, j.jam_selesai
             FROM jadwal j
             WHERE j.id_jadwal = ? LIMIT 1"
        );
        if ($rayhanRPEditStmt) {
            mysqli_stmt_bind_param($rayhanRPEditStmt, "i", $rayhanRPEditId);
        }
    } else {
        $rayhanRPEditStmt = mysqli_prepare(
            $databaseRayhanRP,
            "SELECT j.id_jadwal, j.grup_id, j.judul, j.deskripsi, j.tanggal, j.jam_mulai, j.jam_selesai
             FROM jadwal j
             INNER JOIN grup g ON g.id_grup = j.grup_id
             WHERE j.id_jadwal = ? AND g.dibuat_oleh_akun_id = ? LIMIT 1"
        );
        if ($rayhanRPEditStmt) {
            mysqli_stmt_bind_param($rayhanRPEditStmt, "ii", $rayhanRPEditId, $rayhanRPAdminId);
        }
    }

    if (isset($rayhanRPEditStmt) && $rayhanRPEditStmt) {
        mysqli_stmt_execute($rayhanRPEditStmt);
        mysqli_stmt_bind_result(
            $rayhanRPEditStmt,
            $rayhanRPFormData['id_jadwal'],
            $rayhanRPFormData['grup_id'],
            $rayhanRPFormData['judul'],
            $rayhanRPFormData['deskripsi'],
            $rayhanRPFormData['tanggal'],
            $rayhanRPFormData['jam_mulai'],
            $rayhanRPFormData['jam_selesai']
        );
        if (!mysqli_stmt_fetch($rayhanRPEditStmt)) {
            $rayhanRPFormData['id_jadwal'] = 0;
        }
        mysqli_stmt_close($rayhanRPEditStmt);
    }
}

$rayhanRPGrupOptions = rayhanRP_fetchAvailableGrup($databaseRayhanRP, $rayhanRPAdminId, $rayhanRPCanAccessAll);

$rayhanRPJadwalList = [];
if ($databaseRayhanRP) {
    if ($rayhanRPCanAccessAll) {
        $rayhanRPListSql = "
            SELECT j.id_jadwal, j.judul, j.deskripsi, j.tanggal, j.jam_mulai, j.jam_selesai, COALESCE(g.nama_grup, '-') AS nama_grup
            FROM jadwal j
            LEFT JOIN grup g ON g.id_grup = j.grup_id
            ORDER BY j.tanggal ASC, j.jam_mulai ASC
        ";
        $rayhanRPListResult = mysqli_query($databaseRayhanRP, $rayhanRPListSql);
        if ($rayhanRPListResult) {
            while ($rayhanRPRow = mysqli_fetch_assoc($rayhanRPListResult)) {
                $rayhanRPJadwalList[] = $rayhanRPRow;
            }
            mysqli_free_result($rayhanRPListResult);
        }
    } else {
        $rayhanRPListStmt = mysqli_prepare(
            $databaseRayhanRP,
            "SELECT j.id_jadwal, j.judul, j.deskripsi, j.tanggal, j.jam_mulai, j.jam_selesai, COALESCE(g.nama_grup, '-') AS nama_grup
             FROM jadwal j
             INNER JOIN grup g ON g.id_grup = j.grup_id
             WHERE g.dibuat_oleh_akun_id = ?
             ORDER BY j.tanggal ASC, j.jam_mulai ASC"
        );
        if ($rayhanRPListStmt) {
            mysqli_stmt_bind_param($rayhanRPListStmt, "i", $rayhanRPAdminId);
            mysqli_stmt_execute($rayhanRPListStmt);
            mysqli_stmt_bind_result($rayhanRPListStmt, $rayhanRPIdJadwal, $rayhanRPJudul, $rayhanRPDeskripsi, $rayhanRPTanggal, $rayhanRPJamMulai, $rayhanRPJamSelesai, $rayhanRPNamaGrup);
            while (mysqli_stmt_fetch($rayhanRPListStmt)) {
                $rayhanRPJadwalList[] = [
                    'id_jadwal' => $rayhanRPIdJadwal,
                    'judul' => $rayhanRPJudul,
                    'deskripsi' => $rayhanRPDeskripsi,
                    'tanggal' => $rayhanRPTanggal,
                    'jam_mulai' => $rayhanRPJamMulai,
                    'jam_selesai' => $rayhanRPJamSelesai,
                    'nama_grup' => $rayhanRPNamaGrup,
                ];
            }
            mysqli_stmt_close($rayhanRPListStmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jadwal - Bot SiRey</title>
    <style>
        :root {
            --bg: #f0f4fb;
            --card: #ffffff;
            --line: #dbe4f0;
            --txt: #0f172a;
            --muted: #64748b;
            --primary: #0f5ae0;
            --primary-hover: #0c49b8;
            --danger: #d92d20;
            --danger-hover: #b42318;
            --ok-bg: #dcfce7;
            --ok-txt: #166534;
            --err-bg: #fee2e2;
            --err-txt: #991b1b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: linear-gradient(180deg, #ebf2ff 0%, var(--bg) 45%);
            color: var(--txt);
        }

        .container {
            max-width: 1180px;
            margin: 0 auto;
            padding: 22px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 14px 16px;
        }

        .title {
            margin: 0;
            font-size: 1.2rem;
        }

        .subtitle {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .btn-link {
            text-decoration: none;
            border: 0;
            background: #fff;
            color: var(--primary);
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 9px 12px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .btn-link:hover {
            background: #eff6ff;
        }

        .grid {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 14px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 16px;
        }

        .card h3 {
            margin: 0;
            font-size: 1rem;
        }

        .card p.note {
            margin: 6px 0 0;
            font-size: 0.86rem;
            color: var(--muted);
        }

        .field {
            margin-top: 12px;
        }

        .field label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.88rem;
            font-weight: 600;
        }

        .field input,
        .field select,
        .field textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 10px 11px;
            font-size: 0.92rem;
            outline: none;
            background: #fff;
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(15, 90, 224, 0.15);
        }

        .field textarea {
            min-height: 88px;
            resize: vertical;
        }

        .row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .btn-primary {
            margin-top: 14px;
            width: 100%;
            border: 0;
            border-radius: 10px;
            background: var(--primary);
            color: #fff;
            padding: 11px 12px;
            font-size: 0.92rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .msg {
            margin-top: 12px;
            border-radius: 10px;
            padding: 10px 11px;
            font-size: 0.9rem;
        }

        .msg-ok {
            background: var(--ok-bg);
            color: var(--ok-txt);
        }

        .msg-err {
            background: var(--err-bg);
            color: var(--err-txt);
        }

        .table-wrap {
            overflow-x: auto;
            margin-top: 12px;
        }

        table {
            width: 100%;
            min-width: 740px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px 8px;
            border-bottom: 1px solid var(--line);
            text-align: left;
            vertical-align: top;
            font-size: 0.88rem;
        }

        th {
            text-transform: uppercase;
            font-size: 0.78rem;
            letter-spacing: 0.04em;
            color: var(--muted);
        }

        .tag {
            display: inline-block;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: 0.76rem;
            color: #1e3a8a;
            background: #dbeafe;
            font-weight: 600;
        }

        .table-actions {
            display: flex;
            gap: 6px;
        }

        .btn-sm {
            border: 1px solid transparent;
            border-radius: 8px;
            padding: 7px 10px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-edit {
            border-color: #bfdbfe;
            color: #1d4ed8;
            background: #eff6ff;
        }

        .btn-edit:hover {
            background: #dbeafe;
        }

        .btn-delete {
            border-color: #fecaca;
            color: #b42318;
            background: #fff1f2;
        }

        .btn-delete:hover {
            color: #fff;
            background: var(--danger);
            border-color: var(--danger);
        }

        @media (max-width: 1040px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 560px) {
            .container {
                padding: 12px;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .actions {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <section class="topbar">
            <div>
                <h1 class="title">Kelola Jadwal</h1>
                <p class="subtitle">Login sebagai <?php echo htmlspecialchars($rayhanRPAdminNisNip, ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($rayhanRPAdminRole, ENT_QUOTES, 'UTF-8'); ?>)</p>
            </div>
            <div class="actions">
                <a class="btn-link" href="adminWeb_rayhanRP.php">Dashboard</a>
                <a class="btn-link" href="grup_rayhanRP.php">Kelola Grup</a>
            </div>
        </section>

        <section class="grid">
            <article class="card">
                <h3><?php echo $rayhanRPFormData['id_jadwal'] > 0 ? 'Edit Jadwal' : 'Tambah Jadwal'; ?></h3>
                <p class="note">Lengkapi informasi jadwal, lalu simpan perubahan.</p>

                <?php if ($rayhanRPError !== ''): ?>
                    <div class="msg msg-err"><?php echo htmlspecialchars($rayhanRPError, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php if ($rayhanRPSuccess !== ''): ?>
                    <div class="msg msg-ok"><?php echo htmlspecialchars($rayhanRPSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form method="post" action="">
                    <input type="hidden" name="action" value="<?php echo $rayhanRPFormData['id_jadwal'] > 0 ? 'update' : 'create'; ?>">
                    <input type="hidden" name="id_jadwal" value="<?php echo (int)$rayhanRPFormData['id_jadwal']; ?>">

                    <div class="field">
                        <label for="grup_id">Grup</label>
                        <select name="grup_id" id="grup_id" required>
                            <option value="">Pilih grup</option>
                            <?php foreach ($rayhanRPGrupOptions as $rayhanRPGrup): ?>
                                <option value="<?php echo (int)$rayhanRPGrup['id_grup']; ?>" <?php echo ((int)$rayhanRPFormData['grup_id'] === (int)$rayhanRPGrup['id_grup']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string)$rayhanRPGrup['nama_grup'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="judul">Judul Jadwal</label>
                        <input type="text" name="judul" id="judul" required value="<?php echo htmlspecialchars((string)$rayhanRPFormData['judul'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="field">
                        <label for="deskripsi">Deskripsi</label>
                        <textarea name="deskripsi" id="deskripsi"><?php echo htmlspecialchars((string)$rayhanRPFormData['deskripsi'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>

                    <div class="field row-2">
                        <div>
                            <label for="tanggal">Tanggal</label>
                            <input type="date" name="tanggal" id="tanggal" required value="<?php echo htmlspecialchars((string)$rayhanRPFormData['tanggal'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div></div>
                    </div>

                    <div class="field row-2">
                        <div>
                            <label for="jam_mulai">Jam Mulai</label>
                            <input type="time" name="jam_mulai" id="jam_mulai" required value="<?php echo htmlspecialchars(substr((string)$rayhanRPFormData['jam_mulai'], 0, 5), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div>
                            <label for="jam_selesai">Jam Selesai</label>
                            <input type="time" name="jam_selesai" id="jam_selesai" required value="<?php echo htmlspecialchars(substr((string)$rayhanRPFormData['jam_selesai'], 0, 5), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>

                    <button class="btn-primary" type="submit"><?php echo $rayhanRPFormData['id_jadwal'] > 0 ? 'Simpan Perubahan' : 'Tambah Jadwal'; ?></button>
                </form>
            </article>

            <article class="card">
                <h3>Daftar Jadwal</h3>
                <p class="note">Data jadwal yang dapat Anda lihat dan kelola.</p>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Grup</th>
                                <th>Judul</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($rayhanRPJadwalList) === 0): ?>
                                <tr>
                                    <td colspan="6">Belum ada data jadwal.</td>
                                </tr>
                            <?php else: ?>
                                <?php $rayhanRPNo = 1; ?>
                                <?php foreach ($rayhanRPJadwalList as $rayhanRPJadwal): ?>
                                    <tr>
                                        <td><?php echo $rayhanRPNo++; ?></td>
                                        <td><span class="tag"><?php echo htmlspecialchars((string)$rayhanRPJadwal['nama_grup'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars((string)$rayhanRPJadwal['judul'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <?php if (!empty($rayhanRPJadwal['deskripsi'])): ?>
                                                <br><small><?php echo htmlspecialchars((string)$rayhanRPJadwal['deskripsi'], ENT_QUOTES, 'UTF-8'); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars((string)$rayhanRPJadwal['tanggal'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars(substr((string)$rayhanRPJadwal['jam_mulai'], 0, 5), ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars(substr((string)$rayhanRPJadwal['jam_selesai'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <div class="table-actions">
                                                <a class="btn-sm btn-edit" href="?edit=<?php echo (int)$rayhanRPJadwal['id_jadwal']; ?>">Edit</a>
                                                <form method="post" action="" onsubmit="return confirm('Hapus jadwal ini?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id_jadwal" value="<?php echo (int)$rayhanRPJadwal['id_jadwal']; ?>">
                                                    <button type="submit" class="btn-sm btn-delete">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </div>
</body>

</html>
