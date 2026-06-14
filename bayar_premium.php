<?php
// ==========================================================
// BAYAR PREMIUM  (langkah 2 dari 3)
// Menampilkan popup pembayaran Midtrans Snap.
// Token diambil dari DB (bukan dari URL) berdasarkan order_id milik user yang login.
// ==========================================================
require_once __DIR__ . '/auth.php';
require_login();
include 'admin/koneksi.php';

$order_id  = $_GET['order_id'] ?? '';
$id_user   = (int) current_id();
$order_esc = mysqli_real_escape_string($koneksi, $order_id);

$q = mysqli_query($koneksi, "SELECT * FROM t_premium
    WHERE no_referensi_pembayaran = '$order_esc'
      AND id_user = '$id_user'
      AND status = 'pending'
    LIMIT 1");
$data = $q ? mysqli_fetch_assoc($q) : null;

if (!$data) {
    sweetalert_redirect('Transaksi premium tidak ditemukan atau sudah diproses.', 'premium.php', 'info', 'Informasi');
}

$snap_token = $data['snap_token'];
$harga      = (int) $data['harga'];
$tipe_label = ucfirst($data['tipe_premium']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pembayaran Premium - DENS CREATIVE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="images/icons/favicon.png"/>

    <!-- Midtrans Snap (Sandbox) -->
    <script src="https://app.sandbox.midtrans.com/snap/snap.js"
            data-client-key="SB-Mid-client-6m0YatujRuhkkQ1w"></script>

    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f4f6fb; margin: 0;
               display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .pay-card { background: #fff; max-width: 420px; width: 100%; border-radius: 18px;
                    box-shadow: 0 14px 40px rgba(0,0,0,0.10); padding: 40px 32px; text-align: center; }
        .pay-crown { width: 64px; height: 64px; border-radius: 50%; margin: 0 auto 18px;
                     background: linear-gradient(135deg,#1591DC,#6b4eff); color: #fff;
                     display: flex; align-items: center; justify-content: center; font-size: 28px; }
        .pay-card h2 { font-size: 21px; font-weight: 800; color: #1f2937; margin: 0 0 6px; }
        .pay-card .paket { color: #6b7280; font-size: 14px; margin: 0 0 18px; }
        .pay-amount { font-size: 30px; font-weight: 800; color: #1591DC; margin-bottom: 22px; }
        .spinner { width: 34px; height: 34px; border: 4px solid #e5e7eb; border-top-color: #1591DC;
                   border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 14px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .pay-hint { color: #9ca3af; font-size: 13px; margin-bottom: 22px; }
        .btn-pay { display: inline-block; width: 100%; padding: 14px 0; border: none; cursor: pointer;
                   border-radius: 50px; font-weight: 700; font-size: 16px; color: #fff;
                   background: linear-gradient(90deg,#1591DC,#6b4eff); transition: 0.25s; }
        .btn-pay:hover { opacity: 0.92; }
        .pay-cancel { display: inline-block; margin-top: 16px; color: #9ca3af; font-size: 13px; text-decoration: none; }
        .pay-cancel:hover { color: #ef4444; }
    </style>
</head>
<body>
    <div class="pay-card">
        <div class="pay-crown"><i class="fa fa-star">&#9733;</i></div>
        <h2>Pembayaran Premium</h2>
        <p class="paket">Paket Premium <?php echo htmlspecialchars($tipe_label); ?> &middot; 30 Hari</p>
        <div class="pay-amount">Rp <?php echo number_format($harga, 0, ',', '.'); ?></div>

        <div class="spinner" id="loader"></div>
        <p class="pay-hint">Mengarahkan ke pembayaran&hellip; Jika popup tidak muncul, klik tombol di bawah.</p>

        <button id="pay-button" class="btn-pay">Buka Pembayaran</button>
        <a href="premium.php" class="pay-cancel">Batalkan</a>
    </div>

    <script type="text/javascript">
        var orderId = "<?php echo urlencode($order_id); ?>";

        function bayarSekarang() {
            window.snap.pay("<?php echo htmlspecialchars($snap_token, ENT_QUOTES); ?>", {
                onSuccess: function () { window.location.href = "cek_status_premium.php?order_id=" + orderId; },
                onPending: function () { window.location.href = "cek_status_premium.php?order_id=" + orderId; },
                onError:   function () { window.location.href = "cek_status_premium.php?order_id=" + orderId; },
                onClose:   function () { /* user menutup popup; tetap di halaman ini agar bisa coba lagi */ }
            });
        }

        document.getElementById('pay-button').addEventListener('click', bayarSekarang);
        window.addEventListener('load', bayarSekarang);
    </script>
</body>
</html>
