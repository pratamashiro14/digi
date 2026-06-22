<?php
// ==========================================================
// NOTIFIKASI.PHP — Halaman daftar notifikasi (pembeli premium)
// Fitur premium "Notifikasi Custom". Membuka halaman ini menandai
// semua notifikasi sebagai sudah dibaca.
// ==========================================================
require_once __DIR__ . '/auth.php';
require_login(); // pembeli ATAU desainer — semua punya kotak notifikasi
include 'admin/koneksi.php';
require_once __DIR__ . '/notifikasi_helper.php';

$id_user = (int) current_id();

// Ambil daftar sebelum menandai terbaca (agar yang baru tetap tersorot di tampilan ini).
$daftar = notif_recent($koneksi, $id_user, 50);

// Tandai semua sebagai dibaca saat halaman dibuka.
mysqli_query($koneksi, "UPDATE t_notifikasi SET is_read=1 WHERE id_user=$id_user AND is_read=0");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Notifikasi - DENS CREATIVE</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="images/icons/favicon.png"/>
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/iconic/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/css-hamburgers/hamburgers.min.css">
    <link rel="stylesheet" type="text/css" href="css/util.css">
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <link rel="stylesheet" type="text/css" href="css/account-ui.css">
    <style>
        .notif-wrap { max-width: 760px; margin: 40px auto 70px; padding: 0 15px; font-family: 'Poppins', sans-serif; }
        .notif-wrap h1 { font-size: 28px; font-weight: 800; color: #1f2937; margin: 0 0 6px; }
        .notif-wrap > p { color: #6b7280; margin: 0 0 24px; }
        .notif-row { display: flex; gap: 14px; align-items: flex-start; background: #fff; border: 1px solid #eee;
            border-radius: 12px; padding: 16px 18px; margin-bottom: 12px; text-decoration: none; transition: 0.18s; }
        .notif-row:hover { box-shadow: 0 8px 22px rgba(0,0,0,0.06); transform: translateY(-2px); }
        .notif-row.is-unread { border-left: 4px solid #1591DC; background: #f4f9ff; }
        .notif-row i { font-size: 22px; color: #1591DC; margin-top: 2px; }
        .notif-row p { margin: 0; color: #333; font-size: 14px; line-height: 1.5; }
        .notif-row small { color: #9ca3af; font-size: 12px; }
        .notif-kosong { text-align: center; color: #9ca3af; padding: 70px 20px; }
        .notif-kosong i { font-size: 54px; display: block; margin-bottom: 14px; }
    </style>
</head>
<body class="animsition account-page">

    <?php $active_page = 'notifikasi'; include 'navbar.php'; ?>

    <div class="notif-wrap">
        <h1>Notifikasi</h1>
        <p>Update terbaru dari desainer favoritmu dan aktivitas akun.</p>

        <?php if (count($daftar) > 0) { ?>
            <?php foreach ($daftar as $n) {
                $href = !empty($n['url']) ? 'notif_go.php?id=' . (int) $n['id_notifikasi'] : '#';
            ?>
                <a href="<?php echo htmlspecialchars($href); ?>" class="notif-row <?php echo $n['dibaca'] ? '' : 'is-unread'; ?>">
                    <i class="zmdi zmdi-collection-image"></i>
                    <div>
                        <p><?php echo htmlspecialchars($n['pesan']); ?></p>
                        <small><i class="fa fa-clock-o"></i> <?php echo date('d M Y, H:i', strtotime($n['created_at'])); ?></small>
                    </div>
                </a>
            <?php } ?>
        <?php } else { ?>
            <div class="notif-kosong">
                <i class="zmdi zmdi-notifications-none"></i>
                <h3>Belum ada notifikasi</h3>
                <p>Favoritkan desainer di Pasar Desain agar kamu dapat kabar saat mereka mengunggah karya baru.</p>
            </div>
        <?php } ?>
    </div>

    <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
