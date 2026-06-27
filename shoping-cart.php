<?php
// ==========================================
// 1. BAGIAN PHP (BACKEND) - AUTO DETECT PEMENANG
// ==========================================
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';
require_once __DIR__ . '/bidding_helper.php'; // bid_leader_id(): pemenang dgn prioritas premium

if (is_designer_login()) {
    redirect_with_alert('Halaman pembelian khusus pembeli. Anda sedang login sebagai Desainer.', 'index.php');
}
if (is_admin_login()) {
    redirect_with_alert('Halaman pembelian khusus pembeli. Anda sedang login sebagai Admin.', 'admin/beranda.php');
}

// Cek Login untuk navbar
$is_designer_logged_in = isset($_SESSION['status_designer']) && $_SESSION['status_designer'] == "login";
$nama_desainer = isset($_SESSION['nama_desainer']) ? $_SESSION['nama_desainer'] : 'Desainer';

$is_user_logged_in = isset($_SESSION['status']) && $_SESSION['status'] == "login";
$nama_user = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'User';

// Hitung Keranjang
$jumlah_item_keranjang = 0;
if(isset($_SESSION['keranjang'])) {
    foreach($_SESSION['keranjang'] as $jml) {
        $jumlah_item_keranjang += $jml;
    }
}

// --- CEK LOGIN UNTUK CHECKOUT ---
// Jika user belum login dan ada action checkout, redirect ke login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    if (isset($_POST['checkout']) || isset($_POST['proses_bayar'])) {
        sweetalert_redirect('Silakan login terlebih dahulu untuk melakukan pembayaran.', 'login.php', 'info', 'Login Diperlukan');
    }
    // Tapi biar bisa lihat halaman shopping-cart (tanpa membayar), kita tidak exit di sini
}

// --- KONFIGURASI DEFAULT ---
$biaya_layanan = 10000;
$asal_transaksi = "biasa"; 
$harga_barang = 0;
$row = []; // Variabel penampung data

// --- CEK APAKAH USER LOGIN? ---
// Kita butuh ID user untuk ngecek otomatis dia menang lelang apa nggak
$id_user_login = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null;

