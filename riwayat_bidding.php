<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';
require_once __DIR__ . '/bidding_helper.php'; // bid_leader_id(): pemenang dgn prioritas premium
require_once __DIR__ . '/wanprestasi_helper.php'; // sweep wanprestasi & tenggat

// Cek Login User
require_user();

// Halaman status pemenang/gugur — wajib selalu akurat, jangan throttled.
jalankan_pemeliharaan_lelang($koneksi, true);

$id_user = (int) current_id();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Riwayat Lelang Saya</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="css/util.css">
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; padding-top: 50px; }
        .card-bid { background: #fff; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: 0.3s; border-left: 5px solid #ccc; }
        .card-bid:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        
        .status-running { border-left-color: #f39c12; }
        .status-win { border-left-color: #2ecc71; background: #eaffef; }
        .status-lost { border-left-color: #e74c3c; opacity: 0.8; }

        .thumb-img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; margin-right: 20px; }
        .bid-info { flex: 1; }
        .btn-bayar { background: #2ecc71; color: #fff; padding: 10px 25px; border-radius: 50px; text-decoration: none; font-weight: bold; box-shadow: 0 4px 10px rgba(46, 204, 113, 0.3); transition:0.3s; }
        .btn-bayar:hover { background: #27ae60; color: #fff; transform: scale(1.05); }
    </style>
</head>
<body>

    <div class="container">
        <h3 class="mtext-105 cl2 p-b-30">
            <a href="index.php" class="text-dark"><i class="fa fa-arrow-left"></i></a> 
            Riwayat Bidding Saya
        </h3>

        <?php
        // 1. Ambil semua produk yang pernah SAYA tawar
        // Kita GROUP BY id_design biar gak dobel-dobel kalau nawar berkali-kali di produk sama
        // masih_berjalan & sisa_detik_bayar dihitung di MySQL (bukan PHP date()) supaya kebal
        // selisih zona waktu antara PHP dan MySQL — lihat catatan di pembayaran_helper.php.
        $batas_bayar_sql = batas_bayar_pemenang_sql('d');
        $stmt = mysqli_prepare($koneksi, "
            SELECT b.*, d.judul, d.gambar, d.waktu_berakhir, d.status as status_produk,
                   MAX(b.harga_tawaran) as tawaran_tertinggi_saya,
                   (d.waktu_berakhir IS NOT NULL AND d.waktu_berakhir > NOW()) AS masih_berjalan,
                   TIMESTAMPDIFF(SECOND, NOW(), {$batas_bayar_sql}) AS sisa_detik_bayar,
                   DATE_FORMAT({$batas_bayar_sql}, '%d %b %Y, %H:%i') AS batas_bayar_fmt,
                   EXISTS (SELECT 1 FROM t_wanprestasi w WHERE w.id_user = b.id_buyer AND w.id_design = b.id_design AND w.dimaafkan = 0) AS gugur
            FROM t_bidding b
            JOIN t_design d ON b.id_design = d.id_design
            WHERE b.id_buyer = ?
            GROUP BY b.id_design
            ORDER BY b.id_bid DESC
        ");
        mysqli_stmt_bind_param($stmt, 'i', $id_user);
        mysqli_stmt_execute($stmt);
        $query = mysqli_stmt_get_result($stmt);

        if(mysqli_num_rows($query) > 0) {
            while($row = mysqli_fetch_assoc($query)) {

                $id_design = $row['id_design'];
                $judul = $row['judul'];
                $gambar = $row['gambar'];
                $deadline = $row['waktu_berakhir'];
                $tawaran_saya = $row['tawaran_tertinggi_saya'];

                // --- LOGIKA PENENTUAN STATUS (THE BRAIN) ---
                $status_lelang = "";
                $class_css = "";
                $pesan_status = "";
                $info_tenggat_bayar = "";

                if (!empty($row['gugur'])) {
                    // GUGUR: sempat memimpin tapi tidak membayar sampai tenggat
                    // (lihat wanprestasi_helper.php) — beda dari sekadar "kalah".
                    $status_lelang = "gugur";
                    $class_css = "status-lost";
                    $pesan_status = "<span class='badge badge-danger'><i class='fa fa-exclamation-triangle'></i> Gugur (Tidak Bayar Tepat Waktu)</span>";
                } elseif (!empty($row['masih_berjalan'])) {
                    // MASIH JALAN
                    $status_lelang = "running";
                    $class_css = "status-running";
                    $pesan_status = "<span class='badge badge-warning'>Sedang Berjalan</span>";
                } else {
                    // SUDAH BERAKHIR -> Cek Pemenang

                    // Pemenang = pemimpin lelang (sudah memperhitungkan prioritas premium saat seri)
                    $winner_id = bid_leader_id($koneksi, $id_design);

                    if($winner_id == $id_user) {
                        // SAYA MENANG!
                        $status_lelang = "win";
                        $class_css = "status-win";
                        $pesan_status = "<span class='badge badge-success'><i class='fa fa-trophy'></i> SELAMAT! KAMU MENANG</span>";

                        $sisa_detik = (int) ($row['sisa_detik_bayar'] ?? 0);
                        if ($sisa_detik > 0) {
                            $info_tenggat_bayar = '<div class="tenggat-bayar" data-remaining="' . $sisa_detik . '" style="color:#b8860b; font-size:12px; margin-top:6px;">'
                                . '<i class="fa fa-clock-o"></i> Bayar sebelum ' . htmlspecialchars($row['batas_bayar_fmt'] ?? '')
                                . ' &middot; <span class="sisa-waktu">menghitung...</span></div>';
                        }
                    } else {
                        // KALAH
                        $status_lelang = "lost";
                        $class_css = "status-lost";
                        $pesan_status = "<span class='badge badge-danger'>Lelang Berakhir (Kalah)</span>";
                    }
                }
        ?>

        <div class="card-bid flex-w flex-m <?php echo $class_css; ?>">
            <img src="admin/uploads/<?php echo $gambar; ?>" class="thumb-img" alt="IMG">
            
            <div class="bid-info">
                <h5 class="mtext-106 cl2 p-b-5">
                    <?php echo $judul; ?>
                </h5>
                <span class="stext-102 cl3">
                    Tawaranmu: <b>Rp <?php echo number_format($tawaran_saya,0,',','.'); ?></b>
                </span>
                <br>
                <div class="p-t-10">
                    <?php echo $pesan_status; ?>
                    <span class="stext-102 cl6 m-l-10">
                        <i class="fa fa-clock-o"></i> Selesai: <?php echo date('d M Y, H:i', strtotime($deadline)); ?>
                    </span>
                    <?php echo $info_tenggat_bayar; ?>
                </div>
            </div>

            <div class="p-l-20">
                <?php if($status_lelang == 'win') { ?>
                    <a href="shoping-cart.php?id_bidding=<?php echo $row['id_bid']; ?>" class="btn-bayar">
                        BAYAR SEKARANG <i class="fa fa-chevron-right"></i>
                    </a>
                <?php } else if($status_lelang == 'running') { ?>
                    <a href="product-detail.php?id=<?php echo $id_design; ?>" class="stext-104 cl4 hov-cl1 trans-04 js-name-b2 p-b-6">
                        Lihat Lelang
                    </a>
                <?php } else if($status_lelang == 'gugur') { ?>
                    <span class="stext-102 cl3">Kesempatanmu hangus karena tidak membayar tepat waktu.</span>
                <?php } else { ?>
                    <span class="stext-102 cl3">Yah, belum beruntung.</span>
                <?php } ?>
            </div>
        </div>

        <?php 
            }
        } else {
            echo "<div class='alert alert-light text-center'>Kamu belum pernah ikut lelang apapun.</div>";
        } 
        ?>

    </div>

    <script>
    // Countdown tenggat bayar pemenang — sisa detik dihitung SERVER (MySQL)
    // saat halaman dimuat, dikurangi delta waktu browser sejak load, supaya
    // kebal selisih zona waktu/jam antara server & browser (pola sama dgn riwayat.php).
    (function () {
        var nodes = document.querySelectorAll('.tenggat-bayar');
        if (!nodes.length) return;
        var startMs = Date.now();
        var initial = [];
        nodes.forEach(function (el) { initial.push(parseInt(el.getAttribute('data-remaining'), 10) || 0); });

        function render() {
            var elapsed = Math.floor((Date.now() - startMs) / 1000);
            var adaExpired = false;
            nodes.forEach(function (el, i) {
                var sisa = initial[i] - elapsed;
                var span = el.querySelector('.sisa-waktu');
                if (sisa <= 0) {
                    if (span) span.textContent = 'waktu habis';
                    adaExpired = true;
                    return;
                }
                var jam = Math.floor(sisa / 3600);
                var menit = Math.floor((sisa % 3600) / 60);
                if (span) span.textContent = jam > 0 ? ('sisa ' + jam + ' jam ' + menit + ' menit') : ('sisa ' + menit + ' menit');
            });
            if (adaExpired) setTimeout(function () { window.location.reload(); }, 1500);
        }
        render();
        setInterval(render, 1000);
    })();
    </script>

</body>
</html>