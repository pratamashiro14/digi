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
        ['label' => 'Total Karya', 'value' => number_format($designer_metrics['total_karya'], 0, ',', '.'), 'icon' => 'zmdi-palette'],
        ['label' => 'Lelang Aktif', 'value' => number_format($designer_metrics['lelang_aktif'], 0, ',', '.'), 'icon' => 'zmdi-hourglass-alt'],
        ['label' => 'Pesanan', 'value' => number_format($designer_metrics['pesanan'], 0, ',', '.'), 'icon' => 'zmdi-shopping-cart'],
        ['label' => 'Total Penjualan', 'value' => 'Rp' . number_format($designer_metrics['total_penjualan'], 0, ',', '.'), 'icon' => 'zmdi-trending-up'],
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
        <style>        /* PREMIUM DESIGNER DASHBOARD OVERRIDES */
        /* Button styles (Navy theme) */
        .designer-dashboard-button {
            border-radius: 10px !important;
            transition: all 0.2s ease !important;
            font-weight: 700 !important;
            font-size: 13px !important;
        }
        .designer-dashboard-button.is-primary {
            background: #0f172a !important; /* Navy */
            border: 1px solid #0f172a !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15) !important;
        }
        .designer-dashboard-button.is-primary:hover {
            background: #1e293b !important;
            border-color: #1e293b !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.25) !important;
            color: #ffffff !important;
        }
        .designer-dashboard-button:not(.is-primary) {
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #0f172a !important;
        }
        .designer-dashboard-button:not(.is-primary):hover {
            background: #f8fafc !important;
            border-color: #0f172a !important;
            color: #0f172a !important;
            transform: translateY(-2px);
        }

        /* Stats Grid & Stat Cards (Mixed Navy themes) */
        .designer-stats-grid {
            margin-top: 28px !important;
        }
        .designer-stat-card {
            border-radius: 16px !important;
            padding: 20px !important;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        .designer-stat-card:hover {
            transform: translateY(-3px) !important;
        }
        .designer-stat-card i {
            width: 44px !important;
            height: 44px !important;
            font-size: 20px !important;
            border-radius: 12px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05) !important;
            color: #ffffff !important;
        }
        .designer-stat-card strong {
            color: #0f172a !important;
            font-size: 22px !important;
            font-weight: 800 !important;
        }
        .designer-stat-card span {
            color: #64748b !important;
            font-size: 13px !important;
            margin-top: 2px !important;
        }

        .designer-stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%) !important;
            border: 1px solid #e2e8f0 !important;
            border-left: 4px solid #0f172a !important; /* Navy border-left */
        }
        .designer-stat-card:hover {
            box-shadow: 0 12px 24px -10px rgba(15, 23, 42, 0.15) !important;
        }
        .designer-stat-card i {
            background: linear-gradient(135deg, #0f172a 0%, #3b82f6 100%) !important; /* Navy-Blue gradient mix */
        }

        /* Workspace Panels (Karya Terbaru, Penjualan Terbaru) */
        .designer-workspace-section { background: #f8fafc !important; }
        .designer-workspace-panel {
            background: #fff !important;
            border: none !important;
            box-shadow: 0 10px 30px -10px rgba(15, 23, 42, 0.08) !important;
            border-radius: 20px !important;
            padding: 30px !important;
        }
        .designer-panel-header {
            margin-bottom: 24px !important;
            border-bottom: 1px dashed #e2e8f0 !important;
            padding-bottom: 20px !important;
        }
        .designer-panel-header span {
            color: #3b82f6 !important;
            font-size: 12px !important;
            letter-spacing: 1px !important;
        }
        .designer-panel-header h2 {
            color: #0f172a !important;
            font-size: 22px !important;
        }
        .designer-panel-header a {
            color: #3b82f6 !important;
            font-weight: 600 !important;
            padding: 8px 16px;
            background: #eff6ff;
            border-radius: 50px;
            transition: all 0.2s ease;
            font-size: 13px;
        }
        .designer-panel-header a:hover {
            background: #3b82f6;
            color: #fff !important;
            box-shadow: 0 4px 12px rgba(59,130,246,0.3);
        }
        .designer-work-item, .designer-sale-item {
            border-bottom: 1px solid #f1f5f9;
            padding: 16px 10px !important;
            transition: all 0.2s ease;
            border-radius: 12px;
            margin-bottom: 4px;
        }
        .designer-work-item:last-child, .designer-sale-item:last-child {
            border-bottom: none;
        }
        .designer-work-item:hover, .designer-sale-item:hover {
            background: #f8fafc;
            transform: translateX(4px);
        }
        .designer-work-item img {
            border-radius: 10px !important;
            width: 50px !important; height: 50px !important;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .designer-sale-icon {
            background: #eff6ff !important;
            color: #3b82f6 !important;
            width: 46px !important; height: 46px !important;
            border-radius: 10px !important;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px !important;
        }
        .designer-work-info strong, .designer-sale-info strong {
            color: #1e293b !important;
            font-size: 15px !important;
        }
        .designer-work-info span, .designer-sale-info span {
            color: #64748b !important;
        }
        .designer-sale-value strong {
            color: #10b981 !important;
            font-size: 16px !important;
        }
        </style>
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

	<?php
	// ===== DESAINER PREMIUM (fitur "Tampilan di halaman utama website") =====
	$sql_premdes = "SELECT u.id_user, u.nama, u.foto_profil, u.foto,
	                  (SELECT COUNT(*) FROM t_design d
	                   WHERE d.id_designer = u.id_user AND d.status IN ('approved','sold')) AS total_karya
	                FROM t_user u
	                WHERE u.role = 'designer'
	                  AND (u.status_member='premium' OR u.premium=1
	                       OR EXISTS (SELECT 1 FROM t_premium p
	                                  WHERE p.id_user=u.id_user AND p.status='aktif'
	                                    AND p.tanggal_berakhir>=CURDATE()))
	                ORDER BY u.id_user DESC LIMIT 10";
	$res_premdes = mysqli_query($koneksi, $sql_premdes);
	if ($res_premdes && mysqli_num_rows($res_premdes) > 0) { ?>
	<section class="home-premdes-section">
		<div class="container">
			<div class="home-premdes-head">
				<h3><i class="fa fa-diamond" style="color: #f1c40f; margin-right: 8px;"></i>Desainer Premium</h3>
				<p>Kreator pilihan dengan keanggotaan premium — tampil utama untukmu.</p>
			</div>
			<div class="home-premdes-grid">
				<?php while ($pd = mysqli_fetch_assoc($res_premdes)) {
					$foto_pd = !empty($pd['foto_profil']) ? $pd['foto_profil'] : ($pd['foto'] ?? '');
					$ava_pd  = $foto_pd ? 'admin/uploads/' . $foto_pd : 'images/icons/logo-01.png';
				?>
					<a href="toko_desainer.php?id=<?php echo $pd['id_user']; ?>" class="home-premdes-card">
						<span class="home-premdes-badge"><i class="fa fa-star"></i></span>
						<img src="<?php echo htmlspecialchars($ava_pd); ?>"
						     onerror="this.src='images/icons/logo-01.png'; this.onerror=null;" alt="Desainer">
						<span class="home-premdes-name"><?php echo htmlspecialchars($pd['nama']); ?></span>
						<span class="home-premdes-karya"><?php echo (int) $pd['total_karya']; ?> karya</span>
					</a>
				<?php } ?>
			</div>
		</div>
		<style>
			.home-premdes-section { padding: 60px 0 30px; background: linear-gradient(180deg, #fffdf0 0%, #ffffff 100%); position: relative; }
			.home-premdes-section::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #f1c40f, #e67e22, #f1c40f); }
			.home-premdes-head { text-align: center; margin-bottom: 28px; }
			.home-premdes-head h3 { font-size: 28px; font-weight: 800; color: #1f2937; margin: 0 0 6px; }
			.home-premdes-head p { color: #6b7280; margin: 0; font-size: 14px; }
			.home-premdes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px,1fr)); gap: 18px; }
			.home-premdes-card { position: relative; display: flex; flex-direction: column; align-items: center; text-align: center;
				background: #fff; border: 1px solid #fef3c7; border-radius: 16px; padding: 22px 14px; text-decoration: none; transition: 0.25s; box-shadow: 0 4px 15px rgba(241, 196, 15, 0.08); }
			.home-premdes-card:hover { box-shadow: 0 12px 28px rgba(241, 196, 15, 0.25); transform: translateY(-5px); border-color: #f1c40f; }
			.home-premdes-badge { position: absolute; top: 12px; right: 12px; width: 26px; height: 26px; border-radius: 50%;
				background: linear-gradient(45deg,#f1c40f,#e67e22); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; }
			.home-premdes-card img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #fef3c7; margin-bottom: 12px; }
			.home-premdes-name { font-weight: 700; color: #1f2937; font-size: 15px; }
			.home-premdes-karya { color: #6b7280; font-size: 12px; margin-top: 3px; }
			
			/* === NEW UI TWEAKS (Pills, Card Hover, Colors) === */
			/* 1. Category Filter Pills */
			.filter-tope-group button {
				border-radius: 50px;
				padding: 8px 24px;
				border: 1px solid #e2e8f0;
				background-color: #fff;
				color: #64748b;
				margin-right: 10px;
				font-weight: 500;
				transition: all 0.3s ease;
			}
			.filter-tope-group button:hover {
				background-color: #f8fafc;
				color: #1591DC;
				border-color: #cbd5e1;
			}
			.filter-tope-group button.how-active1 {
				background-color: #1591DC !important;
				color: #fff !important;
				border-color: #1591DC !important;
				box-shadow: 0 4px 10px rgba(21, 145, 220, 0.3);
			}

			/* 2. Premium Product Card Hover & Radius */
			.block2 {
				background: #fff;
				border-radius: 12px;
				transition: all 0.3s ease;
				padding: 10px;
				border: 1px solid #f1f5f9;
				box-shadow: 0 6px 18px rgba(0,0,0,0.06);
			}
			.block2:hover {
				box-shadow: 0 15px 35px rgba(0,0,0,0.12);
				transform: translateY(-4px);
				border-color: #e2e8f0;
			}
			.block2-pic {
				border-radius: 8px;
				overflow: hidden;
			}

			/* 3. Color Harmony for Pricing & Titles */
			.block2-txt-child1 .stext-105 {
				color: #1591DC !important;
				font-weight: 700;
				font-size: 16px;
			}
			.block2-txt-child1 .js-name-b2 {
				font-weight: 600;
				color: #1e293b;
			}
			.block2-txt-child1 .js-name-b2:hover {
				color: #1591DC;
			}

			/* Responsive tweaks for Hero Search */
			@media (max-width: 576px) {
				.hero-search-container { bottom: 20px; }
				.hero-search-container form { padding-left: 15px; }
			}

			/* === NEW PASAR KARYA LAYOUT === */
			.home-market-section {
				position: relative;
				background: linear-gradient(180deg, #f4f7fb 0%, #ffffff 100%);
			}
			.home-market-section::before {
				content: '';
				position: absolute;
				top: 0; left: 0; right: 0;
				height: 4px;
				background: linear-gradient(90deg, #f1c40f, #e67e22, #f1c40f);
			}
			.home-market-section .container-fluid {
				padding-left: 5%;
				padding-right: 5%;
			}
			.pasar-karya-header {
				text-align: center;
				margin-bottom: 24px;
			}
			.pasar-karya-header h3 {
				font-size: 32px;
				font-weight: 800;
				color: #1e293b;
				letter-spacing: 1px;
				text-transform: uppercase;
				margin: 0 0 6px;
			}
			.pasar-karya-header p {
				color: #6b7280;
				margin: 0;
				font-size: 15px;
			}
			.home-category-row {
				display: flex;
				margin-bottom: 24px;
				border-radius: 16px;
				background: #fff;
				box-shadow: 0 10px 30px rgba(0,0,0,0.03);
				border: 1px solid #f1f5f9;
				overflow: hidden;
			}
			.home-category-sidebar {
				width: 80px;
				background: linear-gradient(180deg, #1e40af 0%, #1e3a8a 100%);
				display: flex;
				align-items: center;
				justify-content: center;
				flex-shrink: 0;
			}
			.home-category-title {
				writing-mode: vertical-rl;
				transform: rotate(180deg);
				font-size: 20px;
				font-weight: 800;
				letter-spacing: 2px;
				color: #ffffff;
				text-transform: uppercase;
				white-space: nowrap;
			}
			.home-category-content {
				flex-grow: 1;
				padding: 10px 10px 10px 0;
				min-width: 0; 
			}
			.home-category-scroll {
				display: flex;
				flex-wrap: nowrap;
				overflow-x: auto;
				scroll-behavior: smooth;
				gap: 12px;
				padding: 15px 15px 25px 15px;
				-ms-overflow-style: none;
				scrollbar-width: none;
			}
			.home-category-scroll::-webkit-scrollbar {
				display: none;
			}
			.home-card-wrapper {
				flex: 0 0 180px;
				max-width: 180px;
			}
			/* Text sizing overrides inside the categories to look clean on smaller cards */
			.home-category-scroll .js-name-b2 {
				font-size: 13px !important;
				line-height: 1.3;
			}
			.home-category-scroll .stext-105 {
				font-size: 14px !important;
			}
			.home-category-scroll .block2-txt {
				padding-top: 8px !important;
			}
			/* Adjust inner elements for smaller cards */
			.label-timer-inline {
				display: inline-flex;
				align-items: center;
				gap: 5px;
				background-color: #fee2e2;
				color: #ef4444;
				padding: 4px 8px;
				border-radius: 6px;
				font-size: 11px;
				font-weight: 700;
				border: 1px solid #fecaca;
				margin-bottom: 8px;
				line-height: 1;
			}
			.label-timer-inline i {
				font-size: 12px;
			}
			.label-timer-inline.expired {
				background-color: #f1f5f9;
				color: #64748b;
				border-color: #e2e8f0;
			}
			.label-premium-designer {
				position: absolute;
				top: 10px;
				left: 10px;
				background: linear-gradient(45deg,#f1c40f,#e67e22);
				color: #fff;
				font-size: 11px;
				font-weight: 700;
				padding: 4px 10px;
				border-radius: 50px;
				z-index: 2;
				box-shadow: 0 2px 6px rgba(0,0,0,0.25);
			}
			.home-category-scroll .label-premium-designer {
				font-size: 10px;
				padding: 3px 8px;
				top: 8px;
				left: 8px;
			}
			/* Keep card image square/compact so cards aren't too tall (lonjong) */
			.home-category-scroll .block2-pic img {
				height: 160px !important;
				aspect-ratio: auto !important;
				object-fit: cover;
			}
			/* Remove margin-bottom from cards inside horizontal scroll */
			.home-category-scroll .isotope-item {
				padding-bottom: 0 !important;
				padding-left: 0 !important;
				padding-right: 0 !important;
			}
			
			@media (max-width: 768px) {
				.home-market-section .container-fluid {
					padding-left: 15px;
					padding-right: 15px;
				}
				.home-category-row {
					flex-direction: column;
					margin-bottom: 16px;
				}
				.home-category-sidebar {
					width: 100%;
					height: auto;
					padding: 15px;
					background: linear-gradient(90deg, #1e40af 0%, #1e3a8a 100%);
				}
				.home-category-title {
					writing-mode: horizontal-tb;
					transform: none;
					font-size: 18px;
				}
				.home-card-wrapper {
					flex: 0 0 145px;
					max-width: 145px;
				}
				.home-category-scroll .block2-pic img {
					height: 130px !important;
				}
				.home-category-scroll {
					padding: 15px 10px 20px 10px;
					gap: 8px;
				}
				.home-category-content {
					padding: 0;
				}
				.home-category-scroll .label-timer-inline {
					font-size: 9px;
					padding: 3px 6px;
				}
				.home-category-scroll .label-premium-designer {
					font-size: 9px;
					padding: 2px 6px;
					top: 6px;
					left: 6px;
				}
			}
		</style>
	</section>
	<?php } ?>

	<section class="home-market-section p-t-45 p-b-140">
		<div class="container-fluid">
            <div class="home-market-panel">
                <div class="pasar-karya-header">
                    <h3><i class="fa fa-shopping-bag" style="color: #3b82f6; margin-right: 10px;"></i>Pasar Karya</h3>
                    <p>Koleksi desain digital eksklusif dan terbaik dari kreator pilihan kami.</p>
                </div>

                <div class="market-products-wrapper">
                <?php
                // Fetch and group products
                $kategori_utama = [
                    'Ilustrasi' => 'Ilustrasi',
                    'Tipografi' => 'Tipografi',
                    'UI/UX' => 'UI/UX',
                    'Animasi' => 'Animasi'
                ];
                $grouped_products = [];
                foreach ($kategori_utama as $key => $name) {
                    $grouped_products[$key] = [];
                }
                $grouped_products['Lainnya'] = [];

                // Karya desainer PREMIUM didahulukan (fitur "Tampilan di halaman utama").
                $sql_produk = "SELECT d.*, u.nama AS nama_desainer,
                                 (u.status_member='premium' OR u.premium=1
                                  OR EXISTS (SELECT 1 FROM t_premium p
                                             WHERE p.id_user=u.id_user AND p.status='aktif'
                                               AND p.tanggal_berakhir>=CURDATE())) AS is_premium_designer
                               FROM t_design d
                               LEFT JOIN t_user u ON d.id_designer = u.id_user
                               WHERE d.status = 'approved'
                                 AND NOT EXISTS (
                                     SELECT 1
                                     FROM t_transaksi t
                                     WHERE t.id_design = d.id_design
                                       AND t.status_pembayaran IN ('berhasil', 'settlement', 'capture')
                                 )
                               ORDER BY is_premium_designer DESC, d.id_design DESC";
                $result_produk = mysqli_query($koneksi, $sql_produk);
                
                if ($result_produk && mysqli_num_rows($result_produk) > 0) {
                    while($row = mysqli_fetch_assoc($result_produk)) {
                        $kat_db = trim($row['kategori']);
                        $matched = false;
                        foreach ($kategori_utama as $key => $name) {
                            if (strcasecmp(str_replace([' ', '/', '&', '-'], '', $kat_db), str_replace([' ', '/', '&', '-'], '', $key)) === 0) {
                                $grouped_products[$key][] = $row;
                                $matched = true;
                                break;
                            }
                        }
                        if (!$matched) {
                            $grouped_products['Lainnya'][] = $row;
                        }
                    }
                }

                // Render each category block
                foreach ($grouped_products as $cat_key => $products) {
                    if (count($products) > 0) {
                ?>
                    <div class="home-category-row" data-category-row="<?php echo htmlspecialchars($cat_key); ?>">
                        <div class="home-category-sidebar">
                            <span class="home-category-title"><?php echo htmlspecialchars($cat_key); ?></span>
                        </div>
                        <div class="home-category-content">
                            <div class="home-category-scroll">
                                <?php
                                foreach ($products as $row) {
                                    $id_produk = $row['id_design'];
                                    $gambar = "admin/uploads/" . $row['gambar']; 
                                    $waktu_berakhir = $row['waktu_berakhir'] ?? '';
                                    $bid_state = 'long';
                                    if (!empty($waktu_berakhir)) {
                                        $sisa_detik = strtotime($waktu_berakhir) - time();
                                        if ($sisa_detik <= 0) {
                                            $bid_state = 'ended';
                                        } elseif ($sisa_detik <= 86400) {
                                            $bid_state = 'soon';
                                        }
                                    }
                                    $search_text = strtolower(trim($row['judul'] . ' ' . ($row['nama_desainer'] ?? '') . ' ' . $row['kategori']));
                                ?>
                                <div class="home-card-wrapper isotope-item" data-bid-state="<?php echo htmlspecialchars($bid_state); ?>" data-search="<?php echo htmlspecialchars($search_text); ?>">
                                    <div class="block2">
                                        <div class="block2-pic hov-img0" style="position: relative;">
                                            <img src="<?php echo $gambar; ?>" alt="IMG-PRODUCT" style="width: 100%; aspect-ratio: 1/1; object-fit: cover;">
                                            <?php if(!empty($row['is_premium_designer'])) { ?>
                                                <span class="label-premium-designer"><i class="fa fa-star"></i> Premium</span>
                                            <?php } ?>
                                            <a href="product-detail.php?id=<?php echo $id_produk; ?>" class="block2-btn flex-c-m stext-103 cl2 size-102 bg0 bor2 hov-btn1 p-lr-15 trans-04">
                                                Lihat Detail
                                            </a>
                                        </div>
                                        <div class="block2-txt flex-w flex-t p-t-14">
                                            <div class="block2-txt-child1 flex-col-l ">
                                                <?php if(!empty($row['waktu_berakhir'])) { ?>
                                                    <div class="label-timer-inline" data-waktu="<?php echo $row['waktu_berakhir']; ?>">
                                                        <i class="fa fa-clock-o"></i> Loading...
                                                    </div>
                                                <?php } ?>
                                                <a href="product-detail.php?id=<?php echo $id_produk; ?>" class="stext-104 cl4 hov-cl1 trans-04 js-name-b2 p-b-6"><?php echo htmlspecialchars($row['judul']); ?></a>
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
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                <?php 
                    }
                } 
                ?>
                </div>

                <div class="market-empty-state" aria-live="polite">
                    <div class="market-empty-icon"><i class="zmdi zmdi-collection-image-o"></i></div>
                    <h3>Karya tidak ditemukan</h3>
                    <p>Silakan coba kata kunci lain atau ubah filter yang Anda pilih.</p>
                    <button type="button" class="market-empty-action" data-empty-reset>
                        <i class="zmdi zmdi-refresh"></i> Reset Pencarian
                    </button>
                </div>
                <div class="market-load-more flex-c-m flex-w w-full p-t-45" style="display:none;">
                    <a href="#" class="flex-c-m stext-101 cl5 size-103 bg2 bor1 hov-btn1 p-lr-15 trans-04">Lihat Lebih Banyak</a>
                </div>
            </div>
		</div>
	</section>
    <?php } ?>

	<?php if ($role_home !== 'designer') { ?>
	<section class="home-faq-section" aria-labelledby="home-faq-title">
		<div class="container">
			<div class="home-faq-layout">
				<div class="home-faq-intro">
					<span class="home-faq-icon" aria-hidden="true"><i class="zmdi zmdi-help-outline"></i></span>
					<span class="home-faq-eyebrow">Pusat Bantuan</span>
					<h2 id="home-faq-title">Ada yang ingin kamu tanyakan?</h2>
					<p>Temukan jawaban singkat seputar pembelian, lelang, pembayaran, dan penjualan karya di Digital Magician.</p>
				</div>

				<div class="home-faq-list">
					<details class="home-faq-item" open>
						<summary>Bagaimana cara membeli atau melakukan bid?</summary>
						<div class="home-faq-answer">
							<p>Pilih karya yang kamu inginkan, buka halaman detail, lalu masukkan nominal bid. Jika karya dijual dengan harga tetap, kamu dapat langsung melanjutkan ke pembayaran.</p>
						</div>
					</details>

					<details class="home-faq-item">
						<summary>Apa yang terjadi jika saya memenangkan lelang?</summary>
						<div class="home-faq-answer">
							<p>Pemenang lelang akan menerima pemberitahuan dan dapat menyelesaikan pembayaran melalui halaman riwayat transaksi.</p>
						</div>
					</details>

					<details class="home-faq-item">
						<summary>Metode pembayaran apa yang tersedia?</summary>
						<div class="home-faq-answer">
							<p>Pembayaran diproses dengan aman melalui Midtrans. Pilihan metode pembayaran yang tersedia akan ditampilkan saat kamu melakukan checkout.</p>
						</div>
					</details>

					<details class="home-faq-item">
						<summary>Bagaimana cara mengunduh karya setelah pembayaran?</summary>
						<div class="home-faq-answer">
							<p>Setelah pembayaran berhasil dikonfirmasi, file karya dapat diunduh dari menu riwayat transaksi pada akunmu.</p>
						</div>
					</details>

					<details class="home-faq-item">
						<summary>Bagaimana cara menjadi desainer dan menjual karya?</summary>
						<div class="home-faq-answer">
							<p>Daftar sebagai desainer, lengkapi profil, lalu unggah karya melalui dashboard. Karya akan ditinjau terlebih dahulu sebelum tampil di pasar.</p>
						</div>
					</details>

					<details class="home-faq-item">
						<summary>Apakah transaksi di Digital Magician aman?</summary>
						<div class="home-faq-answer">
							<p>Ya. Status pembayaran diverifikasi oleh sistem dan akses unduhan diberikan setelah transaksi berhasil dikonfirmasi.</p>
						</div>
					</details>
				</div>

				<div class="home-faq-support">
					<div class="home-faq-support-icon" aria-hidden="true"><i class="zmdi zmdi-headset-mic"></i></div>
					<div class="home-faq-support-copy">
						<strong>Masih belum menemukan jawabannya?</strong>
						<span>Tim kami siap membantu pertanyaanmu.</span>
					</div>
					<a href="contact.php" class="home-faq-contact">
						Hubungi Kami <i class="zmdi zmdi-arrow-right" aria-hidden="true"></i>
					</a>
				</div>
			</div>
		</div>
	</section>
	<?php } ?>

	<?php if ($role_home !== 'designer') { ?>
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
	<?php } ?>

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
        (function($) {
            var $panel = $('.home-market-panel');
            var $rows = $panel.find('.home-category-row');
            var activeBidState = 'all';
            var activeSearch = '';

            if (!$panel.length || !$rows.length) {
                return;
            }

            function updateHomeEmptyState() {
                var totalVisible = 0;
                $rows.each(function() {
                    var visibleInRow = $(this).find('.isotope-item:visible').length;
                    if (visibleInRow === 0) {
                        $(this).hide();
                    } else {
                        $(this).show();
                        totalVisible += visibleInRow;
                    }
                });
                
                $panel.find('.market-empty-state').toggleClass('is-visible', totalVisible === 0);
            }

            function homeItemMatches(item) {
                var $item = $(item);
                var matchesBidState = activeBidState === 'all' || $item.data('bid-state') === activeBidState;
                var searchableText = ($item.data('search') || '').toString().toLowerCase();
                var matchesSearch = activeSearch === '' || searchableText.indexOf(activeSearch) !== -1;
                return matchesBidState && matchesSearch;
            }

            function applyHomeFilter() {
                $panel.find('.isotope-item').each(function() {
                    $(this).toggle(homeItemMatches(this));
                });
                setTimeout(updateHomeEmptyState, 10);
            }

            $panel.find('[data-bid-filter]').off('click').on('click', function(e) {
                e.preventDefault();
                activeBidState = $(this).data('bid-filter') || 'all';
                $panel.find('[data-bid-filter]').removeClass('filter-link-active');
                $(this).addClass('filter-link-active');
                applyHomeFilter();
            });

            $panel.find('input[name="search-product"]').off('input').on('input', function() {
                activeSearch = $.trim($(this).val()).toLowerCase();
                applyHomeFilter();
            });

            $panel.find('.js-show-filter').off('click').on('click', function() {
                $(this).toggleClass('show-filter');
                $panel.find('.panel-filter').stop(true, true).slideToggle(300);
                $panel.find('.js-show-search').removeClass('show-search');
                $panel.find('.panel-search').stop(true, true).slideUp(300);
            });

            $panel.find('.js-show-search').off('click').on('click', function() {
                $(this).toggleClass('show-search');
                $panel.find('.panel-search').stop(true, true).slideToggle(300);
                $panel.find('.js-show-filter').removeClass('show-filter');
                $panel.find('.panel-filter').stop(true, true).slideUp(300);
            });

            $panel.find('[data-empty-reset]').off('click').on('click', function() {
                activeBidState = 'all';
                activeSearch = '';
                $panel.find('[data-bid-filter]').removeClass('filter-link-active');
                $panel.find('[data-bid-filter="all"]').addClass('filter-link-active');
                $panel.find('input[name="search-product"]').val('');
                applyHomeFilter();
            });

            setTimeout(updateHomeEmptyState, 50);
        })(jQuery);
    </script>

    <script>
        // 1. SCRIPT TIMER LABEL DI GAMBAR PRODUK
        setInterval(function() {
            var timers = document.querySelectorAll('.label-timer, .label-timer-inline');
            timers.forEach(function(timer) {
                var waktuStr = timer.getAttribute('data-waktu').replace(" ", "T");
                var deadline = new Date(waktuStr).getTime();
                var now = new Date().getTime();
                var t = deadline - now;

                if (t > 0) {
                    var days = Math.floor(t / (1000 * 60 * 60 * 24));
                    var hours = Math.floor((t % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    var minutes = Math.floor((t % (1000 * 60 * 60)) / (1000 * 60));
                    
                    var tampilan = '<i class="fa fa-clock-o"></i> ';
                    if (days > 0) { tampilan += days + "h "; }
                    
                    if (timer.classList.contains('label-timer-inline')) {
                        tampilan += hours + "j " + minutes + "m";
                    } else {
                        var seconds = Math.floor((t % (1000 * 60)) / 1000);
                        tampilan += hours + "j " + minutes + "m " + seconds + "d";
                    }
                    timer.innerHTML = tampilan;
                } else {
                    if (timer.classList.contains('label-timer-inline')) {
                        timer.innerHTML = '<i class="fa fa-clock-o"></i> WAKTU HABIS';
                    } else {
                        timer.innerHTML = "WAKTU HABIS";
                    }
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
