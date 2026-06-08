<?php
/**
 * NAVBAR HEADER HELPER
 * File ini berisi PHP untuk header navbar yang konsisten di semua halaman
 * 
 * Gunakan di awal setiap .php file dengan:
 * <?php 
 * session_start(); 
 * include 'admin/koneksi.php';
 * // Include code di bawah ini
 * ?>
 */

// Cek Login Status
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

// Function untuk menampilkan link My Account yang tepat
function getAccountLink() {
    global $is_designer_logged_in, $nama_desainer, $is_user_logged_in, $nama_user;
    
    if ($is_designer_logged_in) {
        return '<a href="profil_desainer.php" class="flex-c-m trans-04 p-lr-25">Halo, ' . htmlspecialchars($nama_desainer) . ' (Desainer)</a>
                <a href="logout.php" class="flex-c-m trans-04 p-lr-25">Logout</a>';
    } elseif ($is_user_logged_in) {
        return '<a href="profil.php" class="flex-c-m trans-04 p-lr-25">Halo, ' . htmlspecialchars($nama_user) . '</a>
                <a href="logout.php" class="flex-c-m trans-04 p-lr-25">Logout</a>';
    } else {
        return '<a href="login.php" class="flex-c-m trans-04 p-lr-25">My Account</a>';
    }
}

// Function untuk menampilkan link My Account mobile
function getAccountLinkMobile() {
    global $is_designer_logged_in, $nama_desainer, $is_user_logged_in, $nama_user;
    
    if ($is_designer_logged_in) {
        return '<a href="profil_desainer.php" class="flex-c-m p-lr-10 trans-04">Halo, ' . htmlspecialchars($nama_desainer) . '</a>
                <a href="logout.php" class="flex-c-m p-lr-10 trans-04">Logout</a>';
    } elseif ($is_user_logged_in) {
        return '<a href="profil.php" class="flex-c-m p-lr-10 trans-04">Halo, ' . htmlspecialchars($nama_user) . '</a>
                <a href="logout.php" class="flex-c-m p-lr-10 trans-04">Logout</a>';
    } else {
        return '<a href="login.php" class="flex-c-m p-lr-10 trans-04">My Account</a>';
    }
}
?>
