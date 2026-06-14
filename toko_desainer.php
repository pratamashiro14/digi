<?php
// ==========================================================
// TOKO / PROFIL DESAINER (ETALASE PUBLIK ala Shopee)
// - Bisa dibuka siapa saja
// - Tombol "Favoritkan" hanya untuk PEMBELI PREMIUM
// ==========================================================
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';

// 1. Ambil & validasi id desainer
$id_designer = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id_designer) {
    sweetalert_redirect('Toko desainer tidak ditemukan.', 'product.php', 'error', 'Gagal!');
}

$q_designer = mysqli_query($koneksi, "SELECT * FROM t_user WHERE id_user = '$id_designer' AND role = 'designer' LIMIT 1");
$designer = $q_designer ? mysqli_fetch_assoc($q_designer) : null;
if (!$designer) {
    sweetalert_redirect('Desainer tidak ditemukan.', 'product.php', 'error', 'Tidak Ada');
}

// 2. Status desainer
$designer_premium  = user_is_premium($id_designer);
$designer_verified = ($designer['status_verifikasi'] ?? '') === 'verified';

// 3. Statistik toko
$total_karya = (int) (mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COUNT(*) c FROM t_design WHERE id_designer = '$id_designer' AND status IN ('approved','sold')"))['c'] ?? 0);
$total_pengikut = (int) (mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COUNT(*) c FROM t_favorit_desainer WHERE id_designer = '$id_designer'"))['c'] ?? 0);

// 4. Kumpulan id_design yang SUDAH TERJUAL (ada transaksi berhasil)
$sold_ids = [];
$q_sold = mysqli_query($koneksi, "SELECT DISTINCT t.id_design FROM t_transaksi t
                                  JOIN t_design d ON t.id_design = d.id_design
                                  WHERE d.id_designer = '$id_designer'
                                    AND t.status_pembayaran IN ('berhasil','settlement','capture')");
while ($q_sold && $r = mysqli_fetch_assoc($q_sold)) { $sold_ids[(int) $r['id_design']] = true; }

// 5. Status favorit pembeli yang sedang login
$viewer_is_premium_buyer = is_premium_buyer();
$sudah_favorit = false;
if (is_user_login()) {
    $uid = (int) current_id();
    $q_cek = mysqli_query($koneksi, "SELECT 1 FROM t_favorit_desainer
                                     WHERE id_buyer = '$uid' AND id_designer = '$id_designer' LIMIT 1");
    $sudah_favorit = $q_cek && mysqli_num_rows($q_cek) > 0;
}

// 6. Galeri karya (aktif didahulukan, lalu yang berakhir)
$q_karya = mysqli_query($koneksi, "SELECT * FROM t_design
                                   WHERE id_designer = '$id_designer' AND status IN ('approved','sold')
                                   ORDER BY (waktu_berakhir IS NOT NULL AND waktu_berakhir < NOW()) ASC, id_design DESC");

// 7. Avatar desainer
$avatar_fallback = 'images/icons/logo-01.png';
$foto_desainer = !empty($designer['foto_profil']) ? $designer['foto_profil'] : ($designer['foto'] ?? '');
$avatar_src = $foto_desainer ? 'admin/uploads/' . $foto_desainer : $avatar_fallback;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Toko <?php echo htmlspecialchars($designer['nama']); ?> - DENS CREATIVE</title>
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
        .toko-wrap { max-width: 1100px; margin: 30px auto 60px; padding: 0 15px; font-family: 'Poppins', sans-serif; }

        /* Header toko */
        .toko-header {
            background: linear-gradient(120deg, #1591DC, #6b4eff);
            border-radius: 16px; padding: 30px; color: #fff;
            display: flex; align-items: center; gap: 25px; flex-wrap: wrap;
            box-shadow: 0 10px 30px rgba(21,145,220,0.25);
        }
        .toko-avatar {
            width: 110px; height: 110px; border-radius: 50%; object-fit: cover;
            border: 4px solid rgba(255,255,255,0.7); background: #fff; flex-shrink: 0;
        }
        .toko-info { flex: 1; min-width: 220px; }
        .toko-nama { font-size: 28px; font-weight: 800; margin: 0 0 6px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .toko-badge {
            font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 50px;
            text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 5px;
        }
        .badge-premium { background: #ffd54f; color: #6b4b00; }
        .badge-verified { background: rgba(255,255,255,0.25); color: #fff; border: 1px solid rgba(255,255,255,0.5); }
        .toko-stats { display: flex; gap: 28px; margin-top: 14px; }
        .toko-stat b { font-size: 20px; display: block; line-height: 1; }
        .toko-stat span { font-size: 12px; opacity: 0.85; }

        .toko-actions { display: flex; flex-direction: column; gap: 10px; min-width: 190px; }
        .btn-toko {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 12px 18px; border-radius: 50px; font-weight: 700; font-size: 14px;
            text-decoration: none; border: none; cursor: pointer; transition: 0.25s; width: 100%;
        }
        .btn-fav-outline { background: #fff; color: #1591DC; }
        .btn-fav-outline:hover { background: #f0f6ff; color: #1178b8; }
        .btn-fav-active { background: #e53935; color: #fff; }
        .btn-fav-active:hover { background: #c62828; color: #fff; }
        .btn-fav-premium { background: #ffd54f; color: #6b4b00; }
        .btn-fav-premium:hover { background: #ffca28; color: #5a3f00; }
        .btn-chat-toko { background: rgba(255,255,255,0.18); color: #fff; border: 1px solid rgba(255,255,255,0.6); }
        .btn-chat-toko:hover { background: rgba(255,255,255,0.3); color: #fff; }

        /* Galeri */
        .toko-section-title { font-size: 20px; font-weight: 800; color: #1f2937; margin: 35px 0 18px; }
        .toko-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        @media (max-width: 992px) { .toko-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 768px) { .toko-grid { grid-template-columns: repeat(2, 1fr); } .toko-header { text-align: center; justify-content: center; } .toko-info { text-align: center; } .toko-nama, .toko-stats { justify-content: center; } }

        .karya-card { background: #fff; border: 1px solid #eee; border-radius: 12px; overflow: hidden; transition: 0.25s; }
        .karya-card:hover { box-shadow: 0 8px 22px rgba(0,0,0,0.10); transform: translateY(-3px); }
        .karya-pic { position: relative; }
        .karya-pic img { width: 100%; aspect-ratio: 4/5; object-fit: cover; display: block; }
        .karya-badge {
            position: absolute; top: 10px; left: 10px; font-size: 11px; font-weight: 800;
            padding: 5px 12px; border-radius: 6px; letter-spacing: 0.5px; color: #fff;
            box-shadow: 0 3px 8px rgba(0,0,0,0.25);
        }
        .badge-sold { background: #16a34a; }
        .badge-berakhir { background: #6b7280; }
        .badge-aktif { background: #1591DC; }
        .karya-body { padding: 12px 14px 16px; }
        .karya-judul { font-size: 14px; font-weight: 700; color: #1f2937; display: block; margin-bottom: 4px; text-decoration: none; }
        .karya-judul:hover { color: #1591DC; }
        .karya-harga { font-size: 14px; font-weight: 600; color: #1591DC; }

        .toko-empty { text-align: center; padding: 60px 20px; color: #9ca3af; }
        .toko-empty i { font-size: 46px; margin-bottom: 12px; }
    </style>
</head>
<body class="animsition account-page">

    <?php
    $active_page = 'product';
    include 'navbar.php';
    ?>

    <div class="toko-wrap">

        <!-- HEADER TOKO -->
        <div class="toko-header">
            <img src="<?php echo htmlspecialchars($avatar_src); ?>" class="toko-avatar"
                 onerror="this.src='images/icons/logo-01.png'; this.onerror=null;" alt="Avatar">

            <div class="toko-info">
                <h1 class="toko-nama">
                    <?php echo htmlspecialchars($designer['nama']); ?>
                    <?php if ($designer_premium) { ?>
                        <span class="toko-badge badge-premium"><i class="fa fa-star"></i> Verified Premium Designer</span>
                    <?php } elseif ($designer_verified) { ?>
                        <span class="toko-badge badge-verified"><i class="fa fa-check-circle"></i> Terverifikasi</span>
                    <?php } ?>
                </h1>
                <div class="toko-stats">
                    <div class="toko-stat"><b><?php echo $total_karya; ?></b><span>Karya</span></div>
                    <div class="toko-stat"><b><?php echo $total_pengikut; ?></b><span>Pengikut</span></div>
                </div>
            </div>

            <div class="toko-actions">
                <?php if (is_designer_login() || is_admin_login()) { ?>
                    <!-- Desainer/Admin tidak memfavoritkan -->
                <?php } elseif (!is_user_login()) { ?>
                    <a href="login.php" class="btn-toko btn-fav-outline"><i class="fa fa-heart-o"></i> Favoritkan</a>
                <?php } elseif (!$viewer_is_premium_buyer) { ?>
                    <a href="premium.php" class="btn-toko btn-fav-premium" title="Fitur khusus pembeli Premium">
                        <i class="fa fa-star"></i> Favoritkan (Premium)
                    </a>
                <?php } else { ?>
                    <form action="proses_favorit.php" method="POST" style="margin:0;">
                        <input type="hidden" name="id_designer" value="<?php echo $id_designer; ?>">
                        <input type="hidden" name="redirect" value="toko_desainer.php?id=<?php echo $id_designer; ?>">
                        <button type="submit" class="btn-toko <?php echo $sudah_favorit ? 'btn-fav-active' : 'btn-fav-outline'; ?>">
                            <i class="fa <?php echo $sudah_favorit ? 'fa-heart' : 'fa-heart-o'; ?>"></i>
                            <?php echo $sudah_favorit ? 'Difavoritkan' : 'Favoritkan'; ?>
                        </button>
                    </form>
                <?php } ?>

                <?php if (!is_designer_login() && !is_admin_login()) { ?>
                    <a href="detail_chat.php?tujuan=<?php echo $id_designer; ?>" class="btn-toko btn-chat-toko">
                        <i class="fa fa-comments"></i> Hubungi Desainer
                    </a>
                <?php } ?>
            </div>
        </div>

        <!-- GALERI KARYA -->
        <h2 class="toko-section-title">Karya Desainer</h2>

        <?php if ($q_karya && mysqli_num_rows($q_karya) > 0) { ?>
            <div class="toko-grid">
                <?php while ($k = mysqli_fetch_assoc($q_karya)) {
                    $is_sold    = isset($sold_ids[(int) $k['id_design']]);
                    $is_expired = !empty($k['waktu_berakhir']) && strtotime($k['waktu_berakhir']) < time();
                ?>
                    <div class="karya-card">
                        <div class="karya-pic">
                            <img src="admin/uploads/<?php echo htmlspecialchars($k['gambar']); ?>"
                                 onerror="this.src='images/item-cart-04.jpg'; this.onerror=null;" alt="<?php echo htmlspecialchars($k['judul']); ?>">
                            <?php if ($is_sold) { ?>
                                <span class="karya-badge badge-sold">SOLD</span>
                            <?php } elseif ($is_expired) { ?>
                                <span class="karya-badge badge-berakhir">BERAKHIR</span>
                            <?php } else { ?>
                                <span class="karya-badge badge-aktif">AKTIF</span>
                            <?php } ?>
                        </div>
                        <div class="karya-body">
                            <a href="product-detail.php?id=<?php echo $k['id_design']; ?>" class="karya-judul">
                                <?php echo htmlspecialchars($k['judul']); ?>
                            </a>
                            <span class="karya-harga">Rp <?php echo number_format($k['harga_awal'], 0, ',', '.'); ?></span>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } else { ?>
            <div class="toko-empty">
                <i class="zmdi zmdi-collection-image-o"></i>
                <h3>Desainer ini belum punya karya</h3>
                <p>Belum ada karya yang dipublikasikan.</p>
            </div>
        <?php } ?>

    </div>

    <!-- Footer -->
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

    <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
