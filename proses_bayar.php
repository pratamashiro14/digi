<?php
require_once __DIR__ . '/auth.php';
// 1. HUBUNGKAN KONEKSI DATABASE (Arahkan ke folder admin)
include 'admin/koneksi.php';

// Hubungkan ke Midtrans + konfigurasi (key dari config.php)
require_once __DIR__ . '/midtrans_config.php';

// Cek apakah user sudah login
require_user();

$id_user = current_id();

// 3. PROSES SAAT TOMBOL BAYAR DITEKAN
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // --- A. AMBIL DATA DARI FORM (SHOPING-CART) ---
    $id_buyer = (int) $_SESSION['id_user'];      // ID User yang login
    $id_design = (int) $_POST['id_design_midtrans'];
    $judul_produk = $_POST['judul_midtrans'];
    $total_bayar = (int) preg_replace('/[^0-9]/', '', $_POST['total_bayar_midtrans']);
    
    // Validasi tambahan
    $tipe_transaksi = isset($_POST['tipe_transaksi']) ? $_POST['tipe_transaksi'] : 'biasa';
    $id_bidding = isset($_POST['id_bidding_midtrans']) ? $_POST['id_bidding_midtrans'] : '0';

    // --- B. BUAT ORDER ID UNIK ---
    $order_id = "ORD-" . time() . "-" . rand(100, 999);

    // --- C. SIMPAN KE DATABASE (Status: pending) ---
    // Perbaikan: Menggunakan variabel $koneksi (bukan $conn)
    $stmt_simpan = mysqli_prepare($koneksi, "INSERT INTO t_transaksi (
                        id_midtrans_order,
                        id_design,
                        id_buyer,
                        harga_final,
                        status_pembayaran,
                        tanggal_transaksi
                    ) VALUES (?, ?, ?, ?, 'pending', NOW())");
    mysqli_stmt_bind_param($stmt_simpan, 'siii', $order_id, $id_design, $id_buyer, $total_bayar);

    // Eksekusi Simpan ke Database
    if (!mysqli_stmt_execute($stmt_simpan)) {
        die("Error menyimpan data transaksi.");
    }

    // --- D. SUSUN PARAMETER UNTUK MIDTRANS ---
    $transaction_details = array(
        'order_id' => $order_id,
        'gross_amount' => (int) $total_bayar, 
    );

    $item1_details = array(
        'id' => $id_design,
        'price' => (int) $total_bayar,
        'quantity' => 1,
        'name' => substr($judul_produk, 0, 50) 
    );

    $item_details = array($item1_details);

    $custom_field1 = $tipe_transaksi; 
    $custom_field2 = $id_bidding;     
    $custom_field3 = $id_design;      

    $params = array(
        'transaction_details' => $transaction_details,
        'item_details' => $item_details,
        'custom_field1' => $custom_field1,
        'custom_field2' => $custom_field2,
        'custom_field3' => $custom_field3,
    );

    try {
        // Minta Snap Token ke Midtrans
        $snapToken = \Midtrans\Snap::getSnapToken($params);
    } catch (Exception $e) {
        echo "Error Midtrans: " . $e->getMessage();
        exit;
    }
} else {
    // Jika dibuka langsung tanpa form
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Menunggu Pembayaran</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script type="text/javascript"
            src="https://app.sandbox.midtrans.com/snap/snap.js"
            data-client-key="<?php echo htmlspecialchars(MIDTRANS_CLIENT_KEY, ENT_QUOTES); ?>"></script>
    <script src="/digi/vendor/sweetalert/sweetalert.min.js"></script>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; }
        .btn-pay {
            background-color: #000; color: white; padding: 15px 30px; 
            text-decoration: none; font-size: 16px; border-radius: 5px; cursor: pointer; border: none;
        }
    </style>
</head>
<body>
    <h3>Menyiapkan Pembayaran...</h3>
    <p>Silakan selesaikan pembayaran untuk: <strong><?php echo $judul_produk; ?></strong></p>
    <p>Total: <strong>Rp <?php echo number_format($total_bayar, 0, ',', '.'); ?></strong></p>
    
    <button id="pay-button" class="btn-pay">BAYAR SEKARANG</button>

    <script type="text/javascript">
        var payButton = document.getElementById('pay-button');
        
// GANTI BAGIAN SCRIPT PALING BAWAH DI 'proses_bayar.php' JADI INI:

        window.onload = function() {
             window.snap.pay('<?php echo $snapToken; ?>', {
                onSuccess: function(result){
                    // [UBAH DI SINI] Arahkan ke cek_status.php bawa Order ID
                    window.location.href = "cek_status.php?order_id=<?php echo $order_id; ?>";
                },
                onPending: function(result){
                    swal('Menunggu Pembayaran', 'Pembayaran belum selesai.', 'info').then(function () {
                        window.location.href = "riwayat.php";
                    });
                },
                onError: function(result){
                    swal('Pembayaran Gagal!', 'Pembayaran tidak dapat diproses.', 'error').then(function () {
                        window.location.href = "riwayat.php";
                    });
                },
                onClose: function(){
                    swal('Pembayaran Belum Selesai', 'Anda menutup popup tanpa menyelesaikan pembayaran.', 'warning');
                }
            });
        };
    </script>
</body>
</html>
