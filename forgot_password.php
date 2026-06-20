<?php
session_start();
include 'admin/koneksi.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Format email tidak valid!';
        $message_type = 'error';
    } else {
        $email_safe = mysqli_real_escape_string($koneksi, $email);
        $query = mysqli_query($koneksi, "SELECT id_user FROM t_user WHERE email='$email_safe'");

        if (mysqli_num_rows($query) > 0) {
            $token = bin2hex(random_bytes(32));
            $insert = mysqli_query($koneksi, "INSERT INTO t_password_reset (email, token, expiry) VALUES ('$email_safe', '$token', DATE_ADD(NOW(), INTERVAL 1 HOUR))");

            if ($insert) {
                $reset_url = "http://localhost/digi/reset_password.php?token=$token";
                $_SESSION['user_reset_url'] = $reset_url;

                $subject = "Reset Password DensCreative";
                $body = "<html><body style='font-family:Arial,sans-serif;'>
                    <div style='max-width:600px;margin:0 auto;background:#f5f5f5;padding:20px;border-radius:10px;'>
                        <h2 style='color:#4e60ff;'>Reset Password DensCreative</h2>
                        <p>Anda meminta reset password untuk akun Anda.</p>
                        <p>Klik link berikut untuk membuat password baru (berlaku 1 jam):</p>
                        <p><a href='$reset_url' style='background:#4e60ff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>Reset Password</a></p>
                        <p style='color:#666;font-size:12px;'>Atau salin link ini:<br>$reset_url</p>
                        <p style='color:#999;font-size:11px;margin-top:30px;'>Abaikan email ini jika Anda tidak meminta reset password.</p>
                    </div></body></html>";

                $headers  = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                $headers .= "From: noreply@denscreative.com\r\n";

                $sent = @mail($email_safe, $subject, $body, $headers);

                if ($sent) {
                    $message = 'Email reset password telah dikirim! Cek inbox Anda.';
                    $message_type = 'success';
                } else {
                    $message = 'Link reset password berhasil dibuat.';
                    $message_type = 'warning';
                }
            } else {
                $message = 'Gagal membuat token. Coba lagi nanti.';
                $message_type = 'error';
            }
        } else {
            $message = 'Email tidak terdaftar di sistem.';
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - DensCreative</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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
        .btn-brand {
            background: var(--brand); color: #fff; font-weight: 600; font-size: 15px;
            border-radius: 50px; padding: 11px; width: 100%; border: none; transition: .25s; margin-top: 6px;
        }
        .btn-brand:hover { background: var(--brand-dark); color: #fff; }
        .back-link { text-align: center; margin-top: 18px; font-size: 13px; }
        .back-link a { color: var(--brand); text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
        .reset-btn-direct {
            display: inline-block; padding: 10px 20px; background: #198754;
            color: white; text-decoration: none; border-radius: 10px;
            font-weight: 500; margin-top: 10px; transition: .3s; font-size: 14px;
        }
        .reset-btn-direct:hover { background: #157347; color: white; }
        code.reset-link {
            background: #f0f0f0; padding: 6px 10px; border-radius: 6px;
            display: block; margin-top: 8px; word-break: break-all; font-size: 11px;
        }
        .icon-wrapper { text-align: center; margin-bottom: 16px; }
        .icon-wrapper i { font-size: 48px; color: var(--brand); opacity: .85; }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="auth-card">
    <div class="icon-wrapper"><i class="fa-solid fa-lock-open"></i></div>
    <h3 class="brand-title">DensCreative</h3>
    <p class="brand-sub">Pulihkan akses ke akunmu</p>

    <?php if (!empty($message)): ?>
        <?php if ($message_type === 'success'): ?>
            <div class="alert alert-success" role="alert">
                <i class="fa-solid fa-circle-check me-1"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php elseif ($message_type === 'warning'): ?>
            <div class="alert alert-warning" role="alert">
                <strong><i class="fa-solid fa-triangle-exclamation me-1"></i> Link berhasil dibuat!</strong><br>
                <small>Catatan: Email tidak terkirim (normal di localhost tanpa konfigurasi SMTP).</small><br><br>
                Klik tombol di bawah untuk langsung reset password:
                <?php if (isset($_SESSION['user_reset_url'])): ?>
                    <div class="mt-2">
                        <a href="<?= htmlspecialchars($_SESSION['user_reset_url']) ?>" class="reset-btn-direct">
                            <i class="fa-solid fa-key me-1"></i> Reset Password Sekarang
                        </a>
                        <br>
                        <small style="color:#555;display:block;margin-top:10px;"><strong>Atau salin link ini:</strong></small>
                        <code class="reset-link"><?= htmlspecialchars($_SESSION['user_reset_url']) ?></code>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-danger" role="alert">
                <i class="fa-solid fa-circle-xmark me-1"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($message_type !== 'success'): ?>
    <p style="font-size:13.5px;color:#555;text-align:center;margin-bottom:22px;">
        Masukkan email yang terdaftar, kami akan mengirimkan link untuk membuat password baru.
    </p>
    <form method="POST" class="needs-validation" novalidate>
        <div class="mb-3">
            <label class="form-label">Alamat Email</label>
            <input type="email" name="email" class="form-control" placeholder="email@contoh.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            <div class="invalid-feedback">Masukkan email yang valid.</div>
        </div>
        <button type="submit" class="btn-brand">
            <i class="fa-solid fa-paper-plane me-1"></i> Kirim Link Reset
        </button>
    </form>
    <?php else: ?>
    <div style="text-align:center;margin-top:10px;">
        <a href="login.php" class="btn-brand" style="display:inline-block;text-decoration:none;">
            <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Kembali ke Login
        </a>
    </div>
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
</script>
</body>
</html>
