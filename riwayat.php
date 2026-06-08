<?php
require_once __DIR__ . '/auth.php';
// 1. HUBUNGKAN KE DATABASE
// Pastikan path ini benar (sesuai struktur folder kamu)
include 'admin/koneksi.php';

// 2. CEK LOGIN (user atau desainer)
require_login();

$id_buyer = current_id();

// 3. QUERY DATABASE
// Mengambil data transaksi digabung (JOIN) dengan data desain
$query = "SELECT t.*, d.judul, d.gambar, d.file_master 
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
    <link rel="stylesheet" type="text/css" href="css/util.css">
    <link rel="stylesheet" type="text/css" href="css/main.css">

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
        .product-title { font-size: 16px; font-weight: 700; color: #333; display: block; margin-bottom: 5px; }
        .product-price { font-size: 15px; color: #007bff; font-weight: 600; }
    </style>
</head>
<body class="animsition">

    <header class="header-v4">
        <div class="container-menu-desktop">
            <div class="top-bar">
                <div class="content-topbar flex-sb-m h-full container">
                    <div class="left-top-bar">Riwayat Transaksi Kamu</div>
                    <div class="right-top-bar flex-w h-full">
                        <a href="index.php" class="flex-c-m trans-04 p-lr-25">Kembali ke Beranda</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container-riwayat">
        <h3 class="mtext-105 cl2 txt-center p-b-40">Riwayat Pembelian & File Saya</h3>
        
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

                        // 1. JIKA SUKSES (Support: 'berhasil', 'settlement', 'capture')
                        if ($status_db == 'berhasil' || $status_db == 'settlement' || $status_db == 'capture') {
                            $badge_class = "bg-sukses";
                            $status_text = "Lunas";
                            
                            // Tombol Download
                            $link_file = "admin/uploads/" . $row['file_master'];
                            $tombol_aksi = '<a href="'.$link_file.'" class="btn-aksi btn-download" download>
                                                <i class="fa fa-download"></i> Download File
                                            </a>';
                        
                        // 2. JIKA PENDING (Support: 'pending')
                        } elseif ($status_db == 'pending') {
                            $badge_class = "bg-pending";
                            $status_text = "Menunggu Bayar";
                            
                            // Tombol Bayar (Arahkan ke URL snap atau logic bayar kamu)
                            // Jika kamu pakai Snap Midtrans, idealnya simpan snap_token di DB, tapi ini contoh link bayar ulang
                            $tombol_aksi = '<a href="https://simulator.sandbox.midtrans.com/bca/va/payment" target="_blank" class="btn-aksi btn-bayar">
                                                <i class="fa fa-credit-card"></i> Bayar Sekarang
                                            </a>'; 

                        // 3. JIKA GAGAL (Support: 'gagal', 'expire', 'cancel', 'deny')
                        } else {
                            $badge_class = "bg-gagal";
                            $status_text = "Gagal / Expired";
                            $tombol_aksi = '<span style="color:#aaa; font-size:13px;">Transaksi Dibatalkan</span>';
                        }
                    ?>

                <tr>
                    <td>
                        <img src="admin/uploads/<?php echo $row['gambar']; ?>" class="img-thumb" onerror="this.src='images/icons/logo-01.png'">
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

    <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>

</body>
</html>