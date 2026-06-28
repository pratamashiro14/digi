<?php
/**
 * ============================================================
 *  notification_handler.php — Webhook Midtrans (Payment Notification)
 * ============================================================
 * URL ini didaftarkan di Dashboard Midtrans:
 *   Settings -> Configuration -> Payment Notification URL
 *   contoh: https://domainmu/notification_handler.php
 *
 * Keamanan: kelas \Midtrans\Notification TIDAK percaya body kiriman browser.
 * Ia memanggil ulang Transaction::status() ke server Midtrans (server-to-server)
 * memakai Server Key kita, sehingga status & nominal yang dipakai bersifat otoritatif.
 */

require_once __DIR__ . '/midtrans_config.php';   // memuat config.php + set Server Key
require_once __DIR__ . '/admin/koneksi.php';      // menyediakan $koneksi
require_once __DIR__ . '/keuangan_helper.php';    // notifikasi penjualan ke desainer

header('Content-Type: text/plain');

// 1. Ambil status otoritatif dari Midtrans
try {
    $notif = new \Midtrans\Notification();
} catch (\Exception $e) {
    http_response_code(400);
    exit('Notifikasi tidak valid');
}

$order_id    = $notif->order_id ?? '';
$transaction = $notif->transaction_status ?? '';
$fraud       = $notif->fraud_status ?? '';
$gross       = (int) ($notif->gross_amount ?? 0);

if ($order_id === '') {
    http_response_code(400);
    exit('order_id kosong');
}

// 2. Terjemahkan status Midtrans -> status aplikasi
$lunas = ($transaction === 'settlement')
      || ($transaction === 'capture' && $fraud === 'accept');
$gagal = in_array($transaction, ['deny', 'cancel', 'expire'], true);

// 3. Update tabel sesuai prefix order_id
//    - "PREM-..." => langganan premium (t_premium)
//    - lainnya    => transaksi pembelian karya (t_transaksi)
if (strpos($order_id, 'PREM-') === 0) {
    // ----- Langganan premium -----
    $stmt = mysqli_prepare($koneksi,
        "SELECT id_premium, harga, durasi_hari, status FROM t_premium
         WHERE no_referensi_pembayaran = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $order_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($row) {
        $amount_ok = ($gross === (int) $row['harga']);

        if ($lunas && $amount_ok && $row['status'] !== 'aktif') {
            $durasi = (int) $row['durasi_hari'];
            if ($durasi <= 0) $durasi = 30;
            $idp = (int) $row['id_premium'];
            $up = mysqli_prepare($koneksi,
                "UPDATE t_premium
                 SET status='aktif', tanggal_aktif=CURDATE(),
                     tanggal_berakhir=DATE_ADD(CURDATE(), INTERVAL ? DAY)
                 WHERE id_premium=?");
            mysqli_stmt_bind_param($up, 'ii', $durasi, $idp);
            mysqli_stmt_execute($up);
        } elseif ($gagal) {
            $idp = (int) $row['id_premium'];
            $up = mysqli_prepare($koneksi, "UPDATE t_premium SET status='nonaktif' WHERE id_premium=?");
            mysqli_stmt_bind_param($up, 'i', $idp);
            mysqli_stmt_execute($up);
        }
    }
} else {
    // ----- Transaksi pembelian karya -----
    $stmt = mysqli_prepare($koneksi,
        "SELECT id_transaksi, harga_final, status_pembayaran FROM t_transaksi
         WHERE id_midtrans_order = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $order_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($row) {
        $amount_ok = ($gross === (int) $row['harga_final']);
        $status_baru = null;

        if ($lunas && $amount_ok) {
            $status_baru = 'berhasil';
        } elseif ($gagal) {
            $status_baru = 'gagal';
        } elseif ($transaction === 'pending') {
            $status_baru = 'pending';
        }

        if ($status_baru !== null) {
            $idt = (int) $row['id_transaksi'];

            if ($status_baru === 'berhasil') {
                // Update HANYA bila belum 'berhasil' — affected_rows>0 menandakan
                // transisi pertama ke LUNAS, agar notifikasi penjualan tidak ganda
                // (webhook ini + cek_status.php).
                $up = mysqli_prepare($koneksi,
                    "UPDATE t_transaksi SET status_pembayaran = 'berhasil'
                     WHERE id_transaksi = ? AND status_pembayaran <> 'berhasil'");
                mysqli_stmt_bind_param($up, 'i', $idt);
                mysqli_stmt_execute($up);
                $baru_lunas = mysqli_stmt_affected_rows($up) > 0;

                // Tandai karyanya 'sold'
                $sold = mysqli_prepare($koneksi,
                    "UPDATE t_design d
                     JOIN t_transaksi t ON t.id_design = d.id_design
                     SET d.status = 'sold'
                     WHERE t.id_transaksi = ?");
                mysqli_stmt_bind_param($sold, 'i', $idt);
                mysqli_stmt_execute($sold);

                // Notifikasi "karya terjual + potongan admin" ke desainer (sekali saja)
                if ($baru_lunas) {
                    kirim_notif_penjualan($koneksi, $idt);
                }
            } else {
                $up = mysqli_prepare($koneksi,
                    "UPDATE t_transaksi SET status_pembayaran = ? WHERE id_transaksi = ?");
                mysqli_stmt_bind_param($up, 'si', $status_baru, $idt);
                mysqli_stmt_execute($up);
            }
        }
    }
}

// 4. Balas 200 OK supaya Midtrans berhenti mengirim ulang
http_response_code(200);
echo 'OK';