function lelang_masih_berjalan($koneksi, $id_design) {
    $id_design = (int) $id_design;
    $result = mysqli_query($koneksi, "SELECT (waktu_berakhir IS NOT NULL AND waktu_berakhir > NOW()) AS masih_berjalan
                                      FROM t_design
                                      WHERE id_design = '$id_design'
                                      LIMIT 1");
    $data = $result ? mysqli_fetch_assoc($result) : null;
    return !empty($data['masih_berjalan']);
}

// ==========================================================
// LOGIKA PENCARIAN DATA (PRIORITAS 1 -> 2 -> 3)
// ==========================================================

// --- SKENARIO 0: AKSES BELI LANGSUNG (?id_design=...&beli_langsung=1) ---
if (isset($_GET['id_design']) && isset($_GET['beli_langsung'])) {
    $id_design = (int) $_GET['id_design'];
    $asal_transaksi = "beli_langsung";

    $query = "SELECT * FROM t_design WHERE id_design = '$id_design'";
    $result = mysqli_query($koneksi, $query);
    $row = mysqli_fetch_assoc($result);
    if ($row) {
        $harga_barang = $row['harga_beli_langsung'];
        if ($harga_barang <= 0) {
            sweetalert_redirect('Fitur Beli Langsung tidak tersedia untuk karya ini.', 'index.php', 'error', 'Tidak Tersedia');
            exit;
        }
    }

// --- SKENARIO 1: AKSES DARI LINK KHUSUS (?id_bidding=...) ---
} elseif (isset($_GET['id_bidding'])) {
    $id_bidding = $_GET['id_bidding'];
    $asal_transaksi = "lelang";

    $query = "SELECT * FROM t_bidding 
              JOIN t_design ON t_bidding.id_design = t_design.id_design 
              WHERE t_bidding.id_bid = '$id_bidding'";
    
    $result = mysqli_query($koneksi, $query);
    $row = mysqli_fetch_assoc($result);
    if ($row) {
        // Cek apakah pembeli ini PEMIMPIN lelang (sudah memperhitungkan prioritas premium:
        // saat nominal seri, pembeli premium yang dianggap memimpin).
        $id_design = $row['id_design'];
        if (!is_bid_leader($koneksi, $id_design, $row['id_buyer'])) {
            sweetalert_redirect('Penawaran Anda sudah tersusul. Anda tidak dapat melakukan pembayaran.', 'riwayat.php', 'error', 'Tersusul');
            exit;
        }

        // Cek apakah waktu lelang sudah berakhir
        if (lelang_masih_berjalan($koneksi, $id_design)) {
            sweetalert_redirect('Waktu lelang belum berakhir. Checkout hanya bisa dilakukan setelah lelang ditutup.', 'riwayat.php', 'info', 'Lelang Berjalan');
            exit;
        }

        $harga_barang = $row['harga_tawaran'];
    }

// --- SKENARIO 2: AKSES BELI BIASA (?id_design=...) ---
} elseif (isset($_GET['id_design'])) {
    $id_design = (int) $_GET['id_design'];
    $asal_transaksi = "biasa";

    $query = "SELECT * FROM t_design WHERE id_design = '$id_design'";
    $result = mysqli_query($koneksi, $query);
    $row = mysqli_fetch_assoc($result);
    if ($row) $harga_barang = $row['harga_awal'];

// --- SKENARIO 3: AUTO DETECT (Jika Buka Menu "Pembelian" Langsung) ---
} else {
    // Jika user login, kita cari apakah dia punya bidding terakhir?
    if ($id_user_login) {
        // Cari di tabel t_bidding punya si user ini (id_buyer)
        $query_auto = "SELECT * FROM t_bidding 
                       JOIN t_design ON t_bidding.id_design = t_design.id_design 
                       WHERE id_buyer = '$id_user_login' 
                       ORDER BY id_bid DESC LIMIT 1";

        $result_auto = mysqli_query($koneksi, $query_auto);
        $data_auto = mysqli_fetch_assoc($result_auto);

        if ($data_auto) {
            $id_design_cek = $data_auto['id_design'];
            
            // Cek apakah user ini PEMIMPIN lelang (memperhitungkan prioritas premium saat seri)
            if (is_bid_leader($koneksi, $id_design_cek, $id_user_login)) {
                // --- [UPDATE BARU] CEK KE DATABASE TRANSAKSI ---
                // Kita cek: Apakah desain ini sudah pernah dibayar (settlement) atau sedang proses (pending)?
                $cek_transaksi = mysqli_query($koneksi, "SELECT * FROM t_transaksi
                                 WHERE id_buyer = '$id_user_login'
                                 AND id_design = '$id_design_cek'
                                 AND (status_pembayaran = 'berhasil' OR status_pembayaran = 'pending')");
                
                // Jika BELUM ADA di tabel transaksi (artinya belum dibayar), baru kita tampilkan tagihannya
                if(mysqli_num_rows($cek_transaksi) == 0) {
                    $row = $data_auto;
                    $id_bidding = $row['id_bid']; 
                    $harga_barang = $row['harga_tawaran'];
                    $asal_transaksi = "lelang"; 
                    
                    // Cek apakah waktu lelang sudah berakhir
                    if (lelang_masih_berjalan($koneksi, $id_design_cek)) {
                        // Jika belum berakhir, kosongkan row agar tidak muncul di keranjang (karena belum bisa dibayar)
                        $row = [];
                    }
                }
                // Jika sudah ada (num_rows > 0), biarkan $row kosong supaya dianggap tidak ada tagihan
            }
        }
    }

    // Kalau user gak login ATAU gak punya history bidding yang belum dibayar
    if (empty($row)) {
        header('Location: riwayat.php');
        exit;
    }
}

// --- VALIDASI AKHIR ---
if (!$row) {
    sweetalert_redirect('Data tidak ditemukan.', 'index.php', 'error', 'Gagal!');
}

$id_design_checkout = (int) $row['id_design'];
$cek_sudah_terjual = mysqli_prepare($koneksi, "SELECT 1
                         FROM t_transaksi
                         WHERE id_design = ?
                           AND status_pembayaran IN ('berhasil', 'settlement', 'capture')
                         LIMIT 1");
mysqli_stmt_bind_param($cek_sudah_terjual, 'i', $id_design_checkout);
mysqli_stmt_execute($cek_sudah_terjual);
$hasil_sudah_terjual = mysqli_stmt_get_result($cek_sudah_terjual);

if (($row['status'] ?? '') === 'sold' || mysqli_num_rows($hasil_sudah_terjual) > 0) {
    sweetalert_redirect('Karya ini sudah terjual dan tidak bisa dibeli atau dibayar lagi.', 'product.php', 'info', 'Karya Terjual');
}

// --- PERSIAPAN TAMPILAN ---
$folder_gambar = "admin/uploads/"; 
$path_gambar = $folder_gambar . $row['gambar'];
$total_bayar = $harga_barang + $biaya_layanan;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Checkout - <?php echo $row['judul']; ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="icon" type="image/png" href="images/icons/favicon.png"/>
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/iconic/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/linearicons-v1.0.0/icon-font.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/animate/animate.css">   
    <link rel="stylesheet" type="text/css" href="vendor/css-hamburgers/hamburgers.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/select2/select2.min.css">
    <link rel="stylesheet" type="text/css" href="css/util.css">
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <link rel="stylesheet" type="text/css" href="css/account-ui.css?v=<?php echo filemtime(__DIR__ . '/css/account-ui.css'); ?>">

    <style>
        .box-checkout {
            background: #fff;
            border: 1px solid #e6e6e6;
            padding: 30px;
            margin-bottom: 30px;
        }
        .section-header { display: flex; justify-content: space-between; margin-bottom: 25px; }
        .section-title { font-weight: bold; font-size: 24px; color: #333; text-transform: uppercase; }
        
        /* Layout Produk */
        .product-card { display: flex; align-items: flex-start; gap: 20px; }
        .product-img { width: 120px; height: 120px; object-fit: cover; border-radius: 4px; border: 1px solid #eee; }
        .product-info h5 { font-weight: bold; font-size: 18px; color: #333; margin-bottom: 5px; }
        .product-meta { color: #888; font-size: 14px; margin-bottom: 5px; }
        .product-price { margin-left: auto; font-weight: bold; font-size: 18px; color: #333; }

        .btn-reload {
            background: #e6e6e6; color: #333; border: none; padding: 10px 30px;
            border-radius: 25px; font-weight: bold; float: right; margin-top: 20px; cursor: pointer;
        }
        .btn-reload:hover { background: #d0d0d0; }

        /* Ringkasan Biaya */
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; color: #333; }
        .summary-total { border-top: 2px solid #333; padding-top: 15px; margin-top: 15px; font-weight: bold; font-size: 16px; }

        /* Layout Pembayaran */
        .payment-header { border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
        .grand-total { font-size: 32px; font-weight: bold; color: #000; }
        .timer { float: right; color: #007bff; font-weight: bold; }
        
        .payment-logo-row {
            display: flex; align-items: center; flex-wrap: wrap; gap: 18px;
            padding: 6px 2px 8px; pointer-events: none; user-select: none;
        }
        .payment-logo {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 62px; min-height: 34px; line-height: 1;
        }
        .payment-logo-bank { gap: 5px; font-size: 21px; font-weight: 800; font-style: italic; }
        .payment-logo-bank i { font-size: 17px; }
        .payment-logo-bca { color: #0766b2; }
        .payment-logo-bni { color: #f58220; }
        .payment-logo-bni small { color: #006a70; font-size: 9px; font-style: normal; margin-right: 1px; }
        .payment-logo-card i { font-size: 38px; }
        .payment-logo-visa { color: #1434cb; }
        .payment-logo-mastercard { color: #eb001b; }
        .payment-logo-qris {
            color: #111; font-size: 21px; font-weight: 900; letter-spacing: -1.5px;
            border-left: 4px solid #e62129; padding-left: 7px;
        }
        .payment-logo-gopay { gap: 6px; color: #111; font-size: 20px; font-weight: 700; }
        .payment-logo-gopay-mark {
            display: inline-flex; align-items: center; justify-content: center;
            width: 22px; height: 22px; border: 4px solid #00aed6; border-radius: 50%;
            color: #00aed6; font-size: 9px; font-style: normal;
        }

        .btn-pay-black {
            background: #000; color: #fff; width: 100%; padding: 18px;
            border-radius: 30px; font-weight: bold; font-size: 14px;
            text-transform: uppercase; border: none; cursor: pointer; transition: 0.3s;
        }
        .btn-pay-black:hover { background: #333; }

        @media (max-width: 768px) {
            .product-card { flex-direction: column; }
            .product-price { margin-left: 0; margin-top: 10px; }
        }
    </style>
</head>
<body class="animsition account-page customer-checkout">
    
    <?php
    $active_page = 'cart';
    include 'navbar.php';
    ?>


    <div class="container">
        <div class="bread-crumb flex-w p-l-25 p-r-15 p-t-30 p-lr-0-lg">
            <a href="index.php" class="stext-109 cl8 hov-cl1 trans-04">
                Home <i class="fa fa-angle-right m-l-9 m-r-10" aria-hidden="true"></i>
            </a>
            <span class="stext-109 cl4">Checkout</span>
        </div>
    </div>
        

    <form action="proses_bayar.php" method="POST">
        
        <input type="hidden" name="id_design_midtrans" value="<?php echo $row['id_design']; ?>">
        <input type="hidden" name="total_bayar_midtrans" value="<?php echo $total_bayar; ?>">
        <input type="hidden" name="judul_midtrans" value="<?php echo $row['judul']; ?>">

        <input type="hidden" name="tipe_transaksi" value="<?php echo $asal_transaksi; ?>">

        <?php if($asal_transaksi == 'lelang'): ?>
            <input type="hidden" name="id_bidding_midtrans" value="<?php echo isset($id_bidding) ? $id_bidding : ''; ?>">
        <?php endif; ?>

        <div class="container">
            <div class="row">
                
                <div class="col-lg-7 m-b-50">
                    <div class="box-checkout">
                        <div class="section-header">
                            <span class="section-title">KARYA</span>
                            <span class="section-title">HARGA</span>
                        </div>

                        <div class="product-card">
                            <img src="<?php echo $path_gambar; ?>" alt="IMG" class="product-img" onerror="this.src='images/item-cart-04.jpg'">
                            
                            <div class="product-info">
                                <h5><?php echo $row['judul']; ?></h5>
                                <div class="product-meta">Desainer ID: #<?php echo $row['id_designer']; ?></div>
                                <div class="product-meta">
                                    Kategori: <?php echo ucfirst($row['kategori']); ?>
                                </div>
                                
                                <?php if($asal_transaksi == 'lelang'): ?>
                                    <div class="product-meta" style="color: #009900; font-weight: bold; margin-top:5px;">
                                        <i class="fa fa-check-circle"></i> Pemenang Lelang
                                    </div>
                                    <div style="font-size:10px; color:#aaa;">ID Bid: #<?php echo $id_bidding; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-price">
                                Rp <?php echo number_format($harga_barang, 0, ',', '.'); ?>
                            </div>
                        </div>

                        <?php if($asal_transaksi == 'biasa'): ?>
                            <div style="clear: both; overflow: hidden;">
                                <button type="button" class="btn-reload" onclick="location.reload()">MUAT ULANG</button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="box-checkout">
                        <h4 class="mtext-109 cl2 p-b-20">TOTAL PEMBELIAN</h4>
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>RP <?php echo number_format($harga_barang, 0, ',', '.'); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Biaya Layanan</span>
                            <span>Rp <?php echo number_format($biaya_layanan, 0, ',', '.'); ?></span>
                        </div>
                        <div class="summary-row summary-total">
                            <span>Total</span>
                            <span>RP <?php echo number_format($total_bayar, 0, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 m-b-50">
                    <div class="box-checkout">
                        <div class="payment-header">
                            <span class="stext-110 cl2">Total</span>
                            <span class="timer">Bayar dalam <span id="countdown">00:15:00</span></span>
                            
                            <div class="grand-total p-t-10">
                                Rp <?php echo number_format($total_bayar, 0, ',', '.'); ?>
                            </div>
                            <div class="stext-111 cl6 p-t-5">
                                Order Id #<?php echo rand(100000, 999999); ?>
                            </div>
                        </div>

                        <div class="p-t-10">
                            <h5 class="mtext-108 cl2 p-b-20">Metode Pembayaran</h5>
                            
                            <div class="payment-group m-b-20">
                                <span class="stext-110 cl2 d-block m-b-10">Transfer bank</span>
                                <div class="payment-logo-row" aria-label="Tersedia BCA dan BNI">
                                    <span class="payment-logo payment-logo-bank payment-logo-bca" title="BCA"><i class="fa fa-bank"></i>BCA</span>
                                    <span class="payment-logo payment-logo-bank payment-logo-bni" title="BNI"><small>46</small>BNI</span>
                                </div>
                            </div>

                            <div class="payment-group m-b-20">
                                <span class="stext-110 cl2 d-block m-b-10">Kartu kredit/ debit</span>
                                <div class="payment-logo-row" aria-label="Tersedia Visa dan Mastercard">
                                    <span class="payment-logo payment-logo-card payment-logo-visa" title="Visa"><i class="fa fa-cc-visa"></i></span>
                                    <span class="payment-logo payment-logo-card payment-logo-mastercard" title="Mastercard"><i class="fa fa-cc-mastercard"></i></span>
                                </div>
                            </div>

                            <div class="payment-group m-b-20">
                                <span class="stext-110 cl2 d-block m-b-10">Gopay/ QRIS</span>
                                <div class="payment-logo-row" aria-label="Tersedia QRIS dan GoPay">
                                    <span class="payment-logo payment-logo-qris" title="QRIS">QRIS</span>
                                    <span class="payment-logo payment-logo-gopay" title="GoPay"><i class="payment-logo-gopay-mark">●</i>gopay</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-pay-black">
                        PILIH METODE PEMBAYARAN
                    </button>
                </div>
            </div>
        </div>

    </form>

    <footer class="bg3 p-t-75 p-b-32">
        <div class="container">
            <div class="row">
                <div class="col-sm-6 col-lg-3 p-b-50">
                    <h4 class="stext-301 cl0 p-b-30">Categories</h4>
                    <ul>
                        <li class="p-b-10"><a href="#" class="stext-107 cl7 hov-cl1 trans-04">Women</a></li>
                        <li class="p-b-10"><a href="#" class="stext-107 cl7 hov-cl1 trans-04">Men</a></li>
                        <li class="p-b-10"><a href="#" class="stext-107 cl7 hov-cl1 trans-04">Shoes</a></li>
                    </ul>
                </div>

                <div class="col-sm-6 col-lg-3 p-b-50">
                    <h4 class="stext-301 cl0 p-b-30">Help</h4>
                    <ul>
                        <li class="p-b-10"><a href="#" class="stext-107 cl7 hov-cl1 trans-04">Track Order</a></li>
                        <li class="p-b-10"><a href="#" class="stext-107 cl7 hov-cl1 trans-04">Returns</a></li>
                        <li class="p-b-10"><a href="#" class="stext-107 cl7 hov-cl1 trans-04">Shipping</a></li>
                    </ul>
                </div>

                <div class="col-sm-6 col-lg-3 p-b-50">
                    <h4 class="stext-301 cl0 p-b-30">GET IN TOUCH</h4>
                    <p class="stext-107 cl7 size-201">
                        Any questions? Let us know in store at 8th floor, 379 Hudson St, New York, NY 10018 or call us on (+1) 96 716 6879
                    </p>
                    <div class="p-t-27">
                        <a href="#" class="fs-18 cl7 hov-cl1 trans-04 m-r-16"><i class="fa fa-facebook"></i></a>
                        <a href="#" class="fs-18 cl7 hov-cl1 trans-04 m-r-16"><i class="fa fa-instagram"></i></a>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3 p-b-50">
                    <h4 class="stext-301 cl0 p-b-30">Newsletter</h4>
                    <form>
                        <div class="wrap-input1 w-full p-b-4">
                            <input class="input1 bg-none plh1 stext-107 cl7" type="text" name="email" placeholder="email@example.com">
                            <div class="focus-input1 trans-04"></div>
                        </div>
                        <div class="p-t-18">
                            <button class="flex-c-m stext-101 cl0 size-103 bg1 bor1 hov-btn2 p-lr-15 trans-04">Subscribe</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="p-t-40">
                <p class="stext-107 cl6 txt-center">
Copyright &copy;<script>document.write(new Date().getFullYear());</script> All rights reserved | Made with <i class="fa fa-heart-o" aria-hidden="true"></i> by <a href="https://colorlib.com" target="_blank">Colorlib</a>
                </p>
            </div>
        </div>
    </footer>


    <div class="btn-back-to-top" id="myBtn">
        <span class="symbol-btn-back-to-top"><i class="zmdi zmdi-chevron-up"></i></span>
    </div>

    <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
    <script src="vendor/animsition/js/animsition.min.js"></script>
    <script src="vendor/bootstrap/js/popper.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="vendor/select2/select2.min.js"></script>
    <script>
        $(".js-select2").each(function(){
            $(this).select2({ minimumResultsForSearch: 20, dropdownParent: $(this).next('.dropDownSelect2') });
        })
    </script>
    <script src="vendor/MagnificPopup/jquery.magnific-popup.min.js"></script>
    <script src="vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script>
        $('.js-pscroll').each(function(){
            $(this).css('position','relative');
            $(this).css('overflow','hidden');
            var ps = new PerfectScrollbar(this, { wheelSpeed: 1, scrollingThreshold: 1000, wheelPropagation: false, });
            $(window).on('resize', function(){ ps.update(); })
        });
    </script>
    <script src="js/main.js"></script>

    <script>
        // 1. Timer
        var timerDuration = 15 * 60; 
        var display = document.querySelector('#countdown');
        function startTimer(duration, display) {
            var timer = duration, minutes, seconds;
            setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);
                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;
                if(display) display.textContent = "00:" + minutes + ":" + seconds;
                if (--timer < 0) timer = 0;
            }, 1000);
        }
        startTimer(timerDuration, display);

    </script>

</body>
</html>
