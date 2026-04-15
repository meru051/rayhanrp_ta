<?php
require_once __DIR__ . '/../koneksi_rayhanRP.php';
require_once __DIR__ . '/includes/admin_layout_rayhanRP.php';
rayhanRPStartSession();

$rayhanRPAdmin = rayhanRPRequireAdminSession('loginAdmin_rayhanRP.php');
$rayhanRPAdminId = (int)$rayhanRPAdmin['akun_id'];
$rayhanRPAdminNisNip = (string)$rayhanRPAdmin['nis_nip'];
$rayhanRPAdminLabel = (string)$rayhanRPAdmin['label'];
$rayhanRPAdminRole = (string)$rayhanRPAdmin['role'];

$rayhanRPError = '';
$rayhanRPSuccess = '';
$rayhanRPQuery = trim((string)($_GET['q'] ?? ''));
$rayhanRPFilterKelas = trim((string)($_GET['kelas'] ?? ''));
$rayhanRPFilterJk = strtoupper(trim((string)($_GET['jk'] ?? '')));
$rayhanRPForm = [
    'akun_id' => 0,
    'nis_nip' => '',
    'nama_lengkap' => '',
    'kelas_label' => '',
    'jenis_kelamin' => '',
    'password' => '',
];

function rayhanRPSiswaHashPassword($rayhanRPPassword)
{
    return (string)password_hash((string)$rayhanRPPassword, PASSWORD_DEFAULT);
}

