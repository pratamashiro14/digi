<?php
/**
 * Callback dari Google setelah pengguna menyetujui/menolak consent.
 * Menukar authorization code, verifikasi identitas, lalu cari/buat akun
 * desainer dan login. Lihat google_auth_helper.php untuk detail logika.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/google_auth_helper.php';
include 'admin/koneksi.php';

if (isset($_GET['error'])) {
    redirect_with_alert('Login dengan Google dibatalkan.', 'login.php', 'info', 'Dibatalkan');
}

$code  = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

if ($code === '' || !google_state_valid($state)) {
    redirect_with_alert('Sesi login Google tidak valid atau kedaluwarsa. Silakan coba lagi.', 'login.php', 'error', 'Gagal');
}

$tukar = google_tukar_code_dan_verifikasi($code);
if (!$tukar['ok']) {
    redirect_with_alert($tukar['error'], 'login.php', 'error', 'Login Google Gagal');
}

$hasil = google_resolve_akun_desainer($koneksi, $tukar['email'], $tukar['nama']);
if (!$hasil['ok']) {
    redirect_with_alert($hasil['error'], 'login.php', 'error', 'Login Google Gagal');
}

redirect_with_alert('Berhasil masuk dengan Google.', 'index.php', 'success', 'Selamat Datang!');
