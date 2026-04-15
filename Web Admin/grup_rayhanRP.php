<?php
require_once __DIR__ . '/../koneksi_rayhanRP.php';
require_once __DIR__ . '/includes/admin_layout_rayhanRP.php';
rayhanRPStartSession();

$rayhanRPAdmin = rayhanRPRequireAdminSession('loginAdmin_rayhanRP.php');
$rayhanRPAdminId = (int)$rayhanRPAdmin['akun_id'];
$rayhanRPAdminNisNip = (string)$rayhanRPAdmin['nis_nip'];
$rayhanRPAdminLabel = (string)$rayhanRPAdmin['label'];

$rayhanRPError = '';
$rayhanRPSuccess = '';
$rayhanRPAdminRole = (string)$rayhanRPAdmin['role'];
$rayhanRPCanAccessAll = (bool)$rayhanRPAdmin['can_access_all'];

function rayhanRP_parseIntList($rayhanRPInputList)
{
    if (!is_array($rayhanRPInputList)) {
        return [];
    }

    $rayhanRPParsed = [];
    foreach ($rayhanRPInputList as $rayhanRPInputValue) {
        $rayhanRPInt = (int)$rayhanRPInputValue;
        if ($rayhanRPInt > 0) {
            $rayhanRPParsed[$rayhanRPInt] = true;
        }
    }

    return array_keys($rayhanRPParsed);
}

function rayhanRP_canManageGroup($databaseRayhanRP, $rayhanRPIdGrup, $rayhanRPAdminId, $rayhanRPCanAccessAll)
{
    if ($rayhanRPCanAccessAll) {
        $rayhanRPCheckStmt = mysqli_prepare(
            $databaseRayhanRP,
            'SELECT id_grup FROM grup WHERE id_grup = ? LIMIT 1'
        );
        if (!$rayhanRPCheckStmt) {
            return false;
        }
        mysqli_stmt_bind_param($rayhanRPCheckStmt, 'i', $rayhanRPIdGrup);
    } else {
        $rayhanRPCheckStmt = mysqli_prepare(
            $databaseRayhanRP,
            'SELECT id_grup FROM grup WHERE id_grup = ? AND dibuat_oleh_akun_id = ? LIMIT 1'
        );
        if (!$rayhanRPCheckStmt) {
            return false;
        }
        mysqli_stmt_bind_param($rayhanRPCheckStmt, 'ii', $rayhanRPIdGrup, $rayhanRPAdminId);
    }

    mysqli_stmt_execute($rayhanRPCheckStmt);
    mysqli_stmt_bind_result($rayhanRPCheckStmt, $rayhanRPFoundIdGrup);
    $rayhanRPFound = mysqli_stmt_fetch($rayhanRPCheckStmt);
    mysqli_stmt_close($rayhanRPCheckStmt);

    return (bool)$rayhanRPFound;
}

function rayhanRP_addMembersBulk($databaseRayhanRP, $rayhanRPIdGrup, $rayhanRPAkunIdList, &$rayhanRPErrorMessage = '')
{
    $rayhanRPErrorMessage = '';
    if (count($rayhanRPAkunIdList) === 0) {
        return 0;
    }

    $rayhanRPAddStmt = mysqli_prepare(
        $databaseRayhanRP,
        'INSERT INTO grup_anggota (grup_id, akun_id, joined_at, deleted_at)
         VALUES (?, ?, NOW(), NULL)
         ON DUPLICATE KEY UPDATE deleted_at = NULL, joined_at = CURRENT_TIMESTAMP'
    );
    if (!$rayhanRPAddStmt) {
        $rayhanRPErrorMessage = 'Gagal menyiapkan query tambah anggota.';
        return -1;
    }

    $rayhanRPAddedCount = 0;
    foreach ($rayhanRPAkunIdList as $rayhanRPAkunId) {
        mysqli_stmt_bind_param($rayhanRPAddStmt, 'ii', $rayhanRPIdGrup, $rayhanRPAkunId);
        if (!mysqli_stmt_execute($rayhanRPAddStmt)) {
            $rayhanRPErrorMessage = 'Gagal menambahkan sebagian anggota.';
            mysqli_stmt_close($rayhanRPAddStmt);
            return -1;
        }
        $rayhanRPAddedCount++;
    }

    mysqli_stmt_close($rayhanRPAddStmt);
    return $rayhanRPAddedCount;
}

