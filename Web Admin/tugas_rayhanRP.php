<?php
require_once __DIR__ . '/../koneksi_rayhanRP.php';
session_start();

if (empty($_SESSION['rayhanRP_admin_login'])) {
    header('Location: loginAdmin_rayhanRP.php');
    exit;
}

$rayhanRPAdminId = (int)($_SESSION['rayhanRP_admin_id'] ?? 0);
$rayhanRPAdminNisNip = (string)($_SESSION['rayhanRP_admin_nis_nip'] ?? 'admin');
$rayhanRPAdminRole = strtolower(trim((string)($_SESSION['rayhanRP_admin_role'] ?? '')));
$rayhanRPError = '';
$rayhanRPSuccess = '';

function rayhanRPEnsureTugasTable($db)
{
    $sql = "CREATE TABLE IF NOT EXISTS tugas_pengumpulan (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    return mysqli_query($db, $sql) !== false;
}

function rayhanRPRoleFromDb($db, $akunId)
{
    $stmt = mysqli_prepare($db, 'SELECT role FROM akun WHERE akun_id = ? LIMIT 1');
    if (!$stmt) {
        return '';
    }
    mysqli_stmt_bind_param($stmt, 'i', $akunId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $role);
    $out = mysqli_stmt_fetch($stmt) ? strtolower(trim((string)$role)) : '';
    mysqli_stmt_close($stmt);
    return $out;
}

function rayhanRPNormalizeDateTime($v)
{
    $v = str_replace('T', ' ', trim((string)$v));
    if (preg_match('/^\d{4}\-\d{2}\-\d{2}\s\d{2}\:\d{2}$/', $v)) {
        return $v . ':00';
    }
    if (preg_match('/^\d{4}\-\d{2}\-\d{2}\s\d{2}\:\d{2}\:\d{2}$/', $v)) {
        return $v;
    }
    return '';
}

function rayhanRPDateTimeForInput($v)
{
    $v = trim((string)$v);
    if ($v === '' || $v === '0000-00-00 00:00:00') {
        return '';
    }
    $t = strtotime($v);
    return $t === false ? '' : date('Y-m-d\TH:i', $t);
}

