<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';

$nama = mysqli_real_escape_string($koneksi, $_POST['nama'] ?? '');
$email = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
$password_input = $_POST['password'] ?? '';

if ($nama === '' || $email === '' || $password_input === '') {
    redirect_with_alert('Lengkapi nama, email, dan kata sandi desainer.', 'index.php', 'error', 'Pendaftaran Gagal!');
}

$cek_email = mysqli_query($koneksi, "SELECT id_user FROM t_user WHERE email = '$email'");
if ($cek_email && mysqli_num_rows($cek_email) > 0) {
    sweetalert_back('Email sudah digunakan. Silakan gunakan email lain.', 'error', 'Pendaftaran Gagal!');
}

$password_hashed = password_hash($password_input, PASSWORD_DEFAULT);
do {
    $id_user = rand(100, 999);
    $cek_id = mysqli_query($koneksi, "SELECT id_user FROM t_user WHERE id_user = '$id_user'");
} while ($cek_id && mysqli_num_rows($cek_id) > 0);

$query_insert = "INSERT INTO t_user (id_user, nama, email, password, role, status, premium, foto)
                 VALUES ('$id_user', '$nama', '$email', '$password_hashed', 'designer', 'aktif', 0, 'default.jpg')";

if (mysqli_query($koneksi, $query_insert)) {
    redirect_with_alert('Pendaftaran Desainer berhasil! Silakan login untuk melengkapi profil dan verifikasi KTP.', 'login.php', 'success', 'Pendaftaran Berhasil!');
}

sweetalert_back('Terjadi kesalahan sistem. Coba lagi nanti.', 'error', 'Pendaftaran Gagal!');
?>