function rayhanRP_removeMembersBulk($databaseRayhanRP, $rayhanRPIdGrup, $rayhanRPAkunIdList, &$rayhanRPErrorMessage = '')
{
    $rayhanRPErrorMessage = '';
    if (count($rayhanRPAkunIdList) === 0) {
        return 0;
    }

    $rayhanRPDeleteStmt = mysqli_prepare(
        $databaseRayhanRP,
        'UPDATE grup_anggota SET deleted_at = NOW() WHERE grup_id = ? AND akun_id = ? AND deleted_at IS NULL'
    );
    if (!$rayhanRPDeleteStmt) {
        $rayhanRPErrorMessage = 'Gagal menyiapkan query hapus anggota.';
        return -1;
    }

    $rayhanRPRemovedCount = 0;
    foreach ($rayhanRPAkunIdList as $rayhanRPAkunId) {
        mysqli_stmt_bind_param($rayhanRPDeleteStmt, 'ii', $rayhanRPIdGrup, $rayhanRPAkunId);
        if (!mysqli_stmt_execute($rayhanRPDeleteStmt)) {
            $rayhanRPErrorMessage = 'Gagal menghapus sebagian anggota.';
            mysqli_stmt_close($rayhanRPDeleteStmt);
            return -1;
        }
        if (mysqli_stmt_affected_rows($rayhanRPDeleteStmt) > 0) {
            $rayhanRPRemovedCount++;
        }
    }

    mysqli_stmt_close($rayhanRPDeleteStmt);
    return $rayhanRPRemovedCount;
}

function rayhanRP_getActiveMemberIds($databaseRayhanRP, $rayhanRPIdGrup, &$rayhanRPErrorMessage = '')
{
    $rayhanRPErrorMessage = '';
    $rayhanRPActiveIds = [];

    $rayhanRPActiveStmt = mysqli_prepare(
        $databaseRayhanRP,
        'SELECT akun_id FROM grup_anggota WHERE grup_id = ? AND deleted_at IS NULL'
    );
    if (!$rayhanRPActiveStmt) {
        $rayhanRPErrorMessage = 'Gagal menyiapkan query data anggota.';
        return null;
    }

    mysqli_stmt_bind_param($rayhanRPActiveStmt, 'i', $rayhanRPIdGrup);
    if (!mysqli_stmt_execute($rayhanRPActiveStmt)) {
        $rayhanRPErrorMessage = 'Gagal mengambil data anggota.';
        mysqli_stmt_close($rayhanRPActiveStmt);
        return null;
    }

    mysqli_stmt_bind_result($rayhanRPActiveStmt, $rayhanRPAktifAkunId);
    while (mysqli_stmt_fetch($rayhanRPActiveStmt)) {
        $rayhanRPActiveIds[] = (int)$rayhanRPAktifAkunId;
    }
    mysqli_stmt_close($rayhanRPActiveStmt);

    return $rayhanRPActiveIds;
}

