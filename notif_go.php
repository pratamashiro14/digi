<?php
// ==========================================================
// NOTIF_GO.PHP — Tandai notifikasi terbaca lalu arahkan ke tujuannya
//   notif_go.php?id=X    -> tandai 1 notif terbaca, redirect ke link-nya
//   notif_go.php?all=1   -> tandai SEMUA terbaca, redirect kembali
// ==========================================================
require_once __DIR__ . '/auth.php';
require_login();
include 'admin/koneksi.php';

$id_user = (int) current_id();

/** Hanya izinkan path lokal (cegah open-redirect ke domain luar). */
function tujuan_lokal_aman($url, $default = 'index.php') {
    $url = (string) $url;
    if ($url === '') return $default;
    if (preg_match('#^https?://#i', $url) || strpos($url, '//') === 0) return $default;
    return $url;
}

if (isset($_GET['all'])) {
    mysqli_query($koneksi, "UPDATE t_notifikasi SET dibaca=1 WHERE id_user=$id_user AND dibaca=0");
    $balik = tujuan_lokal_aman($_GET['go'] ?? ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    header('Location: ' . $balik);
    exit;
}

$id_notif = (int) ($_GET['id'] ?? 0);
$tujuan   = 'index.php';

if ($id_notif) {
    // Ambil url & pastikan notif milik user ini, lalu tandai terbaca.
    $stmt = mysqli_prepare($koneksi, "SELECT url FROM t_notifikasi WHERE id_notifikasi=? AND id_user=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ii', $id_notif, $id_user);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($row) {
        mysqli_query($koneksi, "UPDATE t_notifikasi SET dibaca=1 WHERE id_notifikasi=$id_notif AND id_user=$id_user");
        if (!empty($row['url'])) $tujuan = tujuan_lokal_aman($row['url']);
    }
}

header('Location: ' . $tujuan);
exit;
