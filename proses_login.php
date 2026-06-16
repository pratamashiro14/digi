<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';

$email          = $_POST['email'] ?? '';
$password_input = $_POST['password'] ?? '';

if (empty(trim($email)) || empty(trim($password_input))) {
    sweetalert_back('Alamat email dan kata sandi wajib diisi!', 'error', 'Login Gagal!');
    exit;
}

// 1. Cari akun berdasarkan email (prepared statement — cegah SQL injection)
$stmt = mysqli_prepare($koneksi, "SELECT * FROM t_user WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$query     = mysqli_stmt_get_result($stmt);
$cek_email = $query ? mysqli_num_rows($query) : 0;

if ($cek_email > 0) {
    $data        = mysqli_fetch_assoc($query);
    $password_db = $data['password'];

    // 2. Verifikasi password + migrasi otomatis format lama (md5/plaintext) ke bcrypt
    $cocok = verify_and_upgrade_password($password_input, $password_db, 't_user', 'id_user', (int) $data['id_user']);

    if ($cocok) {
        // 3. OTOMATIS arahkan sesuai ROLE di database
        $role = strtolower(trim($data['role'] ?? ''));

        if ($role === 'designer' || $role === 'desainer') {
            login_as_designer($data['id_user'], $data['nama'], $data['email']);
            redirect_with_alert('Login berhasil sebagai Desainer! Selamat datang, ' . $data['nama'] . '.', 'index.php', 'success', 'Login Berhasil!');
        } else {
            login_as_user($data['id_user'], $data['nama'], $data['email']);
            redirect_with_alert('Login berhasil sebagai Pembeli! Selamat datang, ' . $data['nama'] . '.', 'product.php', 'success', 'Login Berhasil!');
        }
    } else {
        // Password salah
        sweetalert_back('Password salah! Pastikan Caps Lock tidak aktif.', 'error', 'Login Gagal!');
    }
} else {
    // Email tidak ditemukan
    sweetalert_back('Email tidak terdaftar. Silakan daftar terlebih dahulu.', 'error', 'Login Gagal!');
}
?>
