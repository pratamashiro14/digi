<?php
session_start();
include 'admin/koneksi.php';
require_once __DIR__ . '/midtrans_config.php';
require_once __DIR__ . '/keuangan_helper.php';

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

    if ($status_baru === 'berhasil') {
        // Update HANYA bila belum 'berhasil' — affected_rows>0 menandakan transisi
        // pertama ke LUNAS, sehingga notifikasi penjualan tidak ganda (webhook + halaman ini).
        $stmt = mysqli_prepare($koneksi,
            "UPDATE t_transaksi SET status_pembayaran = 'berhasil'
             WHERE id_midtrans_order = ? AND status_pembayaran <> 'berhasil'");
        mysqli_stmt_bind_param($stmt, 's', $order_id);
        mysqli_stmt_execute($stmt);
        $baru_lunas = mysqli_stmt_affected_rows($stmt) > 0;

        // Tandai karyanya 'sold' agar tidak tampil "Tayang" & tidak bisa dibeli lagi
        $sold = mysqli_prepare($koneksi,
            "UPDATE t_design d
             JOIN t_transaksi t ON t.id_design = d.id_design
             SET d.status = 'sold'
             WHERE t.id_midtrans_order = ?");
        mysqli_stmt_bind_param($sold, 's', $order_id);
        mysqli_stmt_execute($sold);

        // Notifikasi "karya terjual + potongan admin" ke desainer (sekali saja)
        if ($baru_lunas) {
            $oid = mysqli_real_escape_string($koneksi, $order_id);
            $q = mysqli_query($koneksi, "SELECT id_transaksi FROM t_transaksi WHERE id_midtrans_order = '$oid' LIMIT 1");
            if ($q && ($t = mysqli_fetch_assoc($q))) {
                kirim_notif_penjualan($koneksi, (int) $t['id_transaksi']);
            }
        }
    } else {
        // UPDATE DATABASE (prepared statement — cegah SQL injection lewat order_id)
        $stmt = mysqli_prepare($koneksi, "UPDATE t_transaksi SET status_pembayaran = ? WHERE id_midtrans_order = ?");
        mysqli_stmt_bind_param($stmt, 'ss', $status_baru, $order_id);
        mysqli_stmt_execute($stmt);
    }

    // BALIK KE RIWAYAT
    header("Location: riwayat.php");

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>