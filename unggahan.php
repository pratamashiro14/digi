<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/designer_layout.php';
include 'admin/koneksi.php';

// 1. CEK LOGIN KHUSUS DESAINER
require_verified_designer();

$id_desainer = current_id();
$nama_desainer = current_name();
$works_view = $_GET['view'] ?? 'auction-create';
if (!in_array($works_view, ['all', 'auction-create', 'active'], true)) {
    $works_view = 'auction-create';
}

// 2. LOGIKA HAPUS KARYA
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    
    // Ambil info gambar & file master untuk dihapus fisiknya
    $q_data = mysqli_query($koneksi, "SELECT gambar, file_master FROM t_design WHERE id_design='$id_hapus' AND id_designer='$id_desainer'");
    
    if($row = mysqli_fetch_assoc($q_data)){
        $path_img = "admin/uploads/" . $row['gambar'];
        if(file_exists($path_img)) { unlink($path_img); }

        if(!empty($row['file_master'])){
            $path_master = "admin/uploads/" . $row['file_master'];
            if(file_exists($path_master)) { unlink($path_master); }
        }
    }
    
    // Hapus Jejak di Tabel Lain Dulu
    mysqli_query($koneksi, "DELETE FROM t_bidding WHERE id_design='$id_hapus'"); 
    mysqli_query($koneksi, "DELETE FROM t_transaksi WHERE id_design='$id_hapus'");

    // Hapus Data Produk
    $delete = mysqli_query($koneksi, "DELETE FROM t_design WHERE id_design='$id_hapus' AND id_designer='$id_desainer'");
    
    if($delete){
        sweetalert_redirect('Karya dan riwayat lelang berhasil dihapus.', 'unggahan.php?view=all#daftar-karya', 'success', 'Berhasil Dihapus!');
    }
}

