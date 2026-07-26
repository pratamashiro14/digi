<?php
/**
 * Registrasi DESAINER — KTP TETAP WAJIB (beda dari pembeli).
 * Alasan: desainer MENERIMA pencairan dana (t_pencairan), jadi KYC di sini
 * proporsional & mudah dipertanggungjawabkan, tidak seperti pembeli yang
 * cuma membayar. Ditambah verifikasi email OTP (email_terverifikasi) yang
 * berlaku sama untuk semua role sejak revisi keamanan ini.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin/koneksi.php';
require_once __DIR__ . '/identitas_helper.php';
require_once __DIR__ . '/otp_helper.php';

const OTP_TUJUAN_DAFTAR_DESAINER = 'registrasi';

$nama = mysqli_real_escape_string($koneksi, $_POST['nama'] ?? '');
$email = trim(strtolower((string) ($_POST['email'] ?? '')));
$password_input = $_POST['password'] ?? '';
$nik = mysqli_real_escape_string($koneksi, $_POST['nik'] ?? '');

// 1. Validasi Input Dasar
if ($nama === '' || $email === '' || $password_input === '' || $nik === '') {
    redirect_with_alert('Lengkapi semua data pendaftaran desainer termasuk NIK.', 'register.php', 'error', 'Pendaftaran Gagal!');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_with_alert('Format email tidak valid.', 'register.php', 'error', 'Pendaftaran Gagal!');
}

// 2. Validasi Format NIK (16 digit angka)
if (!preg_match('/^\d{16}$/', $nik)) {
    redirect_with_alert('Format NIK tidak valid. Harus terdiri dari 16 digit angka.', 'register.php', 'error', 'Pendaftaran Gagal!');
}

// 2b. NIK / email yang sudah diblokir admin tidak boleh dipakai daftar ulang.
if (identitas_diblokir($koneksi, 'nik', $nik)) {
    redirect_with_alert('NIK ini telah diblokir permanen oleh admin dan tidak dapat digunakan untuk mendaftar.', 'register.php', 'error', 'Pendaftaran Gagal!');
}
if (identitas_diblokir($koneksi, 'email', $email)) {
    redirect_with_alert('Email ini diblokir permanen oleh admin dan tidak dapat digunakan untuk mendaftar.', 'register.php', 'error', 'Pendaftaran Gagal!');
}

// 3. Cek Email Terdaftar (hanya tolak bila SUDAH terverifikasi — sama seperti
// alur pembeli, supaya "email squatting" oleh akun yang tak pernah
// diverifikasi tidak mengunci pemilik email yang sebenarnya).
$cek_email = mysqli_query($koneksi, "SELECT id_user, email_terverifikasi FROM t_user WHERE email = '$email'");
$existing_row = $cek_email ? mysqli_fetch_assoc($cek_email) : null;
if ($existing_row && (int) $existing_row['email_terverifikasi'] === 1) {
    sweetalert_back('Email sudah digunakan. Silakan gunakan email lain.', 'error', 'Pendaftaran Gagal!');
}

// 4. Validasi Unggah Foto KTP
if (empty($_FILES['foto_ktp']['name'])) {
    redirect_with_alert('Foto KTP wajib diunggah untuk pendaftaran desainer.', 'register.php', 'error', 'Pendaftaran Gagal!');
}

// 5. ID Pengguna: pakai baris lama (klaim ulang) bila belum terverifikasi,
// atau MAX(id_user)+1 untuk akun baru — bukan rand(100,999) yang ruangnya
// cuma ~900 akun & bisa infinite-loop bila penuh.
if ($existing_row) {
    $id_user = (int) $existing_row['id_user'];
} else {
    $row = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT GREATEST(COALESCE(MAX(id_user), 0) + 1, 100) AS next_id FROM t_user"));
    $id_user = (int) $row['next_id'];
}

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

if ($existing_row) {
    // Klaim ulang akun yang belum pernah diverifikasi emailnya.
    $query_simpan = "UPDATE t_user SET nama='$nama', password='$password_hashed', nik='$nik',
                      foto_ktp='$nama_ktp', status_verifikasi='pending', email_terverifikasi=0
                      WHERE id_user='$id_user'";
} else {
    $query_simpan = "INSERT INTO t_user (id_user, nama, email, password, role, status, premium, foto, nik, foto_ktp, status_verifikasi, email_terverifikasi)
                     VALUES ('$id_user', '$nama', '$email', '$password_hashed', 'designer', 'aktif', 0, 'default.jpg', '$nik', '$nama_ktp', 'pending', 0)";
}

if (mysqli_query($koneksi, $query_simpan)) {
    $_SESSION['otp_email'] = $email;
    $_SESSION['otp_nama']  = $nama;

    $hasil = otp_buat_dan_kirim($koneksi, $email, $nama, OTP_TUJUAN_DAFTAR_DESAINER);
    if ($hasil['ok'] && $hasil['kode_dev'] !== null) {
        $_SESSION['otp_kode_dev'] = $hasil['kode_dev'];
    }

    sweetalert_redirect('Pendaftaran Desainer berhasil! Verifikasi email Anda dulu, lalu KTP akan dicek Admin.', 'verifikasi_email.php', 'success', 'Hampir Selesai!');
}

// Jika query simpan gagal, hapus KTP yang diunggah
if (file_exists($folder_simpan . $nama_ktp)) {
    @unlink($folder_simpan . $nama_ktp);
}

sweetalert_back('Terjadi kesalahan sistem saat menyimpan data. Coba lagi nanti.', 'error', 'Pendaftaran Gagal!');
?>
