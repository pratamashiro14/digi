<?php
session_start();
include 'admin/koneksi.php';
require_once __DIR__ . '/midtrans/Midtrans.php';

if (!isset($_GET['order_id'])) {
    header("Location: riwayat.php");
    exit;
}

$order_id = $_GET['order_id'];

// Konfigurasi Midtrans
\Midtrans\Config::$serverKey = 'Mid-server-yE95ZcyAgzoCQHosJ868mVL0'; // Pastikan Server Key Benar
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

try {
    // TANYA STATUS KE MIDTRANS
    $notif = \Midtrans\Transaction::status($order_id);
    $transaction = $notif->transaction_status;

    // TENTUKAN STATUS BARU
    $status_baru = 'pending';
    if ($transaction == 'settlement' || $transaction == 'capture') {
        $status_baru = 'settlement'; // LUNAS
    } else if ($transaction == 'expire' || $transaction == 'cancel' || $transaction == 'deny') {
        $status_baru = 'expire'; // GAGAL
    }

    // UPDATE DATABASE
    $query = "UPDATE t_transaksi SET status_pembayaran = '$status_baru' WHERE id_midtrans_order = '$order_id'";
    mysqli_query($koneksi, $query);

    // BALIK KE RIWAYAT
    header("Location: riwayat.php");

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>