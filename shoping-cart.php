<?php
// ==========================================
// 1. BAGIAN PHP (BACKEND) - AUTO DETECT PEMENANG
// ==========================================
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php'; 

if (is_designer_login()) {
    redirect_with_alert('Halaman pembelian khusus pembeli. Anda sedang login sebagai Desainer.', 'profil_desainer.php');
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

// ==========================================================
// LOGIKA PENCARIAN DATA (PRIORITAS 1 -> 2 -> 3)
// ==========================================================

// --- SKENARIO 1: AKSES DARI LINK KHUSUS (?id_bidding=...) ---
if (isset($_GET['id_bidding'])) {
    $id_bidding = $_GET['id_bidding'];
    $asal_transaksi = "lelang";

    $query = "SELECT * FROM t_bidding 
              JOIN t_design ON t_bidding.id_design = t_design.id_design 
              WHERE t_bidding.id_bid = '$id_bidding'";
    
    $result = mysqli_query($koneksi, $query);
    $row = mysqli_fetch_assoc($result);
    if ($row) $harga_barang = $row['harga_tawaran'];

// --- SKENARIO 2: AKSES BELI BIASA (?id_design=...) ---
} elseif (isset($_GET['id_design'])) {
    $id_design = $_GET['id_design'];
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
            // --- [UPDATE BARU] CEK KE DATABASE TRANSAKSI ---
            // Kita cek: Apakah desain ini sudah pernah dibayar (settlement) atau sedang proses (pending)?
            $id_design_cek = $data_auto['id_design'];
            
            $cek_transaksi = mysqli_query($koneksi, "SELECT * FROM t_transaksi 
                             WHERE id_buyer = '$id_user_login' 
                             AND id_design = '$id_design_cek'
                             AND (status_pembayaran = 'settlement' OR status_pembayaran = 'pending')");
            
            // Jika BELUM ADA di tabel transaksi (artinya belum dibayar), baru kita tampilkan tagihannya
            if(mysqli_num_rows($cek_transaksi) == 0) {
                $row = $data_auto;
                $id_bidding = $row['id_bid']; 
                $harga_barang = $row['harga_tawaran'];
                $asal_transaksi = "lelang"; 
            }
            // Jika sudah ada (num_rows > 0), biarkan $row kosong supaya dianggap tidak ada tagihan
        }
    }

    // Kalau user gak login ATAU gak punya history bidding yang belum dibayar
    if (empty($row)) {
       // KITA UBAH SEDIKIT: Jangan ambil data dummy, tapi lempar ke halaman riwayat atau tampilkan pesan
       sweetalert_redirect('Keranjang belanja kosong atau semua tagihan sudah dibayar.', 'riwayat.php', 'info', 'Informasi');
    }
}

// --- VALIDASI AKHIR ---
if (!$row) {
    sweetalert_redirect('Data tidak ditemukan.', 'index.php', 'error', 'Gagal!');
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
    <link rel="stylesheet" type="text/css" href="css/account-ui.css">

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
        
        .payment-option {
            border: 1px solid #ddd; border-radius: 5px; padding: 10px 15px;
            margin-right: 10px; display: inline-flex; align-items: center;
            cursor: pointer; transition: 0.2s; margin-bottom: 10px;
        }
        .payment-option:hover { border-color: #333; }
        .payment-option input[type="radio"] { display: none; } /* Sembunyikan radio asli */
        .payment-option.selected { border: 2px solid #000; background: #f9f9f9; } /* Style saat dipilih */
        .payment-option img { height: 20px; width: auto; }

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
                                <label class="payment-option" onclick="selectPayment(this)">
                                    <input type="radio" name="payment_method" value="bca">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/5/5c/Bank_Central_Asia.svg" alt="BCA">
                                </label>
                                <label class="payment-option" onclick="selectPayment(this)">
                                    <input type="radio" name="payment_method" value="bni">
                                    <img src="https://upload.wikimedia.org/wikipedia/id/5/55/BNI_logo.svg" alt="BNI">
                                </label>
                            </div>

                            <div class="payment-group m-b-20">
                                <span class="stext-110 cl2 d-block m-b-10">Kartu kredit/ debit</span>
                                <label class="payment-option" onclick="selectPayment(this)">
                                    <input type="radio" name="payment_method" value="visa">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" alt="VISA">
                                </label>
                                <label class="payment-option" onclick="selectPayment(this)">
                                    <input type="radio" name="payment_method" value="mastercard">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="Mastercard">
                                </label>
                            </div>

                            <div class="payment-group m-b-20">
                                <span class="stext-110 cl2 d-block m-b-10">Gopay/ QRIS</span>
                                <label class="payment-option" onclick="selectPayment(this)">
                                    <input type="radio" name="payment_method" value="qris">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/Qris_logo.svg/1200px-Qris_logo.svg.png" alt="QRIS" style="height: 15px;">
                                </label>
                                <label class="payment-option" onclick="selectPayment(this)">
                                    <input type="radio" name="payment_method" value="gopay">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/8/86/Gopay_logo.svg" alt="GoPay">
                                </label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-pay-black">
                        PILIH PEMBAYARAN
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

        // 2. Select Payment Styling
        function selectPayment(element) {
            var options = document.querySelectorAll('.payment-option');
            options.forEach(function(opt) {
                opt.classList.remove('selected');
                opt.style.borderColor = '#ddd';
            });
            element.classList.add('selected');
            element.style.borderColor = '#000';
            element.querySelector('input').checked = true;
        }
    </script>

</body>
</html>