function rayhanRPSiswaFetchByNis($databaseRayhanRP, $rayhanRPNisNip)
{
    $rayhanRPStmt = mysqli_prepare(
        $databaseRayhanRP,
        "SELECT akun_id, nis_nip, role, COALESCE(NULLIF(nama_lengkap, ''), ''), COALESCE(NULLIF(kelas_label, ''), ''), COALESCE(NULLIF(jenis_kelamin, ''), '')
         FROM akun
         WHERE nis_nip = ?
         LIMIT 1"
    );
    if (!$rayhanRPStmt) {
        return null;
    }

    mysqli_stmt_bind_param($rayhanRPStmt, 's', $rayhanRPNisNip);
    mysqli_stmt_execute($rayhanRPStmt);
    mysqli_stmt_bind_result($rayhanRPStmt, $rayhanRPAkunId, $rayhanRPDbNisNip, $rayhanRPRole, $rayhanRPNamaLengkap, $rayhanRPKelasLabel, $rayhanRPJenisKelamin);
    $rayhanRPRow = null;
    if (mysqli_stmt_fetch($rayhanRPStmt)) {
        $rayhanRPRow = [
            'akun_id' => (int)$rayhanRPAkunId,
            'nis_nip' => (string)$rayhanRPDbNisNip,
            'role' => rayhanRPNormalizeRole((string)$rayhanRPRole),
            'nama_lengkap' => (string)$rayhanRPNamaLengkap,
            'kelas_label' => (string)$rayhanRPKelasLabel,
            'jenis_kelamin' => (string)$rayhanRPJenisKelamin,
        ];
    }
    mysqli_stmt_close($rayhanRPStmt);

    return $rayhanRPRow;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rayhanRPAction = trim((string)($_POST['action'] ?? ''));

    if (!$databaseRayhanRP) {
        $rayhanRPError = 'Koneksi database gagal.';
    } elseif ($rayhanRPAction === 'create' || $rayhanRPAction === 'update') {
        $rayhanRPForm['akun_id'] = (int)($_POST['akun_id'] ?? 0);
        $rayhanRPForm['nis_nip'] = trim((string)($_POST['nis_nip'] ?? ''));
        $rayhanRPForm['nama_lengkap'] = trim((string)($_POST['nama_lengkap'] ?? ''));
        $rayhanRPForm['kelas_label'] = trim((string)($_POST['kelas_label'] ?? ''));
        $rayhanRPForm['jenis_kelamin'] = strtoupper(trim((string)($_POST['jenis_kelamin'] ?? '')));
        $rayhanRPForm['password'] = (string)($_POST['password'] ?? '');

        $rayhanRPAkunId = (int)$rayhanRPForm['akun_id'];
        $rayhanRPNisNip = $rayhanRPForm['nis_nip'];
        $rayhanRPNamaLengkap = $rayhanRPForm['nama_lengkap'];
        $rayhanRPKelasLabel = $rayhanRPForm['kelas_label'];
        $rayhanRPJenisKelamin = in_array($rayhanRPForm['jenis_kelamin'], ['L', 'P'], true) ? $rayhanRPForm['jenis_kelamin'] : '';

        if ($rayhanRPNisNip === '' || $rayhanRPNamaLengkap === '') {
            $rayhanRPError = 'NIS/NIP dan nama lengkap wajib diisi.';
        } elseif (!preg_match('/^[A-Za-z0-9._-]{3,25}$/', $rayhanRPNisNip)) {
            $rayhanRPError = 'Format NIS/NIP tidak valid.';
        } elseif ($rayhanRPAction === 'create' && trim($rayhanRPForm['password']) === '') {
            $rayhanRPError = 'Password wajib diisi untuk akun baru.';
        } else {
            $rayhanRPExisting = rayhanRPSiswaFetchByNis($databaseRayhanRP, $rayhanRPNisNip);
            if ($rayhanRPExisting && (int)$rayhanRPExisting['akun_id'] !== $rayhanRPAkunId) {
                $rayhanRPError = 'NIS/NIP sudah dipakai akun lain.';
            } elseif ($rayhanRPAction === 'create') {
                $rayhanRPPasswordHash = rayhanRPSiswaHashPassword($rayhanRPForm['password']);
                $rayhanRPStmt = mysqli_prepare(
                    $databaseRayhanRP,
                    "INSERT INTO akun (nis_nip, password, role, nama_lengkap, kelas_label, jenis_kelamin)
                     VALUES (?, ?, 'siswa', ?, ?, ?)"
                );
                if (!$rayhanRPStmt) {
                    $rayhanRPError = 'Gagal menyiapkan query tambah siswa.';
                } else {
                    mysqli_stmt_bind_param($rayhanRPStmt, 'sssss', $rayhanRPNisNip, $rayhanRPPasswordHash, $rayhanRPNamaLengkap, $rayhanRPKelasLabel, $rayhanRPJenisKelamin);
                    if (mysqli_stmt_execute($rayhanRPStmt)) {
                        $rayhanRPSuccess = 'Akun siswa berhasil ditambahkan.';
                        $rayhanRPForm = [
                            'akun_id' => 0,
                            'nis_nip' => '',
                            'nama_lengkap' => '',
                            'kelas_label' => '',
                            'jenis_kelamin' => '',
                            'password' => '',
                        ];
                    } else {
                        $rayhanRPError = 'Gagal menambahkan akun siswa.';
                    }
                    mysqli_stmt_close($rayhanRPStmt);
                }
            } else {
                if ($rayhanRPAkunId <= 0) {
                    $rayhanRPError = 'ID siswa tidak valid.';
                } else {
                    $rayhanRPPasswordBaru = trim($rayhanRPForm['password']);
                    if ($rayhanRPPasswordBaru !== '') {
                        $rayhanRPPasswordHash = rayhanRPSiswaHashPassword($rayhanRPPasswordBaru);
                        $rayhanRPStmt = mysqli_prepare(
                            $databaseRayhanRP,
                            "UPDATE akun
                             SET nis_nip = ?, password = ?, nama_lengkap = ?, kelas_label = ?, jenis_kelamin = ?
                             WHERE akun_id = ? AND role = 'siswa'
                             LIMIT 1"
                        );
                        if ($rayhanRPStmt) {
                            mysqli_stmt_bind_param($rayhanRPStmt, 'sssssi', $rayhanRPNisNip, $rayhanRPPasswordHash, $rayhanRPNamaLengkap, $rayhanRPKelasLabel, $rayhanRPJenisKelamin, $rayhanRPAkunId);
                        }
                    } else {
                        $rayhanRPStmt = mysqli_prepare(
                            $databaseRayhanRP,
                            "UPDATE akun
                             SET nis_nip = ?, nama_lengkap = ?, kelas_label = ?, jenis_kelamin = ?
                             WHERE akun_id = ? AND role = 'siswa'
                             LIMIT 1"
                        );
                        if ($rayhanRPStmt) {
                            mysqli_stmt_bind_param($rayhanRPStmt, 'ssssi', $rayhanRPNisNip, $rayhanRPNamaLengkap, $rayhanRPKelasLabel, $rayhanRPJenisKelamin, $rayhanRPAkunId);
                        }
                    }

                    if (!$rayhanRPStmt) {
                        $rayhanRPError = 'Gagal menyiapkan query ubah siswa.';
                    } else {
                        if (mysqli_stmt_execute($rayhanRPStmt)) {
                            $rayhanRPSuccess = 'Data siswa berhasil diperbarui.';
                            $rayhanRPForm['password'] = '';
                        } else {
                            $rayhanRPError = 'Gagal memperbarui data siswa.';
                        }
                        mysqli_stmt_close($rayhanRPStmt);
                    }
                }
            }
        }
    } elseif ($rayhanRPAction === 'delete') {
        $rayhanRPAkunId = (int)($_POST['akun_id'] ?? 0);
        if ($rayhanRPAkunId <= 0) {
            $rayhanRPError = 'ID siswa tidak valid.';
        } else {
            $rayhanRPStmt = mysqli_prepare($databaseRayhanRP, "DELETE FROM akun WHERE akun_id = ? AND role = 'siswa' LIMIT 1");
            if (!$rayhanRPStmt) {
                $rayhanRPError = 'Gagal menyiapkan query hapus siswa.';
            } else {
                mysqli_stmt_bind_param($rayhanRPStmt, 'i', $rayhanRPAkunId);
                if (mysqli_stmt_execute($rayhanRPStmt) && mysqli_stmt_affected_rows($rayhanRPStmt) > 0) {
                    $rayhanRPSuccess = 'Akun siswa berhasil dihapus.';
                } else {
                    $rayhanRPError = 'Akun siswa tidak ditemukan atau gagal dihapus.';
                }
                mysqli_stmt_close($rayhanRPStmt);
            }
        }
    }
}

