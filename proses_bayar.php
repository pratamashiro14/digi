<?php
require_once __DIR__ . '/auth.php';
// 1. HUBUNGKAN KONEKSI DATABASE (Arahkan ke folder admin)
include 'admin/koneksi.php';

// Hubungkan ke Midtrans + konfigurasi (key dari config.php)
require_once __DIR__ . '/midtrans_config.php';

// Aturan tenggat & anti-duplikat pembayaran pending
require_once __DIR__ . '/pembayaran_helper.php';

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

    $cek_karya = mysqli_prepare($koneksi, "SELECT d.status,
                EXISTS (
                    SELECT 1
                    FROM t_transaksi t
                    WHERE t.id_design = d.id_design
                      AND t.status_pembayaran IN ('berhasil', 'settlement', 'capture')
                ) AS sudah_terjual
            FROM t_design d
            WHERE d.id_design = ?
            LIMIT 1");
    mysqli_stmt_bind_param($cek_karya, 'i', $id_design);
    mysqli_stmt_execute($cek_karya);
    $data_karya = mysqli_fetch_assoc(mysqli_stmt_get_result($cek_karya));

    if (!$data_karya) {
        sweetalert_redirect('Karya tidak ditemukan.', 'product.php', 'error', 'Pembayaran Ditolak');
    }

    if (($data_karya['status'] ?? '') === 'sold' || !empty($data_karya['sudah_terjual'])) {
        sweetalert_redirect('Karya ini sudah terjual dan tidak bisa dibayar lagi.', 'product.php', 'info', 'Karya Terjual');
    }

    // --- A2. BERSIHKAN PENDING KEDALUWARSA & CEGAH TRANSAKSI GANDA ---
    // Pending yang sudah lewat tenggat otomatis digagalkan agar karya bebas lagi.
    expire_pending_kedaluwarsa($koneksi);
    // Bila pembeli ini masih punya pending aktif untuk karya yang sama, jangan
    // buat baris baru: arahkan menyelesaikan pembayaran yang sudah ada.
    if (pending_aktif_untuk_design($koneksi, $id_buyer, $id_design)) {
        sweetalert_redirect(
            'Selesaikan dulu pembayaran yang telah kamu lakukan untuk karya ini. Cek di Riwayat Pembelian.',
            'riwayat.php',
            'info',
            'Pembayaran Belum Selesai'
        );
    }

    // --- B. BUAT ORDER ID UNIK ---
    $order_id = "ORD-" . time() . "-" . rand(100, 999);

    // --- C. SIMPAN KE DATABASE (Status: pending) ---
    // Perbaikan: Menggunakan variabel $koneksi (bukan $conn)
    // batas_pembayaran = tenggat pembeli menyelesaikan pembayaran (limit menit).
    $batas_menit = (int) PEMBAYARAN_LIMIT_MENIT;
    $stmt_simpan = mysqli_prepare($koneksi, "INSERT INTO t_transaksi (
                        id_midtrans_order,
                        id_design,
                        id_buyer,
                        harga_final,
                        status_pembayaran,
                        tanggal_transaksi,
                        batas_pembayaran
                    ) VALUES (?, ?, ?, ?, 'pending', NOW(), NOW() + INTERVAL {$batas_menit} MINUTE)");
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
        // Link pembayaran ikut kedaluwarsa sesuai tenggat (limit menit).
        'expiry' => array(
            'unit'     => 'minute',
            'duration' => (int) PEMBAYARAN_LIMIT_MENIT,
        ),
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
            src="https://app<?php echo MIDTRANS_IS_PRODUCTION ? '' : '.sandbox'; ?>.midtrans.com/snap/snap.js"
            data-client-key="<?php echo htmlspecialchars(MIDTRANS_CLIENT_KEY, ENT_QUOTES); ?>"></script>
    <script src="<?php echo BASE_URL; ?>/vendor/sweetalert/sweetalert.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; text-align: center; padding: 50px; }
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
