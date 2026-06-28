<?php
require_once __DIR__ . '/auth.php';
// 1. HUBUNGKAN KE DATABASE
// Pastikan path ini benar (sesuai struktur folder kamu)
include 'admin/koneksi.php';
// Aturan tenggat pembayaran pending
require_once __DIR__ . '/pembayaran_helper.php';

// 2. CEK LOGIN KHUSUS PEMBELI
require_user();

// Gagalkan pending yang sudah lewat tenggat agar status tampil akurat.
expire_pending_kedaluwarsa($koneksi);

$id_buyer = current_id();

// 3. QUERY DATABASE
// Mengambil data transaksi digabung (JOIN) dengan data desain.
// sisa_detik & batas_fmt dihitung di MySQL (jam server yang sama dengan
// pembuat tenggat) agar countdown kebal selisih zona waktu PHP/browser.
$batas_sql = batas_pembayaran_sql('t');
$query = "SELECT t.*, d.judul, d.gambar, d.file_master,
                 TIMESTAMPDIFF(SECOND, NOW(), $batas_sql) AS sisa_detik,
                 DATE_FORMAT($batas_sql, '%d %b %Y, %H:%i') AS batas_fmt
          FROM t_transaksi t
          JOIN t_design d ON t.id_design = d.id_design
          WHERE t.id_buyer = '$id_buyer'
          ORDER BY t.tanggal_transaksi DESC";

$result = mysqli_query($koneksi, $query);

