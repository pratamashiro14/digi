<?php
require_once __DIR__ . '/auth.php';

// 1. CEK LOGIN TERLEBIH DAHULU — khusus pembeli/user
require_user();

// 2. HUBUNGKAN KE DATABASE
// Pastikan nama file koneksi kamu benar
include 'admin/koneksi.php'; 

// 3. HUBUNGKAN KE LIBRARY MIDTRANS
// Sesuaikan path (lokasi) foldernya dengan struktur folder kamu
// Jika pakai Composer: require_once dirname(__FILE__) . '/vendor/autoload.php';
// Jika manual:
require_once dirname(__FILE__) . '/Midtrans/Midtrans.php'; 

// 4. KONFIGURASI MIDTRANS (WAJIB ADA DISINI)
// Ganti dengan SERVER KEY dari dashboard Midtrans kamu (Sandbox)
\Midtrans\Config::$serverKey = 'Mid-server-yE95ZcyAgzoCQHosJ868mVL0'; 
\Midtrans\Config::$isProduction = false; // Set false untuk Sandbox
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

// 4. AMBIL DATA USER & SIAPKAN TRANSAKSI (id_buyer = id user yang login)
$id_buyer = current_id();
// Buat Order ID unik (misal: PREM + timestamp)
$order_id = 'PREM-' . time(); 
// Tentukan harga premium (bisa hardcode atau ambil dari database paket)
$harga_paket = 50000; // Contoh: Rp 50.000

// 5. SIAPKAN PARAMETER UNTUK MIDTRANS
$transaction_details = array(
    'order_id' => $order_id,
    'gross_amount' => $harga_paket, // Harga harus angka (integer), jangan string
);

// Opsional: Data Pelanggan (biar rapi di dashboard midtrans)
// $customer_details = array(
//    'first_name'    => "User ID $id_buyer",
//    'email'         => "user@example.com",
// );

$params = array(
    'transaction_details' => $transaction_details,
    // 'customer_details' => $customer_details, // Aktifkan jika ada
);

try {
    // 6. MINTA SNAP TOKEN KE MIDTRANS
    $snapToken = \Midtrans\Snap::getSnapToken($params);

    // 7. SIMPAN KE DATABASE (FIX ERROR FOREIGN KEY DISINI)
    // Perhatikan: id_design diisi NULL karena ini beli premium
    $query_sql = "INSERT INTO t_transaksi 
        (id_buyer, id_design, harga_final, metode_pembayaran, status_pembayaran, tanggal_transaksi, id_midtrans_order, snap_token) 
        VALUES 
        ('$id_buyer', NULL, '$harga_paket', 'midtrans_snap', 'pending', NOW(), '$order_id', '$snapToken')
    ";
    
    // Eksekusi Query
    if (mysqli_query($koneksi, $query_sql)) {
        // 8. JIKA BERHASIL, ARAHKAN KE HALAMAN BAYAR
        // Kita kirim tokennya lewat URL atau simpan di session
        header("Location: bayar_premium.php?order_id=$order_id&token=$snapToken");
    } else {
        echo "Gagal menyimpan transaksi ke database: " . mysqli_error($koneksi);
    }

} catch (Exception $e) {
    echo "Terjadi kesalahan Midtrans: " . $e->getMessage();
}

?>