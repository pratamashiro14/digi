<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/designer_layout.php';
include 'admin/koneksi.php';

// 1. CEK LOGIN DESAINER
require_designer();

$id_desainer = current_id();
$nama_desainer = current_name();
$sales_view = $_GET['view'] ?? 'all';
if (!in_array($sales_view, ['all', 'processing', 'history'], true)) {
    $sales_view = 'all';
}
$sales_filter = [
    'all' => '',
    'processing' => " AND t_transaksi.status_pembayaran = 'pending'",
    'history' => " AND t_transaksi.status_pembayaran IN ('berhasil', 'gagal')",
][$sales_view];

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
                    $sales_filter
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
    <link rel="stylesheet" type="text/css" href="css/account-ui.css?v=<?php echo filemtime(__DIR__ . '/css/account-ui.css'); ?>">

    <style>
        body { background-color: #fff; font-family: 'Poppins', sans-serif; }

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
<body class="animsition account-page">

    <?php
    $active_page = 'designer-sales';
    include 'navbar.php';
    ?>

    <div class="container account-shell">
        <div class="designer-layout-grid">
            <?php render_designer_section_sidebar('sales', $sales_view); ?>

            <main class="designer-section-content">
                <div class="account-page-header">
                    <div>
                        <h1><?php echo $sales_view === 'processing' ? 'Sedang Diproses' : ($sales_view === 'history' ? 'Riwayat Penjualan' : 'Semua Penjualan'); ?></h1>
                        <p>Pantau transaksi dan total pemasukan dari karya yang terjual.</p>
                    </div>
                </div>
                
                <div class="sales-container">
                    <div class="table-responsive-account">
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

                                    // Sesuai screenshot t_transaksi: kolomnya 'status_pembayaran'
                                    $status_transaksi = $row['status_pembayaran'] ?? 'Sudah Terjual';

                                    // Hanya transaksi BERHASIL yang dihitung sebagai pemasukan
                                    if ($status_transaksi === 'berhasil') {
                                        $total_pemasukan += $harga;
                                    }
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
                    </div>

                    <div class="sales-footer">
                        <div class="total-label">TOTAL PEMASUKAN (BERHASIL) :</div>
                        <div style="display:flex; align-items:center; gap:18px;">
                            <div class="total-amount">Rp <?php echo number_format($total_pemasukan, 0, ',', '.'); ?></div>
                            <a href="pencairan.php" class="btn" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; border-radius:8px; font-weight:600; padding:10px 18px; margin-right:20px;">
                                <i class="zmdi zmdi-balance-wallet"></i> Cairkan Dana
                            </a>
                        </div>
                    </div>
                </div>

            </main>

        </div>
    </div>

    <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
    <script src="vendor/animsition/js/animsition.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>

</body>
</html>
