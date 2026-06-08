<?php
session_start();
include 'admin/koneksi.php';

$email = $_POST['email'];
$password_input = $_POST['password']; // Password yang diketik user

// 1. Cek dulu apakah Emailnya ada di database?
$query = mysqli_query($koneksi, "SELECT * FROM t_user WHERE email='$email'");
$cek_email = mysqli_num_rows($query);

if($cek_email > 0){
    // Ambil datanya
    $data = mysqli_fetch_assoc($query);
    $password_db = $data['password']; // Password asli di database (Hash/MD5/Plain)

    // 2. CEK PASSWORD (YANG DIPERBAIKI)
    // Sekarang kita cek 3 kemungkinan:
    // A. Apakah cocok pakai password_verify? (Untuk akun yang baru daftar lewat web)
    // B. Apakah cocok pakai MD5? (Untuk akun lama)
    // C. Apakah cocok polos/biasa? (Untuk akun yang diinsert manual di database)
    
    if(password_verify($password_input, $password_db) || md5($password_input) == $password_db || $password_input == $password_db) {
        
        // --- JIKA LOGIN BERHASIL ---

        // Cek Role: Kalau ini akun Desainer, suruh ke login desainer
        if(isset($data['role']) && $data['role'] == 'designer'){
             echo "<script>
                alert('Ini akun Desainer. Silakan login di menu Desainer.');
                window.location.href='index.php';
            </script>";
            exit();
        }

        // Hapus sesi lama biar bersih
        session_unset();

        // Simpan sesi baru
        $_SESSION['id_user'] = $data['id_user'];
        $_SESSION['nama'] = $data['nama'];
        $_SESSION['email'] = $data['email'];
        $_SESSION['status'] = "login";
        $_SESSION['role'] = "user"; // Set sebagai user biasa

        echo "<script>
            alert('Login Berhasil!');
            window.location.href='index.php';
        </script>";

    } else {
        // Jika Password Salah
        echo "<script>
            alert('Password Salah! (Pastikan Capslock mati)');
            window.history.back();
        </script>";
    }

} else {
    // Jika Email Tidak Ditemukan
    echo "<script>
        alert('Email tidak terdaftar!');
        window.history.back();
    </script>";
}
?>