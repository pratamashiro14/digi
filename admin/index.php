<?php
require_once __DIR__ . '/../auth.php';
include 'koneksi.php'; // Pastikan koneksi.php ada dan benar

if (isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Cari admin (prepared statement — cegah SQL injection)
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM t_admin WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $query = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($query);

    // Verifikasi password + migrasi otomatis format lama (md5/plaintext) ke bcrypt
    if ($data) {
        $pw_ok = verify_and_upgrade_password($password, $data['password'], 't_admin', 'id_admin', (int) $data['id_admin']);

        if ($pw_ok) {
            login_as_admin($data['id_admin']);
            sweetalert_redirect('Selamat datang di dashboard admin.', 'beranda.php', 'success', 'Login Berhasil!');
        } else {
            sweetalert_back('Email atau password salah.', 'error', 'Login Admin Gagal!');
        }
    } else {
        sweetalert_back('Email atau password salah.', 'error', 'Login Admin Gagal!');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin | DIGIDESAIN</title>
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
        .login-card {
            background: #fff;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            width: 380px;
        }
        .login-card img {
            display: block;
            margin: 0 auto 10px;
            height: 60px;
        }
        .login-card h4 {
            text-align: center;
            color: #2e2e2e;
            margin-bottom: 30px;
            font-weight: 600;
        }
        .form-control {
            border-radius: 10px;
        }
        .btn-login {
            background-color: #7b61ff;
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 500;
            transition: 0.3s;
        }
        .btn-login:hover {
            background-color: #6b52e0;
        }
        .forgot {
            text-align: right;
            display: block;
            font-size: 13px;
            margin-top: -5px;
            margin-bottom: 15px;
            color: #6b52e0;
            text-decoration: none;
        }
        .forgot:hover {
            text-decoration: underline;
        }
    </style>
    <script>
        function goToForgotPassword() {
            const email = document.getElementById('emailInput').value;
            if (email) {
                window.location.href = 'forgot_password.php?email=' + encodeURIComponent(email);
            } else {
                window.location.href = 'forgot_password.php';
            }
        }
    </script>
</head>
<body>
    <div class="login-card">
        <img src="assets/img/dens.png" alt="DIGIDESAIN Logo">
        <h4>Admin</h4>
        <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" id="emailInput" class="form-control" required>
            </div>
            <div class="mb-2">
                <label class="form-label">Kata Sandi</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <a href="#" id="forgotLink" class="forgot" onclick="goToForgotPassword(); return false;">Lupa Kata Sandi</a>
            <button type="submit" name="login" class="btn btn-login w-100 py-2">Masuk</button>
            <div style="margin-top: 15px; text-align: center;">
                <a href="../" class="btn btn-secondary w-100 py-2" style="text-decoration: none; display: inline-block;">
                    <i class="fas fa-home"></i> Kembali ke Beranda
                </a>
            </div>
        </form>
    </div>
</body>
</html>
