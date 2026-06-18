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

$_cur = basename($_SERVER['PHP_SELF'] ?? '');

// Hitung pesan belum dibaca (harus sebelum $main_menu dibuat)
$unread_count = 0;
if (($is_user_logged_in || $is_designer_logged_in) && !empty($koneksi)) {
    $uid = function_exists('current_id') ? current_id() : null;
    if ($uid) {
        $uid = intval($uid);
        $active_chat_lawan = ($_cur === 'pesan.php' && isset($_GET['lawan'])) ? intval($_GET['lawan']) : 0;
        $sql_unread = "SELECT COUNT(*) as c
                       FROM t_chat c
                       INNER JOIN t_user u ON u.id_user=c.id_pengirim
                       WHERE c.id_penerima=$uid AND c.is_read=0";
        if ($active_chat_lawan > 0) {
            $sql_unread .= " AND c.id_pengirim<>$active_chat_lawan";
        }
        $q_unread = mysqli_query($koneksi, $sql_unread);
        if ($q_unread) $unread_count = (int)(mysqli_fetch_assoc($q_unread)['c'] ?? 0);
    }
}

if ($is_admin_logged_in) {
    $main_menu = [
        ['href' => 'admin/beranda.php', 'label' => 'Dashboard', 'active' => 'admin-dashboard'],
        ['href' => 'admin/tables/datapengguna.php', 'label' => 'Pengguna', 'active' => 'admin-users'],
        ['href' => 'admin/tables/transaksi.php', 'label' => 'Transaksi', 'active' => 'admin-transactions'],
        ['href' => 'index.php', 'label' => 'Lihat Situs', 'active' => 'beranda'],
    ];
} elseif ($is_designer_logged_in) {
    $main_menu = [
        ['href' => 'index.php', 'label' => 'Dashboard', 'active' => 'beranda'],
        ['href' => 'unggahan.php?view=all#daftar-karya', 'label' => 'Karya Saya', 'active' => 'designer-uploads'],
        ['href' => 'penjualan.php', 'label' => 'Penjualan', 'active' => 'designer-sales'],
        ['href' => 'pencairan.php', 'label' => 'Pencairan', 'active' => 'designer-finance'],
        ['href' => 'pesan.php', 'label' => 'Pesan', 'active' => 'messages', 'badge' => $unread_count],
        ['href' => 'profil_desainer.php', 'label' => 'Profil', 'active' => 'designer-profile'],
    ];
} else {
    $main_menu = [
        ['href' => 'index.php', 'label' => 'Beranda', 'active' => 'beranda'],
        ['href' => 'product.php', 'label' => 'Pasar Desain', 'active' => 'product'],
        ['href' => 'shoping-cart.php', 'label' => 'Pembelian', 'active' => 'cart'],
        ['href' => 'premium.php', 'label' => 'Fitur Unggulan', 'active' => 'premium'],
        ['href' => 'contact.php', 'label' => 'Hubungi Kami', 'active' => 'contact'],
    ];
    // Menu "Desainer Favorit" hanya untuk pembeli yang sudah login
    if ($is_user_logged_in) {
        array_splice($main_menu, 3, 0, [
            ['href' => 'desainer_favorit.php', 'label' => 'Desainer Favorit', 'active' => 'favorit'],
        ]);
    }
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
        ['href' => 'unggahan.php?view=auction-create#formUpload', 'icon' => 'zmdi-cloud-upload', 'label' => 'Buat Lelang'],
        ['href' => 'penjualan.php', 'icon' => 'zmdi-store', 'label' => 'Penjualan'],
        ['href' => 'pencairan.php', 'icon' => 'zmdi-balance-wallet', 'label' => 'Pencairan Dana'],
        ['href' => 'pesan.php', 'icon' => 'zmdi-comments', 'label' => 'Pesan', 'badge' => $unread_count],
        ['divider' => true],
        ['href' => 'logout.php', 'icon' => 'zmdi-power', 'label' => 'Logout'],
    ];
} elseif ($is_user_logged_in) {
    $account_links = [
        ['href' => 'profil.php', 'icon' => 'zmdi-account', 'label' => 'Profil Saya'],
        ['href' => 'riwayat.php', 'icon' => 'zmdi-receipt', 'label' => 'Riwayat Pembelian'],
        ['href' => 'desainer_favorit.php', 'icon' => 'zmdi-favorite', 'label' => 'Desainer Favorit'],
        ['href' => 'pesan.php', 'icon' => 'zmdi-comments', 'label' => 'Pesan', 'badge' => $unread_count],
        ['href' => 'premium.php', 'icon' => 'zmdi-star', 'label' => 'Upgrade Premium'],
        ['divider' => true],
        ['href' => 'logout.php', 'icon' => 'zmdi-power', 'label' => 'Logout'],
    ];
}
?>

<?php
// Cek apakah perlu tampilkan toast notif
$_show_toast = false;
if ($unread_count > 0 && !in_array($_cur, ['pesan.php', 'detail_chat.php'])) {
    $_last = $_SESSION['msg_toast_count'] ?? -1;
    if ($unread_count !== (int)$_last) {
        $_show_toast = true;
        $_SESSION['msg_toast_count'] = $unread_count;
    }
}
if ($unread_count === 0) unset($_SESSION['msg_toast_count']);
?>