if (!$result) {
    die("Error Query: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Riwayat Pembelian</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/iconic/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/css-hamburgers/hamburgers.min.css">
    <link rel="stylesheet" type="text/css" href="css/util.css">
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <link rel="stylesheet" type="text/css" href="css/account-ui.css?v=<?php echo filemtime(__DIR__ . '/css/account-ui.css'); ?>">

    <style>
        .container-riwayat { width: 90%; max-width: 1000px; margin: 50px auto; font-family: 'Poppins', sans-serif; }
        .table-riwayat { width: 100%; border-collapse: separate; border-spacing: 0 15px; }
        .table-riwayat thead th { border: none; padding: 15px; background: #333; color: #fff; font-weight: 600; text-transform: uppercase; font-size: 14px; }
        .table-riwayat tbody tr { background: #fff; box-shadow: 0 5px 10px rgba(0,0,0,0.05); }
        .table-riwayat td { padding: 20px; vertical-align: middle; border-top: 1px solid #f0f0f0; border-bottom: 1px solid #f0f0f0; }
        .table-riwayat td:first-child { border-left: 1px solid #f0f0f0; border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .table-riwayat td:last-child { border-right: 1px solid #f0f0f0; border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

        /* Badge Status Design */
        .badge-status { padding: 8px 15px; border-radius: 50px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .bg-sukses { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; } /* Hijau Soft */
        .bg-pending { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; } /* Kuning Soft */
        .bg-gagal { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; } /* Merah Soft */

        /* Button Actions */
        .btn-aksi { padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; }
        
        .btn-download { background: #007bff; color: white; border: none; }
        .btn-download:hover { background: #0056b3; color: white; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,123,255,0.3); }
        
        .btn-bayar { background: #ffc107; color: #333; border: none; }
        .btn-bayar:hover { background: #e0a800; color: #000; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(255,193,7,0.3); }

        .img-thumb { width: 90px; height: 90px; object-fit: cover; border-radius: 10px; }
        .order-id { font-size: 12px; color: #999; margin-bottom: 5px; display: block; }
        .tenggat-bayar { margin-top: 8px; font-size: 12px; color: #b8860b; line-height: 1.4; }
        .tenggat-bayar .sisa-waktu { font-weight: 700; }
        .tenggat-bayar.is-urgent { color: #c0392b; }
        .product-title { font-size: 16px; font-weight: 700; color: #333; display: block; margin-bottom: 5px; }
        .product-price { font-size: 15px; color: #007bff; font-weight: 600; }
    </style>
</head>
<body class="animsition account-page">

    <?php
    $active_page = 'cart';
    include 'navbar.php';
    ?>

    <div class="container account-shell">
        <div class="account-page-header">
            <div><h1>Riwayat Pembelian</h1><p>Pantau status transaksi dan unduh file karya yang sudah lunas.</p></div>
            <a href="product.php" class="role-action is-primary">Cari Desain</a>
        </div>
        <div class="table-responsive-account">
        <table class="table-riwayat">
            <thead>
                <tr>
                    <th width="15%">Produk</th>
                    <th width="40%">Detail Info</th>
                    <th width="20%">Status</th>
                    <th width="25%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                    
                    <?php 
                        // --- LOGIKA STATUS (PEMBARUAN) ---
                        $status_db = strtolower($row['status_pembayaran']); // Ubah ke huruf kecil biar aman

                        // Default Values
                        $badge_class = "bg-gagal";
                        $status_text = "Gagal / Expired";
                        $tombol_aksi = "";
                        $info_tenggat = ""; // teks tenggat untuk status pending

                        // 1. JIKA SUKSES (Support: 'berhasil', 'settlement', 'capture')
                        if ($status_db == 'berhasil' || $status_db == 'settlement' || $status_db == 'capture') {
                            $badge_class = "bg-sukses";
                            $status_text = "Lunas";
                            
                            // Tombol Download (Aman melewati download.php)
                            $link_file = "download.php?id=" . $row['id_transaksi'];
                            $tombol_aksi = '<a href="'.$link_file.'" class="btn-aksi btn-download">
                                                <i class="fa fa-download"></i> Download File
                                            </a>';
                        
                        // 2. JIKA PENDING (Support: 'pending')
                        } elseif ($status_db == 'pending') {
                            $badge_class = "bg-pending";
                            $status_text = "Menunggu Bayar";

                            // Sisa waktu dihitung di MySQL (kebal zona waktu). Detik utuh.
                            $sisa_detik = (int) ($row['sisa_detik'] ?? 0);

                            if ($sisa_detik > 0) {
                                // Tombol Bayar (Arahkan ke bayar_ulang.php untuk memunculkan Snap popup)
                                $tombol_aksi = '<a href="bayar_ulang.php?id=' . $row['id_transaksi'] . '" class="btn-aksi btn-bayar">
                                                    <i class="fa fa-credit-card"></i> Bayar Sekarang
                                                </a>';
                                $sisa_menit_awal = (int) ceil($sisa_detik / 60);
                                $info_tenggat = '<div class="tenggat-bayar" data-remaining="' . $sisa_detik . '">'
                                    . '<i class="fa fa-clock-o"></i> Bayar sebelum '
                                    . htmlspecialchars($row['batas_fmt'] ?? '', ENT_QUOTES)
                                    . ' &middot; <span class="sisa-waktu">sisa ' . $sisa_menit_awal . ' menit</span></div>';
                            } else {
                                // Sudah lewat tenggat (jaga-jaga bila lazy-expire belum sempat jalan)
                                $badge_class = "bg-gagal";
                                $status_text = "Gagal / Expired";
                                $tombol_aksi = '<span style="color:#aaa; font-size:13px;">Batas waktu pembayaran habis</span>';
                            }

                        // 3. JIKA GAGAL (Support: 'gagal', 'expire', 'cancel', 'deny')
                        } else {
                            $badge_class = "bg-gagal";
                            $status_text = "Gagal / Expired";
                            $tombol_aksi = '<span style="color:#aaa; font-size:13px;">Transaksi Dibatalkan</span>';
                        }
                    ?>

                <tr>
                    <td>
                        <img src="admin/uploads/<?php echo $row['gambar']; ?>" class="img-thumb" onerror="this.src='images/icons/dens.png'">
                    </td>
                    <td>
                        <span class="order-id">Order ID: <?php echo $row['id_midtrans_order']; ?></span>
                        <span class="product-title"><?php echo $row['judul']; ?></span>
                        <small style="color:#666;">Tanggal: <?php echo date('d M Y, H:i', strtotime($row['tanggal_transaksi'])); ?></small><br>
                        <span class="product-price">Rp <?php echo number_format($row['harga_final'], 0, ',', '.'); ?></span>
                    </td>
                    <td>
                        <span class="badge-status <?php echo $badge_class; ?>">
                            <?php echo $status_text; ?>
                        </span>
                        <?php echo $info_tenggat; ?>
                    </td>
                    <td>
                        <?php echo $tombol_aksi; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                
                <?php if(mysqli_num_rows($result) == 0): ?>
                    <tr>
                        <td colspan="4" align="center" style="padding: 50px; color: #888;">
                            <i class="fa fa-shopping-cart" style="font-size: 40px; margin-bottom: 15px;"></i><br>
                            Belum ada riwayat pembelian. Yuk belanja dulu!
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>

    <script>
    // Countdown tenggat pembayaran.
    // data-remaining = sisa detik dihitung SERVER (MySQL) saat halaman dimuat.
    // Kita kurangi dengan selisih waktu sejak load (delta, bukan jam absolut)
    // sehingga kebal terhadap perbedaan zona waktu/jam antara server & browser.
    (function () {
        var nodes = document.querySelectorAll('.tenggat-bayar');
        if (!nodes.length) return;

        var startMs = Date.now();
        var initial = [];
        nodes.forEach(function (el) {
            initial.push(parseInt(el.getAttribute('data-remaining'), 10) || 0);
        });

        function render() {
            var elapsed = Math.floor((Date.now() - startMs) / 1000); // detik sejak load
            var adaExpired = false;
            nodes.forEach(function (el, i) {
                var sisa = initial[i] - elapsed; // detik tersisa
                var span = el.querySelector('.sisa-waktu');
                if (sisa <= 0) {
                    if (span) span.textContent = 'waktu habis';
                    el.classList.add('is-urgent');
                    adaExpired = true;
                    return;
                }
                var menit = Math.floor(sisa / 60);
                var detik = sisa % 60;
                if (span) {
                    span.textContent = menit > 0
                        ? 'sisa ' + menit + ' menit ' + detik + ' detik'
                        : 'sisa ' + detik + ' detik';
                }
                el.classList.toggle('is-urgent', menit < 10);
            });
            // Bila ada yang baru saja habis, muat ulang agar status server diperbarui.
            if (adaExpired) setTimeout(function () { window.location.reload(); }, 1500);
        }

        render();
        setInterval(render, 1000);
    })();
    </script>

</body>
</html>
