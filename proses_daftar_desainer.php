<?php
/**
 * ============================================================
 *  PROSES_DAFTAR_DESAINER.PHP — Registrasi DESAINER (manual)
 * ============================================================
 * KTP/NIK DIHAPUS: KYC identitas digantikan Google Login (lihat
 * google_auth_helper.php) untuk yang mendaftar lewat Google, dan untuk
 * jalur manual di sini cukup OTP email seperti pembeli. Gerbang sebelum
 * bisa berjualan bukan lagi verifikasi KTP, melainkan persetujuan MOU
 * (lihat mou_helper.php::require_mou_disetujui(), dipasang di unggahan.php).
 * Jangkar anti-fraud berpindah ke cross-check manual admin saat pencairan
 * dana (keputusan produk, lihat komentar di bidding_helper.php).
 *
 * Pola sama persis dengan proses_daftar.php (pembeli): prepared statement,
 * ID user MAX(id_user)+1, klaim ulang akun yang belum terverifikasi email.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin/koneksi.php';
require_once __DIR__ . '/identitas_helper.php';
require_once __DIR__ . '/otp_helper.php';

const OTP_TUJUAN_DAFTAR_DESAINER = 'registrasi';

$nama           = trim((string) ($_POST['nama'] ?? ''));
$email          = trim(strtolower((string) ($_POST['email'] ?? '')));
$password_input = (string) ($_POST['password'] ?? '');

if ($nama === '' || $email === '' || $password_input === '') {
    redirect_with_alert('Lengkapi semua data pendaftaran desainer.', 'register.php', 'error', 'Pendaftaran Gagal!');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_with_alert('Format email tidak valid.', 'register.php', 'error', 'Pendaftaran Gagal!');
}
if (strlen($password_input) < 6) {
    redirect_with_alert('Kata sandi minimal 6 karakter.', 'register.php', 'error', 'Pendaftaran Gagal!');
}
if (identitas_diblokir($koneksi, 'email', $email)) {
    redirect_with_alert('Email ini diblokir permanen oleh admin dan tidak dapat digunakan untuk mendaftar.', 'register.php', 'error', 'Pendaftaran Gagal!');
}

$password_hashed = password_hash($password_input, PASSWORD_DEFAULT);

// Cek email: kalau sudah ada & SUDAH terverifikasi -> tolak (akun asli orang
// lain). Kalau belum -> klaim ulang, sama seperti proses_daftar.php.
$stmt = mysqli_prepare($koneksi, "SELECT id_user, email_terverifikasi, role FROM t_user WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if ($existing && (int) $existing['email_terverifikasi'] === 1) {
    sweetalert_back('Email sudah digunakan. Silakan gunakan email lain.', 'error', 'Pendaftaran Gagal!');
}

if ($existing) {
    // Klaim ulang akun yang belum pernah diverifikasi emailnya.
    $stmt = mysqli_prepare($koneksi, "UPDATE t_user SET nama = ?, password = ?, role = 'designer', email_terverifikasi = 0 WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, 'ssi', $nama, $password_hashed, $existing['id_user']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
} else {
    $row = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT GREATEST(COALESCE(MAX(id_user), 0) + 1, 100) AS next_id FROM t_user"));
    $id_user = (int) $row['next_id'];

    $stmt = mysqli_prepare($koneksi,
        "INSERT INTO t_user (id_user, nama, email, password, role, status, premium, foto, email_terverifikasi)
         VALUES (?, ?, ?, ?, 'designer', 'aktif', 0, 'default.jpg', 0)");
    mysqli_stmt_bind_param($stmt, 'isss', $id_user, $nama, $email, $password_hashed);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        sweetalert_back('Terjadi kesalahan sistem saat menyimpan data. Coba lagi nanti.', 'error', 'Pendaftaran Gagal!');
    }
    mysqli_stmt_close($stmt);
}

$_SESSION['otp_email'] = $email;
$_SESSION['otp_nama']  = $nama;

$hasil = otp_buat_dan_kirim($koneksi, $email, $nama, OTP_TUJUAN_DAFTAR_DESAINER);
if ($hasil['ok'] && $hasil['kode_dev'] !== null) {
    $_SESSION['otp_kode_dev'] = $hasil['kode_dev'];
}

sweetalert_redirect('Pendaftaran Desainer berhasil! Verifikasi email Anda, lalu baca &amp; setujui MOU untuk mulai berjualan.', 'verifikasi_email.php', 'success', 'Hampir Selesai!');
