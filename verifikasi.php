<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';
require_once __DIR__ . '/bidding_helper.php';

// Cek Login — khusus pembeli/user
require_user();

$id_user = (int) current_id();

// Proses Upload
if (isset($_POST['upload_ktp'])) {
    $nik = preg_replace('/[^0-9]/', '', $_POST['nik'] ?? '');

    if (strlen($nik) < 16) {
        sweetalert_back('NIK harus 16 digit angka sesuai KTP.', 'error', 'NIK Tidak Valid!');
        exit;
    }

    // Blokir berbasis NIK: NIK yang sudah diban tidak boleh verifikasi ulang
    // (mencegah pelaku bikin akun baru dengan NIK yang sama).
    if (nik_diblokir($koneksi, $nik)) {
        sweetalert_back('NIK ini telah diblokir permanen oleh admin dan tidak dapat diverifikasi.', 'error', 'NIK Diblokir!');
        exit;
    }

    $tmp_file = $_FILES['foto_ktp']['tmp_name'] ?? '';
    if (!$tmp_file || !is_uploaded_file($tmp_file)) {
        sweetalert_back('Foto KTP wajib diunggah.', 'error', 'Foto Kurang!');
        exit;
    }

    // Ganti nama file biar unik (misal: 12_KTP.jpg)
    $nama_baru = $id_user . "_KTP_" . rand(100,999) . ".jpg";

    // Pastikan folder 'admin/uploads/' ada
    move_uploaded_file($tmp_file, "admin/uploads/" . $nama_baru);

    // Update Database: simpan NIK + foto, ubah status jadi 'pending'
    $stmt = mysqli_prepare($koneksi, "UPDATE t_user SET foto_ktp = ?, nik = ?, status_verifikasi = 'pending' WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, 'ssi', $nama_baru, $nik, $id_user);
    $update = mysqli_stmt_execute($stmt);

    if ($update) {
        sweetalert_redirect('Identitas berhasil diunggah. Tunggu verifikasi admin.', 'product.php', 'success', 'Verifikasi Terkirim!');
    } else {
        sweetalert_back('Gagal menyimpan verifikasi: ' . mysqli_error($koneksi), 'error', 'Gagal!');
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
                <label>Nomor Induk Kependudukan (NIK)</label>
                <input type="text" name="nik" class="form-control" inputmode="numeric" maxlength="16" pattern="\d{16}" placeholder="16 digit angka pada KTP" required>
                <small class="text-muted">*Sesuai 16 digit di KTP. Digunakan untuk verifikasi & pencegahan penyalahgunaan.</small>
            </div>
            <div class="form-group text-left mt-3">
                <label>Foto KTP / KTM Asli</label>
                <input type="file" name="foto_ktp" class="form-control" accept="image/*" required>
                <small class="text-danger">*Data kamu aman dan hanya untuk verifikasi admin.</small>
            </div>

            <button type="submit" name="upload_ktp" class="btn btn-primary btn-block btn-lg mt-4">Kirim Verifikasi</button>
            <a href="index.php" class="btn btn-link mt-2">Kembali ke Beranda</a>
        </form>
    </div>

</body>
</html>