if ($databaseRayhanRP && isset($_GET['edit'])) {
    $rayhanRPEditId = (int)($_GET['edit'] ?? 0);
    if ($rayhanRPEditId > 0) {
        $rayhanRPEditRow = rayhanRPFetchAccountById($databaseRayhanRP, $rayhanRPEditId);
        if ($rayhanRPEditRow && (string)($rayhanRPEditRow['role'] ?? '') === 'siswa') {
            $rayhanRPForm = [
                'akun_id' => (int)$rayhanRPEditRow['akun_id'],
                'nis_nip' => (string)$rayhanRPEditRow['nis_nip'],
                'nama_lengkap' => (string)$rayhanRPEditRow['nama_lengkap'],
                'kelas_label' => (string)$rayhanRPEditRow['kelas_label'],
                'jenis_kelamin' => (string)$rayhanRPEditRow['jenis_kelamin'],
                'password' => '',
            ];
        }
    }
}

$rayhanRPKelasOptions = [];
$rayhanRPSiswaList = [];
if ($databaseRayhanRP) {
    $rayhanRPKelasResult = mysqli_query($databaseRayhanRP, "SELECT DISTINCT COALESCE(NULLIF(kelas_label, ''), '') AS kelas_label FROM akun WHERE role = 'siswa' ORDER BY kelas_label ASC");
    if ($rayhanRPKelasResult) {
        while ($rayhanRPKelasRow = mysqli_fetch_assoc($rayhanRPKelasResult)) {
            $rayhanRPKelasValue = trim((string)($rayhanRPKelasRow['kelas_label'] ?? ''));
            if ($rayhanRPKelasValue !== '') {
                $rayhanRPKelasOptions[] = $rayhanRPKelasValue;
            }
        }
        mysqli_free_result($rayhanRPKelasResult);
    }

    $rayhanRPSql = "
        SELECT akun_id, nis_nip, role, created_at,
               COALESCE(NULLIF(nama_lengkap, ''), '') AS nama_lengkap,
               COALESCE(NULLIF(kelas_label, ''), '') AS kelas_label,
               COALESCE(NULLIF(jenis_kelamin, ''), '') AS jenis_kelamin
        FROM akun
        WHERE role = 'siswa'
    ";

    if ($rayhanRPQuery !== '') {
        $rayhanRPEscapedQuery = mysqli_real_escape_string($databaseRayhanRP, $rayhanRPQuery);
        $rayhanRPSql .= " AND (nis_nip LIKE '%{$rayhanRPEscapedQuery}%' OR nama_lengkap LIKE '%{$rayhanRPEscapedQuery}%' OR kelas_label LIKE '%{$rayhanRPEscapedQuery}%')";
    }
    if ($rayhanRPFilterKelas !== '') {
        $rayhanRPEscapedKelas = mysqli_real_escape_string($databaseRayhanRP, $rayhanRPFilterKelas);
        $rayhanRPSql .= " AND kelas_label = '{$rayhanRPEscapedKelas}'";
    }
    if (in_array($rayhanRPFilterJk, ['L', 'P'], true)) {
        $rayhanRPEscapedJk = mysqli_real_escape_string($databaseRayhanRP, $rayhanRPFilterJk);
        $rayhanRPSql .= " AND jenis_kelamin = '{$rayhanRPEscapedJk}'";
    }
    $rayhanRPSql .= " ORDER BY nama_lengkap ASC, nis_nip ASC";

    $rayhanRPResult = mysqli_query($databaseRayhanRP, $rayhanRPSql);
    if ($rayhanRPResult) {
        while ($rayhanRPRow = mysqli_fetch_assoc($rayhanRPResult)) {
            $rayhanRPSiswaList[] = $rayhanRPRow;
        }
        mysqli_free_result($rayhanRPResult);
    }
}

