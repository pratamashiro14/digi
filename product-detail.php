<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';

// --- 1. LOGIKA HEADER (DARI INDEX.PHP) ---

// Cek Login Desainer
$is_designer_logged_in = isset($_SESSION['status_designer']) && $_SESSION['status_designer'] == "login";
$nama_desainer = isset($_SESSION['nama_desainer']) ? $_SESSION['nama_desainer'] : 'Desainer';

// Cek Login User Biasa
$is_user_logged_in = isset($_SESSION['status']) && $_SESSION['status'] == "login";
$nama_user = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'User';

// Hitung Keranjang
$jumlah_item_keranjang = 0;
if(isset($_SESSION['keranjang'])) {
    foreach($_SESSION['keranjang'] as $jml) {
        $jumlah_item_keranjang += $jml;
    }
}

// --- 2. LOGIKA DETAIL PRODUK (DARI FILE LAMA) ---

// Ambil ID dari URL
if(!isset($_GET['id']) || empty($_GET['id'])){
    sweetalert_redirect('Produk tidak ditemukan.', 'index.php', 'error', 'Gagal!');
}
$id_produk = (int) $_GET['id'];

// Ambil Data Produk
$query = mysqli_query($koneksi, "SELECT d.*, u.nama as nama_desainer, u.id_user as id_pemilik 
                                 FROM t_design d 
                                 JOIN t_user u ON d.id_designer = u.id_user 
                                 WHERE d.id_design='$id_produk'");
$d = mysqli_fetch_assoc($query);

if(!$d){
    sweetalert_redirect('Produk tidak ditemukan.', 'index.php', 'error', 'Gagal!');
}

// Ambil Riwayat Bidding
$q_history = mysqli_query($koneksi, "SELECT b.*, u.nama FROM t_bidding b 
                                     JOIN t_user u ON b.id_buyer = u.id_user 
                                     WHERE b.id_design='$id_produk' 
                                     ORDER BY b.harga_tawaran DESC LIMIT 5");

$q_max_bid = mysqli_query($koneksi, "SELECT MAX(harga_tawaran) as max_bid FROM t_bidding WHERE id_design='$id_produk'");
$d_max_bid = mysqli_fetch_assoc($q_max_bid);
$highest_bid_detail = ($d_max_bid && $d_max_bid['max_bid']) ? $d_max_bid['max_bid'] : $d['harga_awal'];

// Cek Status Verifikasi User
$status_verifikasi = 'unverified'; // Default
if(isset($_SESSION['id_user'])){
    $id_user_login = (int) $_SESSION['id_user'];
    $cek_user = mysqli_query($koneksi, "SELECT status_verifikasi FROM t_user WHERE id_user='$id_user_login'");
    $u = mysqli_fetch_assoc($cek_user);
    $status_verifikasi = $u['status_verifikasi'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title><?php echo $d['judul']; ?> - Detail Lelang</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="icon" type="image/png" href="images/icons/favicon.png"/>
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/iconic/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/linearicons-v1.0.0/icon-font.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/animate/animate.css">
    <link rel="stylesheet" type="text/css" href="vendor/css-hamburgers/hamburgers.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/animsition/css/animsition.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/select2/select2.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/perfect-scrollbar/perfect-scrollbar.css">
    <link rel="stylesheet" type="text/css" href="css/util.css">
    <link rel="stylesheet" type="text/css" href="css/main.css">

    <style>
        .product-img { width: 100%; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .btn-chat-desainer { display: inline-flex; align-items: center; justify-content: center; background-color: #25D366; color: white; padding: 10px 20px; border-radius: 5px; font-size: 14px; font-weight: 600; text-decoration: none; margin-bottom: 20px; transition: 0.3s; }
        .btn-chat-desainer:hover { background-color: #128C7E; color: white; text-decoration: none; }
        .timer-box { background: linear-gradient(45deg, #d90429, #ef233c); color: white; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; font-weight: 700; font-size: 18px; box-shadow: 0 4px 10px rgba(217, 4, 41, 0.3); }
        .price-tag { font-size: 28px; font-weight: 800; color: #333; display: block; margin-bottom: 5px; }
        .desainer-tag { font-size: 14px; color: #888; display: block; margin-bottom: 15px; }
        .bid-control { display: flex; align-items: center; margin-bottom: 20px; }
        .btn-bid-qty { width: 45px; height: 45px; border: 1px solid #ddd; background: #fff; font-size: 20px; color: #555; cursor: pointer; border-radius: 5px; transition: 0.2s; }
        .input-bid-val { height: 45px; border: 1px solid #ddd; text-align: center; font-weight: 700; width: 100%; margin: 0 10px; border-radius: 5px; background: #fff; font-size: 18px; color: #333; }
        .history-box { background: #f9f9f9; padding: 20px; border-radius: 10px; margin-top: 30px; border: 1px solid #eee; }
        .history-title { font-weight: 700; margin-bottom: 15px; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        .bid-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; font-size: 14px; }
        .bid-item:last-child { border-bottom: none; }
        
        .btn-verif { width: 100%; padding: 15px; border-radius: 50px; font-weight: 700; font-size: 16px; text-transform: uppercase; background: #f39c12; color: #fff; border: none; cursor: pointer; transition: 0.3s; display:block; text-align:center; text-decoration:none;}
        .btn-verif:hover { background: #e67e22; color: #fff; }
        
        .btn-lelang { width: 100%; padding: 15px; border-radius: 50px; font-weight: 700; font-size: 16px; text-transform: uppercase; background: linear-gradient(90deg, #4e8eff, #6b4eff); color: #fff; border: none; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 15px rgba(78, 142, 255, 0.4); }
        .btn-lelang:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(78, 142, 255, 0.6); }

        /* TABS LOGIN (DARI INDEX) */
        .auth-tab { font-family: 'Poppins', sans-serif; font-weight: 600; cursor: pointer; font-size: 18px; padding-bottom: 5px; color: #aaa; border-bottom: 2px solid transparent; }
        .auth-tab.active { color: #4e60ff; border-bottom: 2px solid #4e60ff; }
        .form-control-custom { border-radius: 8px; border: 1px solid #ddd; padding: 12px; font-size: 14px; }
    </style>
</head>
<body class="animsition">

    <?php
    $active_page = 'product';
    include 'navbar.php';
    ?>

    <div class="wrap-header-cart js-panel-cart">
        <div class="s-full js-hide-cart"></div>
        <div class="header-cart flex-col-l p-l-65 p-r-25">
            <div class="header-cart-title flex-w flex-sb-m p-b-8">
                <span class="mtext-103 cl2">Keranjang</span>
                <div class="fs-35 lh-10 cl2 p-lr-5 pointer hov-cl1 trans-04 js-hide-cart"><i class="zmdi zmdi-close"></i></div>
            </div>
            <div class="header-cart-content flex-w js-pscroll">
                <ul class="header-cart-wrapitem w-full">
                    <?php 
                    $total_harga_sidebar = 0;
                    if(isset($_SESSION['keranjang']) && !empty($_SESSION['keranjang'])) {
                        foreach($_SESSION['keranjang'] as $id_produk_cart => $qty) {
                            $sql_cart = "SELECT * FROM t_design WHERE id_design = '$id_produk_cart'";
                            $res_cart = mysqli_query($koneksi, $sql_cart);
                            $d_cart = mysqli_fetch_assoc($res_cart);
                            if($d_cart) {
                                $subtotal = $d_cart['harga_awal'] * $qty;
                                $total_harga_sidebar += $subtotal;
                    ?>
                    <li class="header-cart-item flex-w flex-t m-b-12">
                        <div class="header-cart-item-img"><img src="admin/uploads/<?php echo $d_cart['gambar']; ?>" alt="IMG" style="height:100%; object-fit:cover;"></div>
                        <div class="header-cart-item-txt p-t-8">
                            <a href="#" class="header-cart-item-name m-b-18 hov-cl1 trans-04"><?php echo $d_cart['judul']; ?></a>
                            <span class="header-cart-item-info"><?php echo $qty; ?> x Rp <?php echo number_format($d_cart['harga_awal'],0,',','.'); ?></span>
                        </div>
                    </li>
                    <?php } } } else { echo "<li class='header-cart-item m-b-12'><div class='header-cart-item-txt p-t-8'>Keranjang masih kosong.</div></li>"; } ?>
                </ul>
                <div class="w-full">
                    <div class="header-cart-total w-full p-tb-40">Total: Rp <?php echo number_format($total_harga_sidebar,0,',','.'); ?></div>
                    <div class="header-cart-buttons flex-w w-full">
                        <a href="shoping-cart.php" class="flex-c-m stext-101 cl0 size-107 bg3 bor2 hov-btn3 p-lr-15 trans-04 m-b-10">Check Out</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="bread-crumb flex-w p-l-25 p-r-15 p-t-30 p-lr-0-lg">
            <a href="index.php" class="stext-109 cl8 hov-cl1 trans-04">Beranda <i class="fa fa-angle-right m-l-9 m-r-10"></i></a>
            <span class="stext-109 cl4"><?php echo $d['judul']; ?></span>
        </div>
    </div>

    <section class="sec-product-detail bg0 p-t-30 p-b-60">
        <div class="container">
            <div class="row">
                <div class="col-md-6 col-lg-7 p-b-30">
                    <div class="p-l-25 p-r-30 p-lr-0-lg">
                        <div class="wrap-pic-w pos-relative">
                            <img src="admin/uploads/<?php echo $d['gambar']; ?>" alt="IMG-PRODUCT" class="product-img">
                        </div>
                    </div>
                </div>
                    
                <div class="col-md-6 col-lg-5 p-b-30">
                    <div class="p-r-50 p-t-5 p-lr-0-lg">
                        
                        <h4 class="mtext-105 cl2 js-name-detail p-b-5"><?php echo $d['judul']; ?></h4>
                        <span class="desainer-tag">Karya oleh:
                            <a href="toko_desainer.php?id=<?php echo $d['id_pemilik']; ?>" style="color:#1591DC; text-decoration:underline;">
                                <b><?php echo $d['nama_desainer']; ?></b>
                            </a>
                        </span>
                        <span class="mtext-106 cl2 price-tag">Open Bid: Rp <?php echo number_format($d['harga_awal'],0,',','.'); ?></span>
                        <a href="detail_chat.php?tujuan=<?php echo $d['id_pemilik']; ?>"class="btn-chat-desainer"><i class="fa fa-comments"></i> Hubungi Desainer</a>
                        <p class="stext-102 cl3 p-t-15"><?php echo $d['deskripsi']; ?></p>

                        <div class="p-t-33">
                            <div class="timer-box"><span id="timer-detail"><i class="fa fa-clock-o"></i> Loading Waktu...</span></div>

                            <?php if(current_role() === 'designer') { ?>

                                <div class="alert alert-info text-center" style="font-size:13px;">
                                    Kamu sedang masuk sebagai <b>Desainer</b>. Penawaran hanya tersedia untuk pembeli.
                                </div>
                                <a href="unggahan.php" class="btn-verif" style="background:#2563eb;"><i class="fa fa-cloud-upload"></i> KELOLA UNGGAHAN</a>

                            <?php } elseif(current_role() === 'admin') { ?>

                                <div class="alert alert-secondary text-center" style="font-size:13px;">
                                    Kamu sedang masuk sebagai <b>Admin</b>. Gunakan dashboard admin untuk mengelola data platform.
                                </div>
                                <a href="admin/beranda.php" class="btn-verif" style="background:#171717;"><i class="fa fa-dashboard"></i> BUKA DASHBOARD ADMIN</a>

                            <?php } elseif(current_role() === 'guest') { ?>

                                <div class="alert alert-warning text-center" style="font-size:13px;">
                                    Masuk sebagai pembeli untuk ikut lelang dan mengajukan penawaran.
                                </div>
                                <a href="login.php" class="btn-verif"><i class="fa fa-sign-in"></i> MASUK SEBAGAI PEMBELI</a>

                            <?php } elseif($status_verifikasi == 'verified') { ?>
                                
                                <form action="proses_bidding.php" method="POST">
                                    <input type="hidden" name="id_design" value="<?php echo $id_produk; ?>">
                                    <div class="p-b-10">
                                        <label class="stext-102 cl3" style="font-weight:600;">Tawaran Anda:</label>
                                        <div class="bid-control">
                                            <button type="button" class="btn-bid-qty" onclick="changeBid(-10000)">-</button>
                                            <input type="text" name="harga_tawaran" id="inputBid" class="input-bid-val" value="Rp <?php echo number_format($highest_bid_detail+10000,0,',','.'); ?>" readonly>
                                            <button type="button" class="btn-bid-qty" onclick="changeBid(10000)">+</button>
                                        </div>
                                        <input type="hidden" name="angka_tawaran_asli" id="inputBidAsli" value="<?php echo $highest_bid_detail+10000; ?>">
                                    </div>
                                    <button type="submit" class="btn-lelang">AJUKAN TAWARAN</button>
                                </form>
                                <?php if(isset($d['harga_beli_langsung']) && $d['harga_beli_langsung'] > 0) { ?>
                                    <hr style="margin: 20px 0;">
                                    <a href="shoping-cart.php?id_design=<?php echo $id_produk; ?>&beli_langsung=1" class="btn-verif" style="background: linear-gradient(90deg, #2ecc71, #27ae60); box-shadow: 0 4px 15px rgba(46, 204, 113, 0.4); text-align: center; border: none; font-size: 14px; padding: 12px;">
                                        <i class="fa fa-flash"></i> BELI LANGSUNG RP <?php echo number_format($d['harga_beli_langsung'],0,',','.'); ?>
                                    </a>
                                <?php } ?>

                            <?php } else if($status_verifikasi == 'pending') { ?>

                                <div class="alert alert-info text-center">
                                    <i class="fa fa-spinner fa-spin"></i> Verifikasi KTP sedang dicek Admin.<br>Mohon tunggu sebentar ya!
                                </div>

                            <?php } else { ?>

                                <div class="alert alert-warning text-center" style="font-size:13px;">
                                    Kamu harus <b>Verifikasi KTP</b> untuk ikut lelang.
                                </div>
                                <a href="verifikasi.php" class="btn-verif"><i class="fa fa-id-card"></i> VERIFIKASI AKUN SEKARANG</a>

                            <?php } ?>

                        </div>

                        <div class="history-box">
                            <div class="history-title">Riwayat Penawaran Tertinggi</div>
                            <?php if(mysqli_num_rows($q_history) > 0) { ?>
                                <?php $rank=1; while($h = mysqli_fetch_assoc($q_history)){ ?>
                                    <div class="bid-item">
                                        <div class="bid-user">
                                            <i class="fa fa-user-circle"></i> <?php echo $h['nama']; ?>
                                            <?php if($rank==1) echo ' <span class="badge badge-warning" style="margin-left:5px;">Highest</span>'; ?>
                                        </div>
                                        <div class="bid-amount">Rp <?php echo number_format($h['harga_tawaran'],0,',','.'); ?></div>
                                    </div>
                                <?php $rank++; } ?>
                            <?php } else { ?>
                                <p style="text-align:center; color:#999;">Belum ada penawaran.</p>
                            <?php } ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg3 p-t-75 p-b-32">
        <div class="container">
            <div class="row">
                <div class="col-sm-6 col-lg-3 p-b-50">
                    <h4 class="stext-301 cl0 p-b-30">Kategori</h4>
                    <ul>
                        <li class="p-b-10"><a href="#" class="stext-107 cl7 hov-cl1 trans-04">Ilustrasi</a></li>
                        <li class="p-b-10"><a href="#" class="stext-107 cl7 hov-cl1 trans-04">Tipografi</a></li>
                    </ul>
                </div>
                <div class="col-sm-6 col-lg-3 p-b-50">
                    <h4 class="stext-301 cl0 p-b-30">Bantuan</h4>
                    <ul>
                        <li class="p-b-10"><a href="#" class="stext-107 cl7 hov-cl1 trans-04">Bantuan</a></li>
                    </ul>
                </div>
                <div class="col-sm-6 col-lg-3 p-b-50">
                    <h4 class="stext-301 cl0 p-b-30">Alamat</h4>
                    <p class="stext-107 cl7 size-201">
                        Jl. Sariasih No.54, Sarijadi, Bandung
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <div class="btn-back-to-top" id="myBtn"><span class="symbol-btn-back-to-top"><i class="zmdi zmdi-chevron-up"></i></span></div>

    <div class="modal fade" id="loginModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 99999;">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius: 15px; border: none;">
                <div class="modal-body p-4 p-md-5">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="position: absolute; right: 20px; top: 15px;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <div class="d-flex justify-content-center mb-4">
                        <div class="auth-tab active mx-3" id="tabMasukBtn" onclick="switchAuth('masuk')">Masuk</div>
                        <div class="auth-tab mx-3" id="tabDaftarBtn" onclick="switchAuth('daftar')">Daftar</div>
                    </div>
                    <div id="formMasuk">
                        <form action="proses_login.php" method="POST">
                            <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control form-control-custom" placeholder="Email" required></div>
                            <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control form-control-custom" placeholder="Password" required></div>
                            <button type="submit" class="btn btn-primary btn-block" style="border-radius:50px; background: linear-gradient(90deg, #4e8eff, #6b4eff); border:none; padding:12px; font-weight:600;">LOGIN</button>
                        </form>
                    </div>
                    <div id="formDaftar" style="display:none;">
                        <form action="proses_daftar.php" method="POST">
                            <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama" class="form-control form-control-custom" placeholder="Nama Lengkap" required></div>
                            <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control form-control-custom" placeholder="Email" required></div>
                            <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control form-control-custom" placeholder="Password" required></div>
                            <button type="submit" class="btn btn-primary btn-block" style="border-radius:50px; background: linear-gradient(90deg, #4e8eff, #6b4eff); border:none; padding:12px; font-weight:600;">DAFTAR</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="designerModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 99999;">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius: 15px; border: none;">
                <div class="modal-body p-4 p-md-5">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="position: absolute; right: 20px; top: 15px;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    
                    <h5 class="text-center mb-2" style="font-weight:700;">Area Desainer</h5>
                    <div class="d-flex justify-content-center mb-4">
                        <div class="auth-tab active mx-3" id="tabMasukDesainerBtn" onclick="switchAuthDesigner('masuk')">Masuk</div>
                        <div class="auth-tab mx-3" id="tabDaftarDesainerBtn" onclick="switchAuthDesigner('daftar')">Daftar</div>
                    </div>

                    <div id="formMasukDesainer">
                        <form action="proses_login_designer.php" method="POST">
                            <div class="form-group"><label>Email Desainer</label><input type="email" name="email" class="form-control form-control-custom" placeholder="Email Desainer" required></div>
                            <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control form-control-custom" placeholder="Password" required></div>
                            <button type="submit" class="btn btn-primary btn-block" style="border-radius:50px; background: linear-gradient(90deg, #4e8eff, #6b4eff); border:none; padding:12px; font-weight:600;">LOGIN DESAINER</button>
                        </form>
                    </div>

                    <div id="formDaftarDesainer" style="display:none;">
                        <form action="proses_daftar_desainer.php" method="POST">
                            <div class="form-group"><label>Nama Desainer</label><input type="text" name="nama" class="form-control form-control-custom" placeholder="Nama Lengkap" required></div>
                            <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control form-control-custom" placeholder="Email" required></div>
                            <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control form-control-custom" placeholder="Password" required></div>
                            <button type="submit" class="btn btn-primary btn-block" style="border-radius:50px; background: linear-gradient(90deg, #4e8eff, #6b4eff); border:none; padding:12px; font-weight:600;">DAFTAR DESAINER</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
    <script src="vendor/animsition/js/animsition.min.js"></script>
    <script src="vendor/bootstrap/js/popper.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="vendor/select2/select2.min.js"></script>
    <script src="vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="js/main.js"></script>

    <script>
        // LOGIKA TIMER
        var waktuBerakhir = "<?php echo $d['waktu_berakhir']; ?>"; 
        if(waktuBerakhir){
            var countDownDate = new Date(waktuBerakhir.replace(" ", "T")).getTime();
            var x = setInterval(function() {
                var now = new Date().getTime();
                var distance = countDownDate - now;

                if (distance > 0) {
                    var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    document.getElementById("timer-detail").innerHTML = "<i class='fa fa-clock-o'></i> Sisa: " + days + "h " + hours + "j " + minutes + "m " + seconds + "d";
                } else {
                    clearInterval(x);
                    document.getElementById("timer-detail").innerHTML = "LELANG BERAKHIR";
                    var btnLelang = document.querySelector('.btn-lelang');
                    if(btnLelang) { btnLelang.style.display = 'none'; }
                }
            }, 1000);
        } else {
            document.getElementById("timer-detail").innerHTML = "Waktu Tidak Terbatas";
        }

        // LOGIKA INPUT BID
        var currentBid = <?php echo $highest_bid_detail + 10000; ?>;
        function changeBid(amount) {
            var newBid = currentBid + amount;
            if(newBid > <?php echo $highest_bid_detail; ?>) {
                currentBid = newBid;
                updateView();
            }
        }
        function updateView() {
            var formatted = "Rp " + new Intl.NumberFormat('id-ID').format(currentBid);
            var inputBid = document.getElementById('inputBid');
            if(inputBid) inputBid.value = formatted;
            var inputBidAsli = document.getElementById('inputBidAsli');
            if(inputBidAsli) inputBidAsli.value = currentBid; 
        }

        // FUNGSI GANTI TAB LOGIN/DAFTAR (UNTUK MODAL)
        function switchAuth(mode) {
            if(mode == 'masuk') {
                $('#formMasuk').show(); $('#formDaftar').hide();
                $('#tabMasukBtn').addClass('active'); $('#tabDaftarBtn').removeClass('active');
            } else {
                $('#formMasuk').hide(); $('#formDaftar').show();
                $('#tabMasukBtn').removeClass('active'); $('#tabDaftarBtn').addClass('active');
            }
        }
        function switchAuthDesigner(mode) {
            if(mode == 'masuk') {
                $('#formMasukDesainer').show(); $('#formDaftarDesainer').hide();
                $('#tabMasukDesainerBtn').addClass('active'); $('#tabDaftarDesainerBtn').removeClass('active');
            } else {
                $('#formMasukDesainer').hide(); $('#formDaftarDesainer').show();
                $('#tabMasukDesainerBtn').removeClass('active'); $('#tabDaftarDesainerBtn').addClass('active');
            }
        }
    </script>
</body>
</html>
