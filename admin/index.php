<?php
require_once __DIR__ . '/../auth.php';
include 'koneksi.php'; // Pastikan koneksi.php ada dan benar

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
    $password = $_POST['password'];

    $query = mysqli_query($koneksi, "SELECT * FROM t_admin WHERE email='$email'");
    $data = mysqli_fetch_assoc($query);

    // Verifikasi password (mendukung beberapa format: plaintext, md5, password_hash)
    if ($data) {
        $pw_db = $data['password'];
        $pw_ok = false;

        // 1) Langsung cocok plaintext
        if ($password === $pw_db) {
            $pw_ok = true;
        }
        // 2) Cek MD5 (akun lama)
        if (!$pw_ok && md5($password) === $pw_db) {
            $pw_ok = true;
        }
        // 3) Cek password_hash / password_verify (jika disimpan hashed)
        if (!$pw_ok && function_exists('password_verify') && password_verify($password, $pw_db)) {
            $pw_ok = true;
        }

        if ($pw_ok) {
            login_as_admin($data['id_admin']);
            header("Location: beranda.php");
            exit;
        } else {
            $error = "Email atau password salah!";
        }
    } else {
        $error = "Email atau password salah!";
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
        <img src="assets/img/digidesain.png" alt="DIGIDESAIN Logo">
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
