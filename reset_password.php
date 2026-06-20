<?php
session_start();
include 'admin/koneksi.php';

$message = '';
$message_type = '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$valid_token = false;
$email = '';

// Validasi token dari URL
if (!empty($token)) {
    $token_safe = mysqli_real_escape_string($koneksi, $token);
    $query = mysqli_query($koneksi, "SELECT email FROM t_password_reset WHERE token='$token_safe' AND expiry > NOW()");
    if (mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);
        $email = $row['email'];
        // Pastikan email ada di t_user (bukan admin)
        $email_safe = mysqli_real_escape_string($koneksi, $email);
        $cek_user = mysqli_query($koneksi, "SELECT id_user FROM t_user WHERE email='$email_safe'");
        if (mysqli_num_rows($cek_user) > 0) {
            $valid_token = true;
        } else {
            $message = 'Token tidak valid untuk akun ini.';
            $message_type = 'error';
        }
    } else {
        $message = 'Link sudah tidak berlaku atau tidak valid. Silakan minta link baru.';
        $message_type = 'error';
    }
} else {
    $message = 'Token tidak ditemukan. Silakan minta link reset password kembali.';
    $message_type = 'error';
}

// Proses form reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $token_post = trim($_POST['token']);
    $token_post_safe = mysqli_real_escape_string($koneksi, $token_post);
    $pass_baru   = $_POST['password_baru'] ?? '';
    $pass_ulang  = $_POST['password_ulang'] ?? '';

    if (empty($pass_baru) || empty($pass_ulang)) {
        $message = 'Password tidak boleh kosong!';
        $message_type = 'error';
    } elseif ($pass_baru !== $pass_ulang) {
        $message = 'Konfirmasi password tidak cocok!';
        $message_type = 'error';
    } elseif (strlen($pass_baru) < 6) {
        $message = 'Password minimal 6 karakter!';
        $message_type = 'error';
    } else {
        $check = mysqli_query($koneksi, "SELECT email FROM t_password_reset WHERE token='$token_post_safe' AND expiry > NOW()");
        if (mysqli_num_rows($check) > 0) {
            $row2  = mysqli_fetch_assoc($check);
            $email = $row2['email'];
            $email_safe = mysqli_real_escape_string($koneksi, $email);

            $hash = password_hash($pass_baru, PASSWORD_BCRYPT);
            $update = mysqli_query($koneksi, "UPDATE t_user SET password='$hash' WHERE email='$email_safe'");

            if ($update) {
                mysqli_query($koneksi, "DELETE FROM t_password_reset WHERE token='$token_post_safe'");
                unset($_SESSION['user_reset_url']);
                $message = 'Password berhasil diperbarui! Silakan login dengan password baru Anda.';
                $message_type = 'success';
                $valid_token = false;
            } else {
                $message = 'Gagal memperbarui password. Coba lagi nanti.';
                $message_type = 'error';
            }
        } else {
            $message = 'Token tidak valid atau sudah kadaluarsa. Silakan minta link baru.';
            $message_type = 'error';
            $valid_token = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - DensCreative</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --brand: #4e60ff; --brand-dark: #3b4dcc; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #eef1ff 0%, #f8f9ff 100%);
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh; margin: 0; padding: 20px;
        }
        .auth-card {
            width: 100%; max-width: 420px; background: #fff;
            border-radius: 18px; padding: 36px 32px;
            box-shadow: 0 12px 40px rgba(78,96,255,.12);
        }
        .brand-title { text-align: center; font-weight: 700; color: var(--brand); margin-bottom: 4px; }
        .brand-sub { text-align: center; color: #888; font-size: 13px; margin-bottom: 26px; }
        .form-label { font-weight: 600; font-size: 13.5px; color: #333; margin-bottom: 6px; }
        .form-control { border-radius: 10px; padding: 11px 14px; border: 1px solid #d8d8e8; font-size: 14px; }
        .form-control:focus { box-shadow: none; border-color: var(--brand); }
        .form-control:disabled { background: #f8f9ff; color: #555; }
        .btn-brand {
            background: var(--brand); color: #fff; font-weight: 600; font-size: 15px;
            border-radius: 50px; padding: 11px; width: 100%; border: none; transition: .25s; margin-top: 6px;
        }
        .btn-brand:hover { background: var(--brand-dark); color: #fff; }
        .back-link { text-align: center; margin-top: 18px; font-size: 13px; }
        .back-link a { color: var(--brand); text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
        .password-hint { font-size: 12px; color: #999; margin-top: 5px; }
        .icon-wrapper { text-align: center; margin-bottom: 16px; }
        .icon-wrapper i { font-size: 48px; color: var(--brand); opacity: .85; }
        .input-group-text {
            background: transparent; border-left: none; cursor: pointer;
            border-radius: 0 10px 10px 0 !important; border-color: #d8d8e8;
        }
        .input-group .form-control { border-right: none; border-radius: 10px 0 0 10px !important; }
        .input-group .form-control:focus { border-color: var(--brand); }
        .input-group:focus-within .input-group-text { border-color: var(--brand); }
    </style>
</head>
<body>
<div class="auth-card">
    <div class="icon-wrapper"><i class="fa-solid fa-key"></i></div>
    <h3 class="brand-title">DensCreative</h3>
    <p class="brand-sub">Buat password baru</p>

    <?php if (!empty($message)): ?>
        <?php if ($message_type === 'success'): ?>
            <div class="alert alert-success" role="alert">
                <i class="fa-solid fa-circle-check me-1"></i> <?= htmlspecialchars($message) ?>
            </div>
            <div style="text-align:center;margin-top:10px;">
                <a href="login.php" class="btn-brand" style="display:inline-block;text-decoration:none;">
                    <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Login Sekarang
                </a>
            </div>
        <?php elseif ($message_type === 'error'): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fa-solid fa-circle-xmark me-1"></i> <?= htmlspecialchars($message) ?>
            </div>
            <?php if (!$valid_token): ?>
            <div style="text-align:center;margin-top:6px;">
                <a href="forgot_password.php" class="btn-brand" style="display:inline-block;text-decoration:none;">
                    <i class="fa-solid fa-rotate-left me-1"></i> Minta Link Baru
                </a>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($valid_token): ?>
    <form method="POST" class="needs-validation" novalidate>
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" value="<?= htmlspecialchars($email) ?>" disabled>
        </div>

        <div class="mb-3">
            <label class="form-label">Password Baru</label>
            <div class="input-group">
                <input type="password" name="password_baru" id="pass1" class="form-control"
                       placeholder="Minimal 6 karakter" required minlength="6">
                <span class="input-group-text" onclick="togglePass('pass1', this)">
                    <i class="fa fa-eye-slash"></i>
                </span>
            </div>
            <div class="password-hint">Gunakan kombinasi huruf, angka, dan simbol.</div>
            <div class="invalid-feedback">Password minimal 6 karakter.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Konfirmasi Password</label>
            <div class="input-group">
                <input type="password" name="password_ulang" id="pass2" class="form-control"
                       placeholder="Ulangi password baru" required>
                <span class="input-group-text" onclick="togglePass('pass2', this)">
                    <i class="fa fa-eye-slash"></i>
                </span>
            </div>
            <div class="invalid-feedback">Konfirmasi password wajib diisi.</div>
        </div>

        <button type="submit" class="btn-brand">
            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Password Baru
        </button>
    </form>
    <?php endif; ?>

    <div class="back-link">
        <a href="login.php"><i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Login</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (() => {
        'use strict';
        document.querySelectorAll('.needs-validation').forEach(form => {
            form.addEventListener('submit', e => {
                if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
                form.classList.add('was-validated');
            });
        });
    })();

    function togglePass(id, el) {
        const input = document.getElementById(id);
        const icon  = el.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        }
    }
</script>
</body>
</html>