$rayhanRPPageTitle = 'Kelola Siswa';
$rayhanRPPageSubtitle = 'Login sebagai ' . htmlspecialchars($rayhanRPAdminNisNip, ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($rayhanRPAdminRole, ENT_QUOTES, 'UTF-8') . ') | Kelola akun siswa, pencarian, filter, dan CRUD.';
rayhanRPRenderAdminLayoutStart([
    'title' => $rayhanRPPageTitle,
    'subtitle' => $rayhanRPPageSubtitle,
    'page_key' => 'siswa',
    'admin' => $rayhanRPAdmin,
]);
?>
<div class="page-stack">
    <section class="panel">
        <form method="get" class="filters">
            <div class="field wide">
                <label for="q">Cari siswa</label>
                <input id="q" name="q" placeholder="Cari NIS, nama, atau kelas" value="<?php echo htmlspecialchars($rayhanRPQuery, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="field">
                <label for="kelas">Filter kelas</label>
                <select id="kelas" name="kelas">
                    <option value="">Semua kelas</option>
                    <?php foreach ($rayhanRPKelasOptions as $rayhanRPKelasOption): ?>
                        <option value="<?php echo htmlspecialchars($rayhanRPKelasOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $rayhanRPFilterKelas === $rayhanRPKelasOption ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($rayhanRPKelasOption, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="jk">Filter JK</label>
                <select id="jk" name="jk">
                    <option value="">Semua</option>
                    <option value="L" <?php echo $rayhanRPFilterJk === 'L' ? 'selected' : ''; ?>>Laki-laki</option>
                    <option value="P" <?php echo $rayhanRPFilterJk === 'P' ? 'selected' : ''; ?>>Perempuan</option>
                </select>
            </div>
            <div class="field action-field">
                <label>&nbsp;</label>
                <button class="btn" type="submit">Terapkan</button>
                <a class="btn secondary" href="siswa_rayhanRP.php">Reset</a>
            </div>
        </form>
    </section>

    <section class="grid">
        <article class="card">
            <h3><?php echo (int)$rayhanRPForm['akun_id'] > 0 ? 'Edit Siswa' : 'Tambah Siswa'; ?></h3>
            <p class="note">Role akun otomatis disimpan sebagai <strong>siswa</strong>. Kosongkan password saat edit jika tidak ingin diubah.</p>

            <?php if ($rayhanRPError !== ''): ?>
                <div class="msg msg-err"><?php echo htmlspecialchars($rayhanRPError, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($rayhanRPSuccess !== ''): ?>
                <div class="msg msg-ok"><?php echo htmlspecialchars($rayhanRPSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="post" action="">
                <input type="hidden" name="action" value="<?php echo (int)$rayhanRPForm['akun_id'] > 0 ? 'update' : 'create'; ?>">
                <input type="hidden" name="akun_id" value="<?php echo (int)$rayhanRPForm['akun_id']; ?>">

                <div class="field">
                    <label for="nis_nip">NIS / NIP</label>
                    <input id="nis_nip" name="nis_nip" required value="<?php echo htmlspecialchars((string)$rayhanRPForm['nis_nip'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="field">
                    <label for="nama_lengkap">Nama lengkap</label>
                    <input id="nama_lengkap" name="nama_lengkap" required value="<?php echo htmlspecialchars((string)$rayhanRPForm['nama_lengkap'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="field">
                    <label for="kelas_label">Kelas</label>
                    <input id="kelas_label" name="kelas_label" value="<?php echo htmlspecialchars((string)$rayhanRPForm['kelas_label'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: XI PPLG B">
                </div>

                <div class="field">
                    <label for="jenis_kelamin">Jenis kelamin</label>
                    <select id="jenis_kelamin" name="jenis_kelamin">
                        <option value="">Pilih</option>
                        <option value="L" <?php echo (string)$rayhanRPForm['jenis_kelamin'] === 'L' ? 'selected' : ''; ?>>Laki-laki</option>
                        <option value="P" <?php echo (string)$rayhanRPForm['jenis_kelamin'] === 'P' ? 'selected' : ''; ?>>Perempuan</option>
                    </select>
                </div>

                <div class="field">
                    <label for="password">Password <?php echo (int)$rayhanRPForm['akun_id'] > 0 ? '(opsional)' : ''; ?></label>
                    <input id="password" type="password" name="password" <?php echo (int)$rayhanRPForm['akun_id'] > 0 ? '' : 'required'; ?>>
                </div>

                <div class="field-actions">
                    <button class="btn-primary" type="submit"><?php echo (int)$rayhanRPForm['akun_id'] > 0 ? 'Simpan Perubahan' : 'Tambah Siswa'; ?></button>
                    <?php if ((int)$rayhanRPForm['akun_id'] > 0): ?>
                        <a class="btn secondary" href="siswa_rayhanRP.php">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </article>

        <article class="card">
            <h3>Daftar Siswa</h3>
            <p class="note">Menampilkan <?php echo count($rayhanRPSiswaList); ?> akun siswa berdasarkan pencarian dan filter saat ini.</p>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIS / Nama</th>
                            <th>Kelas</th>
                            <th>JK</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($rayhanRPSiswaList) === 0): ?>
                            <tr>
                                <td colspan="6">Belum ada data siswa yang cocok.</td>
                            </tr>
                        <?php else: ?>
                            <?php $rayhanRPNo = 1; ?>
                            <?php foreach ($rayhanRPSiswaList as $rayhanRPSiswa): ?>
                                <tr>
                                    <td><?php echo $rayhanRPNo++; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string)$rayhanRPSiswa['nis_nip'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <br><span class="small"><?php echo htmlspecialchars((string)$rayhanRPSiswa['nama_lengkap'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td>
                                        <?php if ((string)$rayhanRPSiswa['kelas_label'] !== ''): ?>
                                            <span class="tag"><?php echo htmlspecialchars((string)$rayhanRPSiswa['kelas_label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php else: ?>
                                            <span class="small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars((string)($rayhanRPSiswa['jenis_kelamin'] !== '' ? $rayhanRPSiswa['jenis_kelamin'] : '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($rayhanRPSiswa['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="btn-sm btn-edit" href="?edit=<?php echo (int)$rayhanRPSiswa['akun_id']; ?>">Edit</a>
                                            <form method="post" action="" onsubmit="return confirm('Hapus akun siswa ini?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="akun_id" value="<?php echo (int)$rayhanRPSiswa['akun_id']; ?>">
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
<?php rayhanRPRenderAdminLayoutEnd(); ?>
