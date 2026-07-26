<?php
/**
 * ============================================================
 *  PROSES_DAFTAR.PHP — Registrasi PEMBELI (revisi keamanan)
 * ============================================================
 * Perubahan dari versi lama:
 *  - Tidak lagi memakai koneksi mysqli sendiri (dulu tidak lewat
 *    admin/koneksi.php, sehingga auto-migration skema tidak pernah
 *    terpicu dari jalur ini) — sekarang konsisten dengan file lain.
 *  - String-concat SQL diganti prepared statement.
 *  - ID user tidak lagi rand(100,999) (ruang cuma ~900 akun & bisa infinite
 *    loop bila penuh) — dipakai MAX(id_user)+1.
 *  - Pelanggan TIDAK lagi diminta KTP. Sebagai gantinya, akun dibuat dengan
 *    email_terverifikasi=0 dan wajib memasukkan kode OTP yang dikirim ke
 *    email (lihat verifikasi_email.php) sebelum bisa dipakai penuh.
 *  - Email yang sudah diblokir admin (t_blokir_identitas) ditolak di sini,
 *    bukan menunggu sampai user coba bidding.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin/koneksi.php';
require_once __DIR__ . '/identitas_helper.php';
require_once __DIR__ . '/otp_helper.php';

const OTP_TUJUAN_DAFTAR = 'registrasi';

$nama           = trim((string) ($_POST['nama'] ?? ''));
$email          = trim(strtolower((string) ($_POST['email'] ?? '')));
$password_input = (string) ($_POST['password'] ?? '');

if ($nama === '' || $email === '' || $password_input === '') {
    sweetalert_redirect('Semua kolom pendaftaran wajib diisi!', 'register.php', 'error', 'Pendaftaran Gagal!');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sweetalert_redirect('Format email tidak valid.', 'register.php', 'error', 'Pendaftaran Gagal!');
}
if (strlen($password_input) < 6) {
    sweetalert_redirect('Kata sandi minimal 6 karakter.', 'register.php', 'error', 'Pendaftaran Gagal!');
}
if (identitas_diblokir($koneksi, 'email', $email)) {
    sweetalert_redirect('Email ini diblokir permanen oleh admin dan tidak dapat digunakan untuk mendaftar.', 'register.php', 'error', 'Pendaftaran Gagal!');
}

$password_hashed = password_hash($password_input, PASSWORD_DEFAULT);

// Cek email: kalau sudah ada & SUDAH terverifikasi -> tolak (akun asli orang lain).
// Kalau sudah ada tapi BELUM terverifikasi -> klaim ulang (update nama/password &
// kirim kode baru). Ini mencegah "email squatting": orang lain iseng daftar pakai
// email kita tapi tidak pernah menyelesaikan verifikasi, sehingga kita terkunci
// selamanya tidak bisa memakai email sendiri.
$stmt = mysqli_prepare($koneksi, "SELECT id_user, email_terverifikasi FROM t_user WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if ($existing && (int) $existing['email_terverifikasi'] === 1) {
    sweetalert_redirect('Email sudah terdaftar. Silakan gunakan email lain atau login.', 'register.php', 'error', 'Pendaftaran Gagal!');
}

if ($existing) {
    // Klaim ulang akun yang belum pernah diverifikasi.
    $stmt = mysqli_prepare($koneksi, "UPDATE t_user SET nama = ?, password = ? WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, 'ssi', $nama, $password_hashed, $existing['id_user']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
} else {
    // ID user: MAX(id_user)+1, mulai dari 100 untuk konsisten dgn rentang lama.
    $row = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT GREATEST(COALESCE(MAX(id_user), 0) + 1, 100) AS next_id FROM t_user"));
    $id_user = (int) $row['next_id'];

    $stmt = mysqli_prepare($koneksi,
        "INSERT INTO t_user (id_user, nama, email, password, role, status, premium, foto, email_terverifikasi)
         VALUES (?, ?, ?, ?, 'pelanggan', 'aktif', 0, 'default.jpg', 0)");
    mysqli_stmt_bind_param($stmt, 'isss', $id_user, $nama, $email, $password_hashed);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        sweetalert_redirect('Terjadi kesalahan sistem saat menyimpan data. Coba lagi nanti.', 'register.php', 'error', 'Pendaftaran Gagal!');
    }
    mysqli_stmt_close($stmt);
}

// Kirim kode OTP & arahkan ke halaman verifikasi.
$_SESSION['otp_email'] = $email;
$_SESSION['otp_nama']  = $nama;

$hasil = otp_buat_dan_kirim($koneksi, $email, $nama, OTP_TUJUAN_DAFTAR);
if ($hasil['ok'] && $hasil['kode_dev'] !== null) {
    $_SESSION['otp_kode_dev'] = $hasil['kode_dev'];
}

sweetalert_redirect('Akun berhasil dibuat! Kami telah mengirim kode verifikasi ke email Anda.', 'verifikasi_email.php', 'success', 'Hampir Selesai!');
