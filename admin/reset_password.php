<?php
session_start();
include 'koneksi.php';

$message = '';
$message_type = '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$valid_token = false;
$email = '';

// Validasi token
if (!empty($token)) {
    $query = mysqli_query($koneksi, "SELECT email FROM t_password_reset WHERE token='$token' AND expiry > NOW()");
    if (mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);
        $email = $row['email'];
        $valid_token = true;
    } else {
        $message = 'Token tidak valid atau sudah kadaluarsa (1 jam).';
        $message_type = 'error';
    }
} else {
    $message = 'Token tidak ditemukan.';
    $message_type = 'error';
}

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $token = trim($_POST['token']);
    $password_baru = $_POST['password_baru'] ?? '';
    $password_ulang = $_POST['password_ulang'] ?? '';

    if (empty($password_baru) || empty($password_ulang)) {
        $message = 'Password tidak boleh kosong!';
        $message_type = 'error';
    } elseif ($password_baru !== $password_ulang) {
        $message = 'Password tidak cocok!';
        $message_type = 'error';
    } elseif (strlen($password_baru) < 6) {
        $message = 'Password minimal 6 karakter!';
        $message_type = 'error';
    } else {
        // Validasi token masih berlaku
        $check_token = mysqli_query($koneksi, "SELECT email FROM t_password_reset WHERE token='$token' AND expiry > NOW()");
        
        if (mysqli_num_rows($check_token) > 0) {
            $token_row = mysqli_fetch_assoc($check_token);
            $email = $token_row['email'];

            // Hash password menggunakan password_hash
            $password_hashed = password_hash($password_baru, PASSWORD_BCRYPT);

            // Update password di t_admin
            $update_query = mysqli_query($koneksi, "UPDATE t_admin SET password='$password_hashed' WHERE email='$email'");

            if ($update_query) {
                // Hapus token dari database
                mysqli_query($koneksi, "DELETE FROM t_password_reset WHERE token='$token'");

                $message = 'Password berhasil diperbarui! Silakan login dengan password baru.';
                $message_type = 'success';
                $valid_token = false; // Jangan tampilkan form lagi
            } else {
                $message = 'Gagal mengupdate password. Coba lagi nanti.';
                $message_type = 'error';
            }
        } else {
            $message = 'Token tidak valid atau sudah kadaluarsa.';
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
    <title>Reset Password - Admin DIGIDESAIN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(to right, #dbe7ff, #7b61ff);
            font-family: 'Poppins', sans-serif;
        }
        .reset-card {
            background: #fff;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            width: 380px;
        }
        .reset-card img {
            display: block;
            margin: 0 auto 10px;
            height: 60px;
        }
        .reset-card h4 {
            text-align: center;
            color: #2e2e2e;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .reset-card p {
            text-align: center;
            color: #666;
            font-size: 13px;
            margin-bottom: 20px;
        }
        .form-control {
            border-radius: 10px;
        }
        .btn-reset {
            background-color: #7b61ff;
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 500;
            transition: 0.3s;
        }
        .btn-reset:hover {
            background-color: #6b52e0;
        }
        .back-link {
            text-align: center;
            margin-top: 15px;
        }
        .back-link a {
            color: #7b61ff;
            text-decoration: none;
            font-size: 13px;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        .alert {
            border-radius: 10px;
        }
        .password-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="reset-card">
        <img src="assets/img/digidesain.png" alt="DIGIDESAIN Logo">
        <h4>Reset Password</h4>
        <p>Masukkan password baru Anda</p>

        <?php if (!empty($message)) { ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>" role="alert">
                <?php echo $message; ?>
            </div>
        <?php } ?>

        <?php if ($valid_token && $message_type !== 'success') { ?>
            <form method="post">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="password_baru" class="form-control" placeholder="Minimal 6 karakter" required>
                    <div class="password-info">Gunakan kombinasi huruf, angka, dan simbol untuk keamanan lebih baik</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Ulangi Password</label>
                    <input type="password" name="password_ulang" class="form-control" placeholder="Ulangi password" required>
                </div>

                <button type="submit" class="btn btn-reset w-100 py-2">Reset Password</button>
            </form>
        <?php } else if ($message_type === 'success') { ?>
            <div class="back-link" style="margin-top: 30px;">
                <p style="color: #666; margin-bottom: 15px;">Silakan kembali ke halaman login dengan password baru Anda.</p>
                <a href="index.php" class="btn btn-reset w-100 py-2" style="text-decoration: none;">Ke Halaman Login</a>
            </div>
        <?php } ?>

        <div class="back-link">
            <a href="index.php">&larr; Kembali ke Login</a>
        </div>
    </div>
</body>
</html>