// 3. PROSES UPLOAD KARYA BARU
// Kita pakai hidden input 'simpan_karya' karena tombol submit akan di-intercept oleh JS
if (isset($_POST['simpan_karya'])) {
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $kategori = $_POST['kategori']; 
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $harga = $_POST['harga'];
    $harga_beli_langsung = empty($_POST['harga_beli_langsung']) ? 0 : $_POST['harga_beli_langsung'];
    $waktu_berakhir = $_POST['waktu_berakhir'];

    // Upload Gambar
    $nama_gambar = "default.jpg";
    if (!empty($_FILES['gambar']['name'])) {
        $nama_file = $_FILES['gambar']['name'];
        $tmp_file = $_FILES['gambar']['tmp_name'];
        $nama_gambar = rand(1000,9999) . "_" . $nama_file; 
        move_uploaded_file($tmp_file, "admin/uploads/" . $nama_gambar);
    }

    // Upload File Master
    $nama_file_master = "";
    if (!empty($_FILES['file_master']['name'])) {
        $nama_master = $_FILES['file_master']['name'];
        $tmp_master = $_FILES['file_master']['tmp_name'];
        $nama_file_master = rand(1000,9999) . "_MASTER_" . $nama_master;
        move_uploaded_file($tmp_master, "admin/uploads/" . $nama_file_master);
    }

    // Simpan ke Database
    $query_insert = "INSERT INTO t_design 
        (id_designer, judul, deskripsi, kategori, harga_awal, harga_beli_langsung, gambar, tanggal_upload, status, waktu_berakhir, file_master) 
        VALUES 
        ('$id_desainer', '$judul', '$deskripsi', '$kategori', '$harga', '$harga_beli_langsung', '$nama_gambar', NOW(), 'pending', '$waktu_berakhir', '$nama_file_master')";

    if (mysqli_query($koneksi, $query_insert)) {
        sweetalert_redirect('Karya berhasil diunggah. Menunggu verifikasi dan persetujuan Admin sebelum ditayangkan.', 'unggahan.php?view=all#daftar-karya', 'success', 'Upload Berhasil!');
    } else {
        sweetalert_back('Gagal mengunggah karya: ' . mysqli_error($koneksi), 'error', 'Upload Gagal!');
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Unggahan Desainer - Mulai Lelang</title>
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
        body { background-color: #fff; font-family: 'Poppins', sans-serif; }

        /* FORM */
        .upload-container { border: 1px solid #e0e0e0; padding: 30px; border-radius: 8px; position: relative; margin-bottom: 50px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        
        .image-upload-box { width: 100%; height: 250px; border: 2px dashed #ccc; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; background: #fafafa; position: relative; overflow: hidden; transition: 0.3s; }
        .image-upload-box:hover { border-color: #4e8eff; background: #f0f8ff; }
        .preview-img { width: 100%; height: 100%; object-fit: cover; position: absolute; top:0; left:0; display: none; }
        .upload-hint { text-align: center; color: #999; font-size: 13px; margin-top: 10px; }

        .form-input-custom { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; margin-bottom: 5px; }
        .form-input-custom:focus { border-color: #4e8eff; outline: none; }

        .btn-simpan-upload { background: linear-gradient(90deg, #4e8eff, #6b4eff); color: #fff; padding: 12px 30px; border: none; border-radius: 50px; font-size: 16px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 10px; transition: 0.3s; }
        .btn-simpan-upload:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(78, 142, 255, 0.4); }

        /* TABEL */
        .list-karya-title { font-size: 18px; font-weight: 700; color: #333; margin-bottom: 20px; border-left: 5px solid #4e8eff; padding-left: 15px; }
        .table-karya { width: 100%; border: 1px solid #eee; }
        .table-karya th { background: #f9f9f9; padding: 15px; text-align: center; font-size: 13px; font-weight: 700; color: #555; text-transform: uppercase; }
        .table-karya td { padding: 15px; vertical-align: middle; text-align: center; border-bottom: 1px solid #eee; font-size: 14px; color: #666; }
        .thumb-small { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #eee; }
        .btn-hapus { background: #ff4e4e; color: #fff; padding: 6px 12px; border-radius: 4px; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-hapus:hover { background: #d00000; color: #fff; }
    </style>
</head>
<body class="animsition account-page">

    <?php
    $active_page = 'designer-uploads';
    include 'navbar.php';
    ?>

    <div class="container account-shell">
        <div class="designer-layout-grid">
            <?php render_designer_section_sidebar('works', $works_view); ?>

            <main class="designer-section-content">
                <div class="account-page-header">
                    <div>
                        <h1><?php echo $works_view === 'auction-create' ? 'Buat Lelang' : ($works_view === 'active' ? 'Lelang Aktif' : 'Semua Karya'); ?></h1>
                        <p><?php echo $works_view === 'auction-create' ? 'Publikasikan karya baru dan tentukan detail lelang.' : 'Kelola koleksi dan status karya yang sudah kamu unggah.'; ?></p>
                    </div>
                </div>

                <?php if ($works_view === 'auction-create') { ?>
                <form id="formUpload" action="" method="POST" enctype="multipart/form-data" class="upload-container">
                    
                    <input type="hidden" name="simpan_karya" value="yes">

                    <div class="row">
                        <div class="col-md-4 text-center p-b-30">
                            <div class="image-upload-box" onclick="document.getElementById('fileInput').click()">
                                <div style="text-align:center;">
                                    <span style="font-size: 40px; color: #ccc;">+</span><br>
                                    <span style="color:#999; font-size:12px;">Klik Upload Cover</span>
                                </div>
                                <img id="preview" class="preview-img" src="#">
                                <input type="file" name="gambar" id="fileInput" accept="image/jpeg,image/png,image/webp" style="display: none;" onchange="previewImage(this)" required>
                            </div>
                            <div class="upload-hint">Gambar ini akan tampil di katalog</div>
                        </div>

                        <div class="col-md-8">
                            
                            <div class="row m-b-15">
                                <div class="col-md-6">
                                    <label class="stext-102 cl3 p-b-5">Judul Karya</label>
                                    <input type="text" name="judul" class="form-input-custom" placeholder="Judul Karya" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="stext-102 cl3 p-b-5">Kategori</label>
                                    <select name="kategori" class="form-input-custom">
                                        <option value="ilustrasi">Ilustrasi</option>
                                        <option value="tipografi">Tipografi</option>
                                        <option value="mockup">Mockup</option>
                                        <option value="ui-ux">UI/UX</option>
                                    </select>
                                </div>
                            </div>

                            <div class="m-b-15">
                                <label class="stext-102 cl3 p-b-5">Deskripsi</label>
                                <textarea name="deskripsi" class="form-input-custom" rows="3" placeholder="Jelaskan detail karyamu..." required></textarea>
                            </div>

                            <div class="row m-b-15">
                                <div class="col-md-4">
                                    <label class="stext-102 cl3 p-b-5" style="font-weight:bold; color:#4e8eff;">Harga Awal (Open Bid)</label>
                                    <input type="number" name="harga" class="form-input-custom" placeholder="Rp 0" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="stext-102 cl3 p-b-5" style="font-weight:bold; color:#f39c12;">Harga Beli Langsung</label>
                                    <input type="number" name="harga_beli_langsung" class="form-input-custom" placeholder="Opsional (Rp)">
                                </div>
                                <div class="col-md-4">
                                    <label class="stext-102 cl3 p-b-5" style="font-weight:bold; color:#e74c3c;">Batas Waktu Lelang</label>
                                    <input type="datetime-local" name="waktu_berakhir" class="form-input-custom" required>
                                </div>
                            </div>

                            <div class="m-b-15" style="background: #f8f9fa; padding: 12px; border: 1px dashed #ccc; border-radius: 6px;">
                                <label class="stext-102 cl3 p-b-5">Upload File Master (ZIP/RAR/PSD/Gambar)</label>
                                <input type="file" name="file_master" accept=".zip,.rar,.psd,.ai,.fig,.png,.jpg,.jpeg,.webp" class="form-control-file" style="font-size: 13px;">
                                <div style="font-size: 11px; color: #888; margin-top: 5px;">
                                    <i class="fa fa-lock"></i> File ini aman. Hanya bisa didownload oleh pemenang lelang setelah membayar.
                                </div>
                            </div>

                            <button type="submit" class="btn-simpan-upload">MULAI LELANG SEKARANG</button>
                        </div>
                    </div>
                </form>
                <?php } ?>

                <?php if ($works_view !== 'auction-create') { ?>
                <div id="daftar-karya">
                    <h5 class="list-karya-title"><?php echo $works_view === 'active' ? 'Lelang yang Sedang Berjalan' : 'Seluruh Karya Saya'; ?></h5>
                    <div class="table-responsive-account">
                        <table class="table-karya">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Cover</th>
                                    <th>Info Karya</th>
                                    <th>Harga Awal</th>
                                    <th>Beli Langsung</th>
                                    <th>Deadline</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                $work_filter = $works_view === 'active' ? " AND status = 'approved' AND waktu_berakhir > NOW()" : "";
                                $q_karya = mysqli_query($koneksi, "SELECT * FROM t_design WHERE id_designer = '$id_desainer' $work_filter ORDER BY id_design DESC");
                                if(mysqli_num_rows($q_karya) > 0){
                                    while($k = mysqli_fetch_assoc($q_karya)){
                                        $deadline = ($k['waktu_berakhir']) ? date('d M Y, H:i', strtotime($k['waktu_berakhir'])) : '-';
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><img src="admin/uploads/<?php echo $k['gambar']; ?>" class="thumb-small"></td>
                                    <td style="text-align:left;">
                                        <strong style="color:#333;"><?php echo $k['judul']; ?></strong><br>
                                        <span class="badge badge-light"><?php echo $k['kategori']; ?></span>
                                    </td>
                                    <td>Rp <?php echo number_format($k['harga_awal'],0,',','.'); ?></td>
                                    <td><?php echo $k['harga_beli_langsung'] > 0 ? 'Rp ' . number_format($k['harga_beli_langsung'],0,',','.') : '-'; ?></td>
                                    <td style="color: #e74c3c; font-weight: 500;"><?php echo $deadline; ?></td>
                                    <td>
                                        <a href="unggahan.php?hapus=<?php echo $k['id_design']; ?>" class="btn-hapus" data-swal-confirm="Karya dan riwayat lelang ini akan dihapus permanen."><i class="fa fa-trash"></i> Hapus</a>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                } else {
                                    echo "<tr><td colspan='6' style='padding:30px;'>" . ($works_view === 'active' ? 'Tidak ada lelang yang sedang aktif.' : 'Belum ada karya yang diunggah.') . "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php } ?>

            </main>
        </div>
    </div>

    <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
    <script src="vendor/animsition/js/animsition.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="vendor/sweetalert/sweetalert.min.js"></script>
    <script src="js/sweetalert-confirm.js"></script>
    <script src="js/main.js"></script>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#preview').attr('src', e.target.result);
                    $('#preview').show();
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // --- 1. SCRIPT POPUP WARNING SEBELUM UPLOAD ---
        $('#formUpload').on('submit', function(e) {
            e.preventDefault(); // Tahan dulu, jangan langsung kirim
            
            swal({
                title: "Penting!",
                text: "Sebelum kamu upload karya ini, apabila karya tidak berhasil terjual dalam batas waktu lelang, kamu bisa mempostingnya ulang nanti. Apakah data sudah benar?",
                icon: "info",
                buttons: ["Cek Lagi", "Ya, Mengerti & Upload"],
                dangerMode: false,
            })
            .then((willUpload) => {
                if (willUpload) {
                    // Kalau user klik 'Ya', baru kita kirim form-nya secara manual
                    this.submit(); 
                } else {
                    // Kalau 'Cek Lagi', diam saja
                }
            });
        });

        // --- 2. SCRIPT POPUP SUKSES SETELAH UPLOAD ---
        const urlParams = new URLSearchParams(window.location.search);
        const pesan = urlParams.get('pesan');

        if(pesan === 'upload_sukses'){
            swal({
                title: "Berhasil!",
                text: "Karya kamu sudah tayang dan lelang telah dimulai!",
                icon: "success",
                button: "OK Siap!",
            }).then(() => {
                window.history.replaceState(null, null, window.location.pathname);
            });
        } 
        else if(pesan === 'hapus_sukses'){
            swal({
                title: "Terhapus!",
                text: "Data karya dan riwayat lelang berhasil dihapus.",
                icon: "success",
                button: "OK",
            }).then(() => {
                window.history.replaceState(null, null, window.location.pathname);
            });
        }
    </script>

</body>
</html>
