<?php 
require_once __DIR__ . '/auth.php';

if (is_designer_login()) {
    redirect_with_alert('Pasar Desain dan pemesanan hanya tersedia untuk pembeli.', 'index.php', 'info', 'Mode Desainer');
}

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Product</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
<!--===============================================================================================-->	
	<link rel="icon" type="image/png" href="images/icons/favicon.png"/>
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="fonts/iconic/css/material-design-iconic-font.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="fonts/linearicons-v1.0.0/icon-font.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/animate/animate.css">
<!--===============================================================================================-->	
	<link rel="stylesheet" type="text/css" href="vendor/css-hamburgers/hamburgers.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/animsition/css/animsition.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/select2/select2.min.css">
<!--===============================================================================================-->	
	<link rel="stylesheet" type="text/css" href="vendor/daterangepicker/daterangepicker.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/slick/slick.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/MagnificPopup/magnific-popup.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/perfect-scrollbar/perfect-scrollbar.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="css/util.css">
	<link rel="stylesheet" type="text/css" href="css/main.css">
<!--===============================================================================================-->
	<style>
		/* TAMPILAN MODAL LELANG */
		.auction-price-box { background: #f3f3f3; padding: 12px 15px; border-radius: 10px; margin-bottom: 20px; display: inline-block; min-width: 180px; }
		.auction-price-val { font-size: 22px; font-weight: 700; color: #000; display: block; }
		.auction-timer { font-size: 13px; color: #666; margin-top: 4px; }
		
		.bid-control { display: flex; align-items: center; margin-bottom: 20px; }
		.btn-bid-qty { width: 40px; height: 40px; border: 1px solid #ddd; background: #fff; font-size: 20px; color: #555; cursor: pointer; border-radius: 5px; transition: 0.2s; }
		.btn-bid-qty:hover { background: #4e8eff; color: #fff; border-color: #4e8eff; }
		.input-bid-val { height: 40px; border: 1px solid #ddd; text-align: center; font-weight: 700; width: 160px; margin: 0 10px; border-radius: 5px; background: #fff; font-size: 16px; color: #333; }
		
		.bid-history-list { margin-bottom: 25px; border-top: 1px solid #eee; padding-top: 10px; }
		.bid-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f9f9f9; font-size: 14px; }
		.bid-user { font-weight: 600; color: #555; display: flex; align-items: center; }
		.bid-user i { margin-right: 8px; font-size: 20px; color: #ccc; }
		.bid-amount { font-weight: 700; color: #333; }
		
		.btn-auction-action { display: block; width: 100%; padding: 12px; border-radius: 50px; font-weight: 600; text-align: center; text-decoration: none; margin-bottom: 10px; border: none; cursor: pointer; transition: 0.3s; font-size: 14px; }
		.btn-grey-outline { background: #f0f0f0; color: #333; }
		.btn-grey-outline:hover { background: #e0e0e0; color: #000; }
		.btn-gradient-blue { background: linear-gradient(90deg, #4e8eff, #6b4eff); color: #fff; box-shadow: 0 4px 15px rgba(107, 78, 255, 0.3); font-size: 16px; margin-top: 15px; }
		.btn-gradient-blue:hover { background: linear-gradient(90deg, #3b75dd, #5538e0); color: #fff; transform: translateY(-2px); }

		/* TIMER MERAH DI FOTO PRODUK */
		.label-timer {
			position: absolute; top: 10px; right: 10px;
			background-color: #d90429; color: #fff;
			padding: 6px 12px; border-radius: 5px;
			font-size: 13px; font-weight: 800; letter-spacing: 0.5px;
			box-shadow: 0 4px 8px rgba(0,0,0,0.3); z-index: 10;
			display: flex; align-items: center; gap: 6px; border: 1px solid #fff;
		}
		.label-timer i { font-size: 15px; }
		.label-timer.expired { background-color: #333; border-color: #555; }
	</style>
</head>
<body class="animsition">
	
	<?php 
	$active_page = 'product'; 
	include 'navbar.php';
	?>

	<!-- Cart -->
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
                        foreach($_SESSION['keranjang'] as $id_produk => $qty) {
                            $sql_cart = "SELECT * FROM t_design WHERE id_design = '$id_produk'";
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

						<a href="shoping-cart.php" class="flex-c-m stext-101 cl0 size-107 bg3 bor2 hov-btn3 p-lr-15 trans-04 m-b-10">
							Check Out
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>

	
	<!-- Product -->
	<div class="bg0 m-t-23 p-b-140">
		<div class="container">
			<div class="flex-w flex-sb-m p-b-52">
				<div class="flex-w flex-l-m filter-tope-group m-tb-10">
					<button class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5 how-active1" data-filter="*">
						Semua Karya
					</button>

					<button class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5" data-filter=".ilustrasi">
						Ilustrasi
					</button>

					<button class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5" data-filter=".tipografi">
						Tipografi
					</button>

					<button class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5" data-filter=".mockup">
						Mockup
					</button>

					<button class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5" data-filter=".uiux">
						Ui & Ux
					</button>

					<button class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5" data-filter=".animasi">
						Animasi
					</button>
				</div>

				<div class="flex-w flex-c-m m-tb-10">
					<div class="flex-c-m stext-106 cl6 size-104 bor4 pointer hov-btn3 trans-04 m-r-8 m-tb-4 js-show-filter">
						<i class="icon-filter cl2 m-r-6 fs-15 trans-04 zmdi zmdi-filter-list"></i>
						<i class="icon-close-filter cl2 m-r-6 fs-15 trans-04 zmdi zmdi-close dis-none"></i>
						 Filter
					</div>

					<div class="flex-c-m stext-106 cl6 size-105 bor4 pointer hov-btn3 trans-04 m-tb-4 js-show-search">
						<i class="icon-search cl2 m-r-6 fs-15 trans-04 zmdi zmdi-search"></i>
						<i class="icon-close-search cl2 m-r-6 fs-15 trans-04 zmdi zmdi-close dis-none"></i>
						Search
					</div>
				</div>
				
				<!-- Search product -->
				<div class="dis-none panel-search w-full p-t-10 p-b-15">
					<div class="bor8 dis-flex p-l-15">
						<button class="size-113 flex-c-m fs-16 cl2 hov-cl1 trans-04">
							<i class="zmdi zmdi-search"></i>
						</button>

						<input class="mtext-107 cl2 size-114 plh2 p-r-15" type="text" name="search-product" placeholder="Search">
					</div>	
				</div>

				<!-- Filter -->
				<div class="dis-none panel-filter w-full p-t-10">
					<div class="wrap-filter flex-w bg6 w-full p-lr-40 p-t-27 p-lr-15-sm">
						<div class="filter-col1 p-r-15 p-b-27">
							<div class="mtext-102 cl2 p-b-15">
								Sort By
							</div>

							<ul>
								<li class="p-b-6">
									<a href="#" class="filter-link stext-106 trans-04">
										Default
									</a>
								</li>

								<li class="p-b-6">
									<a href="#" class="filter-link stext-106 trans-04">
										Popularity
									</a>
								</li>

								<li class="p-b-6">
									<a href="#" class="filter-link stext-106 trans-04">
										Average rating
									</a>
								</li>

								<li class="p-b-6">
									<a href="#" class="filter-link stext-106 trans-04 filter-link-active">
										Newness
									</a>
								</li>

								<li class="p-b-6">
									<a href="#" class="filter-link stext-106 trans-04">
										Price: Low to High
									</a>
								</li>

								<li class="p-b-6">
									<a href="#" class="filter-link stext-106 trans-04">
										Price: High to Low
									</a>
								</li>
							</ul>
						</div>

						<div class="filter-col2 p-r-15 p-b-27">
							<div class="mtext-102 cl2 p-b-15">
								Price
							</div>

							<ul>
								<li class="p-b-6">
									<a href="#" class="filter-link stext-106 trans-04 filter-link-active">
										All
									</a>
								</li>

								<li class="p-b-6">
									<a href="#" class="filter-link stext-106 trans-04">
										$0.00 - $50.00
									</a>
								</li>

								<li class="p-b-6">
									<a href="#" class="filter-link stext-106 trans-04">
										$50.00 - $100.00
									</a>
								</li>

								<li class="p-b-6">
									<a href="#" class="filter-link stext-106 trans-04">
										$100.00 - $150.00
									</a>
								</li>

								<li class="p-b-6">
									<a href="#" class="filter-link stext-106 trans-04">
										$150.00 - $200.00
									</a>
								</li>

								<li class="p-b-6">
									<a href="#" class="filter-link stext-106 trans-04">
										$200.00+
									</a>
								</li>
							</ul>
						</div>

						<div class="filter-col3 p-r-15 p-b-27">
							<div class="mtext-102 cl2 p-b-15">
								Color
							</div>

							<ul>
								<li class="p-b-6">
									<span class="fs-15 lh-12 m-r-6" style="color: #222;">
										<i class="zmdi zmdi-circle"></i>
									</span>

									<a href="#" class="filter-link stext-106 trans-04">
										Black
									</a>
								</li>

								<li class="p-b-6">
									<span class="fs-15 lh-12 m-r-6" style="color: #4272d7;">
										<i class="zmdi zmdi-circle"></i>
									</span>

									<a href="#" class="filter-link stext-106 trans-04 filter-link-active">
										Blue
									</a>
								</li>

								<li class="p-b-6">
									<span class="fs-15 lh-12 m-r-6" style="color: #b3b3b3;">
										<i class="zmdi zmdi-circle"></i>
									</span>

									<a href="#" class="filter-link stext-106 trans-04">
										Grey
									</a>
								</li>

								<li class="p-b-6">
									<span class="fs-15 lh-12 m-r-6" style="color: #00ad5f;">
										<i class="zmdi zmdi-circle"></i>
									</span>

									<a href="#" class="filter-link stext-106 trans-04">
										Green
									</a>
								</li>

								<li class="p-b-6">
									<span class="fs-15 lh-12 m-r-6" style="color: #fa4251;">
										<i class="zmdi zmdi-circle"></i>
									</span>

									<a href="#" class="filter-link stext-106 trans-04">
										Red
									</a>
								</li>

								<li class="p-b-6">
									<span class="fs-15 lh-12 m-r-6" style="color: #aaa;">
										<i class="zmdi zmdi-circle-o"></i>
									</span>

									<a href="#" class="filter-link stext-106 trans-04">
										White
									</a>
								</li>
							</ul>
						</div>

						<div class="filter-col4 p-b-27">
							<div class="mtext-102 cl2 p-b-15">
								Tags
							</div>

							<div class="flex-w p-t-4 m-r--5">
								<a href="#" class="flex-c-m stext-107 cl6 size-301 bor7 p-lr-15 hov-tag1 trans-04 m-r-5 m-b-5">
									Fashion
								</a>

								<a href="#" class="flex-c-m stext-107 cl6 size-301 bor7 p-lr-15 hov-tag1 trans-04 m-r-5 m-b-5">
									Lifestyle
								</a>

								<a href="#" class="flex-c-m stext-107 cl6 size-301 bor7 p-lr-15 hov-tag1 trans-04 m-r-5 m-b-5">
									Denim
								</a>

								<a href="#" class="flex-c-m stext-107 cl6 size-301 bor7 p-lr-15 hov-tag1 trans-04 m-r-5 m-b-5">
									Streetstyle
								</a>

								<a href="#" class="flex-c-m stext-107 cl6 size-301 bor7 p-lr-15 hov-tag1 trans-04 m-r-5 m-b-5">
									Crafts
								</a>
							</div>
						</div>
					</div>
				</div>
			</div>

			<?php
// 1. KONEKSI DATABASE & QUERY (Taruh paling atas sebelum php grid)
require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Query mengambil data
// - Karya aktif (atau tanpa batas waktu) tampil lebih dulu
// - Karya yang waktunya sudah habis ditaruh paling belakang
// - Karya yang sudah lewat 2 hari dari waktu berakhir otomatis tidak tampil (arsip)
$sql = "SELECT d.*, u.nama AS nama_desainer
        FROM t_design d
        LEFT JOIN t_user u ON d.id_designer = u.id_user
        WHERE d.status = 'approved'
          AND (d.waktu_berakhir IS NULL OR d.waktu_berakhir >= DATE_SUB(NOW(), INTERVAL 2 DAY))
        ORDER BY (d.waktu_berakhir IS NOT NULL AND d.waktu_berakhir < NOW()) ASC, d.id_design DESC";
$result = $conn->query($sql);
?>

<div class="row isotope-grid">

    <?php
    if ($result->num_rows > 0) {
        // 3. LOOPING DATA
        while($row = $result->fetch_assoc()) {
            // Persiapan Variabel
            $id = $row['id_design'];
            $judul = $row['judul'];
            $harga_awal = $row['harga_awal'];
            $harga_display = "Rp " . number_format($harga_awal, 0, ',', '.');
            $gambar2 = "admin/uploads/" . $row['gambar']; 
            $kategori = $row['kategori'];
            $waktu_berakhir = isset($row['waktu_berakhir']) ? $row['waktu_berakhir'] : '';
            $harga_beli_langsung = isset($row['harga_beli_langsung']) ? $row['harga_beli_langsung'] : 0;
    ?>

    <div class="col-sm-6 col-md-4 col-lg-3 p-b-35 isotope-item <?php echo $kategori; ?>">
        
        <?php
            // Get highest bid for this design
            $sql_bid = "SELECT MAX(harga_tawaran) as max_bid FROM t_bidding WHERE id_design = '$id'";
            $res_bid = $conn->query($sql_bid);
            $d_bid = $res_bid->fetch_assoc();
            $current_max_bid = ($d_bid && $d_bid['max_bid']) ? $d_bid['max_bid'] : $harga_awal;
        ?>

        <div class="block2">
            <div class="block2-pic hov-img0" style="position: relative;">
                
                <img src="<?php echo $gambar2; ?>" 
                     alt="<?php echo $judul; ?>" 
                     style="width: 100%; aspect-ratio: 4/5; object-fit: cover; display: block;">

                <?php if(!empty($waktu_berakhir)) { ?>
                    <div class="label-timer" data-waktu="<?php echo $waktu_berakhir; ?>">
                        <i class="fa fa-clock-o"></i> Loading...
                    </div>
                <?php } ?>

                <a href="#" class="block2-btn flex-c-m stext-103 cl2 size-102 bg0 bor2 hov-btn1 p-lr-15 trans-04 js-show-modal1" 
                   data-id="<?php echo $id; ?>"
                   data-judul="<?php echo htmlspecialchars($judul); ?>"
                   data-harga="<?php echo $harga_awal; ?>"
                   data-highest-bid="<?php echo $current_max_bid; ?>"
                   data-img="<?php echo $gambar2; ?>"
                   data-endtime="<?php echo $waktu_berakhir; ?>"
                   data-beli-langsung="<?php echo $harga_beli_langsung; ?>">
                    Quick View
                </a>
            </div>

            <div class="block2-txt flex-w flex-t p-t-14">
                <div class="block2-txt-child1 flex-col-l ">
                    <a href="product-detail.php?id=<?php echo $id; ?>" class="stext-104 cl4 hov-cl1 trans-04 js-name-b2 p-b-6">
                        <?php echo $judul; ?>
                    </a>

                    <span class="stext-105 cl3">
                        <?php echo $harga_display; ?>
                    </span>

                    <?php if (!empty($row['id_designer']) && !empty($row['nama_desainer'])) { ?>
                        <a href="toko_desainer.php?id=<?php echo $row['id_designer']; ?>" class="stext-107 cl5 hov-cl1 trans-04" style="font-size:12px; margin-top:5px;">
                            <i class="fa fa-user-circle-o"></i> <?php echo htmlspecialchars($row['nama_desainer']); ?>
                        </a>
                    <?php } ?>
                </div>

                <div class="block2-txt-child2 flex-r p-t-3">
                    <a href="#" class="btn-addwish-b2 dis-block pos-relative js-addwish-b2" data-id="<?php echo $id; ?>">
                        <img class="icon-heart1 dis-block trans-04" src="images/icons/icon-heart-01.png" alt="ICON">
                        <img class="icon-heart2 dis-block trans-04 ab-t-l" src="images/icons/icon-heart-02.png" alt="ICON">
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php 
        } // Tutup While
    } else {
        // Empty state ditampilkan oleh komponen market-empty-state.
    }
    ?>

</div>

<div class="market-empty-state" aria-live="polite">
    <div class="market-empty-icon"><i class="zmdi zmdi-collection-image-o"></i></div>
    <h3>Belum ada karya di kategori ini</h3>
    <p>Coba pilih kategori lain atau tampilkan semua karya yang tersedia.</p>
    <button type="button" class="market-empty-action" data-empty-reset>
        <i class="zmdi zmdi-refresh"></i> Lihat Semua Karya
    </button>
</div>

</div>
				</div>

			<!-- Load more -->
			<div class="market-load-more flex-c-m flex-w w-full p-t-45">
				<a href="#" class="flex-c-m stext-101 cl5 size-103 bg2 bor1 hov-btn1 p-lr-15 trans-04">
					Lihat Lebih Banyak
				</a>
			</div>
		</div>
	</div>
		

	<!-- Footer -->
	<footer class="bg3 p-t-75 p-b-32">
		<div class="container">
			<div class="row">
				<div class="col-sm-6 col-lg-3 p-b-50">
					<h4 class="stext-301 cl0 p-b-30">
						Kategori
					</h4>

					<ul>
						<li class="p-b-10">
							<a href="#" class="stext-107 cl7 hov-cl1 trans-04">
								Ilustrasi
							</a>
						</li>

						<li class="p-b-10">
							<a href="#" class="stext-107 cl7 hov-cl1 trans-04">
								Tipografi
							</a>
						</li>

						<li class="p-b-10">
							<a href="#" class="stext-107 cl7 hov-cl1 trans-04">
								Mockup
							</a>
						</li>

						<li class="p-b-10">
							<a href="#" class="stext-107 cl7 hov-cl1 trans-04">
								Ui & Ux
							</a>
						</li>
					</ul>
				</div>

				<div class="col-sm-6 col-lg-3 p-b-50">
					<h4 class="stext-301 cl0 p-b-30">
						Bantuan
					</h4>

					<ul>
						<li class="p-b-10">
							<a href="#" class="stext-107 cl7 hov-cl1 trans-04">
								Fitur Unggulan
							</a>
						</li>

						<li class="p-b-10">
							<a href="#" class="stext-107 cl7 hov-cl1 trans-04">
								Pesanan 
							</a>
						</li>

						<li class="p-b-10">
							<a href="#" class="stext-107 cl7 hov-cl1 trans-04">
								Bantuan
							</a>
						</li>
					</ul>
				</div>

				<div class="col-sm-6 col-lg-3 p-b-50">
					<h4 class="stext-301 cl0 p-b-30">
						Alamat
					</h4>

					<p class="stext-107 cl7 size-201">
						Ada Pertanyaan? Hubungi kami di Jl. Sariasih No.54, Sarijadi, Kec. Sukasari, Kota Bandung, Jawa Barat 40151, (+62) 881010229410

					<div class="p-t-27">
						<a href="#" class="fs-18 cl7 hov-cl1 trans-04 m-r-16">
							<i class="fa fa-instagram"></i>
						</a>

						<a href="#" class="fs-18 cl7 hov-cl1 trans-04 m-r-16">
   							 <i class="fab fa-tiktok"></i>
						</a>
					</div>
				</div>

	<!-- Back to top -->
	<div class="btn-back-to-top" id="myBtn">
		<span class="symbol-btn-back-to-top">
			<i class="zmdi zmdi-chevron-up"></i>
		</span>
	</div>



<!--===============================================================================================-->	
	<script src="vendor/jquery/jquery-3.2.1.min.js"></script>
<!--===============================================================================================-->
	<script src="vendor/animsition/js/animsition.min.js"></script>
<!--===============================================================================================-->
	<script src="vendor/bootstrap/js/popper.js"></script>
	<script src="vendor/bootstrap/js/bootstrap.min.js"></script>
<!--===============================================================================================-->
	<script src="vendor/select2/select2.min.js"></script>
	<script>
		$(".js-select2").each(function(){
			$(this).select2({
				minimumResultsForSearch: 20,
				dropdownParent: $(this).next('.dropDownSelect2')
			});
		})
	</script>
<!--===============================================================================================-->
	<script src="vendor/daterangepicker/moment.min.js"></script>
	<script src="vendor/daterangepicker/daterangepicker.js"></script>
<!--===============================================================================================-->
	<script src="vendor/slick/slick.min.js"></script>
	<script src="js/slick-custom.js"></script>
<!--===============================================================================================-->
	<script src="vendor/parallax100/parallax100.js"></script>
	<script>
        $('.parallax100').parallax100();
	</script>
<!--===============================================================================================-->
	<script src="vendor/MagnificPopup/jquery.magnific-popup.min.js"></script>
	<script>
		$('.gallery-lb').each(function() { // the containers for all your galleries
			$(this).magnificPopup({
		        delegate: 'a', // the selector for gallery item
		        type: 'image',
		        gallery: {
		        	enabled:true
		        },
		        mainClass: 'mfp-fade'
		    });
		});
	</script>
<!--===============================================================================================-->
	<script src="vendor/isotope/isotope.pkgd.min.js"></script>
<!--===============================================================================================-->
	<script src="vendor/sweetalert/sweetalert.min.js"></script>
	<script>
		$('.js-addwish-b2, .js-addwish-detail').on('click', function(e){
			e.preventDefault();
		});

		$('.js-addwish-b2').each(function(){
			var nameProduct = $(this).parent().parent().find('.js-name-b2').php();
			$(this).on('click', function(){
				swal(nameProduct, "is added to wishlist !", "success");

				$(this).addClass('js-addedwish-b2');
				$(this).off('click');
			});
		});

		$('.js-addwish-detail').each(function(){
			var nameProduct = $(this).parent().parent().parent().find('.js-name-detail').php();

			$(this).on('click', function(){
				swal(nameProduct, "is added to wishlist !", "success");

				$(this).addClass('js-addedwish-detail');
				$(this).off('click');
			});
		});

		/*---------------------------------------------*/

		$('.js-addcart-detail').each(function(){
			var nameProduct = $(this).parent().parent().parent().parent().find('.js-name-detail').php();
			$(this).on('click', function(){
				swal(nameProduct, "is added to cart !", "success");
			});
		});
	
	</script>
<!--===============================================================================================-->
	<script src="vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
	<script>
		$('.js-pscroll').each(function(){
			$(this).css('position','relative');
			$(this).css('overflow','hidden');
			var ps = new PerfectScrollbar(this, {
				wheelSpeed: 1,
				scrollingThreshold: 1000,
				wheelPropagation: false,
			});

			$(window).on('resize', function(){
				ps.update();
			})
		});
	</script>
<!--===============================================================================================-->
	<script src="js/main.js"></script>

	<!-- MODAL LELANG QUICK VIEW -->
	<div class="js-modal1 modal fade show" id="quickViewModal" style="margin-top: 60px;">
		<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Loading...</h5>
					<button type="button" class="close js-hide-modal1" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body" style="padding: 30px;">
					<div class="row">
						<div class="col-md-6 mb-4 mb-md-0">
							<img src="" alt="Modal Image" class="modal-img" style="width: 100%; border-radius: 10px; object-fit: cover; max-height: 400px;">
						</div>
						<div class="col-md-6">
							<h3 class="modal-title mb-3" style="font-size: 1.5rem; font-weight: 600;">Product Title</h3>
							
							<div class="auction-price-box" style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
								<div style="margin-bottom: 10px;">
									<span class="auction-price-val modal-price" style="font-size: 1.1rem; font-weight: 600; color: #333;">Start: Rp 0</span>
								</div>
								<span class="auction-timer" id="modal-timer-display" style="font-size: 0.95rem; color: #666;">Loading...</span>
							</div>

							<form id="biddingForm" action="proses_bidding.php" method="POST">
								<input type="hidden" name="id_design" id="input-id-design" value="">
								
								<div class="bid-control" style="display: flex; gap: 10px; margin-bottom: 20px; align-items: stretch;">
									<button type="button" class="btn-bid-qty" style="flex: 0 0 45px; padding: 8px; font-size: 1.2rem; border: 1px solid #ddd; background: #fff; border-radius: 5px; cursor: pointer; font-weight: bold;" onclick="changeBid(-10000)">−</button>
									<input type="text" class="input-bid-val" id="modalBidInput" name="harga_tawaran" style="flex: 1; padding: 10px 12px; border: 2px solid #4e8eff; border-radius: 5px; font-size: 1rem; font-weight: 600; text-align: center; color: #4e8eff;" value="Rp 0" placeholder="Rp 0">
									<button type="button" class="btn-bid-qty" style="flex: 0 0 45px; padding: 8px; font-size: 1.2rem; border: 1px solid #ddd; background: #fff; border-radius: 5px; cursor: pointer; font-weight: bold;" onclick="changeBid(10000)">+</button>
								</div>

								<button type="submit" class="btn-auction-action btn-gradient-blue" style="width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; margin-bottom: 10px;">Pasang Penawaran</button>
								<a href="#" id="btn-modal-beli-langsung" class="btn-auction-action" style="display: none; width: 100%; text-align: center; padding: 12px; background: linear-gradient(90deg, #2ecc71, #27ae60); color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; text-decoration: none; margin-bottom: 10px; box-shadow: 0 4px 15px rgba(46, 204, 113, 0.4);"><i class="fa fa-flash"></i> Beli Langsung</a>
								<a href="product-detail.php" id="btn-detail-lengkap" class="btn-auction-action btn-grey-outline" style="display: block; text-align: center; padding: 12px; background: #f0f0f0; color: #333; text-decoration: none; border-radius: 8px; font-size: 1rem; font-weight: 600; border: 1px solid #ddd;">Lihat Detail Lengkap</a>
							</form>

							<div class="bid-history-list" id="modal-bid-history" style="margin-top: 25px; max-height: 250px; overflow-y: auto;">
								<p style="text-align:center; color:#999; font-size: 0.9rem;">Riwayat bid akan dimuat di sini</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- JAVASCRIPT UNTUK MODAL LELANG -->
	<script>
		// 1. TIMER COUNTDOWN
		window.addEventListener('load', function() {
			var timers = document.querySelectorAll('.label-timer');
			setInterval(function() {
				timers.forEach(function(timer) {
					var waktuStr = timer.getAttribute('data-waktu').replace(" ", "T");
					var deadline = new Date(waktuStr).getTime();
					var now = new Date().getTime();
					var t = deadline - now;

					if (t > 0) {
						var days = Math.floor(t / (1000 * 60 * 60 * 24));
						var hours = Math.floor((t % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
						var minutes = Math.floor((t % (1000 * 60 * 60)) / (1000 * 60));
						var seconds = Math.floor((t % (1000 * 60)) / 1000);
						
						var tampilan = '<i class="fa fa-clock-o"></i> ';
						if (days > 0) { tampilan += days + "h "; }
						tampilan += hours + "j " + minutes + "m " + seconds + "d";
						timer.innerHTML = tampilan;
					} else {
						timer.innerHTML = "WAKTU HABIS";
						timer.classList.add('expired');
					}
				});
			}, 1000);
		});

		// 2. LOGIKA MODAL QUICK VIEW
		var modalInterval; 
		var currentBidValue = 0;
		var minBidValue = 0;

		function formatRupiah(value) {
			return "Rp " + new Intl.NumberFormat('id-ID').format(value);
		}

		function parseRupiah(text) {
			return parseInt(text.replace(/[^0-9]/g, '')) || 0;
		}

		function changeBid(amount) {
			currentBidValue += amount;
			if(currentBidValue < minBidValue) currentBidValue = minBidValue;
			updateBidInput();
		}

		function updateBidInput() {
			var formatted = formatRupiah(currentBidValue);
			$('#modalBidInput').val(formatted);
		}

		// Handle manual input pada field harga
		$(document).on('input', '#modalBidInput', function() {
			var inputValue = $(this).val();
			var numValue = parseRupiah(inputValue);
			
			// Update internal value
			currentBidValue = numValue;
			
			// Format ulang display
			if(numValue > 0) {
				$(this).val(formatRupiah(numValue));
			}
		});

		// Handle form submit
		$('#biddingForm').on('submit', function(e) {
			var bidAmount = parseRupiah($('#modalBidInput').val());
			
			if(bidAmount < minBidValue) {
				e.preventDefault();
				swal('Tawaran Ditolak!', 'Penawaran minimum adalah ' + formatRupiah(minBidValue) + '.', 'error');
				return false;
			}
			
			if(bidAmount <= 0) {
				e.preventDefault();
				swal('Tawaran Ditolak!', 'Masukkan jumlah penawaran yang valid.', 'error');
				return false;
			}
		});

		$('.js-show-modal1').on('click', function(e){
			e.preventDefault();
			
			var id = $(this).data('id');
			var judul = $(this).data('judul');
			var harga = $(this).data('harga'); 
			var highestBid = $(this).data('highest-bid');
			var img = $(this).data('img');
			var endtime = $(this).data('endtime');
			var beliLangsung = $(this).data('beli-langsung');

			$('.modal-title').text(judul);
			if (highestBid > harga) {
				$('.modal-price').text("Highest Bid: " + formatRupiah(highestBid));
			} else {
				$('.modal-price').text("Start: " + formatRupiah(harga));
			}
			
			$('.modal-img').attr('src', img);

			$('#input-id-design').val(id);
			$('#btn-detail-lengkap').attr('href', 'product-detail.php?id=' + id);

			if (beliLangsung && parseInt(beliLangsung) > 0) {
				$('#btn-modal-beli-langsung').show();
				$('#btn-modal-beli-langsung').attr('href', 'shoping-cart.php?id_design=' + id + '&beli_langsung=1');
				$('#btn-modal-beli-langsung').html('<i class="fa fa-flash"></i> Beli Langsung Rp ' + formatRupiah(beliLangsung));
			} else {
				$('#btn-modal-beli-langsung').hide();
			}

			// Set minimum bid value
			minBidValue = parseInt(highestBid) + 10000;
			currentBidValue = minBidValue;
			updateBidInput();

			$('#modal-bid-history').html('<div style="padding:10px; text-align:center;">Mengambil data...</div>');
			$('#modal-bid-history').load('ambil_history.php?id=' + id);

			clearInterval(modalInterval); 
			if(endtime) {
				startModalTimer(endtime);
			} else {
				$('#modal-timer-display').text("Tidak ada batas waktu");
			}

			$('#quickViewModal').modal('show');
		});

		function startModalTimer(waktuAkhir) {
			function update() {
				var deadline = new Date(waktuAkhir.replace(" ", "T")).getTime();
				var now = new Date().getTime();
				var t = deadline - now;

				if (t > 0) {
					var days = Math.floor(t / (1000 * 60 * 60 * 24));
					var hours = Math.floor((t % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
					var minutes = Math.floor((t % (1000 * 60 * 60)) / (1000 * 60));
					var seconds = Math.floor((t % (1000 * 60)) / 1000);
					
					var tampilan = '<i class="fa fa-clock-o"></i> Sisa Waktu: ';
					if(days > 0) { tampilan += days + "h "; }
					tampilan += hours + "j " + minutes + "m " + seconds + "d";

					$('#modal-timer-display').html(tampilan);
				} else {
					$('#modal-timer-display').text("LELANG BERAKHIR");
					clearInterval(modalInterval);
				}
			}
			update(); 
			modalInterval = setInterval(update, 1000); 
		}

		$('.js-hide-modal1').on('click', function(){
			$('#quickViewModal').modal('hide');
			clearInterval(modalInterval); 
		});
	</script>

</body>
    </html>