function rayhanRPCanAccessTask($db, $taskId, $adminId, $all)
{
    if ($all) {
        $stmt = mysqli_prepare($db, 'SELECT id_tugas FROM tugas WHERE id_tugas = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'i', $taskId);
    } else {
        $stmt = mysqli_prepare($db, 'SELECT id_tugas FROM tugas WHERE id_tugas = ? AND dibuat_oleh_akun_id = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'ii', $taskId, $adminId);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $id);
    $ok = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return (bool)$ok;
}

if ($databaseRayhanRP && $rayhanRPAdminRole === '' && $rayhanRPAdminId > 0) {
    $rayhanRPAdminRole = rayhanRPRoleFromDb($databaseRayhanRP, $rayhanRPAdminId);
}
if ($rayhanRPAdminRole !== 'admin' && $rayhanRPAdminRole !== 'guru') {
    session_unset();
    session_destroy();
    header('Location: loginAdmin_rayhanRP.php');
    exit;
}
$rayhanRPCanAccessAll = ($rayhanRPAdminRole === 'admin');

if ($databaseRayhanRP && !rayhanRPEnsureTugasTable($databaseRayhanRP)) {
    $rayhanRPError = 'Gagal menyiapkan tabel pengumpulan tugas.';
}

$rayhanRPForm = ['id_tugas' => 0, 'judul' => '', 'deskripsi' => '', 'tenggat' => '', 'grup_id' => '', 'jadwal_id' => '0'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    if (!$databaseRayhanRP) {
        $rayhanRPError = 'Koneksi database gagal.';
    } elseif (!rayhanRPEnsureTugasTable($databaseRayhanRP)) {
        $rayhanRPError = 'Gagal menyiapkan tabel pengumpulan tugas.';
    } elseif ($action === 'create' || $action === 'update') {
        $rayhanRPForm['id_tugas'] = (int)($_POST['id_tugas'] ?? 0);
        $rayhanRPForm['judul'] = trim((string)($_POST['judul'] ?? ''));
        $rayhanRPForm['deskripsi'] = trim((string)($_POST['deskripsi'] ?? ''));
        $rayhanRPForm['tenggat'] = trim((string)($_POST['tenggat'] ?? ''));
        $rayhanRPForm['grup_id'] = trim((string)($_POST['grup_id'] ?? ''));
        $rayhanRPForm['jadwal_id'] = trim((string)($_POST['jadwal_id'] ?? '0'));

        $idTugas = (int)$rayhanRPForm['id_tugas'];
        $idGrup = (int)$rayhanRPForm['grup_id'];
        $idJadwal = (int)$rayhanRPForm['jadwal_id'];
        $tenggat = rayhanRPNormalizeDateTime($rayhanRPForm['tenggat']);

        if ($rayhanRPForm['judul'] === '' || $idGrup <= 0 || $tenggat === '') {
            $rayhanRPError = 'Judul, grup, dan tenggat wajib valid.';
        } else {
            if ($rayhanRPCanAccessAll) {
                $gstmt = mysqli_prepare($databaseRayhanRP, 'SELECT id_grup FROM grup WHERE id_grup = ? LIMIT 1');
                if ($gstmt) {
                    mysqli_stmt_bind_param($gstmt, 'i', $idGrup);
                }
            } else {
                $gstmt = mysqli_prepare($databaseRayhanRP, 'SELECT id_grup FROM grup WHERE id_grup = ? AND dibuat_oleh_akun_id = ? LIMIT 1');
                if ($gstmt) {
                    mysqli_stmt_bind_param($gstmt, 'ii', $idGrup, $rayhanRPAdminId);
                }
            }
            if (!$gstmt) {
                $rayhanRPError = 'Gagal validasi grup.';
            } else {
                mysqli_stmt_execute($gstmt);
                mysqli_stmt_bind_result($gstmt, $gid);
                $validGroup = mysqli_stmt_fetch($gstmt);
                mysqli_stmt_close($gstmt);
                if (!$validGroup) {
                    $rayhanRPError = 'Anda tidak memiliki akses ke grup.';
                }
            }
        }

        if ($rayhanRPError === '' && $idJadwal > 0) {
            if ($rayhanRPCanAccessAll) {
                $jstmt = mysqli_prepare($databaseRayhanRP, 'SELECT id_jadwal FROM jadwal WHERE id_jadwal = ? AND grup_id = ? LIMIT 1');
                if ($jstmt) {
                    mysqli_stmt_bind_param($jstmt, 'ii', $idJadwal, $idGrup);
                }
            } else {
                $jstmt = mysqli_prepare($databaseRayhanRP, 'SELECT j.id_jadwal FROM jadwal j INNER JOIN grup g ON g.id_grup = j.grup_id WHERE j.id_jadwal = ? AND j.grup_id = ? AND g.dibuat_oleh_akun_id = ? LIMIT 1');
                if ($jstmt) {
                    mysqli_stmt_bind_param($jstmt, 'iii', $idJadwal, $idGrup, $rayhanRPAdminId);
                }
            }
            if (!$jstmt) {
                $rayhanRPError = 'Gagal validasi jadwal.';
            } else {
                mysqli_stmt_execute($jstmt);
                mysqli_stmt_bind_result($jstmt, $jid);
                $validJadwal = mysqli_stmt_fetch($jstmt);
                mysqli_stmt_close($jstmt);
                if (!$validJadwal) {
                    $rayhanRPError = 'Jadwal tidak sesuai grup.';
                }
            }
        }

        if ($rayhanRPError === '') {
            if ($action === 'create') {
                $stmt = mysqli_prepare($databaseRayhanRP, 'INSERT INTO tugas (judul, deskripsi, tenggat, grup_id, jadwal_id, dibuat_oleh_akun_id) VALUES (?, ?, ?, ?, NULLIF(?, 0), ?)');
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'sssiii', $rayhanRPForm['judul'], $rayhanRPForm['deskripsi'], $tenggat, $idGrup, $idJadwal, $rayhanRPAdminId);
                }
            } else {
                if ($idTugas <= 0 || !rayhanRPCanAccessTask($databaseRayhanRP, $idTugas, $rayhanRPAdminId, $rayhanRPCanAccessAll)) {
                    $stmt = null;
                    $rayhanRPError = 'Tugas tidak ditemukan atau tidak dapat diubah.';
                } elseif ($rayhanRPCanAccessAll) {
                    $stmt = mysqli_prepare($databaseRayhanRP, 'UPDATE tugas SET judul = ?, deskripsi = ?, tenggat = ?, grup_id = ?, jadwal_id = NULLIF(?, 0) WHERE id_tugas = ? LIMIT 1');
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, 'sssiii', $rayhanRPForm['judul'], $rayhanRPForm['deskripsi'], $tenggat, $idGrup, $idJadwal, $idTugas);
                    }
                } else {
                    $stmt = mysqli_prepare($databaseRayhanRP, 'UPDATE tugas SET judul = ?, deskripsi = ?, tenggat = ?, grup_id = ?, jadwal_id = NULLIF(?, 0) WHERE id_tugas = ? AND dibuat_oleh_akun_id = ? LIMIT 1');
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, 'sssiiii', $rayhanRPForm['judul'], $rayhanRPForm['deskripsi'], $tenggat, $idGrup, $idJadwal, $idTugas, $rayhanRPAdminId);
                    }
                }
            }

            if ($rayhanRPError === '' && !$stmt) {
                $rayhanRPError = 'Gagal menyiapkan query tugas.';
            } elseif ($rayhanRPError === '') {
                if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) >= 0) {
                    $rayhanRPSuccess = $action === 'create' ? 'Tugas berhasil ditambahkan.' : 'Tugas berhasil diperbarui.';
                    if ($action === 'create') {
                        $rayhanRPForm = ['id_tugas' => 0, 'judul' => '', 'deskripsi' => '', 'tenggat' => '', 'grup_id' => '', 'jadwal_id' => '0'];
                    }
                } else {
                    $rayhanRPError = 'Gagal menyimpan tugas.';
                }
                mysqli_stmt_close($stmt);
            }
        }
    } elseif ($action === 'delete') {
        $idTugas = (int)($_POST['id_tugas'] ?? 0);
        if ($idTugas <= 0) {
            $rayhanRPError = 'ID tugas tidak valid.';
        } elseif ($rayhanRPCanAccessAll) {
            $stmt = mysqli_prepare($databaseRayhanRP, 'DELETE FROM tugas WHERE id_tugas = ? LIMIT 1');
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $idTugas);
            }
        } else {
            $stmt = mysqli_prepare($databaseRayhanRP, 'DELETE FROM tugas WHERE id_tugas = ? AND dibuat_oleh_akun_id = ? LIMIT 1');
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ii', $idTugas, $rayhanRPAdminId);
            }
        }

        if ($rayhanRPError === '' && !$stmt) {
            $rayhanRPError = 'Gagal menyiapkan query hapus.';
        } elseif ($rayhanRPError === '') {
            if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
                $rayhanRPSuccess = 'Tugas berhasil dihapus.';
            } else {
                $rayhanRPError = 'Tugas tidak ditemukan atau tidak dapat dihapus.';
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($action === 'grade') {
        $idPengumpulan = (int)($_POST['id_pengumpulan'] ?? 0);
        $status = trim((string)($_POST['status'] ?? 'dikumpulkan'));
        $nilaiRaw = trim((string)($_POST['nilai'] ?? ''));
        $catatan = trim((string)($_POST['catatan_guru'] ?? ''));
        if (!in_array($status, ['dikumpulkan', 'dinilai', 'revisi', 'terlambat'], true)) {
            $rayhanRPError = 'Status pengumpulan tidak valid.';
        } elseif ($nilaiRaw !== '' && (!is_numeric($nilaiRaw) || (float)$nilaiRaw < 0 || (float)$nilaiRaw > 100)) {
            $rayhanRPError = 'Nilai harus angka 0-100.';
        } elseif ($idPengumpulan <= 0) {
            $rayhanRPError = 'Data pengumpulan tidak valid.';
        } else {
            $nilaiDb = $nilaiRaw === '' ? '' : number_format((float)$nilaiRaw, 2, '.', '');
            if ($rayhanRPCanAccessAll) {
                $stmt = mysqli_prepare($databaseRayhanRP, "UPDATE tugas_pengumpulan tp INNER JOIN tugas t ON t.id_tugas = tp.tugas_id SET tp.status = ?, tp.nilai = IF(? = '', NULL, CAST(? AS DECIMAL(5,2))), tp.catatan_guru = ?, tp.graded_at = NOW() WHERE tp.id_pengumpulan = ?");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'ssssi', $status, $nilaiDb, $nilaiDb, $catatan, $idPengumpulan);
                }
            } else {
                $stmt = mysqli_prepare($databaseRayhanRP, "UPDATE tugas_pengumpulan tp INNER JOIN tugas t ON t.id_tugas = tp.tugas_id SET tp.status = ?, tp.nilai = IF(? = '', NULL, CAST(? AS DECIMAL(5,2))), tp.catatan_guru = ?, tp.graded_at = NOW() WHERE tp.id_pengumpulan = ? AND t.dibuat_oleh_akun_id = ?");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'ssssii', $status, $nilaiDb, $nilaiDb, $catatan, $idPengumpulan, $rayhanRPAdminId);
                }
            }
            if (!$stmt) {
                $rayhanRPError = 'Gagal menyiapkan query nilai.';
            } else {
                if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) >= 0) {
                    $rayhanRPSuccess = 'Status/nilai berhasil diperbarui.';
                } else {
                    $rayhanRPError = 'Gagal memperbarui status/nilai.';
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

$editId = (int)($_GET['edit'] ?? 0);
if ($editId > 0 && $databaseRayhanRP) {
    if ($rayhanRPCanAccessAll) {
        $stmt = mysqli_prepare($databaseRayhanRP, 'SELECT id_tugas, judul, deskripsi, tenggat, grup_id, COALESCE(jadwal_id, 0) FROM tugas WHERE id_tugas = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $editId);
        }
    } else {
        $stmt = mysqli_prepare($databaseRayhanRP, 'SELECT id_tugas, judul, deskripsi, tenggat, grup_id, COALESCE(jadwal_id, 0) FROM tugas WHERE id_tugas = ? AND dibuat_oleh_akun_id = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ii', $editId, $rayhanRPAdminId);
        }
    }
    if (isset($stmt) && $stmt) {
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $fId, $fJudul, $fDesc, $fTenggat, $fGrup, $fJadwal);
        if (mysqli_stmt_fetch($stmt)) {
            $rayhanRPForm = [
                'id_tugas' => (int)$fId,
                'judul' => (string)$fJudul,
                'deskripsi' => (string)$fDesc,
                'tenggat' => rayhanRPDateTimeForInput($fTenggat),
                'grup_id' => (string)$fGrup,
                'jadwal_id' => (string)$fJadwal,
            ];
        }
        mysqli_stmt_close($stmt);
    }
}

