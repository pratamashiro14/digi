<?php
require_once __DIR__ . '/auth.php';
// Hubungkan ke database (Folder admin)
include 'admin/koneksi.php';
// Hubungkan Library + konfigurasi Midtrans (key dari config.php)
require_once __DIR__ . '/midtrans_config.php';

// Cek Login — khusus pembeli/user
require_user();

// Cek ID Transaksi dari URL
if (!isset($_GET['id'])) {
    header("Location: riwayat.php");
    exit;
}

$id_transaksi = (int) $_GET['id'];
$id_user = (int) $_SESSION['id_user'];

// 1. AMBIL DATA TRANSAKSI LAMA
// Kita cari transaksi yang statusnya pending milik user ini
$stmt = mysqli_prepare($koneksi, "SELECT t.*, d.judul
          FROM t_transaksi t
          JOIN t_design d ON t.id_design = d.id_design
          WHERE t.id_transaksi = ?
          AND t.id_buyer = ?
          AND t.status_pembayaran = 'pending'");
mysqli_stmt_bind_param($stmt, 'ii', $id_transaksi, $id_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
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
$update_stmt = mysqli_prepare($koneksi, "UPDATE t_transaksi
                 SET id_midtrans_order = ?,
                     tanggal_transaksi = NOW()
                 WHERE id_transaksi = ?");
mysqli_stmt_bind_param($update_stmt, 'si', $order_id_baru, $id_transaksi);

if (!mysqli_stmt_execute($update_stmt)) {
    die("Gagal update ID baru.");
}

// Konfigurasi Midtrans sudah dimuat dari midtrans_config.php (key dari config.php)

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
            data-client-key="<?php echo htmlspecialchars(MIDTRANS_CLIENT_KEY, ENT_QUOTES); ?>"></script>
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
