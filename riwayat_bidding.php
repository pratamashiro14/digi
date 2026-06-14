<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';

// Cek Login User
require_user();

$id_user = current_id();
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
        $query = mysqli_query($koneksi, "
            SELECT b.*, d.judul, d.gambar, d.waktu_berakhir, d.status as status_produk, MAX(b.harga_tawaran) as tawaran_tertinggi_saya
            FROM t_bidding b
            JOIN t_design d ON b.id_design = d.id_design
            WHERE b.id_buyer = '$id_user'
            GROUP BY b.id_design
            ORDER BY b.id_bid DESC
        ");

        if(mysqli_num_rows($query) > 0) {
            while($row = mysqli_fetch_assoc($query)) {
                
                $id_design = $row['id_design'];
                $judul = $row['judul'];
                $gambar = $row['gambar'];
                $deadline = $row['waktu_berakhir'];
                $tawaran_saya = $row['tawaran_tertinggi_saya'];
                
                // --- LOGIKA PENENTUAN STATUS (THE BRAIN) ---
                $sekarang = date('Y-m-d H:i:s');
                $status_lelang = "";
                $class_css = "";
                $pesan_status = "";
                
                // Cek 1: Apakah waktu sudah habis?
                if($sekarang < $deadline) {
                    // MASIH JALAN
                    $status_lelang = "running";
                    $class_css = "status-running";
                    $pesan_status = "<span class='badge badge-warning'>Sedang Berjalan</span>";
                } else {
                    // SUDAH BERAKHIR -> Cek Pemenang
                    
                    // Ambil penawar tertinggi GLOBAL dari database
                    $q_max = mysqli_query($koneksi, "SELECT id_buyer FROM t_bidding WHERE id_design='$id_design' ORDER BY harga_tawaran DESC LIMIT 1");
                    $winner = mysqli_fetch_assoc($q_max);
                    
                    if($winner['id_buyer'] == $id_user) {
                        // SAYA MENANG!
                        $status_lelang = "win";
                        $class_css = "status-win";
                        $pesan_status = "<span class='badge badge-success'><i class='fa fa-trophy'></i> SELAMAT! KAMU MENANG</span>";
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

</body>
</html>