$grupOptions = [];
$jadwalOptions = [];
$tugasList = [];
$detailId = (int)($_GET['detail'] ?? 0);
if ($detailId <= 0 && (int)$rayhanRPForm['id_tugas'] > 0) {
    $detailId = (int)$rayhanRPForm['id_tugas'];
}
$pengumpulanList = [];
$detailTitle = '';
$detailDeadline = '';

if ($databaseRayhanRP) {
    if ($rayhanRPCanAccessAll) {
        $gres = mysqli_query($databaseRayhanRP, 'SELECT id_grup, nama_grup FROM grup ORDER BY nama_grup ASC');
    } else {
        $gstmt = mysqli_prepare($databaseRayhanRP, 'SELECT id_grup, nama_grup FROM grup WHERE dibuat_oleh_akun_id = ? ORDER BY nama_grup ASC');
        $gres = false;
        if ($gstmt) {
            mysqli_stmt_bind_param($gstmt, 'i', $rayhanRPAdminId);
            mysqli_stmt_execute($gstmt);
            mysqli_stmt_bind_result($gstmt, $gid, $gnama);
            while (mysqli_stmt_fetch($gstmt)) {
                $grupOptions[] = ['id_grup' => $gid, 'nama_grup' => $gnama];
            }
            mysqli_stmt_close($gstmt);
        }
    }
    if ($rayhanRPCanAccessAll && $gres) {
        while ($row = mysqli_fetch_assoc($gres)) {
            $grupOptions[] = $row;
        }
        mysqli_free_result($gres);
    }

    if ($rayhanRPCanAccessAll) {
        $jres = mysqli_query($databaseRayhanRP, "SELECT j.id_jadwal, j.grup_id, j.judul, COALESCE(g.nama_grup, '-') AS nama_grup FROM jadwal j LEFT JOIN grup g ON g.id_grup = j.grup_id ORDER BY g.nama_grup ASC, j.judul ASC");
        if ($jres) {
            while ($row = mysqli_fetch_assoc($jres)) {
                $jadwalOptions[] = $row;
            }
            mysqli_free_result($jres);
        }
    } else {
        $jstmt = mysqli_prepare($databaseRayhanRP, "SELECT j.id_jadwal, j.grup_id, j.judul, COALESCE(g.nama_grup, '-') AS nama_grup FROM jadwal j INNER JOIN grup g ON g.id_grup = j.grup_id WHERE g.dibuat_oleh_akun_id = ? ORDER BY g.nama_grup ASC, j.judul ASC");
        if ($jstmt) {
            mysqli_stmt_bind_param($jstmt, 'i', $rayhanRPAdminId);
            mysqli_stmt_execute($jstmt);
            mysqli_stmt_bind_result($jstmt, $jid, $jgid, $jj, $jgn);
            while (mysqli_stmt_fetch($jstmt)) {
                $jadwalOptions[] = ['id_jadwal' => $jid, 'grup_id' => $jgid, 'judul' => $jj, 'nama_grup' => $jgn];
            }
            mysqli_stmt_close($jstmt);
        }
    }

    if ($rayhanRPCanAccessAll) {
        $tres = mysqli_query($databaseRayhanRP, "SELECT t.id_tugas, t.judul, t.deskripsi, t.tenggat, COALESCE(g.nama_grup, '-') AS nama_grup, COALESCE(a.nis_nip, '-') AS pembuat_nis_nip, COALESCE(a.role, '-') AS pembuat_role, COALESCE(x.total_pengumpulan, 0) AS total_pengumpulan, COALESCE(x.total_dinilai, 0) AS total_dinilai FROM tugas t LEFT JOIN grup g ON g.id_grup = t.grup_id LEFT JOIN akun a ON a.akun_id = t.dibuat_oleh_akun_id LEFT JOIN (SELECT tugas_id, COUNT(*) AS total_pengumpulan, SUM(CASE WHEN status = 'dinilai' THEN 1 ELSE 0 END) AS total_dinilai FROM tugas_pengumpulan GROUP BY tugas_id) x ON x.tugas_id = t.id_tugas ORDER BY t.tenggat DESC, t.id_tugas DESC");
        if ($tres) {
            while ($row = mysqli_fetch_assoc($tres)) {
                $tugasList[] = $row;
            }
            mysqli_free_result($tres);
        }
    } else {
        $tstmt = mysqli_prepare($databaseRayhanRP, "SELECT t.id_tugas, t.judul, t.deskripsi, t.tenggat, COALESCE(g.nama_grup, '-') AS nama_grup, COALESCE(a.nis_nip, '-') AS pembuat_nis_nip, COALESCE(a.role, '-') AS pembuat_role, COALESCE(x.total_pengumpulan, 0) AS total_pengumpulan, COALESCE(x.total_dinilai, 0) AS total_dinilai FROM tugas t LEFT JOIN grup g ON g.id_grup = t.grup_id LEFT JOIN akun a ON a.akun_id = t.dibuat_oleh_akun_id LEFT JOIN (SELECT tugas_id, COUNT(*) AS total_pengumpulan, SUM(CASE WHEN status = 'dinilai' THEN 1 ELSE 0 END) AS total_dinilai FROM tugas_pengumpulan GROUP BY tugas_id) x ON x.tugas_id = t.id_tugas WHERE t.dibuat_oleh_akun_id = ? ORDER BY t.tenggat DESC, t.id_tugas DESC");
        if ($tstmt) {
            mysqli_stmt_bind_param($tstmt, 'i', $rayhanRPAdminId);
            mysqli_stmt_execute($tstmt);
            mysqli_stmt_bind_result($tstmt, $tid, $tjudul, $tdesc, $ttenggat, $tnama, $tpnis, $tprole, $tpeng, $tdinilai);
            while (mysqli_stmt_fetch($tstmt)) {
                $tugasList[] = ['id_tugas' => $tid, 'judul' => $tjudul, 'deskripsi' => $tdesc, 'tenggat' => $ttenggat, 'nama_grup' => $tnama, 'pembuat_nis_nip' => $tpnis, 'pembuat_role' => $tprole, 'total_pengumpulan' => $tpeng, 'total_dinilai' => $tdinilai];
            }
            mysqli_stmt_close($tstmt);
        }
    }

    if ($detailId > 0 && rayhanRPCanAccessTask($databaseRayhanRP, $detailId, $rayhanRPAdminId, $rayhanRPCanAccessAll)) {
        $dstmt = mysqli_prepare($databaseRayhanRP, 'SELECT judul, tenggat FROM tugas WHERE id_tugas = ? LIMIT 1');
        if ($dstmt) {
            mysqli_stmt_bind_param($dstmt, 'i', $detailId);
            mysqli_stmt_execute($dstmt);
            mysqli_stmt_bind_result($dstmt, $detailTitle, $detailDeadline);
            mysqli_stmt_fetch($dstmt);
            mysqli_stmt_close($dstmt);
        }

        $pstmt = mysqli_prepare($databaseRayhanRP, "SELECT tp.id_pengumpulan, tp.akun_id, COALESCE(a.nis_nip, '-') AS nis_nip, COALESCE(a.role, '-') AS role, tp.file_type, tp.nama_file_asli, tp.file_mime, tp.file_size, tp.status, tp.nilai, tp.caption, tp.catatan_guru, tp.submitted_at FROM tugas_pengumpulan tp INNER JOIN akun a ON a.akun_id = tp.akun_id WHERE tp.tugas_id = ? ORDER BY tp.submitted_at DESC");
        if ($pstmt) {
            mysqli_stmt_bind_param($pstmt, 'i', $detailId);
            mysqli_stmt_execute($pstmt);
            mysqli_stmt_bind_result($pstmt, $pid, $pakun, $pnis, $prole, $ptype, $pname, $pmime, $psize, $pstatus, $pnilai, $pcap, $pcat, $psub);
            while (mysqli_stmt_fetch($pstmt)) {
                $pengumpulanList[] = ['id_pengumpulan' => $pid, 'akun_id' => $pakun, 'nis_nip' => $pnis, 'role' => $prole, 'file_type' => $ptype, 'nama_file_asli' => $pname, 'file_mime' => $pmime, 'file_size' => $psize, 'status' => $pstatus, 'nilai' => $pnilai, 'caption' => $pcap, 'catatan_guru' => $pcat, 'submitted_at' => $psub];
            }
            mysqli_stmt_close($pstmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Tugas - Bot SiRey</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: #eef3fb;
            color: #0f172a;
        }

        .container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 22px;
        }

        .topbar {
            background: #fff;
            border: 1px solid #dbe4f0;
            border-radius: 14px;
            padding: 14px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        .title {
            margin: 0;
            font-size: 1.2rem;
        }

        .subtitle {
            margin: 4px 0 0;
            font-size: .9rem;
            color: #64748b;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-link {
            text-decoration: none;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 9px 12px;
            font-size: .9rem;
            font-weight: 600;
            color: #1d4ed8;
            background: #fff;
        }

        .btn-link:hover {
            background: #eff6ff;
        }

        .grid {
            display: grid;
            grid-template-columns: 390px 1fr;
            gap: 14px;
        }

        .card {
            background: #fff;
            border: 1px solid #dbe4f0;
            border-radius: 14px;
            padding: 16px;
        }

        .card h3 {
            margin: 0;
            font-size: 1rem;
        }

        .note {
            margin: 6px 0 0;
            color: #64748b;
            font-size: .86rem;
        }

        .field {
            margin-top: 12px;
        }

        .field label {
            display: block;
            margin-bottom: 6px;
            font-size: .88rem;
            font-weight: 600;
        }

        .field input,
        .field select,
        .field textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 10px 11px;
            font-size: .92rem;
            outline: none;
            background: #fff;
        }

        .field textarea {
            min-height: 88px;
            resize: vertical;
        }

        .btn-primary {
            margin-top: 14px;
            width: 100%;
            border: 0;
            border-radius: 10px;
            padding: 11px 12px;
            font-size: .92rem;
            font-weight: 600;
            color: #fff;
            background: #1664d6;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: #1252ae;
        }

        .msg {
            margin-top: 12px;
            border-radius: 10px;
            padding: 10px 11px;
            font-size: .9rem;
        }

        .msg-err {
            background: #fee2e2;
            color: #991b1b;
        }

        .msg-ok {
            background: #dcfce7;
            color: #166534;
        }

        .table-wrap {
            overflow-x: auto;
            margin-top: 12px;
        }

        table {
            width: 100%;
            min-width: 860px;
            border-collapse: collapse;
        }

        th,
        td {
            border-bottom: 1px solid #dbe4f0;
            padding: 10px 8px;
            text-align: left;
            vertical-align: top;
            font-size: .88rem;
        }

        th {
            text-transform: uppercase;
            font-size: .78rem;
            letter-spacing: .04em;
            color: #64748b;
        }

        .tag {
            display: inline-block;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: .76rem;
            font-weight: 600;
            color: #1e3a8a;
            background: #dbeafe;
        }

        .table-actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .btn-sm {
            border: 1px solid transparent;
            border-radius: 8px;
            padding: 7px 10px;
            font-size: .82rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            background: #fff;
        }

        .btn-edit {
            border-color: #bfdbfe;
            color: #1d4ed8;
            background: #eff6ff;
        }

        .btn-delete {
            border-color: #fecaca;
            color: #b42318;
            background: #fff1f2;
        }

        .status {
            display: inline-block;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: .76rem;
            font-weight: 600;
        }

        .status-dikumpulkan {
            color: #1e3a8a;
            background: #dbeafe;
        }

        .status-dinilai {
            color: #166534;
            background: #dcfce7;
        }

        .status-revisi,
        .status-terlambat {
            color: #9a3412;
            background: #ffedd5;
        }

        .small {
            color: #64748b;
            font-size: .8rem;
        }

        .submission-card {
            margin-top: 14px;
        }

        .grade-form {
            display: grid;
            gap: 6px;
        }

        .grade-form input,
        .grade-form select,
        .grade-form textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 7px 8px;
            font-size: .82rem;
            background: #fff;
            outline: none;
        }

        .grade-form textarea {
            min-height: 52px;
            resize: vertical;
        }

        .grade-form button {
            border: 0;
            border-radius: 8px;
            padding: 8px;
            font-size: .82rem;
            font-weight: 600;
            color: #fff;
            background: #1664d6;
            cursor: pointer;
        }

        @media (max-width: 1080px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <section class="topbar">
            <div>
                <h1 class="title">Kelola Tugas</h1>
                <p class="subtitle">Login sebagai <?php echo htmlspecialchars($rayhanRPAdminNisNip, ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($rayhanRPAdminRole, ENT_QUOTES, 'UTF-8'); ?>)</p>
            </div>
            <div class="actions">
                <a class="btn-link" href="adminWeb_rayhanRP.php">Dashboard</a>
                <a class="btn-link" href="grup_rayhanRP.php">Kelola Grup</a>
                <a class="btn-link" href="jadwal_rayhanRP.php">Kelola Jadwal</a>
            </div>
        </section>

        <section class="grid">
            <article class="card">
                <h3><?php echo (int)$rayhanRPForm['id_tugas'] > 0 ? 'Edit Tugas' : 'Tambah Tugas'; ?></h3>
                <p class="note">Siswa mengumpulkan via Telegram: <strong>/kumpul ID_TUGAS</strong>.</p>
                <?php if ($rayhanRPError !== ''): ?>
                    <div class="msg msg-err"><?php echo htmlspecialchars($rayhanRPError, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php if ($rayhanRPSuccess !== ''): ?>
                    <div class="msg msg-ok"><?php echo htmlspecialchars($rayhanRPSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <form method="post" action="">
                    <input type="hidden" name="action" value="<?php echo (int)$rayhanRPForm['id_tugas'] > 0 ? 'update' : 'create'; ?>">
                    <input type="hidden" name="id_tugas" value="<?php echo (int)$rayhanRPForm['id_tugas']; ?>">
                    <div class="field">
                        <label for="grup_id">Grup</label>
                        <select id="grup_id" name="grup_id" required>
                            <option value="">Pilih grup</option>
                            <?php foreach ($grupOptions as $gr): ?>
                                <option value="<?php echo (int)$gr['id_grup']; ?>" <?php echo ((int)$rayhanRPForm['grup_id'] === (int)$gr['id_grup']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string)$gr['nama_grup'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="jadwal_id">Jadwal (opsional)</label>
                        <select id="jadwal_id" name="jadwal_id">
                            <option value="0">Tanpa jadwal</option>
                            <?php foreach ($jadwalOptions as $jd): ?>
                                <option value="<?php echo (int)$jd['id_jadwal']; ?>" data-grup="<?php echo (int)$jd['grup_id']; ?>" <?php echo ((int)$rayhanRPForm['jadwal_id'] === (int)$jd['id_jadwal']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string)$jd['nama_grup'], ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars((string)$jd['judul'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="judul">Judul Tugas</label>
                        <input type="text" id="judul" name="judul" required value="<?php echo htmlspecialchars((string)$rayhanRPForm['judul'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="field">
                        <label for="deskripsi">Deskripsi</label>
                        <textarea id="deskripsi" name="deskripsi"><?php echo htmlspecialchars((string)$rayhanRPForm['deskripsi'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="field">
                        <label for="tenggat">Tenggat</label>
                        <input type="datetime-local" id="tenggat" name="tenggat" required value="<?php echo htmlspecialchars((string)$rayhanRPForm['tenggat'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <button class="btn-primary" type="submit"><?php echo (int)$rayhanRPForm['id_tugas'] > 0 ? 'Simpan Perubahan' : 'Tambah Tugas'; ?></button>
                </form>
            </article>

            <article class="card">
                <h3>Daftar Tugas</h3>
                <p class="note">Gunakan aksi <strong>Lihat Pengumpulan</strong> untuk memberi nilai.</p>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tugas</th>
                                <th>Grup</th>
                                <th>Tenggat</th>
                                <th>Pembuat</th>
                                <th>Pengumpulan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($tugasList) === 0): ?>
                                <tr>
                                    <td colspan="7">Belum ada data tugas.</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; ?>
                                <?php foreach ($tugasList as $tg): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td>
                                            <strong>#<?php echo (int)$tg['id_tugas']; ?> - <?php echo htmlspecialchars((string)$tg['judul'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <?php if (!empty($tg['deskripsi'])): ?>
                                                <br><span class="small"><?php echo htmlspecialchars((string)$tg['deskripsi'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="tag"><?php echo htmlspecialchars((string)$tg['nama_grup'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                        <td><?php echo htmlspecialchars((string)$tg['tenggat'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string)$tg['pembuat_nis_nip'], ENT_QUOTES, 'UTF-8'); ?><br><span class="small"><?php echo htmlspecialchars((string)$tg['pembuat_role'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                        <td><?php echo (int)$tg['total_pengumpulan']; ?> masuk<br><span class="small"><?php echo (int)$tg['total_dinilai']; ?> dinilai</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <a class="btn-sm btn-edit" href="?edit=<?php echo (int)$tg['id_tugas']; ?>">Edit</a>
                                                <a class="btn-sm btn-edit" href="?detail=<?php echo (int)$tg['id_tugas']; ?>">Lihat Pengumpulan</a>
                                                <form method="post" action="" onsubmit="return confirm('Hapus tugas ini?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id_tugas" value="<?php echo (int)$tg['id_tugas']; ?>">
                                                    <button class="btn-sm btn-delete" type="submit">Hapus</button>
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

        <?php if ($detailId > 0): ?>
            <section class="card submission-card">
                <h3>Pengumpulan Tugas: #<?php echo (int)$detailId; ?> - <?php echo htmlspecialchars((string)$detailTitle, ENT_QUOTES, 'UTF-8'); ?></h3>
                <p class="note">Tenggat: <?php echo htmlspecialchars((string)$detailDeadline, ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Siswa/User</th>
                                <th>Waktu Kumpul</th>
                                <th>File</th>
                                <th>Status</th>
                                <th>Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($pengumpulanList) === 0): ?>
                                <tr>
                                    <td colspan="6">Belum ada pengumpulan untuk tugas ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php $noSub = 1; ?>
                                <?php foreach ($pengumpulanList as $pg): ?>
                                    <?php
                                    $st = strtolower(trim((string)$pg['status']));
                                    if ($st === '') {
                                        $st = 'dikumpulkan';
                                    }
                                    $nilaiTampil = ($pg['nilai'] === null || $pg['nilai'] === '') ? '' : number_format((float)$pg['nilai'], 2, '.', '');
                                    $sizeLabel = ((int)$pg['file_size'] > 0) ? number_format(((int)$pg['file_size']) / 1024, 2) . ' KB' : '-';
                                    ?>
                                    <tr>
                                        <td><?php echo $noSub++; ?></td>
                                        <td><?php echo htmlspecialchars((string)$pg['nis_nip'], ENT_QUOTES, 'UTF-8'); ?><br><span class="small"><?php echo htmlspecialchars((string)$pg['role'], ENT_QUOTES, 'UTF-8'); ?> | akun_id <?php echo (int)$pg['akun_id']; ?></span></td>
                                        <td><?php echo htmlspecialchars((string)$pg['submitted_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <a href="../download_tugas_file_rayhanRP.php?id=<?php echo (int)$pg['id_pengumpulan']; ?>" target="_blank" rel="noopener">Unduh File</a>
                                            <br><span class="small"><?php echo htmlspecialchars((string)($pg['nama_file_asli'] ?: '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                                            <br><span class="small"><?php echo htmlspecialchars((string)($pg['file_mime'] ?: '-'), ENT_QUOTES, 'UTF-8'); ?> | <?php echo htmlspecialchars($sizeLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php if (!empty($pg['caption'])): ?>
                                                <br><span class="small">Catatan siswa: <?php echo htmlspecialchars((string)$pg['caption'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status status-<?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(ucfirst($st), ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php if (!empty($pg['catatan_guru'])): ?>
                                                <br><span class="small">Catatan guru: <?php echo htmlspecialchars((string)$pg['catatan_guru'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form class="grade-form" method="post" action="?detail=<?php echo (int)$detailId; ?>">
                                                <input type="hidden" name="action" value="grade">
                                                <input type="hidden" name="id_pengumpulan" value="<?php echo (int)$pg['id_pengumpulan']; ?>">
                                                <select name="status" required>
                                                    <option value="dikumpulkan" <?php echo $st === 'dikumpulkan' ? 'selected' : ''; ?>>Dikumpulkan</option>
                                                    <option value="dinilai" <?php echo $st === 'dinilai' ? 'selected' : ''; ?>>Dinilai</option>
                                                    <option value="revisi" <?php echo $st === 'revisi' ? 'selected' : ''; ?>>Perlu Revisi</option>
                                                    <option value="terlambat" <?php echo $st === 'terlambat' ? 'selected' : ''; ?>>Terlambat</option>
                                                </select>
                                                <input type="number" name="nilai" min="0" max="100" step="0.01" value="<?php echo htmlspecialchars($nilaiTampil, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nilai 0-100">
                                                <textarea name="catatan_guru" placeholder="Catatan guru"><?php echo htmlspecialchars((string)$pg['catatan_guru'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                <button type="submit">Simpan</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <script>
        (function() {
            var grup = document.getElementById('grup_id');
            var jadwal = document.getElementById('jadwal_id');
            if (!grup || !jadwal) return;
            function filterJadwal() {
                var gid = grup.value;
                var cur = jadwal.value;
                var okCur = false;
                for (var i = 0; i < jadwal.options.length; i++) {
                    var o = jadwal.options[i];
                    if (i === 0) {
                        o.hidden = false;
                        continue;
                    }
                    var show = gid === '' || o.getAttribute('data-grup') === gid;
                    o.hidden = !show;
                    if (show && o.value === cur) okCur = true;
                }
                if (!okCur && cur !== '0') {
                    jadwal.value = '0';
                }
            }
            grup.addEventListener('change', filterJadwal);
            filterJadwal();
        })();
    </script>
</body>

</html>