<style>
.msg-badge {
    display: inline-flex; align-items: center; justify-content: center;
    background: #e53935; color: #fff; font-size: 10px; font-weight: 700;
    min-width: 17px; height: 17px; border-radius: 50px; padding: 0 4px;
    margin-left: 5px; vertical-align: middle; line-height: 1;
}
#msgToast {
    position: fixed; top: 80px; right: 20px; z-index: 99999;
    background: #fff; border-radius: 14px; padding: 14px 16px 14px 18px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.13); display: flex; align-items: center;
    gap: 12px; max-width: 290px; border-left: 4px solid #1591DC;
    animation: toastSlide .35s ease;
}
@keyframes toastSlide { from { transform:translateX(120px); opacity:0; } to { transform:translateX(0); opacity:1; } }
#msgToast .toast-close { background:none; border:none; cursor:pointer; color:#bbb; font-size:20px; padding:0; line-height:1; margin-left:auto; }
#msgToast .toast-close:hover { color:#555; }

/* THEME ALIGNMENT WITH ADMIN (OVERRIDE) */
.top-bar {
    background-color: #1591DC !important;
}
.left-top-bar,
.right-top-bar > a,
.account-dd .account-toggle {
    color: #ffffff !important;
}
.account-menu a {
    color: #333333 !important;
}
.account-menu a:hover {
    color: #1591DC !important;
    background: #f3f4ff !important;
}
.main-menu > li.active-menu > a {
    background-color: #1591DC !important;
    box-shadow: 0 10px 22px rgba(21, 145, 220, 0.18) !important;
}
.bg3 {
    background-color: #f6f7fb !important;
    border-top: 1px solid #e5e7eb !important;
}
.bg3 h4 {
    color: #111827 !important;
}
.bg3 a {
    color: #667085 !important;
    transition: color 0.2s ease !important;
}
.bg3 a:hover {
    color: #1591DC !important;
}
.bg3 p, .bg3 span {
    color: #667085 !important;
}
.bg3 .input1 {
    border-bottom: 2px solid #e5e7eb !important;
    color: #111827 !important;
    background: transparent !important;
}
.bg3 .focus-input1 {
    display: none !important;
}
.bg3 button, .bg3 .flex-c-m {
    background-color: #1591DC !important;
    color: #ffffff !important;
    border-radius: 8px !important;
}
.bg3 button:hover, .bg3 .flex-c-m:hover {
    background-color: #1178b8 !important;
    color: #ffffff !important;
}
.bg3 .p-t-40 {
    border-top: 1px solid #e5e7eb !important;
}
.btn-pay-black {
    background: #1591DC !important;
}
.btn-pay-black:hover {
    background: #1178b8 !important;
}
.btn-back-to-top {
    background-color: #1591DC !important;
}
.btn-back-to-top:hover {
    background-color: #1178b8 !important;
}
.icon-header-item:hover {
    background-color: #1591DC !important;
    color: #ffffff !important;
}
.role-pelanggan .role-chip {
    background: rgba(21, 145, 220, 0.24) !important;
    color: #e0f2fe !important;
}
</style>

<?php if ($_show_toast) { ?>
<div id="msgToast">
    <div style="font-size:26px; flex-shrink:0;">💬</div>
    <div>
        <div style="font-weight:700; color:#333; font-size:13.5px;">Pesan Baru!</div>
        <div style="color:#888; font-size:12px; margin:2px 0 6px;">
            <b><?php echo $unread_count; ?></b> pesan belum dibaca
        </div>
        <a href="pesan.php" style="font-size:12px; color:#4e8eff; font-weight:600; text-decoration:none;">
            Lihat Sekarang →
        </a>
    </div>
    <button class="toast-close" onclick="document.getElementById('msgToast').style.display='none'">×</button>
</div>
<script>setTimeout(function(){ var t=document.getElementById('msgToast'); if(t) t.style.display='none'; }, 6000);</script>
<?php } ?>

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
                                            <?php if (!empty($link['badge'])) { ?>
                                                <span class="msg-badge"><?php echo $link['badge']; ?></span>
                                            <?php } ?>
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
                                <a href="<?php echo htmlspecialchars($item['href']); ?>" style="position:relative;">
                                    <?php echo htmlspecialchars($item['label']); ?>
                                    <?php if (!empty($item['badge'])) { ?>
                                        <span class="msg-badge"><?php echo $item['badge']; ?></span>
                                    <?php } ?>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>

                <div class="wrap-icon-header flex-w flex-r-m"></div>
            </nav>
        </div>
    </div>

    <div class="wrap-header-mobile">
        <div class="logo-mobile"><a href="index.php"><img src="images/icons/dens.png?v=<?php echo time(); ?>" alt="IMG-LOGO"></a></div>
        <div class="wrap-icon-header flex-w flex-r-m m-r-15"></div>
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
