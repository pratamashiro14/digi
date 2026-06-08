<?php
/**
 * NAVBAR TEMPLATE
 * File template untuk navbar yang konsisten di semua halaman
 * Perlu didahului dengan session_start() dan include database connection
 * 
 * Gunakan:
 * <?php 
 * session_start(); 
 * include 'admin/koneksi.php';
 * $active_page = 'beranda'; // atau 'product', 'premium', 'contact'
 * include 'navbar.php';
 * ?>
 */

// Set default active page jika tidak didefinisikan
if(!isset($active_page)) {
    $active_page = 'beranda';
}

// Cek Login Status (pastikan sudah di-set di halaman utama)
if(!isset($is_designer_logged_in)) {
    $is_designer_logged_in = isset($_SESSION['status_designer']) && $_SESSION['status_designer'] == "login";
}
if(!isset($nama_desainer)) {
    $nama_desainer = isset($_SESSION['nama_desainer']) ? $_SESSION['nama_desainer'] : 'Desainer';
}
if(!isset($is_user_logged_in)) {
    $is_user_logged_in = isset($_SESSION['status']) && $_SESSION['status'] == "login";
}
if(!isset($nama_user)) {
    $nama_user = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'User';
}
if(!isset($jumlah_item_keranjang)) {
    $jumlah_item_keranjang = 0;
    if(isset($_SESSION['keranjang'])) {
        foreach($_SESSION['keranjang'] as $jml) {
            $jumlah_item_keranjang += $jml;
        }
    }
}
?>

