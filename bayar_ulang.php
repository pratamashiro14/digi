<?php
require_once __DIR__ . '/auth.php';
// Hubungkan ke database (Folder admin)
include 'admin/koneksi.php';
// Hubungkan Library Midtrans
require_once __DIR__ . '/midtrans/Midtrans.php';

// Cek Login — khusus pembeli/user
require_user();

// Cek ID Transaksi dari URL
if (!isset($_GET['id'])) {
    header("Location: riwayat.php");
    exit;
}

$id_transaksi = $_GET['id'];
$id_user = $_SESSION['id_user'];

// 1. AMBIL DATA TRANSAKSI LAMA
// Kita cari transaksi yang statusnya pending milik user ini
$query = "SELECT t.*, d.judul 
          FROM t_transaksi t 
          JOIN t_design d ON t.id_design = d.id_design 
          WHERE t.id_transaksi = '$id_transaksi' 
          AND t.id_buyer = '$id_user' 
          AND t.status_pembayaran = 'pending'";

$result = mysqli_query($koneksi, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    sweetalert_redirect('Transaksi tidak ditemukan atau sudah lunas.', 'riwayat.php', 'error', 'Transaksi Tidak Tersedia');
}

// ============================================================
// PERBAIKAN: BUAT ORDER ID BARU (REGENERATE)
// ============================================================
// Kita buat ID baru supaya tidak ditolak Midtrans
$order_id_baru = "ORD-" . time() . "-" . rand(100, 999);

// UPDATE DATABASE: Ganti ID lama dengan ID baru
$update_query = "UPDATE t_transaksi 
                 SET id_midtrans_order = '$order_id_baru', 
                     tanggal_transaksi = NOW() 
                 WHERE id_transaksi = '$id_transaksi'";

if (!mysqli_query($koneksi, $update_query)) {
    die("Gagal update ID baru: " . mysqli_error($koneksi));
}

// ============================================================
// KONFIGURASI MIDTRANS
// ============================================================
\Midtrans\Config::$serverKey = 'Mid-server-yE95ZcyAgzoCQHosJ868mVL0'; // Pastikan Server Key Benar
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

// SIAPKAN PARAMETER (Gunakan ID BARU tadi)
$params = array(
    'transaction_details' => array(
        'order_id' => $order_id_baru, // <--- Pakai ID Baru
        'gross_amount' => (int) $data['harga_final'], 
    ),
    'item_details' => array(
        array(
            'id' => $data['id_design'],
            'price' => (int) $data['harga_final'],
            'quantity' => 1,
            'name' => substr($data['judul'], 0, 50)
        )
    ),
);

try {
    // Minta Token Baru ke Midtrans
    $snapToken = \Midtrans\Snap::getSnapToken($params);
} catch (Exception $e) {
    echo "Error Midtrans: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lanjutkan Pembayaran</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script type="text/javascript"
            src="https://app.sandbox.midtrans.com/snap/snap.js"
            data-client-key="SB-Mid-client-6m0YatujRuhkkQ1w"></script> 
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; }
        .btn-pay {
            background-color: #007bff; color: white; padding: 15px 30px; 
            text-decoration: none; font-size: 16px; border-radius: 5px; cursor: pointer; border: none; font-weight: bold;
        }
    </style>
</head>
<body>
    <h3>Lanjutkan Pembayaran</h3>
    <p>Produk: <strong><?php echo $data['judul']; ?></strong></p>
    <p>Total: <strong>Rp <?php echo number_format($data['harga_final']); ?></strong></p>
    <p><small>Order ID Baru: <?php echo $order_id_baru; ?></small></p>
    
    <button id="pay-button" class="btn-pay">BAYAR SEKARANG</button>

    <script type="text/javascript">
        var payButton = document.getElementById('pay-button');
        
// GANTI BAGIAN SCRIPT PALING BAWAH DI 'bayar_ulang.php' JADI INI:
        
        window.onload = function() {
             window.snap.pay('<?php echo $snapToken; ?>', {
                onSuccess: function(result){
                    // [UBAH DI SINI] Arahkan ke cek_status.php bawa Order ID Baru
                    window.location.href = "cek_status.php?order_id=<?php echo $order_id_baru; ?>";
                },
                onPending: function(result){
                    window.location.href = "riwayat.php";
                },
                onError: function(result){
                    window.location.href = "riwayat.php";
                },
                onClose: function(){
                    // Biarkan diam
                }
            });
        };
    </script>
</body>
</html>
