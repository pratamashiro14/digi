<?php
if (!isset($active_page)) {
    $active_page = 'beranda';
}

$role = function_exists('current_role') ? current_role() : 'guest';
$role_label = function_exists('current_role_label') ? current_role_label() : 'Tamu';

$is_admin_logged_in = $role === 'admin';
$is_designer_logged_in = $role === 'designer';
$is_user_logged_in = $role === 'pelanggan';

$display_name = 'Tamu';
if ($is_admin_logged_in) {
    $display_name = 'Admin';
} elseif ($is_designer_logged_in) {
    $display_name = $_SESSION['nama_desainer'] ?? ($_SESSION['nama'] ?? 'Desainer');
} elseif ($is_user_logged_in) {
    $display_name = $_SESSION['nama'] ?? 'Pembeli';
}

$jumlah_item_keranjang = $jumlah_item_keranjang ?? 0;
if ($jumlah_item_keranjang === 0 && isset($_SESSION['keranjang']) && is_array($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $jml) {
        $jumlah_item_keranjang += (int) $jml;
    }
}

$topbar_text = [
    'admin' => 'Mode Admin: kelola data, transaksi, dan pengguna.',
    'designer' => 'Mode Desainer: kelola karya, unggahan, dan penjualan.',
    'pelanggan' => 'Mode Pembeli: temukan desain dan pantau pembelian.',
    'guest' => 'Pilih desain sesuai standarmu.',
][$role] ?? 'Pilih desain sesuai standarmu.';

if ($is_admin_logged_in) {
    $main_menu = [
        ['href' => 'admin/beranda.php', 'label' => 'Dashboard', 'active' => 'admin-dashboard'],
        ['href' => 'admin/tables/datapengguna.php', 'label' => 'Pengguna', 'active' => 'admin-users'],
        ['href' => 'admin/tables/transaksi.php', 'label' => 'Transaksi', 'active' => 'admin-transactions'],
        ['href' => 'index.php', 'label' => 'Lihat Situs', 'active' => 'beranda'],
    ];
} elseif ($is_designer_logged_in) {
    $main_menu = [
        ['href' => 'index.php', 'label' => 'Beranda', 'active' => 'beranda'],
        ['href' => 'product.php', 'label' => 'Pasar Desain', 'active' => 'product'],
        ['href' => 'unggahan.php', 'label' => 'Unggahan', 'active' => 'designer-uploads'],
        ['href' => 'penjualan.php', 'label' => 'Penjualan', 'active' => 'designer-sales'],
        ['href' => 'pesan.php', 'label' => 'Pesan', 'active' => 'messages'],
    ];
} else {
    $main_menu = [
        ['href' => 'index.php', 'label' => 'Beranda', 'active' => 'beranda'],
        ['href' => 'product.php', 'label' => 'Pasar Desain', 'active' => 'product'],
        ['href' => 'shoping-cart.php', 'label' => 'Pembelian', 'active' => 'cart'],
        ['href' => 'premium.php', 'label' => 'Fitur Unggulan', 'active' => 'premium'],
        ['href' => 'contact.php', 'label' => 'Hubungi Kami', 'active' => 'contact'],
    ];
}

$account_links = [];
if ($is_admin_logged_in) {
    $account_links = [
        ['href' => 'admin/beranda.php', 'icon' => 'zmdi-view-dashboard', 'label' => 'Dashboard Admin'],
        ['href' => 'admin/tables/datapengguna.php', 'icon' => 'zmdi-accounts', 'label' => 'Data Pengguna'],
        ['href' => 'admin/tables/transaksi.php', 'icon' => 'zmdi-receipt', 'label' => 'Transaksi'],
        ['divider' => true],
        ['href' => 'admin/logout.php', 'icon' => 'zmdi-power', 'label' => 'Logout'],
    ];
} elseif ($is_designer_logged_in) {
    $account_links = [
        ['href' => 'profil_desainer.php', 'icon' => 'zmdi-account', 'label' => 'Profil Desainer'],
        ['href' => 'unggahan.php', 'icon' => 'zmdi-cloud-upload', 'label' => 'Unggahan Karya'],
        ['href' => 'penjualan.php', 'icon' => 'zmdi-store', 'label' => 'Penjualan'],
        ['href' => 'pesan.php', 'icon' => 'zmdi-comments', 'label' => 'Pesan'],
        ['divider' => true],
        ['href' => 'logout.php', 'icon' => 'zmdi-power', 'label' => 'Logout'],
    ];
} elseif ($is_user_logged_in) {
    $account_links = [
        ['href' => 'profil.php', 'icon' => 'zmdi-account', 'label' => 'Profil Saya'],
        ['href' => 'riwayat.php', 'icon' => 'zmdi-receipt', 'label' => 'Riwayat Pembelian'],
        ['href' => 'pesan.php', 'icon' => 'zmdi-comments', 'label' => 'Pesan'],
        ['href' => 'premium.php', 'icon' => 'zmdi-star', 'label' => 'Upgrade Premium'],
        ['divider' => true],
        ['href' => 'logout.php', 'icon' => 'zmdi-power', 'label' => 'Logout'],
    ];
}
?>

