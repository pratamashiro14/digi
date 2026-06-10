<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';

// Cek Login — khusus pembeli/user
require_user();

$id_user = current_id();

// Proses Upload
if (isset($_POST['upload_ktp'])) {
    $nama_file = $_FILES['foto_ktp']['name'];
    $tmp_file = $_FILES['foto_ktp']['tmp_name'];
    
    // Ganti nama file biar unik (misal: 12_KTP.jpg)
    $nama_baru = $id_user . "_KTP_" . rand(100,999) . ".jpg";
    
    // Pastikan folder 'admin/uploads/' ada
    move_uploaded_file($tmp_file, "admin/uploads/" . $nama_baru);
    
    // Update Database: Ubah status jadi 'pending'
    $update = mysqli_query($koneksi, "UPDATE t_user SET foto_ktp='$nama_baru', status_verifikasi='pending' WHERE id_user='$id_user'");
    
    if ($update) {
        sweetalert_redirect('Foto identitas berhasil diunggah. Tunggu verifikasi admin.', 'product.php', 'success', 'Verifikasi Terkirim!');
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verifikasi Akun</title>
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
    <style>
        body { background: #f2f2f2; display: flex; align-items: center; justify-content: center; height: 100vh; font-family: sans-serif; }
        .box-verif { background: white; padding: 40px; border-radius: 10px; width: 100%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; }
        .icon-ktp { font-size: 50px; color: #4e8eff; margin-bottom: 20px; }
    </style>
</head>
<body>

    <div class="box-verif">
        <h3 style="font-weight: bold; margin-bottom: 10px;">Verifikasi Identitas</h3>
        <p class="text-muted mb-4">Untuk menghindari <b>Bid & Run</b> (Kabur setelah menang), silakan upload foto KTP/Kartu Pelajar kamu sebagai jaminan.</p>
        
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group text-left">
                <label>Foto KTP / KTM Asli</label>
                <input type="file" name="foto_ktp" class="form-control" required>
                <small class="text-danger">*Data kamu aman dan hanya untuk verifikasi admin.</small>
            </div>
            
            <button type="submit" name="upload_ktp" class="btn btn-primary btn-block btn-lg mt-4">Kirim Verifikasi</button>
            <a href="index.php" class="btn btn-link mt-2">Kembali ke Beranda</a>
        </form>
    </div>

</body>
</html>
