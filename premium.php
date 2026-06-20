<?php
require_once __DIR__ . '/auth.php';
// Hubungkan ke database
include 'admin/koneksi.php'; 

// Cek Login
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

// Status premium user yang sedang login
$sudah_premium  = is_login() && user_is_premium(current_id());
$premium_sampai = '';
if ($sudah_premium) {
    $uid = (int) current_id();
    $rp = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT MAX(tanggal_berakhir) AS tgl FROM t_premium
         WHERE id_user='$uid' AND status='aktif' AND tanggal_berakhir>=CURDATE()"));
    $premium_sampai = $rp['tgl'] ?? '';
}

function tgl_id_premium($tgl) {
    if (!$tgl) return '';
    $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];
    $ts = strtotime($tgl);
    if (!$ts) return $tgl;
    return date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Premium Membership</title>
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
        .premium-section { padding: 60px 0; background-color: #fff; }
        .premium-card {
            border: 1px solid #d1d1d1; padding: 40px; margin-bottom: 30px;
            display: flex; justify-content: space-between; align-items: center;
            background: #fff; border-radius: 4px; transition: 0.3s;
        }
        .premium-card:hover { box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .premium-title {
            font-size: 34px; font-weight: 800; margin-bottom: 20px;
            background: -webkit-linear-gradient(45deg, #3498db, #8e44ad);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            font-family: Poppins-Bold, sans-serif;
        }
        .feature-list { list-style: none; padding: 0; margin: 0; }
        .feature-list li {
            font-size: 15px; color: #333; font-weight: 600; margin-bottom: 10px;
            display: flex; align-items: center;
        }
        .feature-list li::before { content: ">"; font-weight: 900; margin-right: 12px; font-size: 14px; color: #333; }
        .card-right { text-align: center; min-width: 220px; }
        .price-tag { font-size: 26px; font-weight: bold; color: #000; margin-bottom: 15px; }
        .price-per { font-size: 14px; color: #888; font-weight: normal; }
        
        .btn-beli {
            display: block; width: 100%; padding: 14px 0; border-radius: 50px;
            background: linear-gradient(90deg, #6c85e6, #7a7fe2); color: white;
            font-weight: bold; font-size: 18px; border: none; cursor: pointer;
            transition: 0.3s; text-transform: uppercase; text-decoration: none;
        }
        .btn-beli:hover { background: linear-gradient(90deg, #5a73d4, #686dd0); color: #fff; }

        .premium-active-banner {
            background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534;
            border-radius: 14px; padding: 16px 22px; margin-bottom: 30px;
            display: flex; align-items: center; gap: 14px; font-size: 15px;
        }
        .premium-active-banner i { font-size: 26px; color: #16a34a; }
        .premium-active-banner strong { display: block; font-size: 16px; margin-bottom: 2px; }
        .btn-aktif {
            display: block; width: 100%; padding: 14px 0; border-radius: 50px;
            background: #e5e7eb; color: #6b7280; font-weight: bold; font-size: 16px;
            border: none; text-transform: uppercase; text-align: center; cursor: default;
        }
        
        @media (max-width: 768px) {
            .premium-card { flex-direction: column; text-align: left; align-items: flex-start; padding: 25px; }
            .card-right { width: 100%; margin-top: 25px; text-align: center; border-top: 1px solid #eee; padding-top: 20px; }
            .premium-title { font-size: 26px; }
        }
    </style>
</head>
<body class="animsition membership-page">
	
	<?php 
	$active_page = 'premium'; 
	include 'navbar.php';
	?>

	<div class="container">
		<div class="bread-crumb flex-w p-l-25 p-r-15 p-t-30 p-lr-0-lg">
			<a href="index.php" class="stext-109 cl8 hov-cl1 trans-04">
				Home <i class="fa fa-angle-right m-l-9 m-r-10" aria-hidden="true"></i>
			</a>
			<span class="stext-109 cl4">Premium Membership</span>
		</div>
	</div>
		
	<section class="premium-section">
		<div class="container">
            <div class="membership-header">
                <h1>Pilih Paket yang Sesuai</h1>
                <p>Tingkatkan pengalaman sebagai pembeli atau desainer dengan fitur tambahan yang benar-benar mendukung aktivitasmu.</p>
            </div>

            <?php if ($sudah_premium) { ?>
            <div class="premium-active-banner">
                <i class="fa fa-check-circle"></i>
                <div>
                    <strong>Kamu sudah Premium!</strong>
                    <?php if ($premium_sampai) { ?>
                        <span>Membership aktif sampai <?php echo htmlspecialchars(tgl_id_premium($premium_sampai)); ?>.</span>
                    <?php } else { ?>
                        <span>Akun kamu berstatus premium aktif.</span>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>
			
            <?php if (!$is_designer_logged_in) { ?>
            <div class="premium-card">
                <div class="card-left">
                    <h2 class="premium-title">Premium Pengguna</h2>
                    <ul class="feature-list">
                        <li>Premium Chat</li>
                        <li>Desainer Favorit dan Notifikasi Custom</li>
                        <li>Bidding Prioritas</li>
                        <li>History Bidding & Chat Log Khusus</li>
                    </ul>
                </div>
                <div class="card-right">
                    <div class="price-tag">Rp 25.000 <span class="price-per">/ Bulan</span></div>

                    <?php if ($sudah_premium && $is_user_logged_in) { ?>
                        <span class="btn-aktif"><i class="fa fa-check"></i> Paket Aktif</span>
                    <?php } else { ?>
                    <form action="proses_beli_premium.php" method="POST">
                        <input type="hidden" name="tipe_premium" value="pengguna">
                        <input type="hidden" name="harga" value="25000">
                        <button type="submit" class="btn-beli">Pilih Paket Pembeli</button>
                    </form>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>

            <?php if (!$is_user_logged_in) { ?>
            <div class="premium-card">
                <div class="card-left">
                    <h2 class="premium-title">Premium Desainer</h2>
                    <ul class="feature-list">
                        <li>Tampilan di halaman utama website</li>
                        <li>Fast Reply Tools</li>
                        <li>Fitur Portofolio Showcase</li>
                        <li>Label "Verified Premium Designer"</li>
                    </ul>
                </div>
                <div class="card-right">
                    <div class="price-tag">Rp 28.000 <span class="price-per">/ Bulan</span></div>

                    <?php if ($sudah_premium && $is_designer_logged_in) { ?>
                        <span class="btn-aktif"><i class="fa fa-check"></i> Paket Aktif</span>
                    <?php } else { ?>
                    <form action="proses_beli_premium.php" method="POST">
                        <input type="hidden" name="tipe_premium" value="desainer">
                        <input type="hidden" name="harga" value="28000">
                        <button type="submit" class="btn-beli">Pilih Paket Desainer</button>
                    </form>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>

		</div>
	</section>

	<footer class="membership-footer">
		<div class="container">DensCreative Premium membantu pembeli dan desainer bekerja lebih nyaman.</div>
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

</body>
</html>
