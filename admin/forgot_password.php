<?php
session_start();
include 'koneksi.php';

$message = '';
$message_type = '';
$email_prefill = isset($_GET['email']) ? trim($_GET['email']) : '';

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    // Validasi email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Email tidak valid!';
        $message_type = 'error';
    } else {
        // Cek apakah email ada di database
        $query = mysqli_query($koneksi, "SELECT id_admin FROM t_admin WHERE email='$email'");
        if (mysqli_num_rows($query) > 0) {
            // Generate token random
            $token = bin2hex(random_bytes(32));
            
            // Simpan token ke database dengan expiry dihitung oleh MySQL (lebih reliable)
            // Query: INSERT dengan DATE_ADD(NOW(), INTERVAL 1 HOUR)
            $insert_token = mysqli_query($koneksi, "INSERT INTO t_password_reset (email, token, expiry) VALUES ('$email', '$token', DATE_ADD(NOW(), INTERVAL 1 HOUR))");

            if ($insert_token) {
                // URL reset password
                $reset_url = "http://localhost/xampp/digi/admin/reset_password.php?token=$token";
                
                // Simpan token ke session untuk fallback (jika email gagal)
                $_SESSION['password_reset_token'] = $token;
                $_SESSION['password_reset_email'] = $email;

                // Siapkan email
                $subject = "Reset Password Admin DIGIDESAIN";
                $message_body = "
                <html>
                <head>
                    <title>Reset Password</title>
                </head>
                <body style='font-family: Arial, sans-serif;'>
                    <div style='max-width: 600px; margin: 0 auto; background: #f5f5f5; padding: 20px; border-radius: 10px;'>
                        <h2 style='color: #333;'>Reset Password Admin DIGIDESAIN</h2>
                        <p>Anda telah meminta untuk mereset password admin Anda.</p>
                        <p>Klik link di bawah untuk reset password (link berlaku 1 jam):</p>
                        <p><a href='$reset_url' style='background: #7b61ff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a></p>
                        <p style='color: #666; font-size: 12px;'>Atau copy-paste link berikut:<br>$reset_url</p>
                        <p style='color: #999; font-size: 11px; margin-top: 30px;'>Jika Anda tidak meminta reset password, abaikan email ini.</p>
                    </div>
                </body>
                </html>";

                // Header email
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
                $headers .= "From: admin@digidesain.com" . "\r\n";

                // Coba kirim email
                // Di localhost XAMPP, email() mungkin tidak bekerja (normal!)
                $email_sent = @mail($email, $subject, $message_body, $headers);
                
                if ($email_sent) {
                    $message = 'Email reset password telah dikirim! Cek inbox Anda.';
                    $message_type = 'success';
                } else {
                    // Email gagal (normal di localhost tanpa SMTP)
                    // Tampilkan link reset langsung
                    $message = '✅ <strong>Token reset password sudah dibuat!</strong><br><br>
                    <small>
                    📧 <em>Catatan: Email tidak terkirim (normal di localhost).</em><br><br>
                    Klik link di bawah untuk reset password Anda sekarang:
                    </small>';
                    $message_type = 'warning';
                    // Simpan reset_url ke variable agar bisa ditampilkan di HTML
                    $_SESSION['reset_url'] = $reset_url;
                }
            } else {
                $message = 'Gagal menyimpan token. Coba lagi nanti.';
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
    <title>Lupa Password - Admin DIGIDESAIN</title>
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
        .btn-success-link {
            display: inline-block;
            padding: 10px 20px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            margin-top: 10px;
            transition: 0.3s;
        }
        .btn-success-link:hover {
            background: #218838;
            color: white;
        }
    </style>
</head>
<body>
    <div class="reset-card">
        <img src="assets/img/dens.png" alt="DIGIDESAIN Logo">
        <h4>Lupa Password?</h4>
        <p>Masukkan email Anda untuk menerima link reset password</p>

        <?php if (!empty($message)) { ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : ($message_type === 'warning' ? 'warning' : 'danger'); ?>" role="alert">
                <?php echo $message; ?>
                <?php if ($message_type === 'warning' && isset($_SESSION['reset_url'])) { ?>
                    <div style="margin-top: 15px;">
                        <a href="<?php echo htmlspecialchars($_SESSION['reset_url']); ?>" class="btn-success-link">
                            ➜ Reset Password Sekarang
                        </a>
                        <br><br>
                        <small style="color: #666;">
                            <strong>Atau copy-paste link ini:</strong><br>
                            <code style="background: #f0f0f0; padding: 5px 10px; border-radius: 5px; display: block; margin-top: 5px; word-break: break-all; font-size: 11px;">
                                <?php echo htmlspecialchars($_SESSION['reset_url']); ?>
                            </code>
                        </small>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>

        <form method="post">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="admin@example.com" value="<?php echo htmlspecialchars($email_prefill); ?>" required>
            </div>
            <button type="submit" class="btn btn-reset w-100 py-2">Kirim Email Reset</button>
        </form>

        <div class="back-link">
            <a href="index.php">&larr; Kembali ke Login</a>
        </div>
    </div>
</body>
</html>
