<?php
require_once __DIR__ . '/auth.php';

// Admin tidak perlu lihat halaman publik — langsung ke dashboard admin
if (is_admin_login()) {
    header('Location: admin/beranda.php');
    exit;
}

include 'admin/koneksi.php';

// 1. Cek Login Desainer
$is_designer_logged_in = isset($_SESSION['status_designer']) && $_SESSION['status_designer'] == "login";
$nama_desainer = isset($_SESSION['nama_desainer']) ? $_SESSION['nama_desainer'] : 'Desainer';

// 2. Cek Login User Biasa
$is_user_logged_in = isset($_SESSION['status']) && $_SESSION['status'] == "login";
$nama_user = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'User';

// 3. Hitung Keranjang
$jumlah_item_keranjang = 0;
if(isset($_SESSION['keranjang'])) {
    foreach($_SESSION['keranjang'] as $jml) {
        $jumlah_item_keranjang += $jml;
    }
}

$designer_dashboard_stats = [];
$designer_recent_works = [];
$designer_recent_sales = [];
if ($is_designer_logged_in) {
    $id_designer_dashboard = (int) current_id();
    $designer_metrics = [
        'total_karya' => 0,
        'lelang_aktif' => 0,
        'pesanan' => 0,
        'total_penjualan' => 0,
    ];

    $design_metrics_query = mysqli_query(
        $koneksi,
        "SELECT
            COUNT(*) AS total_karya,
            SUM(CASE WHEN status = 'approved' AND waktu_berakhir > NOW() THEN 1 ELSE 0 END) AS lelang_aktif
         FROM t_design
         WHERE id_designer = '$id_designer_dashboard'"
    );
    if ($design_metrics_query && $design_metrics_data = mysqli_fetch_assoc($design_metrics_query)) {
        $designer_metrics['total_karya'] = (int) ($design_metrics_data['total_karya'] ?? 0);
        $designer_metrics['lelang_aktif'] = (int) ($design_metrics_data['lelang_aktif'] ?? 0);
    }

    $sales_metrics_query = mysqli_query(
        $koneksi,
        "SELECT
            COUNT(t.id_transaksi) AS pesanan,
            SUM(CASE WHEN t.status_pembayaran IN ('berhasil', 'settlement', 'capture') THEN t.harga_final ELSE 0 END) AS total_penjualan
         FROM t_transaksi t
         JOIN t_design d ON t.id_design = d.id_design
         WHERE d.id_designer = '$id_designer_dashboard'"
    );
    if ($sales_metrics_query && $sales_metrics_data = mysqli_fetch_assoc($sales_metrics_query)) {
        $designer_metrics['pesanan'] = (int) ($sales_metrics_data['pesanan'] ?? 0);
        $designer_metrics['total_penjualan'] = (float) ($sales_metrics_data['total_penjualan'] ?? 0);
    }

    $designer_dashboard_stats = [
        ['label' => 'Total Karya', 'value' => number_format($designer_metrics['total_karya'], 0, ',', '.'), 'icon' => 'zmdi-collection-image-o'],
        ['label' => 'Lelang Aktif', 'value' => number_format($designer_metrics['lelang_aktif'], 0, ',', '.'), 'icon' => 'zmdi-time'],
        ['label' => 'Pesanan', 'value' => number_format($designer_metrics['pesanan'], 0, ',', '.'), 'icon' => 'zmdi-receipt'],
        ['label' => 'Total Penjualan', 'value' => 'Rp' . number_format($designer_metrics['total_penjualan'], 0, ',', '.'), 'icon' => 'zmdi-balance-wallet'],
    ];

    $recent_works_query = mysqli_query(
        $koneksi,
        "SELECT id_design, judul, kategori, harga_awal, gambar, status, waktu_berakhir
         FROM t_design
         WHERE id_designer = '$id_designer_dashboard'
         ORDER BY id_design DESC
         LIMIT 4"
    );
    if ($recent_works_query) {
        while ($work = mysqli_fetch_assoc($recent_works_query)) {
            $designer_recent_works[] = $work;
        }
    }

    $recent_sales_query = mysqli_query(
        $koneksi,
        "SELECT t.harga_final, t.status_pembayaran, t.tanggal_transaksi, d.judul, u.nama AS nama_pembeli
         FROM t_transaksi t
         JOIN t_design d ON t.id_design = d.id_design
         JOIN t_user u ON t.id_buyer = u.id_user
         WHERE d.id_designer = '$id_designer_dashboard'
         ORDER BY t.id_transaksi DESC
         LIMIT 4"
    );
    if ($recent_sales_query) {
        while ($sale = mysqli_fetch_assoc($recent_sales_query)) {
            $designer_recent_sales[] = $sale;
        }
    }
}