<header class="header-v4">
	<div class="container-menu-desktop">
		<div class="top-bar">
			<div class="content-topbar flex-sb-m h-full container">
				<div class="left-top-bar">Pilih Desain Sesuai Standarmu!</div>
				<div class="right-top-bar flex-w h-full">
                    <?php if (function_exists('is_admin_login') && is_admin_login()) { ?>
					<a href="admin/" class="flex-c-m trans-04 p-lr-25" style="font-weight: 600; color: #d90429;">Admin</a>
                    <?php } ?>

                    <?php if ($is_designer_logged_in) { ?>
                    <div class="account-dd p-lr-25">
                        <a href="#" class="account-toggle" onclick="return false;"><i class="zmdi zmdi-account"></i> <?php echo htmlspecialchars($nama_desainer); ?> (Desainer) <i class="zmdi zmdi-caret-down caret"></i></a>
                        <div class="account-menu">
                            <a href="profil_desainer.php"><i class="zmdi zmdi-account"></i> Profil Saya</a>
                            <a href="penjualan.php"><i class="zmdi zmdi-store"></i> Penjualan</a>
                            <a href="#"><i class="zmdi zmdi-help-outline"></i> Bantuan</a>
                            <div class="account-divider"></div>
                            <a href="logout.php"><i class="zmdi zmdi-power"></i> Logout</a>
                        </div>
                    </div>
                    <?php } elseif ($is_user_logged_in) { ?>
                    <div class="account-dd p-lr-25">
                        <a href="#" class="account-toggle" onclick="return false;"><i class="zmdi zmdi-account"></i> <?php echo htmlspecialchars($nama_user); ?> <i class="zmdi zmdi-caret-down caret"></i></a>
                        <div class="account-menu">
                            <a href="profil.php"><i class="zmdi zmdi-account"></i> Profil Saya</a>
                            <a href="riwayat.php"><i class="zmdi zmdi-receipt"></i> Riwayat Pembelian</a>
                            <a href="#"><i class="zmdi zmdi-help-outline"></i> Bantuan</a>
                            <div class="account-divider"></div>
                            <a href="logout.php"><i class="zmdi zmdi-power"></i> Logout</a>
                        </div>
                    </div>
                    <?php } else { ?>
                        <a href="#" class="flex-c-m trans-04 p-lr-25">Bantuan</a>
                        <a href="login.php" class="flex-c-m trans-04 p-lr-25">Masuk / Daftar</a>
                    <?php } ?>
				</div>
			</div>
		</div>

		<div class="wrap-menu-desktop">
			<nav class="limiter-menu-desktop container">
				<a href="index.php" class="logo"><img src="images/icons/logo-01.png?v=<?php echo time(); ?>" alt="IMG-LOGO"></a>
				<div class="menu-desktop">
					<ul class="main-menu">
						<li <?php echo ($active_page == 'beranda') ? 'class="active-menu"' : ''; ?>><a href="index.php">Beranda</a></li>
						<li <?php echo ($active_page == 'product') ? 'class="active-menu"' : ''; ?>><a href="product.php">Pasar Desain</a></li>
						<li><a href="shoping-cart.php">Pembelian</a></li>
						<li <?php echo ($active_page == 'premium') ? 'class="active-menu"' : ''; ?>><a href="premium.php">Fitur Unggulan</a></li>
						<li <?php echo ($active_page == 'contact') ? 'class="active-menu"' : ''; ?>><a href="contact.php">Hubungi Kami</a></li>
					</ul>
				</div>	

				<div class="wrap-icon-header flex-w flex-r-m">
					<div class="icon-header-item cl2 hov-cl1 trans-04 p-l-22 p-r-11 js-show-modal-search"><i class="zmdi zmdi-search"></i></div>
					<div class="icon-header-item cl2 hov-cl1 trans-04 p-l-22 p-r-11 icon-header-noti js-show-cart" 
                         data-notify="<?php echo $jumlah_item_keranjang; ?>">
						<i class="zmdi zmdi-shopping-cart"></i>
					</div>
				</div>
			</nav>
		</div>	
	</div>

	<div class="wrap-header-mobile">
		<div class="logo-mobile"><a href="index.php"><img src="images/icons/logo-01.png?v=<?php echo time(); ?>" alt="IMG-LOGO"></a></div>
		<div class="wrap-icon-header flex-w flex-r-m m-r-15">
			<div class="icon-header-item cl2 hov-cl1 trans-04 p-r-11 js-show-modal-search"><i class="zmdi zmdi-search"></i></div>
			<div class="icon-header-item cl2 hov-cl1 trans-04 p-r-11 p-l-10 icon-header-noti js-show-cart" data-notify="<?php echo $jumlah_item_keranjang; ?>">
				<i class="zmdi zmdi-shopping-cart"></i>
			</div>
		</div>
		<div class="btn-show-menu-mobile hamburger hamburger--squeeze"><span class="hamburger-box"><span class="hamburger-inner"></span></span></div>
	</div>

	<div class="menu-mobile">
		<ul class="topbar-mobile">
			<li><div class="left-top-bar">Pilih Desain Sesuai Standarmu!</div></li>
			<li><div class="right-top-bar flex-w h-full">
                <a href="#" class="flex-c-m p-lr-10 trans-04">Bantuan</a>
                <?php if ($is_designer_logged_in) { ?>
                    <a href="profil_desainer.php" class="flex-c-m p-lr-10 trans-04"><i class="zmdi zmdi-account"></i>&nbsp;<?php echo htmlspecialchars($nama_desainer); ?></a>
                    <a href="penjualan.php" class="flex-c-m p-lr-10 trans-04">Penjualan</a>
                    <a href="logout.php" class="flex-c-m p-lr-10 trans-04">Logout</a>
                <?php } elseif ($is_user_logged_in) { ?>
                    <a href="profil.php" class="flex-c-m p-lr-10 trans-04"><i class="zmdi zmdi-account"></i>&nbsp;<?php echo htmlspecialchars($nama_user); ?></a>
                    <a href="riwayat.php" class="flex-c-m p-lr-10 trans-04">Riwayat Pembelian</a>
                    <a href="logout.php" class="flex-c-m p-lr-10 trans-04">Logout</a>
                <?php } else { ?>
                    <a href="login.php" class="flex-c-m p-lr-10 trans-04">Masuk / Daftar</a>
                <?php } ?>
            </div></li>
		</ul>
		<ul class="main-menu-m">
			<li><a href="index.php">Beranda</a></li>
			<li><a href="product.php">Pasar Desain</a></li>
			<li><a href="shoping-cart.php">Pembelian</a></li>
			<li><a href="premium.php">Fitur Unggulan</a></li>
			<li><a href="contact.php">Hubungi Kami</a></li>
		</ul>
	</div>

    <div class="modal-search-header flex-c-m trans-04 js-hide-modal-search">
		<div class="container-search-header">
			<button class="flex-c-m btn-hide-modal-search trans-04 js-hide-modal-search"><img src="images/icons/icon-close2.png" alt="CLOSE"></button>
			<form class="wrap-search-header flex-w p-l-15">
				<button class="flex-c-m trans-04"><i class="zmdi zmdi-search"></i></button>
				<input class="plh3" type="text" name="search" placeholder="Search...">
			</form>
		</div>
	</div>
</header>
