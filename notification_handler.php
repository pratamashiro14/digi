<?php
require_once dirname(__FILE__) . '/midtrans-php-master/Midtrans.php'; // Sesuaikan path library Midtrans kamu
include 'koneksi.php'; // File koneksi database kamu

\Midtrans\Config::$serverKey = 'SB-Mid-server-xxxx'; // GANTI DENGAN SERVER KEY KAMU
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

try {
    $notif = new \Midtrans\Notification();
} catch (\Exception $e) {
    exit($e->getMessage());
}

$notif = $notif->getResponse();
$transaction = $notif->transaction_status;
$type = $notif->payment_type;
$order_id = $notif->order_id;
$fraud = $notif->fraud_status;

// Logika Status Transaksi
if ($transaction == 'capture') {
    if ($type == 'credit_card') {
        if ($fraud == 'challenge') {
            $status = 'Pending';
        } else {
            $status = 'Success'; // Berhasil
        }
    }
} else if ($transaction == 'settlement') {
    // INI YANG PALING PENTING UNTUK TRANSFER BANK/VA
    $status = 'Success'; 
} else if ($transaction == 'pending') {
    $status = 'Pending';
} else if ($transaction == 'deny') {
    $status = 'Failed';
} else if ($transaction == 'expire') {
    $status = 'Expired';
} else if ($transaction == 'cancel') {
    $status = 'Canceled';
}

// Update Database Kamu
// Sesuaikan nama tabel dan kolom dengan database kamu
$query = "UPDATE transaksi SET status_pembayaran = '$status' WHERE order_id = '$order_id'";
mysqli_query($conn, $query);

echo "Status Transaction Updated";
?>