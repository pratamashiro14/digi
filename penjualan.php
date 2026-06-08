<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';

// 1. CEK LOGIN DESAINER
require_designer();

$id_desainer = current_id();
$nama_desainer = current_name();

// 2. QUERY PENJUALAN (SESUAI GAMBAR DATABASE)
// - t_transaksi menggunakan 'id_buyer' untuk join ke t_user
// - t_transaksi menggunakan 'id_design' untuk join ke t_design
// - t_design menggunakan 'id_designer' untuk filter pemilik karya

$query_penjualan = "SELECT 
                        t_transaksi.*, 
                        t_design.judul, 
                        t_design.gambar, 
                        t_design.kategori, 
                        t_user.nama AS nama_pembeli 
                    FROM t_transaksi 
                    JOIN t_design ON t_transaksi.id_design = t_design.id_design 
                    JOIN t_user ON t_transaksi.id_buyer = t_user.id_user 
                    WHERE t_design.id_designer = '$id_desainer' 
                    ORDER BY t_transaksi.id_transaksi DESC";

$result_penjualan = mysqli_query($koneksi, $query_penjualan);

// Cek error query jika masih ada
if (!$result_penjualan) {
    die("Query Error: " . mysqli_error($koneksi));
}

$total_pemasukan = 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Penjualan Desainer</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="icon" type="image/png" href="images/icons/favicon.png"/>
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/iconic/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/css-hamburgers/hamburgers.min.css">
    <link rel="stylesheet" type="text/css" href="css/util.css">
    <link rel="stylesheet" type="text/css" href="css/main.css">

    <style>
        body { background-color: #fff; font-family: 'Poppins', sans-serif; }

        /* STYLE SIDEBAR */
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-menu li { margin-bottom: 12px; }
        .sidebar-menu a { display: flex; align-items: center; font-size: 16px; color: #555; text-decoration: none; font-weight: 500; padding: 10px 15px; border-radius: 8px; transition: 0.2s; }
        .sidebar-menu a i { width: 30px; font-size: 18px; margin-right: 5px; color: #888; text-align: center; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background-color: #f5f5f5; color: #333; font-weight: 700; }
        .sidebar-menu a:hover i, .sidebar-menu a.active i { color: #333; }
        @media (min-width: 768px) { .border-right-custom { border-right: 1px solid #eee; } }

        /* STYLE TABEL PENJUALAN */
        .sales-container { border: 1px solid #e0e0e0; padding: 30px; border-radius: 4px; }
        .table-sales { width: 100%; margin-bottom: 0; }
        .table-sales th { text-transform: uppercase; font-size: 14px; font-weight: 800; color: #333; border-bottom: none; text-align: center; padding-bottom: 20px; }
        .table-sales td { vertical-align: middle; text-align: center; font-size: 14px; color: #666; padding: 15px 0; border-top: none; }
        
        .work-thumb { width: 100px; height: 100px; object-fit: cover; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }

        .sales-footer { border-top: 1px solid #e0e0e0; margin-top: 50px; padding-top: 20px; display: flex; justify-content: space-between; align-items: center; }
        .total-label { font-size: 20px; font-weight: 800; color: #333; text-transform: uppercase; margin-left: 20px; }
        .total-amount { font-size: 24px; font-weight: 800; color: #333; margin-right: 20px; }
    </style>
</head>
<body class="animsition">

    <?php
    $active_page = 'designer-sales';
    include 'navbar.php';
    ?>

    <div class="container p-t-50 p-b-80">
        <div class="row">
            
            <div class="col-md-3 col-lg-3 p-b-30 border-right-custom">
                <h4 class="mtext-105 cl2 p-b-30" style="font-size: 18px;">Menu Desainer</h4>
                <ul class="sidebar-menu">
                    <li><a href="profil_desainer.php"><i class="fa fa-user-circle"></i> Profil Desainer</a></li>
                    <li><a href="unggahan.php" ><i class="fa fa-cloud-upload"></i> Unggahan</a></li>
                    <li><a href="penjualan.php" class="active"><i class="fa fa-shopping-basket"></i> Penjualan</a></li> 
                    <li><a href="pesan.php"><i class="fa fa-comments"></i> Pesan</a></li>
                </ul>
            </div>

            <div class="col-md-9 col-lg-9 p-l-40 p-l-15-lg">
                <p class="mtext-105 cl2 p-b-20">Penjualan</p>
                
                <div class="sales-container">
                    <table class="table table-sales">
                        <thead>
                            <tr>
                                <th>KARYA</th>
                                <th>JENIS</th>
                                <th>PEMBELI</th>
                                <th>AKTIFITAS</th>
                                <th>HARGA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if(mysqli_num_rows($result_penjualan) > 0) {
                                while($row = mysqli_fetch_assoc($result_penjualan)) {
                                    // Sesuai screenshot t_transaksi: kolomnya 'harga_final'
                                    $harga = $row['harga_final'] ?? 0; 
                                    $total_pemasukan += $harga;
                                    
                                    // Sesuai screenshot t_transaksi: kolomnya 'status_pembayaran'
                                    $status_transaksi = $row['status_pembayaran'] ?? 'Sudah Terjual';
                            ?>
                            <tr>
                                <td>
                                    <img src="admin/uploads/<?php echo $row['gambar']; ?>" alt="Karya" class="work-thumb">
                                </td>
                                <td><?php echo $row['kategori']; ?></td>
                                <td><?php echo $row['nama_pembeli']; ?></td>
                                <td><?php echo $status_transaksi; ?></td>
                                <td>Rp <?php echo number_format($harga, 0, ',', '.'); ?></td>
                            </tr>
                            <?php 
                                } 
                            } else { 
                            ?>
                                <tr>
                                    <td colspan="5" style="padding: 30px;">Belum ada karya yang terjual.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>

                    <div class="sales-footer">
                        <div class="total-label">TOTAL PEMASUKAN :</div>
                        <div class="total-amount">Rp <?php echo number_format($total_pemasukan, 0, ',', '.'); ?></div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
    <script src="vendor/animsition/js/animsition.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>

</body>
</html>