<header class="header-v4 role-<?php echo htmlspecialchars($role); ?>">
    <div class="container-menu-desktop">
        <div class="top-bar">
            <div class="content-topbar flex-sb-m h-full container">
                <div class="left-top-bar"><?php echo htmlspecialchars($topbar_text); ?></div>
                <div class="right-top-bar flex-w h-full">
                    <?php if ($is_admin_logged_in || $is_designer_logged_in || $is_user_logged_in) { ?>
                        <span class="role-chip"><?php echo htmlspecialchars($role_label); ?></span>
                        <div class="account-dd p-lr-25">
                            <a href="#" class="account-toggle" onclick="return false;">
                                <i class="zmdi zmdi-account"></i>
                                <?php echo htmlspecialchars($display_name); ?>
                                <i class="zmdi zmdi-caret-down caret"></i>
                            </a>
                            <div class="account-menu">
                                <?php foreach ($account_links as $link) { ?>
                                    <?php if (!empty($link['divider'])) { ?>
                                        <div class="account-divider"></div>
                                    <?php } else { ?>
                                        <a href="<?php echo htmlspecialchars($link['href']); ?>">
                                            <i class="zmdi <?php echo htmlspecialchars($link['icon']); ?>"></i>
                                            <?php echo htmlspecialchars($link['label']); ?>
                                        </a>
                                    <?php } ?>
                                <?php } ?>
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
                <a href="index.php" class="logo"><img src="images/icons/dens.png?v=<?php echo time(); ?>" alt="IMG-LOGO"></a>
                <div class="menu-desktop">
                    <ul class="main-menu">
                        <?php foreach ($main_menu as $item) { ?>
                            <li <?php echo ($active_page === $item['active']) ? 'class="active-menu"' : ''; ?>>
                                <a href="<?php echo htmlspecialchars($item['href']); ?>"><?php echo htmlspecialchars($item['label']); ?></a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>

                <div class="wrap-icon-header flex-w flex-r-m">
                    <div class="icon-header-item cl2 hov-cl1 trans-04 p-l-22 p-r-11 js-show-modal-search"><i class="zmdi zmdi-search"></i></div>
                    <?php if (!$is_admin_logged_in && !$is_designer_logged_in) { ?>
                        <div class="icon-header-item cl2 hov-cl1 trans-04 p-l-22 p-r-11 icon-header-noti js-show-cart"
                             data-notify="<?php echo $jumlah_item_keranjang; ?>">
                            <i class="zmdi zmdi-shopping-cart"></i>
                        </div>
                    <?php } ?>
                </div>
            </nav>
        </div>
    </div>

    <div class="wrap-header-mobile">
        <div class="logo-mobile"><a href="index.php"><img src="images/icons/dens.png?v=<?php echo time(); ?>" alt="IMG-LOGO"></a></div>
        <div class="wrap-icon-header flex-w flex-r-m m-r-15">
            <div class="icon-header-item cl2 hov-cl1 trans-04 p-r-11 js-show-modal-search"><i class="zmdi zmdi-search"></i></div>
            <?php if (!$is_admin_logged_in && !$is_designer_logged_in) { ?>
                <div class="icon-header-item cl2 hov-cl1 trans-04 p-r-11 p-l-10 icon-header-noti js-show-cart" data-notify="<?php echo $jumlah_item_keranjang; ?>">
                    <i class="zmdi zmdi-shopping-cart"></i>
                </div>
            <?php } ?>
        </div>
        <div class="btn-show-menu-mobile hamburger hamburger--squeeze"><span class="hamburger-box"><span class="hamburger-inner"></span></span></div>
    </div>

    <div class="menu-mobile">
        <ul class="topbar-mobile">
            <li><div class="left-top-bar"><?php echo htmlspecialchars($topbar_text); ?></div></li>
            <li>
                <div class="right-top-bar flex-w h-full">
                    <?php if ($is_admin_logged_in || $is_designer_logged_in || $is_user_logged_in) { ?>
                        <span class="role-chip"><?php echo htmlspecialchars($role_label); ?></span>
                        <?php foreach ($account_links as $link) { ?>
                            <?php if (empty($link['divider'])) { ?>
                                <a href="<?php echo htmlspecialchars($link['href']); ?>" class="flex-c-m p-lr-10 trans-04"><?php echo htmlspecialchars($link['label']); ?></a>
                            <?php } ?>
                        <?php } ?>
                    <?php } else { ?>
                        <a href="login.php" class="flex-c-m p-lr-10 trans-04">Masuk / Daftar</a>
                    <?php } ?>
                </div>
            </li>
        </ul>
        <ul class="main-menu-m">
            <?php foreach ($main_menu as $item) { ?>
                <li><a href="<?php echo htmlspecialchars($item['href']); ?>"><?php echo htmlspecialchars($item['label']); ?></a></li>
            <?php } ?>
        </ul>
    </div>

    <div class="modal-search-header flex-c-m trans-04 js-hide-modal-search">
        <div class="container-search-header">
            <button class="flex-c-m btn-hide-modal-search trans-04 js-hide-modal-search"><img src="images/icons/icon-close2.png" alt="CLOSE"></button>
            <form class="wrap-search-header flex-w p-l-15" action="product.php" method="get">
                <button class="flex-c-m trans-04"><i class="zmdi zmdi-search"></i></button>
                <input class="plh3" type="text" name="search" placeholder="Cari desain...">
            </form>
        </div>
    </div>
</header>
