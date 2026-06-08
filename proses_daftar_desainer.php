<?php
// Mulai Session
session_start();

// Hubungkan ke Database
include 'admin/koneksi.php';

// 1. Tangkap Data dari Form
$nama = $_POST['nama'];
$email = $_POST['email'];
$password = $_POST['password']; 
// Jika password di database dienkripsi (md5), gunakan baris ini:
// $password = md5($_POST['password']);

// 2. Validasi: Cek apakah Email sudah pernah terdaftar?
// (Asumsi desainer disimpan di tabel 't_user'. Jika punya tabel 't_desainer' sendiri, ubah nama tabelnya)
$cek_email = mysqli_query($koneksi, "SELECT * FROM t_user WHERE email = '$email'");

if(mysqli_num_rows($cek_email) > 0) {
    // Jika email sudah ada
    echo "<script>
        alert('Gagal Daftar! Email sudah digunakan. Silakan gunakan email lain.');
        window.history.back(); // Kembali ke halaman sebelumnya
    </script>";
    exit();
}

// 3. Simpan ke Database
// Saya tambahkan kolom 'level' = 'desainer' untuk membedakan dengan user biasa.
// Pastikan di database tabel t_user kamu ada kolom 'level' atau 'role'.
// Jika tidak ada, hapus bagian ", level" dan ", 'desainer'".

$query_insert = "INSERT INTO t_user (nama, email, password, role) VALUES ('$nama', '$email', '$password', 'designer')";

if(mysqli_query($koneksi, $query_insert)) {
    // Jika Berhasil Disimpan
    echo "<script>
        alert('Pendaftaran Desainer Berhasil! Silakan Login.');
        window.location.href='index.php';
    </script>";
} else {
    // Jika Gagal
    echo "<script>
        alert('Terjadi kesalahan sistem. Coba lagi nanti.');
        window.history.back();
    </script>";
}
?>