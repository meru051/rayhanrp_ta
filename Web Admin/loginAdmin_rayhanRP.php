<?php
require_once __DIR__ . '/../koneksi_rayhanRP.php';
session_start();

$rayhanRPError = '';
$rayhanRPSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rayhanRPNisNip = trim((string)($_POST['nip_nis'] ?? ''));
    $rayhanRPPassword = (string)($_POST['password'] ?? '');

    if ($rayhanRPNisNip === '' || $rayhanRPPassword === '') {
        $rayhanRPError = 'NIP/NIS dan password wajib diisi.';
    } elseif (!$databaseRayhanRP) {
        $rayhanRPError = 'Koneksi database gagal.';
    } else {
        $rayhanRPStmt = mysqli_prepare(
            $databaseRayhanRP,
            'SELECT akun_id, nis_nip, password, role FROM akun WHERE nis_nip = ? LIMIT 1'
        );

        if (!$rayhanRPStmt) {
            $rayhanRPError = 'Gagal menyiapkan query login.';
        } else {
            mysqli_stmt_bind_param($rayhanRPStmt, 's', $rayhanRPNisNip);
            $rayhanRPExecuted = mysqli_stmt_execute($rayhanRPStmt);
            mysqli_stmt_bind_result($rayhanRPStmt, $rayhanRPIdAkun, $rayhanRPDbNisNip, $rayhanRPDbPassword, $rayhanRPDbRole);
            $rayhanRPFound = $rayhanRPExecuted && mysqli_stmt_fetch($rayhanRPStmt);
            mysqli_stmt_close($rayhanRPStmt);

            if (!$rayhanRPFound) {
                $rayhanRPError = 'Akun tidak ditemukan.';
            } elseif ($rayhanRPDbRole !== 'admin' && $rayhanRPDbRole !== 'guru') {
                $rayhanRPError = 'Akun ini bukan admin.';
            } else {
                $rayhanRPIsValid = password_verify($rayhanRPPassword, (string)$rayhanRPDbPassword);

                if (!$rayhanRPIsValid && hash_equals((string)$rayhanRPDbPassword, $rayhanRPPassword)) {
                    $rayhanRPNewHash = password_hash($rayhanRPPassword, PASSWORD_DEFAULT);
                    if ($rayhanRPNewHash !== false) {
                        $rayhanRPUpdateStmt = mysqli_prepare($databaseRayhanRP, 'UPDATE akun SET password = ? WHERE akun_id = ? LIMIT 1');
                        if ($rayhanRPUpdateStmt) {
                            mysqli_stmt_bind_param($rayhanRPUpdateStmt, 'si', $rayhanRPNewHash, $rayhanRPIdAkun);
                            mysqli_stmt_execute($rayhanRPUpdateStmt);
                            mysqli_stmt_close($rayhanRPUpdateStmt);
                            $rayhanRPIsValid = true;
                        }
                    }
                }

                if (!$rayhanRPIsValid) {
                    $rayhanRPError = 'Password salah.';
                } else {
                    $_SESSION['rayhanRP_admin_login'] = true;
                    $_SESSION['rayhanRP_admin_id'] = (int)$rayhanRPIdAkun;
                    $_SESSION['rayhanRP_admin_nis_nip'] = (string)$rayhanRPDbNisNip;
                    header('Location: adminWeb_rayhanRP.php');
                    exit;
                    }

                    $rayhanRPSuccess = 'Login admin berhasil.';
                }
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin RayhanRP</title>
    <style>
        :root {
            --bg-a: #f8fafc;
            --bg-b: #e2e8f0;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #475569;
            --line: #cbd5e1;
            --line-focus: #2563eb;
            --btn: #2563eb;
            --btn-hover: #1d4ed8;
            --error-bg: #fee2e2;
            --error-text: #991b1b;
            --ok-bg: #dcfce7;
            --ok-text: #166534;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: radial-gradient(circle at top right, var(--bg-b), var(--bg-a) 45%);
            color: var(--text-main);
        }

        .login-card {
            width: 100%;
            max-width: 540px;
            background: var(--card-bg);
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
            overflow: hidden;
        }

        .login-header {
            padding: 30px 34px 16px;
        }

        .login-title {
            margin: 0;
            font-size: 1.85rem;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .login-subtitle {
            margin: 10px 0 0;
            color: var(--text-muted);
            font-size: 1.05rem;
            line-height: 1.5;
        }

        .login-body {
            padding: 20px 34px 34px;
        }

        .field {
            margin-bottom: 20px;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .field input {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 15px 16px;
            font-size: 1.05rem;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .field input:focus {
            border-color: var(--line-focus);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .login-btn {
            width: 100%;
            border: 0;
            border-radius: 12px;
            padding: 14px;
            background: var(--btn);
            color: #fff;
            font-weight: 600;
            font-size: 1.05rem;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .login-btn:hover {
            background: var(--btn-hover);
        }

        .msg {
            margin-top: 16px;
            border-radius: 12px;
            padding: 13px 14px;
            font-size: 1rem;
            line-height: 1.4;
        }

        .msg-error {
            background: var(--error-bg);
            color: var(--error-text);
        }

        .msg-ok {
            background: var(--ok-bg);
            color: var(--ok-text);
        }

        @media (max-width: 480px) {
            .login-header {
                padding: 24px 22px 12px;
            }

            .login-body {
                padding: 16px 22px 24px;
            }
        }
    </style>
</head>

<body>
    <main class="login-card">
        <section class="login-header">
            <h1 class="login-title">Admin Login</h1>
            <p class="login-subtitle">Masuk menggunakan NIP/NIS dan password untuk mengakses panel admin.</p>
        </section>

        <section class="login-body">
            <form method="POST" action="">
                <div class="field">
                    <label for="nip_nis">NIP/NIS</label>
                    <input id="nip_nis" type="text" name="nip_nis" autocomplete="username" required value="<?php echo htmlspecialchars((string)($_POST['nip_nis'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" autocomplete="current-password" required>
                </div>

                <button class="login-btn" type="submit">Masuk</button>
            </form>

            <?php if ($rayhanRPError !== ''): ?>
                <p class="msg msg-error"><?php echo htmlspecialchars($rayhanRPError, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <?php if ($rayhanRPSuccess !== ''): ?>
                <p class="msg msg-ok"><?php echo htmlspecialchars($rayhanRPSuccess, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
        </section>
    </main>
</body>

</html>

