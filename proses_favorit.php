<?php
// ==========================================================
// PROSES FAVORIT DESAINER (follow / unfollow)
// Khusus PEMBELI PREMIUM
// ==========================================================
require_once __DIR__ . '/auth.php';

// Wajib login sebagai pembeli
require_user();

include 'admin/koneksi.php';

// Wajib Premium
if (!is_premium_buyer()) {
    sweetalert_redirect('Fitur "Desainer Favorit" hanya untuk pembeli Premium. Yuk upgrade dulu!',
                        'premium.php', 'info', 'Khusus Premium');
}

$id_buyer    = (int) current_id();
$id_designer = isset($_POST['id_designer']) ? (int) $_POST['id_designer']
            : (isset($_GET['id_designer']) ? (int) $_GET['id_designer'] : 0);

// Tujuan redirect (default kembali ke toko). Cegah open-redirect ke domain luar.
$redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? ('toko_desainer.php?id=' . $id_designer);
if (preg_match('#^https?://#i', $redirect) || strpos($redirect, '//') === 0) {
    $redirect = 'toko_desainer.php?id=' . $id_designer;
}

if (!$id_designer) {
    sweetalert_redirect('Desainer tidak valid.', 'product.php', 'error', 'Gagal!');
}

// Pastikan target memang desainer
$cek = mysqli_query($koneksi, "SELECT id_user FROM t_user WHERE id_user = '$id_designer' AND role = 'designer' LIMIT 1");
if (!$cek || mysqli_num_rows($cek) === 0) {
    sweetalert_redirect('Desainer tidak ditemukan.', 'product.php', 'error', 'Gagal!');
}

// Toggle: kalau sudah ada -> hapus, kalau belum -> tambah
$q = mysqli_query($koneksi, "SELECT id_favorit FROM t_favorit_desainer
                             WHERE id_buyer = '$id_buyer' AND id_designer = '$id_designer' LIMIT 1");
if ($q && mysqli_num_rows($q) > 0) {
    mysqli_query($koneksi, "DELETE FROM t_favorit_desainer
                            WHERE id_buyer = '$id_buyer' AND id_designer = '$id_designer'");
} else {
    mysqli_query($koneksi, "INSERT INTO t_favorit_desainer (id_buyer, id_designer)
                            VALUES ('$id_buyer', '$id_designer')");
}

// Kembali ke halaman asal tanpa alert
header('Location: ' . $redirect);
exit;