$rayhanRPFormData = [
    'id_grup' => 0,
    'nama_grup' => '',
    'dibuat_oleh_akun_id' => $rayhanRPCanAccessAll ? '' : (string)$rayhanRPAdminId,
    'anggota_ids' => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$databaseRayhanRP) {
        $rayhanRPError = 'Koneksi database gagal.';
    } else {
        $rayhanRPAction = (string)($_POST['action'] ?? '');

        if ($rayhanRPAction === 'create' || $rayhanRPAction === 'update') {
            $rayhanRPFormData['id_grup'] = (int)($_POST['id_grup'] ?? 0);
            $rayhanRPFormData['nama_grup'] = trim((string)($_POST['nama_grup'] ?? ''));
            if ($rayhanRPAction === 'update') {
                $rayhanRPFormData['anggota_ids'] = rayhanRP_parseIntList($_POST['anggota_ids'] ?? []);
            } else {
                $rayhanRPFormData['anggota_ids'] = [];
            }

            if ($rayhanRPCanAccessAll) {
                $rayhanRPFormData['dibuat_oleh_akun_id'] = trim((string)($_POST['dibuat_oleh_akun_id'] ?? ''));
            } else {
                $rayhanRPFormData['dibuat_oleh_akun_id'] = (string)$rayhanRPAdminId;
            }

            $rayhanRPIdGrup = (int)$rayhanRPFormData['id_grup'];
            $rayhanRPPembuatAkunId = (int)$rayhanRPFormData['dibuat_oleh_akun_id'];

            if ($rayhanRPFormData['nama_grup'] === '') {
                $rayhanRPError = 'Nama grup wajib diisi.';
            } elseif ($rayhanRPPembuatAkunId <= 0) {
                $rayhanRPError = 'Pembuat grup tidak valid.';
            } else {
                if ($rayhanRPAction === 'create') {
                    $rayhanRPInsertStmt = mysqli_prepare(
                        $databaseRayhanRP,
                        'INSERT INTO grup (nama_grup, dibuat_oleh_akun_id) VALUES (?, ?)'
                    );
                    if (!$rayhanRPInsertStmt) {
                        $rayhanRPError = 'Gagal menyiapkan query tambah grup.';
                    } else {
                        mysqli_stmt_bind_param(
                            $rayhanRPInsertStmt,
                            'si',
                            $rayhanRPFormData['nama_grup'],
                            $rayhanRPPembuatAkunId
                        );
                        if (mysqli_stmt_execute($rayhanRPInsertStmt)) {
                            $rayhanRPSuccess = 'Grup berhasil ditambahkan. Kelola anggota melalui menu Edit.';
                            $rayhanRPFormData = [
                                'id_grup' => 0,
                                'nama_grup' => '',
                                'dibuat_oleh_akun_id' => $rayhanRPCanAccessAll ? '' : (string)$rayhanRPAdminId,
                                'anggota_ids' => [],
                            ];
                        } else {
                            $rayhanRPError = 'Gagal menambahkan grup.';
                        }
                        mysqli_stmt_close($rayhanRPInsertStmt);
                    }
                } else {
                    if ($rayhanRPIdGrup <= 0) {
                        $rayhanRPError = 'ID grup tidak valid.';
                    } elseif (!rayhanRP_canManageGroup($databaseRayhanRP, $rayhanRPIdGrup, $rayhanRPAdminId, $rayhanRPCanAccessAll)) {
                        $rayhanRPError = 'Grup tidak ditemukan atau Anda tidak memiliki akses.';
                    } else {
                        $rayhanRPUpdateCommitted = false;
                        if (!mysqli_begin_transaction($databaseRayhanRP)) {
                            $rayhanRPError = 'Gagal memulai transaksi edit grup.';
                        } else {
                            if ($rayhanRPCanAccessAll) {
                                $rayhanRPUpdateStmt = mysqli_prepare(
                                    $databaseRayhanRP,
                                    'UPDATE grup SET nama_grup = ?, dibuat_oleh_akun_id = ? WHERE id_grup = ? LIMIT 1'
                                );
                                if ($rayhanRPUpdateStmt) {
                                    mysqli_stmt_bind_param(
                                        $rayhanRPUpdateStmt,
                                        'sii',
                                        $rayhanRPFormData['nama_grup'],
                                        $rayhanRPPembuatAkunId,
                                        $rayhanRPIdGrup
                                    );
                                }
                            } else {
                                $rayhanRPUpdateStmt = mysqli_prepare(
                                    $databaseRayhanRP,
                                    'UPDATE grup SET nama_grup = ? WHERE id_grup = ? AND dibuat_oleh_akun_id = ? LIMIT 1'
                                );
                                if ($rayhanRPUpdateStmt) {
                                    mysqli_stmt_bind_param(
                                        $rayhanRPUpdateStmt,
                                        'sii',
                                        $rayhanRPFormData['nama_grup'],
                                        $rayhanRPIdGrup,
                                        $rayhanRPAdminId
                                    );
                                }
                            }

                            if (!$rayhanRPUpdateStmt) {
                                $rayhanRPError = 'Gagal menyiapkan query edit grup.';
                            } elseif (!mysqli_stmt_execute($rayhanRPUpdateStmt)) {
                                $rayhanRPError = 'Gagal memperbarui grup.';
                            }
                            if (isset($rayhanRPUpdateStmt) && $rayhanRPUpdateStmt) {
                                mysqli_stmt_close($rayhanRPUpdateStmt);
                            }

                            if ($rayhanRPError === '') {
                                $rayhanRPAnggotaLoadError = '';
                                $rayhanRPExistingMemberIds = rayhanRP_getActiveMemberIds(
                                    $databaseRayhanRP,
                                    $rayhanRPIdGrup,
                                    $rayhanRPAnggotaLoadError
                                );

                                if ($rayhanRPExistingMemberIds === null) {
                                    $rayhanRPError = $rayhanRPAnggotaLoadError !== '' ? $rayhanRPAnggotaLoadError : 'Gagal memuat anggota grup.';
                                } else {
                                    $rayhanRPToAddIds = array_values(array_diff($rayhanRPFormData['anggota_ids'], $rayhanRPExistingMemberIds));
                                    $rayhanRPToRemoveIds = array_values(array_diff($rayhanRPExistingMemberIds, $rayhanRPFormData['anggota_ids']));
                                    $rayhanRPAddedCount = 0;
                                    $rayhanRPRemovedCount = 0;

                                    if (count($rayhanRPToAddIds) > 0) {
                                        $rayhanRPAddMemberError = '';
                                        $rayhanRPAddedCount = rayhanRP_addMembersBulk(
                                            $databaseRayhanRP,
                                            $rayhanRPIdGrup,
                                            $rayhanRPToAddIds,
                                            $rayhanRPAddMemberError
                                        );
                                        if ($rayhanRPAddedCount < 0) {
                                            $rayhanRPError = $rayhanRPAddMemberError !== '' ? $rayhanRPAddMemberError : 'Gagal menambahkan anggota grup.';
                                        }
                                    }

                                    if ($rayhanRPError === '' && count($rayhanRPToRemoveIds) > 0) {
                                        $rayhanRPRemoveMemberError = '';
                                        $rayhanRPRemovedCount = rayhanRP_removeMembersBulk(
                                            $databaseRayhanRP,
                                            $rayhanRPIdGrup,
                                            $rayhanRPToRemoveIds,
                                            $rayhanRPRemoveMemberError
                                        );
                                        if ($rayhanRPRemovedCount < 0) {
                                            $rayhanRPError = $rayhanRPRemoveMemberError !== '' ? $rayhanRPRemoveMemberError : 'Gagal menghapus anggota grup.';
                                        }
                                    }

                                    if ($rayhanRPError === '') {
                                        if (!mysqli_commit($databaseRayhanRP)) {
                                            $rayhanRPError = 'Gagal menyimpan transaksi edit grup.';
                                        } else {
                                            $rayhanRPUpdateCommitted = true;
                                            $rayhanRPPerubahanAnggota = [];
                                            if ($rayhanRPAddedCount > 0) {
                                                $rayhanRPPerubahanAnggota[] = $rayhanRPAddedCount . ' ditambah';
                                            }
                                            if ($rayhanRPRemovedCount > 0) {
                                                $rayhanRPPerubahanAnggota[] = $rayhanRPRemovedCount . ' dihapus';
                                            }
                                            $rayhanRPSuccess = 'Grup berhasil diperbarui.';
                                            if (count($rayhanRPPerubahanAnggota) > 0) {
                                                $rayhanRPSuccess .= ' Anggota: ' . implode(', ', $rayhanRPPerubahanAnggota) . '.';
                                            }
                                        }
                                    }
                                }
                            }

                            if (!$rayhanRPUpdateCommitted) {
                                mysqli_rollback($databaseRayhanRP);
                            }
                        }
                    }
                }
            }
        } elseif ($rayhanRPAction === 'delete') {
            $rayhanRPIdGrup = (int)($_POST['id_grup'] ?? 0);
            if ($rayhanRPIdGrup <= 0) {
                $rayhanRPError = 'ID grup tidak valid.';
            } else {
                if ($rayhanRPCanAccessAll) {
                    $rayhanRPDeleteStmt = mysqli_prepare($databaseRayhanRP, 'DELETE FROM grup WHERE id_grup = ? LIMIT 1');
                    if ($rayhanRPDeleteStmt) {
                        mysqli_stmt_bind_param($rayhanRPDeleteStmt, 'i', $rayhanRPIdGrup);
                    }
                } else {
                    $rayhanRPDeleteStmt = mysqli_prepare(
                        $databaseRayhanRP,
                        'DELETE FROM grup WHERE id_grup = ? AND dibuat_oleh_akun_id = ? LIMIT 1'
                    );
                    if ($rayhanRPDeleteStmt) {
                        mysqli_stmt_bind_param($rayhanRPDeleteStmt, 'ii', $rayhanRPIdGrup, $rayhanRPAdminId);
                    }
                }

                if (!$rayhanRPDeleteStmt) {
                    $rayhanRPError = 'Gagal menyiapkan query hapus grup.';
                } else {
                    if (mysqli_stmt_execute($rayhanRPDeleteStmt) && mysqli_stmt_affected_rows($rayhanRPDeleteStmt) > 0) {
                        $rayhanRPSuccess = 'Grup berhasil dihapus.';
                    } else {
                        $rayhanRPDbDeleteError = mysqli_stmt_error($rayhanRPDeleteStmt);
                        if ($rayhanRPDbDeleteError !== '') {
                            $rayhanRPError = 'Gagal menghapus grup: relasi data masih digunakan.';
                        } else {
                            $rayhanRPError = 'Grup tidak ditemukan atau tidak bisa dihapus.';
                        }
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
            'SELECT id_grup, nama_grup, dibuat_oleh_akun_id FROM grup WHERE id_grup = ? LIMIT 1'
        );
        if ($rayhanRPEditStmt) {
            mysqli_stmt_bind_param($rayhanRPEditStmt, 'i', $rayhanRPEditId);
        }
    } else {
        $rayhanRPEditStmt = mysqli_prepare(
            $databaseRayhanRP,
            'SELECT id_grup, nama_grup, dibuat_oleh_akun_id FROM grup WHERE id_grup = ? AND dibuat_oleh_akun_id = ? LIMIT 1'
        );
        if ($rayhanRPEditStmt) {
            mysqli_stmt_bind_param($rayhanRPEditStmt, 'ii', $rayhanRPEditId, $rayhanRPAdminId);
        }
    }

    if (isset($rayhanRPEditStmt) && $rayhanRPEditStmt) {
        mysqli_stmt_execute($rayhanRPEditStmt);
        mysqli_stmt_bind_result(
            $rayhanRPEditStmt,
            $rayhanRPEditIdValue,
            $rayhanRPEditNamaValue,
            $rayhanRPEditPembuatValue
        );
        $rayhanRPEditFound = mysqli_stmt_fetch($rayhanRPEditStmt);
        mysqli_stmt_close($rayhanRPEditStmt);

        if ($rayhanRPEditFound) {
            $rayhanRPFormData['id_grup'] = (int)$rayhanRPEditIdValue;
            $rayhanRPFormData['nama_grup'] = (string)$rayhanRPEditNamaValue;
            $rayhanRPFormData['dibuat_oleh_akun_id'] = (string)$rayhanRPEditPembuatValue;

            $rayhanRPEditAnggotaError = '';
            $rayhanRPEditAnggotaIds = rayhanRP_getActiveMemberIds(
                $databaseRayhanRP,
                (int)$rayhanRPFormData['id_grup'],
                $rayhanRPEditAnggotaError
            );
            if ($rayhanRPEditAnggotaIds !== null) {
                $rayhanRPFormData['anggota_ids'] = $rayhanRPEditAnggotaIds;
            }
        } else {
            $rayhanRPFormData['id_grup'] = 0;
        }
    }
}

$rayhanRPPembuatOptions = [];
if ($databaseRayhanRP) {
    if ($rayhanRPCanAccessAll) {
        $rayhanRPPembuatSql = "SELECT akun_id, nis_nip, role FROM akun ORDER BY role DESC, nis_nip ASC";
        $rayhanRPPembuatResult = mysqli_query($databaseRayhanRP, $rayhanRPPembuatSql);
        if ($rayhanRPPembuatResult) {
            while ($rayhanRPRow = mysqli_fetch_assoc($rayhanRPPembuatResult)) {
                $rayhanRPPembuatOptions[] = $rayhanRPRow;
            }
            mysqli_free_result($rayhanRPPembuatResult);
        }
    } else {
        $rayhanRPPembuatStmt = mysqli_prepare(
            $databaseRayhanRP,
            "SELECT akun_id, nis_nip, role FROM akun WHERE akun_id = ? LIMIT 1"
        );
        if ($rayhanRPPembuatStmt) {
            mysqli_stmt_bind_param($rayhanRPPembuatStmt, 'i', $rayhanRPAdminId);
            mysqli_stmt_execute($rayhanRPPembuatStmt);
            mysqli_stmt_bind_result($rayhanRPPembuatStmt, $rayhanRPAkunIdOpt, $rayhanRPNisNipOpt, $rayhanRPRoleOpt);
            while (mysqli_stmt_fetch($rayhanRPPembuatStmt)) {
                $rayhanRPPembuatOptions[] = [
                    'akun_id' => $rayhanRPAkunIdOpt,
                    'nis_nip' => $rayhanRPNisNipOpt,
                    'role' => $rayhanRPRoleOpt,
                ];
            }
            mysqli_stmt_close($rayhanRPPembuatStmt);
        }
    }
}

$rayhanRPAkunAnggotaOptions = [];
if ($databaseRayhanRP && (int)$rayhanRPFormData['id_grup'] > 0) {
    $rayhanRPAkunAnggotaSql = "SELECT akun_id, nis_nip, role FROM akun ORDER BY nis_nip ASC";
    $rayhanRPAkunAnggotaResult = mysqli_query($databaseRayhanRP, $rayhanRPAkunAnggotaSql);
    if ($rayhanRPAkunAnggotaResult) {
        while ($rayhanRPRow = mysqli_fetch_assoc($rayhanRPAkunAnggotaResult)) {
            $rayhanRPAkunAnggotaOptions[] = $rayhanRPRow;
        }
        mysqli_free_result($rayhanRPAkunAnggotaResult);
    }
}

$rayhanRPAnggotaTerpilihMap = [];
foreach ((array)$rayhanRPFormData['anggota_ids'] as $rayhanRPAnggotaTerpilihId) {
    $rayhanRPAnggotaTerpilihMap[(int)$rayhanRPAnggotaTerpilihId] = true;
}

$rayhanRPGrupList = [];
if ($databaseRayhanRP) {
    if ($rayhanRPCanAccessAll) {
        $rayhanRPListSql = "
            SELECT
                g.id_grup,
                g.nama_grup,
                g.dibuat_oleh_akun_id,
                COALESCE(a.nis_nip, '-') AS pembuat_nis_nip,
                COALESCE(a.role, '-') AS pembuat_role,
                COALESCE(ga.jumlah_anggota, 0) AS jumlah_anggota,
                COALESCE(j.jumlah_jadwal, 0) AS jumlah_jadwal
            FROM grup g
            LEFT JOIN akun a ON a.akun_id = g.dibuat_oleh_akun_id
            LEFT JOIN (
                SELECT grup_id, COUNT(*) AS jumlah_anggota
                FROM grup_anggota
                WHERE deleted_at IS NULL
                GROUP BY grup_id
            ) ga ON ga.grup_id = g.id_grup
            LEFT JOIN (
                SELECT grup_id, COUNT(*) AS jumlah_jadwal
                FROM jadwal
                GROUP BY grup_id
            ) j ON j.grup_id = g.id_grup
            ORDER BY g.id_grup DESC
        ";
        $rayhanRPListResult = mysqli_query($databaseRayhanRP, $rayhanRPListSql);
        if ($rayhanRPListResult) {
            while ($rayhanRPRow = mysqli_fetch_assoc($rayhanRPListResult)) {
                $rayhanRPGrupList[] = $rayhanRPRow;
            }
            mysqli_free_result($rayhanRPListResult);
        }
    } else {
        $rayhanRPListStmt = mysqli_prepare(
            $databaseRayhanRP,
            "SELECT
                g.id_grup,
                g.nama_grup,
                g.dibuat_oleh_akun_id,
                COALESCE(a.nis_nip, '-') AS pembuat_nis_nip,
                COALESCE(a.role, '-') AS pembuat_role,
                COALESCE(ga.jumlah_anggota, 0) AS jumlah_anggota,
                COALESCE(j.jumlah_jadwal, 0) AS jumlah_jadwal
             FROM grup g
             LEFT JOIN akun a ON a.akun_id = g.dibuat_oleh_akun_id
             LEFT JOIN (
                SELECT grup_id, COUNT(*) AS jumlah_anggota
                FROM grup_anggota
                WHERE deleted_at IS NULL
                GROUP BY grup_id
             ) ga ON ga.grup_id = g.id_grup
             LEFT JOIN (
                SELECT grup_id, COUNT(*) AS jumlah_jadwal
                FROM jadwal
                GROUP BY grup_id
             ) j ON j.grup_id = g.id_grup
             WHERE g.dibuat_oleh_akun_id = ?
             ORDER BY g.id_grup DESC"
        );
        if ($rayhanRPListStmt) {
            mysqli_stmt_bind_param($rayhanRPListStmt, 'i', $rayhanRPAdminId);
            mysqli_stmt_execute($rayhanRPListStmt);
            mysqli_stmt_bind_result(
                $rayhanRPListStmt,
                $rayhanRPIdGrup,
                $rayhanRPNamaGrup,
                $rayhanRPDibuatOleh,
                $rayhanRPPembuatNisNip,
                $rayhanRPPembuatRole,
                $rayhanRPJumlahAnggota,
                $rayhanRPJumlahJadwal
            );
            while (mysqli_stmt_fetch($rayhanRPListStmt)) {
                $rayhanRPGrupList[] = [
                    'id_grup' => $rayhanRPIdGrup,
                    'nama_grup' => $rayhanRPNamaGrup,
                    'dibuat_oleh_akun_id' => $rayhanRPDibuatOleh,
                    'pembuat_nis_nip' => $rayhanRPPembuatNisNip,
                    'pembuat_role' => $rayhanRPPembuatRole,
                    'jumlah_anggota' => $rayhanRPJumlahAnggota,
                    'jumlah_jadwal' => $rayhanRPJumlahJadwal,
                ];
            }
            mysqli_stmt_close($rayhanRPListStmt);
        }
    }
}

$rayhanRPPageTitle = 'Kelola Grup';
$rayhanRPPageSubtitle = 'Login sebagai ' . htmlspecialchars($rayhanRPAdminNisNip, ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($rayhanRPAdminRole, ENT_QUOTES, 'UTF-8') . ') | Urutan kerja: Grup -> Jadwal -> Tugas -> Notifikasi';
rayhanRPRenderAdminLayoutStart([
    'title' => $rayhanRPPageTitle,
    'subtitle' => $rayhanRPPageSubtitle,
    'page_key' => 'grup',
    'admin' => $rayhanRPAdmin,
]);
?>
<div class="page-stack">
<section class="grid">
    <article class="card">
        <h3><?php echo $rayhanRPFormData['id_grup'] > 0 ? 'Edit Grup' : 'Tambah Grup'; ?></h3>
        <p class="note">Isi nama grup dan pembuat grup untuk menyimpan data.</p>

        <?php if ($rayhanRPError !== ''): ?>
            <div class="msg msg-err"><?php echo htmlspecialchars($rayhanRPError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($rayhanRPSuccess !== ''): ?>
            <div class="msg msg-ok"><?php echo htmlspecialchars($rayhanRPSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <input type="hidden" name="action" value="<?php echo $rayhanRPFormData['id_grup'] > 0 ? 'update' : 'create'; ?>">
            <input type="hidden" name="id_grup" value="<?php echo (int)$rayhanRPFormData['id_grup']; ?>">

            <div class="field">
                <label for="nama_grup">Nama Grup</label>
                <input type="text" name="nama_grup" id="nama_grup" required value="<?php echo htmlspecialchars((string)$rayhanRPFormData['nama_grup'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="field">
                <label for="dibuat_oleh_akun_id">Dibuat Oleh</label>
                <?php if ($rayhanRPCanAccessAll): ?>
                    <select name="dibuat_oleh_akun_id" id="dibuat_oleh_akun_id" required>
                        <option value="">Pilih akun</option>
                    <?php foreach ($rayhanRPPembuatOptions as $rayhanRPPembuat): ?>
                        <option value="<?php echo (int)$rayhanRPPembuat['akun_id']; ?>" <?php echo ((int)$rayhanRPFormData['dibuat_oleh_akun_id'] === (int)$rayhanRPPembuat['akun_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string)$rayhanRPPembuat['nis_nip'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars((string)$rayhanRPPembuat['role'], ENT_QUOTES, 'UTF-8'); ?>)
                        </option>
                    <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="hidden" name="dibuat_oleh_akun_id" value="<?php echo (int)$rayhanRPAdminId; ?>">
                    <input type="text" value="<?php echo htmlspecialchars($rayhanRPAdminNisNip . ' (' . $rayhanRPAdminRole . ')', ENT_QUOTES, 'UTF-8'); ?>" readonly>
                <?php endif; ?>
            </div>

            <?php if ((int)$rayhanRPFormData['id_grup'] > 0): ?>
                <div class="field">
                    <label for="rayhanRPFilterAnggota">Anggota Grup</label>
                    <input
                        id="rayhanRPFilterAnggota"
                        class="member-search"
                        type="text"
                        placeholder="Cari NIS/NIP atau role..."
                        oninput="rayhanRPFilterMembers(this.value)">
                    <div class="member-toolbar">
                        <button class="member-btn" type="button" onclick="rayhanRPSetAllMembers(true)">Pilih Semua</button>
                        <button class="member-btn" type="button" onclick="rayhanRPSetAllMembers(false)">Kosongkan</button>
                    </div>
                    <div class="member-list" id="rayhanRPListAnggota">
                        <?php foreach ($rayhanRPAkunAnggotaOptions as $rayhanRPAkunOption): ?>
                            <?php $rayhanRPLabelAnggota = (string)$rayhanRPAkunOption['nis_nip'] . ' (' . (string)$rayhanRPAkunOption['role'] . ')'; ?>
                            <label class="member-item" data-search="<?php echo htmlspecialchars(strtolower($rayhanRPLabelAnggota), ENT_QUOTES, 'UTF-8'); ?>">
                                <input
                                    type="checkbox"
                                    name="anggota_ids[]"
                                    value="<?php echo (int)$rayhanRPAkunOption['akun_id']; ?>"
                                    <?php echo isset($rayhanRPAnggotaTerpilihMap[(int)$rayhanRPAkunOption['akun_id']]) ? 'checked' : ''; ?>>
                                <span><?php echo htmlspecialchars($rayhanRPLabelAnggota, ENT_QUOTES, 'UTF-8'); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <small class="hint">
                        Centang anggota aktif grup ini. Terpilih: <strong id="rayhanRPCountAnggota"><?php echo count($rayhanRPFormData['anggota_ids']); ?></strong>.
                    </small>
                </div>
            <?php endif; ?>

            <div class="field-actions">
                <button class="btn-primary" type="submit"><?php echo $rayhanRPFormData['id_grup'] > 0 ? 'Simpan Perubahan' : 'Tambah Grup'; ?></button>
                <?php if ((int)$rayhanRPFormData['id_grup'] > 0): ?>
                    <a class="btn secondary" href="grup_rayhanRP.php">Batal Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </article>

    <article class="card">
        <h3>Daftar Grup</h3>
        <p class="note">Data grup yang dapat Anda kelola saat ini.</p>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Grup</th>
                        <th>Pembuat</th>
                        <th>Anggota</th>
                        <th>Jadwal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rayhanRPGrupList) === 0): ?>
                        <tr>
                            <td colspan="6">Belum ada data grup.</td>
                        </tr>
                    <?php else: ?>
                        <?php $rayhanRPNo = 1; ?>
                        <?php foreach ($rayhanRPGrupList as $rayhanRPGrup): ?>
                            <tr>
                                <td><?php echo $rayhanRPNo++; ?></td>
                                <td><strong><?php echo htmlspecialchars((string)$rayhanRPGrup['nama_grup'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars((string)$rayhanRPGrup['pembuat_nis_nip'], ENT_QUOTES, 'UTF-8'); ?>
                                    <span class="tag"><?php echo htmlspecialchars((string)$rayhanRPGrup['pembuat_role'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td><?php echo (int)$rayhanRPGrup['jumlah_anggota']; ?></td>
                                <td><?php echo (int)$rayhanRPGrup['jumlah_jadwal']; ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a class="btn-sm btn-edit" href="?edit=<?php echo (int)$rayhanRPGrup['id_grup']; ?>">Edit</a>
                                        <form method="post" action="" onsubmit="return confirm('Hapus grup ini?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id_grup" value="<?php echo (int)$rayhanRPGrup['id_grup']; ?>">
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
<script>
    var rayhanRPAnggotaTerpilihAwal = <?php echo json_encode(array_map('intval', (array)$rayhanRPFormData['anggota_ids']), JSON_UNESCAPED_UNICODE); ?>;

    function rayhanRPGetMemberCheckboxes() {
        return Array.prototype.slice.call(document.querySelectorAll('input[name="anggota_ids[]"]'));
    }

    function rayhanRPUpdateMemberCount() {
        var rayhanRPCountNode = document.getElementById('rayhanRPCountAnggota');
        if (!rayhanRPCountNode) {
            return;
        }
        var rayhanRPCheckedCount = rayhanRPGetMemberCheckboxes().filter(function (rayhanRPCheckbox) {
            return rayhanRPCheckbox.checked;
        }).length;
        rayhanRPCountNode.textContent = String(rayhanRPCheckedCount);
    }

    function rayhanRPSetAllMembers(rayhanRPChecked) {
        rayhanRPGetMemberCheckboxes().forEach(function (rayhanRPCheckbox) {
            rayhanRPCheckbox.checked = rayhanRPChecked;
        });
        rayhanRPUpdateMemberCount();
    }

    function rayhanRPFilterMembers(rayhanRPKeyword) {
        var rayhanRPNormalizedKeyword = String(rayhanRPKeyword || '').toLowerCase().trim();
        var rayhanRPItems = Array.prototype.slice.call(document.querySelectorAll('#rayhanRPListAnggota .member-item'));
        rayhanRPItems.forEach(function (rayhanRPItem) {
            var rayhanRPSearchText = String(rayhanRPItem.getAttribute('data-search') || '');
            var rayhanRPVisible = rayhanRPNormalizedKeyword === '' || rayhanRPSearchText.indexOf(rayhanRPNormalizedKeyword) !== -1;
            rayhanRPItem.style.display = rayhanRPVisible ? 'flex' : 'none';
        });
    }

    document.addEventListener('change', function (rayhanRPEvent) {
        if (rayhanRPEvent.target && rayhanRPEvent.target.name === 'anggota_ids[]') {
            rayhanRPUpdateMemberCount();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        rayhanRPUpdateMemberCount();
    });
</script>
<?php rayhanRPRenderAdminLayoutEnd(); ?>
