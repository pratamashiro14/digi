<?php
session_start();
include 'admin/koneksi.php';
require_once __DIR__ . '/midtrans_config.php';

if (!isset($_GET['order_id'])) {
    header("Location: riwayat.php");
    exit;
}

$order_id = $_GET['order_id'];

// Konfigurasi Midtrans sudah dimuat dari midtrans_config.php (key dari config.php)

try {
    // TANYA STATUS KE MIDTRANS
    $notif = \Midtrans\Transaction::status($order_id);
    $transaction = $notif->transaction_status;

    // TENTUKAN STATUS BARU (HARUS sesuai enum t_transaksi: 'pending','berhasil','gagal')
    $status_baru = 'pending';
    if ($transaction == 'settlement' || $transaction == 'capture') {
        $status_baru = 'berhasil'; // LUNAS
    } else if ($transaction == 'expire' || $transaction == 'cancel' || $transaction == 'deny') {
        $status_baru = 'gagal'; // GAGAL
    }

    // UPDATE DATABASE (prepared statement — cegah SQL injection lewat order_id)
    $stmt = mysqli_prepare($koneksi, "UPDATE t_transaksi SET status_pembayaran = ? WHERE id_midtrans_order = ?");
    mysqli_stmt_bind_param($stmt, 'ss', $status_baru, $order_id);
    mysqli_stmt_execute($stmt);

    // Jika LUNAS, tandai karyanya 'sold' agar tidak tampil "Tayang" & tidak bisa dibeli lagi
    if ($status_baru === 'berhasil') {
        $sold = mysqli_prepare($koneksi,
            "UPDATE t_design d
             JOIN t_transaksi t ON t.id_design = d.id_design
             SET d.status = 'sold'
             WHERE t.id_midtrans_order = ?");
        mysqli_stmt_bind_param($sold, 's', $order_id);
        mysqli_stmt_execute($sold);
    }

    // BALIK KE RIWAYAT
    header("Location: riwayat.php");

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>