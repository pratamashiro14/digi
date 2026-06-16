<?php
// ==========================================================
// PROSES BELI PREMIUM  (langkah 1 dari 3)
// premium.php  ->  proses_beli_premium.php  ->  bayar_premium.php  ->  cek_status_premium.php
//
// Tugas file ini:
//   1. Validasi paket & role (harga ditentukan SERVER, bukan dari form)
//   2. Buat order id unik & minta Snap Token ke Midtrans
//   3. Simpan langganan berstatus 'pending' di t_premium
//   4. Arahkan ke halaman pembayaran
// ==========================================================
require_once __DIR__ . '/auth.php';
require_login();                 // boleh pembeli ATAU desainer
include 'admin/koneksi.php';
require_once __DIR__ . '/midtrans_config.php';

// ---- 1. Paket resmi (sumber kebenaran harga ada di server) ----
$tipe  = $_POST['tipe_premium'] ?? '';
$paket = [
    'pengguna' => ['harga' => 25000, 'butuh' => 'pembeli'],
    'desainer' => ['harga' => 28000, 'butuh' => 'desainer'],
];

if (!isset($paket[$tipe])) {
    sweetalert_redirect('Paket premium tidak valid.', 'premium.php', 'error', 'Gagal');
}

$harga  = $paket[$tipe]['harga'];
$durasi = 30; // hari

// ---- 2. Role harus cocok dengan paket ----
if ($tipe === 'pengguna' && !is_user_login()) {
    sweetalert_redirect('Paket ini khusus akun pembeli.', 'premium.php', 'error', 'Tidak Sesuai');
}
if ($tipe === 'desainer' && !is_designer_login()) {
    sweetalert_redirect('Paket ini khusus akun desainer.', 'premium.php', 'error', 'Tidak Sesuai');
}

$id_user = (int) current_id();
$nama    = current_name();
$email   = current_email();

// ---- 3. Order ID unik untuk transaksi ini ----
$order_id = 'PREM-' . $id_user . '-' . time();

// ---- 4. Konfigurasi Midtrans sudah dimuat dari midtrans_config.php (key dari config.php) ----

$params = [
    'transaction_details' => [
        'order_id'     => $order_id,
        'gross_amount' => $harga,
    ],
    'item_details' => [[
        'id'       => 'PREMIUM-' . $tipe,
        'price'    => $harga,
        'quantity' => 1,
        'name'     => 'Premium ' . ucfirst($tipe) . ' 30 Hari',
    ]],
    'customer_details' => [
        'first_name' => $nama,
        'email'      => $email,
    ],
];

try {
    $snapToken = \Midtrans\Snap::getSnapToken($params);
} catch (Exception $e) {
    sweetalert_redirect('Gagal terhubung ke pembayaran: ' . $e->getMessage(), 'premium.php', 'error', 'Gagal');
}

// ---- 5. Simpan langganan 'pending' di t_premium ----
$tipe_esc  = mysqli_real_escape_string($koneksi, $tipe);
$order_esc = mysqli_real_escape_string($koneksi, $order_id);
$token_esc = mysqli_real_escape_string($koneksi, $snapToken);

$insert = "INSERT INTO t_premium
    (id_user, tipe_premium, harga, durasi_hari, tanggal_aktif, tanggal_berakhir,
     status, no_referensi_pembayaran, snap_token)
    VALUES
    ('$id_user', '$tipe_esc', '$harga', '$durasi', CURDATE(),
     DATE_ADD(CURDATE(), INTERVAL $durasi DAY), 'pending', '$order_esc', '$token_esc')";

if (!mysqli_query($koneksi, $insert)) {
    sweetalert_redirect('Gagal menyimpan langganan: ' . mysqli_error($koneksi), 'premium.php', 'error', 'Gagal');
}

// ---- 6. Lanjut ke halaman pembayaran ----
header('Location: bayar_premium.php?order_id=' . urlencode($order_id));
exit;
