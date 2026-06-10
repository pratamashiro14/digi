<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';

$email          = $_POST['email'] ?? '';
$password_input = $_POST['password'] ?? '';

// Amankan input email untuk query
$email_safe = mysqli_real_escape_string($koneksi, $email);

// 1. Cari akun berdasarkan email (tabel t_user untuk pembeli & desainer)
$query     = mysqli_query($koneksi, "SELECT * FROM t_user WHERE email='$email_safe'");
$cek_email = $query ? mysqli_num_rows($query) : 0;

if ($cek_email > 0) {
    $data        = mysqli_fetch_assoc($query);
    $password_db = $data['password'];

    // 2. Cek password (dukung 3 format: password_hash, md5, plaintext)
    $cocok = password_verify($password_input, $password_db)
          || md5($password_input) === $password_db
          || $password_input === $password_db;

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
