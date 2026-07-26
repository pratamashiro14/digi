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

// Notifikasi in-app untuk PEMBELI & DESAINER.
// Isi bisa beragam: karya baru dari desainer favorit (khusus pembeli premium),
// menang lelang (pemenang mana pun), pencairan disetujui (desainer), dll.
$show_notif_bell = false;
$notif_unread = 0;
$notif_list = [];
if (($is_user_logged_in || $is_designer_logged_in) && function_exists('current_id') && !empty($koneksi)) {
    require_once __DIR__ . '/notifikasi_helper.php';
    $show_notif_bell = true;
    $uid_notif    = (int) current_id();
    $notif_unread = notif_unread_count($koneksi, $uid_notif);
    $notif_list   = notif_recent($koneksi, $uid_notif, 8);
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
        ['href' => 'premium.php', 'label' => 'Fitur Unggulan', 'active' => 'premium'],
        ['href' => 'mou.php', 'label' => 'MOU', 'active' => 'designer-mou'],
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
        ['href' => 'mou.php', 'icon' => 'zmdi-assignment', 'label' => 'MOU'],
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
// Toast pesan: HANYA muncul saat ada pesan BARU (jumlah unread bertambah).
// - Membuka halaman pesan/detail dianggap "mengakui" jumlah unread saat ini,
//   sehingga toast tidak pop lagi untuk pesan yang sudah pernah dilihat di inbox.
// - Membaca 1 thread (unread turun) TIDAK memunculkan toast.
$_show_toast = false;
$_on_msg_page = in_array($_cur, ['pesan.php', 'detail_chat.php'], true);

if ($unread_count === 0) {
    unset($_SESSION['msg_toast_count']);
} elseif ($_on_msg_page) {
    // Sinkronkan baseline tanpa menampilkan toast.
    $_SESSION['msg_toast_count'] = $unread_count;
} else {
    $_last = (int) ($_SESSION['msg_toast_count'] ?? 0);
    if ($unread_count > $_last) {       // hanya bila benar-benar ada pesan baru
        $_show_toast = true;
    }
    $_SESSION['msg_toast_count'] = $unread_count;
}
?>

<style>
.msg-badge {
    display: inline-flex; align-items: center; justify-content: center;
    background: #e53935; color: #fff; font-size: 10px; font-weight: 700;
    min-width: 17px; height: 17px; border-radius: 50px; padding: 0 4px;
    margin-left: 5px; vertical-align: middle; line-height: 1;
}
/* Lonceng Notifikasi (pembeli premium) */
.notif-dd { position: relative; display: inline-flex; align-items: center; margin-right: 6px; }
.notif-toggle { position: relative; color: #fff !important; font-size: 20px; line-height: 1; padding: 0 6px; display: inline-flex; align-items: center; cursor: pointer; }
.notif-count { position: absolute; top: -6px; right: -3px; background: #e53935; color: #fff; font-size: 9px; font-weight: 700; min-width: 16px; height: 16px; border-radius: 50px; padding: 0 4px; display: flex; align-items: center; justify-content: center; line-height: 1; }
.notif-menu { position: absolute; top: 150%; right: 0; width: 320px; max-height: 430px; overflow-y: auto; background: #fff; border-radius: 12px; box-shadow: 0 12px 34px rgba(0,0,0,0.16); z-index: 10000; display: none; }
.notif-dd.open .notif-menu { display: block; }
.notif-head { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-bottom: 1px solid #f0f0f0; font-weight: 700; color: #333; font-size: 14px; }
.notif-head a { font-size: 12px; color: #1591DC !important; font-weight: 600; text-decoration: none; }
.notif-item { display: flex; gap: 10px; padding: 11px 16px; border-bottom: 1px solid #f5f5f5; text-decoration: none; color: #444 !important; transition: 0.15s; }
.notif-item:hover { background: #f7f9ff; }
.notif-item.is-unread { background: #eff6ff; }
.notif-item i { color: #1591DC; font-size: 18px; margin-top: 2px; flex-shrink: 0; }
.notif-item p { margin: 0; font-size: 13px; line-height: 1.4; color: #333; }
.notif-item small { color: #999; font-size: 11px; }
.notif-empty { padding: 26px 16px; text-align: center; color: #9ca3af; font-size: 13px; }
#msgToast {
    position: fixed; bottom: 24px; right: 24px; z-index: 99999;
    width: 320px; max-width: calc(100vw - 32px);
    background: #fff; border-radius: 16px; padding: 14px 16px;
    box-shadow: 0 16px 44px rgba(15,23,42,0.18); display: flex; align-items: flex-start;
    gap: 12px; border: 1px solid #eef0f5; overflow: hidden;
    transform: translateY(24px) scale(.97); opacity: 0;
    animation: msgToastIn .45s cubic-bezier(.22,1,.36,1) forwards;
}
#msgToast.hide { animation: msgToastOut .3s ease forwards; }
@keyframes msgToastIn  { to { transform: translateY(0) scale(1); opacity: 1; } }
@keyframes msgToastOut { to { transform: translateY(24px) scale(.97); opacity: 0; } }
#msgToast .mt-icon {
    width: 42px; height: 42px; border-radius: 13px; flex-shrink: 0; font-size: 20px;
    background: #eff6ff; display: flex; align-items: center; justify-content: center;
}
#msgToast .mt-body { flex: 1; min-width: 0; }
#msgToast .mt-title { font-weight: 700; color: #1e293b; font-size: 14px; margin: 0 0 2px; }
#msgToast .mt-text { color: #64748b; font-size: 12.5px; line-height: 1.4; margin: 0; }
#msgToast .mt-text b { color: #1591DC; }
#msgToast .mt-link { display: inline-block; margin-top: 8px; font-size: 12.5px; color: #1591DC; font-weight: 600; text-decoration: none; }
#msgToast .mt-link:hover { text-decoration: underline; }
#msgToast .mt-close { position: absolute; top: 9px; right: 9px; background: none; border: none; cursor: pointer;
    color: #cbd5e1; font-size: 18px; line-height: 1; padding: 3px; border-radius: 7px; transition: .15s; }
#msgToast .mt-close:hover { color: #64748b; background: #f1f5f9; }
#msgToast .mt-progress { position: absolute; left: 0; bottom: 0; height: 3px; width: 100%;
    background: linear-gradient(90deg, #4e8eff, #1591DC); transform-origin: left;
    animation: msgToastBar 5s linear forwards; }
@keyframes msgToastBar { from { transform: scaleX(1); } to { transform: scaleX(0); } }
@media (prefers-reduced-motion: reduce) { #msgToast, #msgToast .mt-progress { animation-duration: .01ms !important; } }

/* THEME ALIGNMENT WITH ADMIN (OVERRIDE) */
.wrap-menu-desktop, .wrap-header-mobile {
    background: linear-gradient(90deg, #1e293b 0%, #0f172a 100%) !important;
}
.header-v4 .container-menu-desktop {
    height: 76px !important;
}
.header-v4 .wrap-menu-desktop {
    top: 0 !important;
}
.main-menu > li > a {
    color: #ffffff !important;
}
.main-menu > li > a:hover {
    color: #e0f2fe !important;
}
.main-menu > li.active-menu > a {
    background-color: rgba(255, 255, 255, 0.2) !important;
    color: #ffffff !important;
    box-shadow: none !important;
    border-radius: 8px;
}
.logo img {
    background-color: #ffffff !important;
    padding: 6px 12px !important;
    border-radius: 8px !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
    max-height: 44px !important;
}
.logo-mobile img {
    background-color: #ffffff !important;
    padding: 4px 10px !important;
    border-radius: 6px !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
    max-height: 35px !important;
}
.hamburger-inner, .hamburger-inner:before, .hamburger-inner:after {
    background-color: #ffffff !important;
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

.limiter-menu-desktop {
    max-width: 1380px !important;
    margin-left: auto !important;
    margin-right: auto !important;
    width: 100% !important;
    padding-left: 24px !important;
    padding-right: 24px !important;
    gap: 16px !important;
}

@media (max-width: 1600px) {
    .limiter-menu-desktop {
        max-width: 1200px !important;
    }
}

/* Styling untuk icon header di navbar utama agar rapi & tidak acak-acakan */
.wrap-menu-desktop .wrap-icon-header {
    display: flex !important;
    flex-wrap: nowrap !important;
    align-items: center !important;
    justify-content: flex-end !important;
    flex-grow: 0 !important;
    flex-shrink: 0 !important;
    gap: 15px !important;
    padding-left: 10px;
}
.wrap-menu-desktop .role-chip {
    border: 1px solid rgba(255, 255, 255, 0.4) !important;
    color: #ffffff !important;
    background: rgba(255, 255, 255, 0.15) !important;
    padding: 4px 10px !important;
    border-radius: 50px !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    margin: 0 !important;
    height: 30px !important;
}
.wrap-menu-desktop .account-dd {
    position: relative !important;
    display: inline-flex !important;
    align-items: center !important;
    height: auto !important;
    padding: 0 !important;
    margin: 0 !important;
}
.wrap-menu-desktop .account-toggle {
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    color: #ffffff !important;
    font-weight: 500 !important;
    text-decoration: none !important;
    padding: 6px 12px !important;
    border-radius: 50px !important;
    background: rgba(255, 255, 255, 0.1) !important;
    transition: background 0.2s ease;
    height: 30px !important;
    min-height: 30px !important;
}
.wrap-menu-desktop .account-toggle:hover {
    background: rgba(255, 255, 255, 0.2) !important;
}
.wrap-menu-desktop .notif-dd {
    margin: 0 !important;
    display: inline-flex !important;
    align-items: center !important;
}
.wrap-menu-desktop .notif-toggle {
    color: #ffffff !important;
    font-size: 20px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 32px !important;
    height: 32px !important;
    border-radius: 50% !important;
    background: rgba(255, 255, 255, 0.1) !important;
    transition: background 0.2s ease;
    padding: 0 !important;
}
.wrap-menu-desktop .notif-toggle:hover {
    background: rgba(255, 255, 255, 0.2) !important;
}
.wrap-menu-desktop .notif-count {
    background: #e53935 !important;
    color: #ffffff !important;
    font-size: 9px !important;
    font-weight: 700 !important;
    min-width: 16px !important;
    height: 16px !important;
    border-radius: 50% !important;
    position: absolute !important;
    top: -4px !important;
    right: -4px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    border: 1px solid #1591DC !important;
}

/* ===== KESEIMBANGAN NAVBAR (jarak kiri = jarak kanan) =====
   HARUS ditaruh SETELAH blok .wrap-menu-desktop di atas: spesifisitasnya
   sama persis (0,2,0) dan sama-sama !important, jadi yang menang adalah
   yang ditulis belakangan. Sempat ditaruh di atas dan akibatnya gap serta
   penyembunyian role-chip di bawah ini tidak berlaku sama sekali.

   Masalah yang diperbaiki: container sudah di-center (margin auto), tapi
   isinya lebih lebar daripada container — .menu-desktop tidak bisa menyusut
   (min-width:auto bawaan flex item) dan blok akun dikunci flex-shrink:0,
   sehingga kelebihannya meluber ke KANAN saja: logo rapi di kiri, nama akun
   mentok tepi layar. Aturan di bawah membuat isinya muat lebih dulu, baru
   space-between menghasilkan jarak kiri & kanan yang sama. */
.wrap-menu-desktop .logo {
    /* gap 16px pada .limiter-menu-desktop sudah jadi pemisah; margin-right
       bawaan 34px cuma bikin berat sebelah. */
    margin-right: 0 !important;
    flex: 0 0 auto !important;
}

.wrap-menu-desktop .menu-desktop {
    flex: 1 1 auto !important;
    min-width: 0 !important;             /* kunci: izinkan menyusut, cegah meluber */
    justify-content: center !important;  /* menu duduk di tengah antara logo & akun */
}

.wrap-menu-desktop .main-menu {
    flex-wrap: nowrap !important;
    justify-content: center !important;
    gap: 2px !important;
}

.wrap-menu-desktop .main-menu > li > a {
    padding: 0 11px !important;          /* dirapatkan agar 8 menu muat tanpa meluber */
}

/* Nama akun panjang (mis. "DEN DEN MAULUDI APRIANSYAH") dulu mendorong
   navbar keluar layar karena blok kanan tidak boleh menyusut. Dibatasi
   lebarnya + ellipsis; nama penuh tetap terbaca lewat atribut title. */
.wrap-menu-desktop .account-toggle {
    max-width: 220px !important;
    overflow: hidden !important;
}

.wrap-menu-desktop .account-toggle > i {
    flex-shrink: 0 !important;
}

.wrap-menu-desktop .account-name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
}

/* Ambang 1600px HARUS sama dengan media query .limiter-menu-desktop di atas:
   di bawah 1600px container menyusut 1380 -> 1200px, jadi di situlah isinya
   wajib ikut dirapatkan. Sempat dipasang di 1400px, akibatnya rentang
   1400-1600px punya container 1200px tapi menu masih ukuran lebar -> menu
   meluber ke dua arah dan menimpa logo serta blok akun. */
@media (max-width: 1600px) {
    .wrap-menu-desktop .main-menu > li > a {
        padding: 0 8px !important;
        font-size: 12.5px !important;
    }
    .wrap-menu-desktop .account-toggle {
        max-width: 130px !important;
    }
    .wrap-menu-desktop .wrap-icon-header {
        gap: 10px !important;
    }
}

/* Laptop kecil. Ruangnya tidak cukup untuk nama akun, jadi tinggal ikon +
   caret; nama lengkap tetap terbaca sebagai tooltip lewat atribut title
   pada .account-toggle. */
@media (max-width: 1200px) {
    .limiter-menu-desktop {
        padding-left: 16px !important;
        padding-right: 16px !important;
        gap: 8px !important;
    }
    .wrap-menu-desktop .main-menu > li > a {
        padding: 0 7px !important;
        font-size: 12px !important;
    }
    .wrap-menu-desktop .wrap-icon-header {
        gap: 8px !important;
        padding-left: 0 !important;
    }
    .wrap-menu-desktop .account-toggle {
        max-width: none !important;
    }
    .wrap-menu-desktop .account-name {
        display: none !important;
    }
    /* Logo dens.png rasionya sangat lebar (1448x282) — ikut dikecilkan,
       kalau tidak sendirian memakan ~190px dari ruang navbar. */
    .wrap-menu-desktop .logo img {
        max-height: 38px !important;
    }
}

/* Paling sempit sebelum menu mobile mengambil alih (<=991px). Chip role
   dilepas demi ruang; rolenya tetap terbaca di menu mobile & halaman profil. */
@media (max-width: 1100px) {
    .wrap-menu-desktop .role-chip {
        display: none !important;
    }
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
<div id="msgToast" role="status" aria-live="polite">
    <div class="mt-icon">💬</div>
    <div class="mt-body">
        <p class="mt-title">Pesan Baru</p>
        <p class="mt-text"><b><?php echo $unread_count; ?></b> pesan belum dibaca</p>
        <a href="pesan.php" class="mt-link">Lihat sekarang →</a>
    </div>
    <button class="mt-close" onclick="dismissMsgToast()" aria-label="Tutup">×</button>
    <span class="mt-progress"></span>
</div>
<script>
(function () {
    var t = document.getElementById('msgToast');
    if (!t) return;
    var timer;
    window.dismissMsgToast = function () {
        clearTimeout(timer);
        t.classList.add('hide');
        setTimeout(function () { if (t && t.parentNode) t.parentNode.removeChild(t); }, 320);
    };
    timer = setTimeout(window.dismissMsgToast, 5000);
})();
</script>
<?php } ?>

<header class="header-v4 role-<?php echo htmlspecialchars($role); ?>">
    <div class="container-menu-desktop">
        <!-- Top bar dihilangkan dan isinya dipindah ke wrap-icon-header -->

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

                <div class="wrap-icon-header flex-w flex-r-m">
                    <?php if ($is_admin_logged_in || $is_designer_logged_in || $is_user_logged_in) { ?>
                        <?php if ($show_notif_bell) { ?>
                        <div class="notif-dd" id="notifDD">
                            <a href="#" class="notif-toggle" onclick="toggleNotif(event)" title="Notifikasi">
                                <i class="zmdi zmdi-notifications"></i>
                                <?php if ($notif_unread > 0) { ?>
                                    <span class="notif-count"><?php echo $notif_unread > 9 ? '9+' : $notif_unread; ?></span>
                                <?php } ?>
                            </a>
                            <div class="notif-menu">
                                <div class="notif-head">
                                    <span>Notifikasi</span>
                                    <?php if ($notif_unread > 0) { ?>
                                        <a href="notif_go.php?all=1&go=<?php echo urlencode($_cur); ?>">Tandai semua dibaca</a>
                                    <?php } ?>
                                </div>
                                <?php if (count($notif_list) > 0) { ?>
                                    <?php foreach ($notif_list as $n) { ?>
                                        <a href="notif_go.php?id=<?php echo (int) $n['id_notifikasi']; ?>"
                                           class="notif-item <?php echo $n['dibaca'] ? '' : 'is-unread'; ?>">
                                            <i class="zmdi zmdi-collection-image"></i>
                                            <div>
                                                <p><?php echo htmlspecialchars($n['pesan']); ?></p>
                                                <small><?php echo date('d M Y, H:i', strtotime($n['created_at'])); ?></small>
                                            </div>
                                        </a>
                                    <?php } ?>
                                <?php } else { ?>
                                    <div class="notif-empty"><i class="zmdi zmdi-notifications-none" style="font-size:28px; display:block; margin-bottom:8px;"></i>Belum ada notifikasi.</div>
                                <?php } ?>
                                <a href="notifikasi.php" style="display:block; text-align:center; padding:11px; font-size:13px; font-weight:600; color:#1591DC; text-decoration:none; border-top:1px solid #f0f0f0;">Lihat semua notifikasi</a>
                            </div>
                        </div>
                        <?php } ?>
                        <span class="role-chip"><?php echo htmlspecialchars($role_label); ?></span>
                        <div class="account-dd">
                            <a href="#" class="account-toggle" onclick="return false;" title="<?php echo htmlspecialchars($display_name); ?>">
                                <i class="zmdi zmdi-account"></i>
                                <span class="account-name"><?php echo htmlspecialchars($display_name); ?></span>
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
                        <a href="login.php" class="flex-c-m trans-04 p-lr-15" style="color: #fff; font-weight: 500;">Masuk / Daftar</a>
                    <?php } ?>
                </div>
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
                        <?php if ($show_notif_bell) { ?>
                            <a href="notifikasi.php" class="flex-c-m p-lr-10 trans-04">Notifikasi<?php echo $notif_unread > 0 ? ' (' . $notif_unread . ')' : ''; ?></a>
                        <?php } ?>
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

<?php if ($show_notif_bell) { ?>
<script>
    // Buka/tutup dropdown notifikasi + tutup saat klik di luar.
    function toggleNotif(e) {
        e.preventDefault(); e.stopPropagation();
        var d = document.getElementById('notifDD');
        if (d) d.classList.toggle('open');
    }
    document.addEventListener('click', function(e) {
        var d = document.getElementById('notifDD');
        if (d && !d.contains(e.target)) d.classList.remove('open');
    });
</script>
<?php } ?>
