<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';

$email = $_POST['email'];
$password = $_POST['password'];

// Cek designer
$query = mysqli_query($koneksi, "SELECT * FROM t_user WHERE email='$email' AND password='$password' AND role='designer'");
$cek = mysqli_num_rows($query);

if($cek > 0){
    $data = mysqli_fetch_assoc($query);

    // Simpan sesi desainer secara seragam (lihat auth.php)
    login_as_designer($data['id_user'], $data['nama'], $data['email']);

    echo "<script>
        alert('Halo designer! Login Berhasil.');
        window.location.href='index.php';
    </script>";
} else {
    echo "<script>
        alert('Login Gagal! Akun tidak ditemukan atau bukan akun designer.');
        window.history.back();
    </script>";
}
?>