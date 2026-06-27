<?php
// ==========================================================
// CEK STATUS PREMIUM  (langkah 3 dari 3)  -- INTI KEAMANAN
//
// Setelah user bayar, halaman ini TIDAK percaya kata browser.
// Ia bertanya langsung ke server Midtrans (server-to-server):
//   "Order ini benar-benar lunas / berapa nominalnya?"
// Hanya kalau Midtrans bilang LUNAS dan nominal cocok -> premium diaktifkan.
// ==========================================================
require_once __DIR__ . '/auth.php';
require_login();
include 'admin/koneksi.php';
require_once __DIR__ . '/midtrans_config.php';

$order_id  = $_GET['order_id'] ?? '';
$id_user   = (int) current_id();
$order_esc = mysqli_real_escape_string($koneksi, $order_id);

// 1. Ambil langganan milik user yang login (cegah cek order milik orang lain)
$q = mysqli_query($koneksi, "SELECT * FROM t_premium
    WHERE no_referensi_pembayaran = '$order_esc' AND id_user = '$id_user' LIMIT 1");
$prem = $q ? mysqli_fetch_assoc($q) : null;
if (!$prem) {
    sweetalert_redirect('Transaksi tidak ditemukan.', 'premium.php', 'error', 'Gagal');
}

// 2. Konfigurasi Midtrans sudah dimuat dari midtrans_config.php (key dari config.php)

$view           = 'failed';
$pesan          = '';
$berlaku_sampai = $prem['tanggal_berakhir'];

// 3. Tanya status SUNGGUHAN ke Midtrans
try {
    $status = \Midtrans\Transaction::status($order_id);

    $trx      = $status->transaction_status ?? '';
    $fraud    = $status->fraud_status ?? '';
    $gross    = (int) ($status->gross_amount ?? 0);
    $expected = (int) $prem['harga'];
    $amount_ok = ($gross === $expected); // cegah manipulasi nominal

    $lunas = $amount_ok && (
        $trx === 'settlement' ||
        ($trx === 'capture' && $fraud === 'accept')
    );

    if ($lunas) {
        $view = 'success';
        // Aktifkan hanya sekali; hitung masa berlaku dari hari ini
        if ($prem['status'] !== 'aktif') {
            $durasi = (int) $prem['durasi_hari'];
            if ($durasi <= 0) $durasi = 30;
            $idp = (int) $prem['id_premium'];
            mysqli_query($koneksi, "UPDATE t_premium SET
                status='aktif',
                tanggal_aktif=CURDATE(),
                tanggal_berakhir=DATE_ADD(CURDATE(), INTERVAL $durasi DAY)
                WHERE id_premium='$idp'");
            $r = mysqli_fetch_assoc(mysqli_query($koneksi,
                "SELECT tanggal_berakhir FROM t_premium WHERE id_premium='$idp'"));
            if ($r) $berlaku_sampai = $r['tanggal_berakhir'];
        }
    } elseif ($trx === 'pending') {
        $view  = 'pending';
        $pesan = 'Pembayaran kamu sedang diproses. Status premium akan aktif begitu pembayaran dikonfirmasi.';
    } elseif (!$amount_ok && ($trx === 'settlement' || $trx === 'capture')) {
        $view  = 'failed';
        $pesan = 'Nominal pembayaran tidak sesuai. Silakan hubungi admin.';
    } else {
        // deny / cancel / expire / belum bayar
        $view  = 'failed';
        $pesan = 'Pembayaran belum selesai atau dibatalkan. Kamu bisa mencoba lagi.';
    }
} catch (Exception $e) {
    $view  = 'failed';
    $pesan = 'Tidak dapat memverifikasi pembayaran: ' . $e->getMessage();
}

// 4. Tombol lanjut sesuai role
if (is_designer_login()) {
    $lanjut_url   = 'index.php';
    $lanjut_label = 'Ke Beranda';
} else {
    $lanjut_url   = 'desainer_favorit.php';
    $lanjut_label = 'Mulai Favoritkan Desainer';
}

// 5. Format tanggal Indonesia
function tgl_id($tgl) {
    $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];
    $ts = strtotime($tgl);
    if (!$ts) return $tgl;
    return date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Status Pembayaran Premium - DENS CREATIVE</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" type="image/png" href="images/icons/favicon.png"/>
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/iconic/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/css-hamburgers/hamburgers.min.css">
    <link rel="stylesheet" type="text/css" href="css/util.css">
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <link rel="stylesheet" type="text/css" href="css/account-ui.css?v=<?php echo filemtime(__DIR__ . '/css/account-ui.css'); ?>">

    <style>
        .res-wrap { max-width: 520px; margin: 50px auto 80px; padding: 0 15px; font-family: 'Poppins', sans-serif; }
        .res-card { background: #fff; border-radius: 18px; box-shadow: 0 14px 40px rgba(0,0,0,0.08);
                    padding: 44px 34px; text-align: center; }
        .res-icon { width: 84px; height: 84px; border-radius: 50%; margin: 0 auto 20px;
                    display: flex; align-items: center; justify-content: center; font-size: 40px; color: #fff; }
        .ic-success { background: linear-gradient(135deg,#22c55e,#16a34a); }
        .ic-pending { background: linear-gradient(135deg,#f59e0b,#d97706); }
        .ic-failed  { background: linear-gradient(135deg,#ef4444,#dc2626); }
        .res-card h1 { font-size: 24px; font-weight: 800; color: #1f2937; margin: 0 0 10px; }
        .res-card p  { color: #6b7280; font-size: 15px; margin: 0 0 8px; line-height: 1.6; }
        .res-valid { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534;
                     border-radius: 12px; padding: 12px 16px; margin: 20px 0 4px; font-size: 14px; font-weight: 600; }
        .res-actions { margin-top: 28px; display: flex; flex-direction: column; gap: 10px; }
        .res-btn { display: inline-block; width: 100%; padding: 13px 0; border-radius: 50px;
                   font-weight: 700; font-size: 15px; text-decoration: none; border: none; cursor: pointer; transition: 0.25s; }
        .btn-primary-grad { background: linear-gradient(90deg,#1591DC,#6b4eff); color: #fff; }
        .btn-primary-grad:hover { opacity: 0.92; color: #fff; }
        .btn-ghost { background: #f3f4f6; color: #4b5563; }
        .btn-ghost:hover { background: #e5e7eb; color: #1f2937; }
    </style>
</head>
<body class="animsition account-page">

    <?php
    $active_page = 'premium';
    include 'navbar.php';
    ?>

    <div class="res-wrap">
        <div class="res-card">

            <?php if ($view === 'success') { ?>
                <div class="res-icon ic-success"><i class="fa fa-check"></i></div>
                <h1>Premium Aktif!</h1>
                <p>Selamat, akun kamu sekarang <b>Premium</b>. Semua fitur premium sudah terbuka.</p>
                <div class="res-valid"><i class="fa fa-calendar-check-o"></i>
                    Berlaku sampai <?php echo htmlspecialchars(tgl_id($berlaku_sampai)); ?></div>
                <div class="res-actions">
                    <a href="<?php echo $lanjut_url; ?>" class="res-btn btn-primary-grad"><?php echo $lanjut_label; ?></a>
                    <a href="index.php" class="res-btn btn-ghost">Kembali ke Beranda</a>
                </div>

            <?php } elseif ($view === 'pending') { ?>
                <div class="res-icon ic-pending"><i class="fa fa-clock-o"></i></div>
                <h1>Menunggu Pembayaran</h1>
                <p><?php echo htmlspecialchars($pesan); ?></p>
                <div class="res-actions">
                    <a href="cek_status_premium.php?order_id=<?php echo urlencode($order_id); ?>" class="res-btn btn-primary-grad">
                        <i class="fa fa-refresh"></i> Cek Ulang Status
                    </a>
                    <a href="premium.php" class="res-btn btn-ghost">Kembali</a>
                </div>

            <?php } else { ?>
                <div class="res-icon ic-failed"><i class="fa fa-times"></i></div>
                <h1>Pembayaran Belum Selesai</h1>
                <p><?php echo htmlspecialchars($pesan); ?></p>
                <div class="res-actions">
                    <a href="premium.php" class="res-btn btn-primary-grad">Coba Lagi</a>
                    <a href="index.php" class="res-btn btn-ghost">Kembali ke Beranda</a>
                </div>
            <?php } ?>

        </div>
    </div>

    <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