function render_designer_stat_card($stat) {
    ?>
    <article class="designer-stat-card">
        <i class="zmdi <?php echo htmlspecialchars($stat['icon']); ?>" aria-hidden="true"></i>
        <div>
            <strong><?php echo htmlspecialchars($stat['value']); ?></strong>
            <span><?php echo htmlspecialchars($stat['label']); ?></span>
        </div>
    </article>
    <?php
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Beranda</title>
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
	<link rel="stylesheet" type="text/css" href="vendor/daterangepicker/daterangepicker.css">
	<link rel="stylesheet" type="text/css" href="vendor/slick/slick.css">
	<link rel="stylesheet" type="text/css" href="vendor/MagnificPopup/magnific-popup.css">
	<link rel="stylesheet" type="text/css" href="vendor/perfect-scrollbar/perfect-scrollbar.css">
	<link rel="stylesheet" type="text/css" href="css/util.css">
	<link rel="stylesheet" type="text/css" href="css/main.css">

    <style>
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

        /* TABS LOGIN */
        .auth-tab { font-family: 'Poppins', sans-serif; font-weight: 600; cursor: pointer; font-size: 18px; padding-bottom: 5px; color: #aaa; border-bottom: 2px solid transparent; }
        .auth-tab.active { color: #4e60ff; border-bottom: 2px solid #4e60ff; }
        .form-control-custom { border-radius: 8px; border: 1px solid #ddd; padding: 12px; font-size: 14px; }
    </style>
</head>
<body class="animsition">
	
	<?php
	$active_page = 'beranda';
	include 'navbar.php';
	?>

    <?php
    $role_home = current_role();
    ?>

    <?php if ($role_home === 'designer') { ?>
        <section class="designer-dashboard-section" aria-labelledby="designer-dashboard-title">
            <div class="container">
                <article class="designer-dashboard-card">
                    <div class="designer-dashboard-top">
                        <div class="designer-dashboard-copy">
                            <span class="designer-dashboard-label">Dashboard Desainer</span>
                            <h1 id="designer-dashboard-title">
                                Selamat datang kembali, <?php echo htmlspecialchars(current_name()); ?> 👋
                            </h1>
                            <p>Pantau performa karya dan kelola aktivitas penjualanmu dari satu tempat.</p>
                        </div>
                        <div class="designer-dashboard-actions">
                            <a href="unggahan.php?view=auction-create#formUpload" class="designer-dashboard-button is-primary">
                                <i class="zmdi zmdi-plus" aria-hidden="true"></i> Unggah Karya
                            </a>
                            <a href="unggahan.php?view=all#daftar-karya" class="designer-dashboard-button">
                                Kelola Karya
                            </a>
                        </div>
                    </div>
                    <div class="designer-stats-grid" aria-label="Ringkasan performa desainer">
                        <?php foreach ($designer_dashboard_stats as $stat) {
                            render_designer_stat_card($stat);
                        } ?>
                    </div>
                </article>
            </div>
        </section>

        <section class="designer-workspace-section" aria-label="Aktivitas desainer">
            <div class="container">
                <div class="designer-workspace-grid">
                    <article class="designer-workspace-panel">
                        <header class="designer-panel-header">
                            <div>
                                <span>Koleksi Saya</span>
                                <h2>Karya Terbaru</h2>
                            </div>
                            <a href="unggahan.php?view=all#daftar-karya">Lihat Semua <i class="zmdi zmdi-arrow-right"></i></a>
                        </header>

                        <?php if (!empty($designer_recent_works)) { ?>
                            <div class="designer-work-list">
                                <?php foreach ($designer_recent_works as $work) {
                                    $work_status = strtolower($work['status'] ?? 'pending');
                                    $status_labels = [
                                        'approved' => 'Tayang',
                                        'pending' => 'Menunggu',
                                        'rejected' => 'Ditolak',
                                        'sold' => 'Terjual',
                                    ];
                                    // Lelang yang sudah lewat batas waktu
                                    $sudah_berakhir = !empty($work['waktu_berakhir'])
                                        && strtotime($work['waktu_berakhir']) < time();

                                    if ($work_status === 'sold') {
                                        $status_label = 'Terjual';
                                        $status_class = 'sold';
                                    } elseif ($work_status === 'approved' && $sudah_berakhir) {
                                        $status_label = 'Berakhir';
                                        $status_class = 'rejected'; // pakai gaya badge yang sudah ada
                                    } else {
                                        $status_label = $status_labels[$work_status] ?? ucfirst($work_status);
                                        $status_class = $work_status;
                                    }
                                ?>
                                    <div class="designer-work-item">
                                        <img src="admin/uploads/<?php echo htmlspecialchars($work['gambar']); ?>" alt="<?php echo htmlspecialchars($work['judul']); ?>">
                                        <div class="designer-work-info">
                                            <strong><?php echo htmlspecialchars($work['judul']); ?></strong>
                                            <span><?php echo htmlspecialchars(ucfirst($work['kategori'])); ?> &middot; Rp<?php echo number_format($work['harga_awal'], 0, ',', '.'); ?></span>
                                        </div>
                                        <span class="designer-status is-<?php echo htmlspecialchars($status_class); ?>"><?php echo htmlspecialchars($status_label); ?></span>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } else { ?>
                            <div class="designer-panel-empty">
                                <i class="zmdi zmdi-collection-image-o"></i>
                                <strong>Belum ada karya</strong>
                                <p>Unggah karya pertamamu untuk mulai membangun koleksi.</p>
                                <a href="unggahan.php?view=auction-create#formUpload">Unggah Karya</a>
                            </div>
                        <?php } ?>
                    </article>

                    <article class="designer-workspace-panel">
                        <header class="designer-panel-header">
                            <div>
                                <span>Aktivitas Bisnis</span>
                                <h2>Penjualan Terbaru</h2>
                            </div>
                            <a href="penjualan.php">Lihat Semua <i class="zmdi zmdi-arrow-right"></i></a>
                        </header>

                        <?php if (!empty($designer_recent_sales)) { ?>
                            <div class="designer-sales-list">
                                <?php foreach ($designer_recent_sales as $sale) { ?>
                                    <div class="designer-sale-item">
                                        <div class="designer-sale-icon"><i class="zmdi zmdi-receipt"></i></div>
                                        <div class="designer-sale-info">
                                            <strong><?php echo htmlspecialchars($sale['judul']); ?></strong>
                                            <span><?php echo htmlspecialchars($sale['nama_pembeli']); ?> &middot; <?php echo date('d M Y', strtotime($sale['tanggal_transaksi'])); ?></span>
                                        </div>
                                        <div class="designer-sale-value">
                                            <strong>Rp<?php echo number_format($sale['harga_final'], 0, ',', '.'); ?></strong>
                                            <span><?php echo htmlspecialchars(ucfirst($sale['status_pembayaran'])); ?></span>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } else { ?>
                            <div class="designer-panel-empty">
                                <i class="zmdi zmdi-chart"></i>
                                <strong>Belum ada penjualan</strong>
                                <p>Aktivitas pembelian atas karyamu akan muncul di sini.</p>
                                <a href="unggahan.php?view=all#daftar-karya">Kelola Karya</a>
                            </div>
                        <?php } ?>
                    </article>
                </div>
            </div>
        </section>
    <?php } ?>

    <?php if ($role_home !== 'designer') { ?>
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
					</div>
				</div>
			</div>
		</div>
	</div>

	<section class="section-slide">
		<div class="wrap-slick1">
			<div class="slick1">
				<div class="item-slick1" style="background-image: url(images/banner1.jpg);">
					<div class="container h-full">
						<div class="flex-col-l-m h-full p-t-100 p-b-30 respon5">
							<span class="ltext-101 cl2 respon2">Ilustrator Terbaik</span>
							<h2 class="ltext-201 cl2 p-t-19 p-b-43 respon1">@digitalmagicianart</h2>
							<a href="product.php" class="flex-c-m stext-101 cl0 size-101 bg1 bor1 hov-btn1 p-lr-15 trans-04">Pesan Sekarang</a>
						</div>
					</div>
				</div>
				<div class="item-slick1" style="background-image: url(images/slide-02.jpg?v=<?php echo time(); ?>);">
					<div class="container h-full">
						<div class="flex-col-l-m h-full p-t-100 p-b-30 respon5">
							<span class="ltext-101 cl2 respon2">Desain Terbaru</span>
							<h2 class="ltext-201 cl2 p-t-19 p-b-43 respon1">Koleksi 2025</h2>
							<a href="product.php" class="flex-c-m stext-101 cl0 size-101 bg1 bor1 hov-btn1 p-lr-15 trans-04">Lihat</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="bg0 p-t-23 p-b-140">
		<div class="container">
			<div class="p-b-10"><h3 class="ltext-103 cl5">Pasar Karya</h3></div>
			<div class="flex-w flex-sb-m p-b-52">
				<div class="flex-w flex-l-m filter-tope-group m-tb-10">
					<button class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5 how-active1" data-filter="*">Semua Karya</button>
					<button class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5" data-filter=".ilustrasi">Ilustrasi</button>
					<button class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5" data-filter=".tipografi">Tipografi</button>
                    <button class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5" data-filter=".uiux">UI/UX</button>
                    <button class="stext-106 cl6 hov1 bor3 trans-04 m-r-32 m-tb-5" data-filter=".animasi">Animasi</button>
				</div>
                <div class="flex-w flex-c-m m-tb-10">
					<div class="flex-c-m stext-106 cl6 size-104 bor4 pointer hov-btn3 trans-04 m-r-8 m-tb-4 js-show-filter">
						<i class="icon-filter cl2 m-r-6 fs-15 trans-04 zmdi zmdi-filter-list"></i> Filter
					</div>
					<div class="flex-c-m stext-106 cl6 size-105 bor4 pointer hov-btn3 trans-04 m-tb-4 js-show-search">
						<i class="icon-search cl2 m-r-6 fs-15 trans-04 zmdi zmdi-search"></i> Pencarian
					</div>
				</div>
                <div class="dis-none panel-search w-full p-t-10 p-b-15">
					<div class="bor8 dis-flex p-l-15">
						<button class="size-113 flex-c-m fs-16 cl2 hov-cl1 trans-04"><i class="zmdi zmdi-search"></i></button>
						<input class="mtext-107 cl2 size-114 plh2 p-r-15" type="text" name="search-product" placeholder="Search">
					</div>	
				</div>
			</div>

			<div class="row isotope-grid">
                <?php
                $sql_produk = "SELECT * FROM t_design WHERE status = 'approved' ORDER BY id_design DESC";
                $result_produk = mysqli_query($koneksi, $sql_produk);
                if (mysqli_num_rows($result_produk) > 0) {
                    while($row = mysqli_fetch_assoc($result_produk)) {
                        $id_produk = $row['id_design'];
                        $gambar = "admin/uploads/" . $row['gambar']; 
                        $kategori = $row['kategori']; 
                ?>
                <div class="col-sm-6 col-md-4 col-lg-3 p-b-35 isotope-item <?php echo $kategori; ?>">
                    <div class="block2">
                        <div class="block2-pic hov-img0" style="position: relative;"> 
                            <img src="<?php echo $gambar; ?>" alt="IMG-PRODUCT" style="width: 100%; aspect-ratio: 4/5; object-fit: cover;">
                            
                            <?php if(!empty($row['waktu_berakhir'])) { ?>
                                <div class="label-timer" data-waktu="<?php echo $row['waktu_berakhir']; ?>">
                                    <i class="fa fa-clock-o"></i> Loading...
                                </div>
                            <?php } ?>
                            
                            <a href="product-detail.php?id=<?php echo $id_produk; ?>" class="block2-btn flex-c-m stext-103 cl2 size-102 bg0 bor2 hov-btn1 p-lr-15 trans-04">
    Lihat Detail
</a>
                        </div>
                        <div class="block2-txt flex-w flex-t p-t-14">
                            <div class="block2-txt-child1 flex-col-l ">
                                <a href="product-detail.php?id=<?php echo $id_produk; ?>" class="stext-104 cl4 hov-cl1 trans-04 js-name-b2 p-b-6"><?php echo $row['judul']; ?></a>
                                <span class="stext-105 cl3">Rp <?php echo number_format($row['harga_awal'],0,',','.'); ?></span>
                            </div>
                            <div class="block2-txt-child2 flex-r p-t-3">
                                <a href="#" class="btn-addwish-b2 dis-block pos-relative js-addwish-b2">
                                    <img class="icon-heart1 dis-block trans-04" src="images/icons/icon-heart-01.png" alt="ICON">
                                    <img class="icon-heart2 dis-block trans-04 ab-t-l" src="images/icons/icon-heart-02.png" alt="ICON">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } } ?>
			</div>
            <div class="market-empty-state" aria-live="polite">
                <div class="market-empty-icon"><i class="zmdi zmdi-collection-image-o"></i></div>
                <h3>Belum ada karya di kategori ini</h3>
                <p>Coba pilih kategori lain atau tampilkan semua karya yang tersedia.</p>
                <button type="button" class="market-empty-action" data-empty-reset>
                    <i class="zmdi zmdi-refresh"></i> Lihat Semua Karya
                </button>
            </div>
            <div class="market-load-more flex-c-m flex-w w-full p-t-45">
				<a href="#" class="flex-c-m stext-101 cl5 size-103 bg2 bor1 hov-btn1 p-lr-15 trans-04">Lihat Lebih Banyak</a>
			</div>
		</div>
	</section>
    <?php } ?>

	<footer class="bg3 p-t-75 p-b-32">
		<div class="container">
			<div class="row">
				<div class="col-sm-6 col-lg-3 p-b-50">
					<h4 class="stext-301 cl0 p-b-30">Kategori</h4>
					<ul>
						<li class="p-b-10"><a href="#" class="stext-107 cl7 hov-cl1 trans-04">Ilustrasi</a></li>
						<li class="p-b-10"><a href="#" class="stext-107 cl7 hov-cl1 trans-04">Tipografi</a></li>
						<li class="p-b-10"><a href="#" class="stext-107 cl7 hov-cl1 trans-04">Mockup</a></li>
						<li class="p-b-10"><a href="#" class="stext-107 cl7 hov-cl1 trans-04">Ui & Ux</a></li>
					</ul>
				</div>
				<div class="col-sm-6 col-lg-3 p-b-50">
					<h4 class="stext-301 cl0 p-b-30">Bantuan</h4>
					<ul>
						<li class="p-b-10"><a href="#" class="stext-107 cl7 hov-cl1 trans-04">Fitur Unggulan</a></li>
						<li class="p-b-10"><a href="#" class="stext-107 cl7 hov-cl1 trans-04">Pesanan</a></li>
						<li class="p-b-10"><a href="#" class="stext-107 cl7 hov-cl1 trans-04">Bantuan</a></li>
					</ul>
				</div>
				<div class="col-sm-6 col-lg-3 p-b-50">
					<h4 class="stext-301 cl0 p-b-30">Alamat</h4>
					<p class="stext-107 cl7 size-201">
						Ada Pertanyaan? Hubungi kami di Jl. Sariasih No.54, Sarijadi, Kec. Sukasari, Kota Bandung, Jawa Barat 40151, (+62) 881010229410
					</p>
					<div class="p-t-27">
						<a href="#" class="fs-18 cl7 hov-cl1 trans-04 m-r-16"><i class="fa fa-instagram"></i></a>
						<a href="#" class="fs-18 cl7 hov-cl1 trans-04 m-r-16"><i class="fab fa-tiktok"></i></a>
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
	<script src="vendor/daterangepicker/moment.min.js"></script>
	<script src="vendor/daterangepicker/daterangepicker.js"></script>
	<script src="vendor/slick/slick.min.js"></script>
	<script src="js/slick-custom.js"></script>
	<script src="vendor/parallax100/parallax100.js"></script>
	<script>$('.parallax100').parallax100();</script>
	<script src="vendor/MagnificPopup/jquery.magnific-popup.min.js"></script>
	<script src="vendor/isotope/isotope.pkgd.min.js"></script>
	<script src="vendor/sweetalert/sweetalert.min.js"></script>
	<script src="vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
	<script src="js/main.js"></script>

    <script>
        // 1. SCRIPT TIMER LABEL DI GAMBAR PRODUK
        setInterval(function() {
            var timers = document.querySelectorAll('.label-timer');
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

        // 2. LOGIKA MODAL QUICK VIEW
        var modalInterval; 
        var currentBidValue = 0; // Variabel harga saat ini

        // FUNGSI UBAH BID (+/-)
        function changeBid(amount) {
            currentBidValue += amount;
            if(currentBidValue < 0) currentBidValue = 0; // Gak boleh minus
            updateBidInput();
        }

        // FUNGSI UPDATE TAMPILAN INPUT
        function updateBidInput() {
            var formatted = "Rp " + new Intl.NumberFormat('id-ID').format(currentBidValue);
            $('#modalBidInput').val(formatted);
        }

        $('.js-show-modal1').on('click', function(e){
            e.preventDefault();
            
            var id = $(this).data('id');
            var judul = $(this).data('judul');
            var harga = $(this).data('harga'); 
            var img = $(this).data('img');
            var endtime = $(this).data('endtime'); 

            // Isi Modal
            $('.modal-title').text(judul);
            $('.modal-price').text("Start: Rp " + new Intl.NumberFormat('id-ID').format(harga));
            
            // Fix Gambar Slider Modal (Agar berubah sesuai produk)
            $('.modal-img').attr('src', img);
            // Hapus slider lama & inisialisasi ulang gambar (Opsional, tergantung template)
            $('.item-slick3').find('img').attr('src', img);

            // Set ID Design ke Form
            $('#input-id-design').val(id);

            // Set Harga Default (Harga Awal + 10rb)
            currentBidValue = parseInt(harga) + 10000; 
            updateBidInput();

            // Load History
            $('#modal-bid-history').html('<div style="padding:10px; text-align:center;">Mengambil data...</div>');
            $('#modal-bid-history').load('ambil_history.php?id=' + id);

            // Timer Modal
            clearInterval(modalInterval); 
            if(endtime) {
                startModalTimer(endtime);
            } else {
                $('#modal-timer-display').text("Tidak ada batas waktu");
            }

            $('.js-modal1').addClass('show-modal1');
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
            $('.js-modal1').removeClass('show-modal1');
            clearInterval(modalInterval); 
        });

        // FUNGSI GANTI TAB LOGIN/DAFTAR (USER & DESAINER)
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
