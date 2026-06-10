<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';

$nama = mysqli_real_escape_string($koneksi, $_POST['nama'] ?? '');
$email = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
$password_input = $_POST['password'] ?? '';
$nik = mysqli_real_escape_string($koneksi, $_POST['nik'] ?? '');

// 1. Validasi Input Dasar
if ($nama === '' || $email === '' || $password_input === '' || $nik === '') {
    redirect_with_alert('Lengkapi semua data pendaftaran desainer termasuk NIK.', 'register.php', 'error', 'Pendaftaran Gagal!');
}

// 2. Validasi Format NIK (16 digit angka)
if (!preg_match('/^\d{16}$/', $nik)) {
    redirect_with_alert('Format NIK tidak valid. Harus terdiri dari 16 digit angka.', 'register.php', 'error', 'Pendaftaran Gagal!');
}

// 3. Cek Email Terdaftar
$cek_email = mysqli_query($koneksi, "SELECT id_user FROM t_user WHERE email = '$email'");
if ($cek_email && mysqli_num_rows($cek_email) > 0) {
    sweetalert_back('Email sudah digunakan. Silakan gunakan email lain.', 'error', 'Pendaftaran Gagal!');
}

// 4. Validasi Unggah Foto KTP
if (empty($_FILES['foto_ktp']['name'])) {
    redirect_with_alert('Foto KTP wajib diunggah untuk pendaftaran desainer.', 'register.php', 'error', 'Pendaftaran Gagal!');
}

// 5. Generate ID Pengguna Acak
do {
    $id_user = rand(100, 999);
    $cek_id = mysqli_query($koneksi, "SELECT id_user FROM t_user WHERE id_user = '$id_user'");
} while ($cek_id && mysqli_num_rows($cek_id) > 0);

// 6. Proses Unggah Foto KTP
$f_ktp = $_FILES['foto_ktp'];
$ext = strtolower(pathinfo($f_ktp['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png'];

if (!in_array($ext, $allowed)) {
    redirect_with_alert('Format foto KTP tidak didukung. Gunakan format JPG, JPEG, atau PNG.', 'register.php', 'error', 'Pendaftaran Gagal!');
}

if ($f_ktp['size'] > 4 * 1024 * 1024) {
    redirect_with_alert('Ukuran foto KTP terlalu besar. Maksimal adalah 4MB.', 'register.php', 'error', 'Pendaftaran Gagal!');
}

$nama_ktp = $id_user . "_KTP_" . time() . "_" . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($f_ktp['name']));
$folder_simpan = "admin/uploads/";

if (!is_dir($folder_simpan)) {
    mkdir($folder_simpan, 0755, true);
}

if (!move_uploaded_file($f_ktp['tmp_name'], $folder_simpan . $nama_ktp)) {
    redirect_with_alert('Gagal mengunggah foto KTP. Silakan coba lagi.', 'register.php', 'error', 'Pendaftaran Gagal!');
}

// 7. Enkripsi Password & Simpan Data
$password_hashed = password_hash($password_input, PASSWORD_DEFAULT);

$query_insert = "INSERT INTO t_user (id_user, nama, email, password, role, status, premium, foto, nik, foto_ktp, status_verifikasi)
                 VALUES ('$id_user', '$nama', '$email', '$password_hashed', 'designer', 'aktif', 0, 'default.jpg', '$nik', '$nama_ktp', 'pending')";

if (mysqli_query($koneksi, $query_insert)) {
    redirect_with_alert('Pendaftaran Desainer berhasil! Akun Anda sedang menunggu verifikasi KTP oleh Admin. Silakan login untuk mengecek status secara berkala.', 'login.php', 'success', 'Pendaftaran Berhasil!');
}

// Jika query insert gagal, hapus KTP yang diunggah
if (file_exists($folder_simpan . $nama_ktp)) {
    @unlink($folder_simpan . $nama_ktp);
}

sweetalert_back('Terjadi kesalahan sistem saat menyimpan data. Coba lagi nanti.', 'error', 'Pendaftaran Gagal!');
?>
