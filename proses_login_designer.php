<?php
session_start();
include 'admin/koneksi.php';

$email = $_POST['email'];
$password = $_POST['password'];

// Cek designer
$query = mysqli_query($koneksi, "SELECT * FROM t_user WHERE email='$email' AND password='$password' AND role='designer'");
$cek = mysqli_num_rows($query);

if($cek > 0){
    $data = mysqli_fetch_assoc($query);

    session_unset(); 

    $_SESSION['id_user'] = $data['id_user'];
    
    // PERBAIKAN DI SINI: Ganti 'nama_designer' jadi 'nama_desainer'
    $_SESSION['nama_desainer'] = $data['nama']; 
    
    $_SESSION['email_designer'] = $data['email'];
    $_SESSION['status_designer'] = "login";
    $_SESSION['role'] = "designer"; 

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