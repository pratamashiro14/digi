<?php
// ==========================================================
// DAFTAR DESAINER FAVORIT (milik pembeli)
// ==========================================================
require_once __DIR__ . '/auth.php';
require_user();
include 'admin/koneksi.php';

$id_buyer = (int) current_id();
$is_premium = is_premium_buyer();

$q = mysqli_query($koneksi, "SELECT f.id_designer, f.created_at,
                                    u.nama, u.foto_profil, u.foto, u.status_member, u.status_verifikasi,
                                    (SELECT COUNT(*) FROM t_design d
                                     WHERE d.id_designer = u.id_user AND d.status IN ('approved','sold')) AS total_karya
                             FROM t_favorit_desainer f
                             JOIN t_user u ON f.id_designer = u.id_user
                             WHERE f.id_buyer = '$id_buyer'
                             ORDER BY f.created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Desainer Favorit - DENS CREATIVE</title>
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
        .fav-wrap { max-width: 1000px; margin: 40px auto 70px; padding: 0 15px; font-family: 'Poppins', sans-serif; }
        .fav-head h1 { font-size: 30px; font-weight: 800; color: #1f2937; margin: 0 0 6px; }
        .fav-head p { color: #6b7280; margin: 0 0 25px; }

        .fav-premium-note {
            background: #fff8e1; border: 1px solid #ffe082; color: #8a6d00;
            padding: 14px 18px; border-radius: 12px; margin-bottom: 25px; font-size: 14px;
            display: flex; align-items: center; gap: 10px;
        }
        .fav-premium-note a { color: #b45309; font-weight: 700; text-decoration: underline; }

        .fav-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        @media (max-width: 768px) { .fav-grid { grid-template-columns: repeat(1, 1fr); } }

        .fav-card { background: #fff; border: 1px solid #eee; border-radius: 14px; padding: 22px; text-align: center; transition: 0.25s; }
        .fav-card:hover { box-shadow: 0 8px 22px rgba(0,0,0,0.08); transform: translateY(-3px); }
        .fav-avatar { width: 84px; height: 84px; border-radius: 50%; object-fit: cover; border: 3px solid #eef2ff; margin-bottom: 12px; }
        .fav-nama { font-size: 17px; font-weight: 700; color: #1f2937; margin-bottom: 4px; }
        .fav-badge { font-size: 10px; font-weight: 700; padding: 3px 9px; border-radius: 50px; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-premium { background: #fff3cd; color: #856404; }
        .badge-verified { background: #e0f2fe; color: #075985; }
        .fav-karya { color: #6b7280; font-size: 13px; margin: 10px 0 16px; }

        .fav-actions { display: flex; gap: 8px; }
        .fav-btn { flex: 1; padding: 10px; border-radius: 50px; font-weight: 600; font-size: 13px; text-decoration: none; border: none; cursor: pointer; transition: 0.2s; }
        .fav-visit { background: #1591DC; color: #fff; }
        .fav-visit:hover { background: #1178b8; color: #fff; }
        .fav-unfollow { background: #f3f4f6; color: #6b7280; }
        .fav-unfollow:hover { background: #fee2e2; color: #b91c1c; }

        .fav-empty { text-align: center; padding: 70px 20px; color: #9ca3af; }
        .fav-empty i { font-size: 52px; margin-bottom: 14px; }
        .fav-empty a { display: inline-block; margin-top: 12px; background: #1591DC; color: #fff; padding: 12px 26px; border-radius: 50px; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body class="animsition account-page">

    <?php
    $active_page = 'favorit';
    include 'navbar.php';
    ?>

    <div class="fav-wrap">
        <div class="fav-head">
            <h1>Desainer Favorit</h1>
            <p>Desainer yang kamu ikuti. Kunjungi tokonya kapan saja.</p>
        </div>

        <?php if (!$is_premium) { ?>
            <div class="fav-premium-note">
                <i class="fa fa-star"></i>
                <div>Memfavoritkan desainer baru adalah fitur <b>Premium</b>.
                    <a href="premium.php">Upgrade sekarang</a> untuk mengikuti lebih banyak desainer.</div>
            </div>
        <?php } ?>

        <?php if ($q && mysqli_num_rows($q) > 0) { ?>
            <div class="fav-grid">
                <?php while ($d = mysqli_fetch_assoc($q)) {
                    $foto = !empty($d['foto_profil']) ? $d['foto_profil'] : ($d['foto'] ?? '');
                    $avatar = $foto ? 'admin/uploads/' . $foto : 'images/icons/logo-01.png';
                    $is_prem = strtolower($d['status_member'] ?? '') === 'premium';
                    $is_verif = ($d['status_verifikasi'] ?? '') === 'verified';
                ?>
                    <div class="fav-card">
                        <img src="<?php echo htmlspecialchars($avatar); ?>" class="fav-avatar"
                             onerror="this.src='images/icons/logo-01.png'; this.onerror=null;" alt="Avatar">
                        <div class="fav-nama"><?php echo htmlspecialchars($d['nama']); ?></div>
                        <?php if ($is_prem) { ?>
                            <span class="fav-badge badge-premium"><i class="fa fa-star"></i> Premium</span>
                        <?php } elseif ($is_verif) { ?>
                            <span class="fav-badge badge-verified"><i class="fa fa-check-circle"></i> Terverifikasi</span>
                        <?php } ?>
                        <div class="fav-karya"><?php echo (int) $d['total_karya']; ?> karya</div>

                        <div class="fav-actions">
                            <a href="toko_desainer.php?id=<?php echo $d['id_designer']; ?>" class="fav-btn fav-visit">Kunjungi Toko</a>
                            <form action="proses_favorit.php" method="POST" style="flex:1; margin:0;">
                                <input type="hidden" name="id_designer" value="<?php echo $d['id_designer']; ?>">
                                <input type="hidden" name="redirect" value="desainer_favorit.php">
                                <button type="submit" class="fav-btn fav-unfollow" style="width:100%;">Hapus</button>
                            </form>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } else { ?>
            <div class="fav-empty">
                <i class="zmdi zmdi-favorite-outline"></i>
                <h3>Belum ada desainer favorit</h3>
                <p>Jelajahi Pasar Desain, buka toko desainer, lalu favoritkan yang kamu suka.</p>
                <a href="product.php">Jelajahi Pasar Desain</a>
            </div>
        <?php } ?>
    </div>

    <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
