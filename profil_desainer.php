<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';

// 1. CEK LOGIN KHUSUS DESAINER
require_designer();

$id_user = current_id();

// ==========================================================
// 2. LOGIKA UPDATE DATA (DIGABUNG DI SINI)
// ==========================================================
if (isset($_POST['simpan_profil'])) {
    $nama_baru = $_POST['nama'];
    $telp_baru = $_POST['no_telp'];
    $alamat_baru = $_POST['alamat'];
    $email_baru = $_POST['email'];
    $pass_baru = $_POST['password'];

    // A. Cek Upload Foto
    $query_foto = "";
    if (!empty($_FILES['foto']['name'])) {
        $nama_foto = $_FILES['foto']['name'];
        $file_tmp = $_FILES['foto']['tmp_name'];
        $folder_simpan = "admin/uploads/"; 
        
        if (move_uploaded_file($file_tmp, $folder_simpan . $nama_foto)) {
            $query_foto = ", foto = '$nama_foto'";
        }
    }

    // B. Cek Ganti Password (Pakai MD5 biar cocok sama login user)
    $query_pass = "";
    if (!empty($pass_baru)) {
        $pass_md5 = md5($pass_baru);
        $query_pass = ", password = '$pass_md5'";
    }

    // C. Update Database
    $sql_update = "UPDATE t_user SET 
                   nama = '$nama_baru', 
                   no_telp = '$telp_baru', 
                   alamat = '$alamat_baru', 
                   email = '$email_baru'
                   $query_foto
                   $query_pass
                   WHERE id_user = '$id_user'";

    $run_update = mysqli_query($koneksi, $sql_update);

    if ($run_update) {
        // Update Session Nama supaya Header langsung berubah tanpa logout
        $_SESSION['nama_desainer'] = $nama_baru;
        $_SESSION['nama_designer'] = $nama_baru; // Jaga-jaga kalau pake ejaan ini
        $_SESSION['email_designer'] = $email_baru;

        echo "<script>
            alert('Profil Berhasil Diperbarui!');
            window.location.href='profil_desainer.php'; // Refresh halaman
        </script>";
    } else {
        echo "<script>alert('Gagal update: " . mysqli_error($koneksi) . "');</script>";
    }
}
// ==========================================================
// AKHIR LOGIKA UPDATE
// ==========================================================


// 3. AMBIL DATA TERBARU UNTUK DITAMPILKAN DI FORM
$query = mysqli_query($koneksi, "SELECT * FROM t_user WHERE id_user = '$id_user'");
$data = mysqli_fetch_assoc($query);

// Variabel Data (Biar form tidak error kalau kosong)
$nama = $data['nama'] ?? '';
$telp = $data['no_telp'] ?? '';
$alamat = $data['alamat'] ?? '';
$email = $data['email'] ?? '';
$foto = $data['foto'] ?? 'default.jpg'; 
$nama_desainer_header = $_SESSION['nama_desainer'] ?? 'Desainer';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Profil Desainer</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="icon" type="image/png" href="images/icons/favicon.png"/>
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/iconic/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" type="text/css" href="css/util.css">
    <link rel="stylesheet" type="text/css" href="css/main.css">

    <style>
        body { background-color: #fff; font-family: 'Poppins', sans-serif; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-menu li { margin-bottom: 12px; }
        .sidebar-menu a { display: flex; align-items: center; font-size: 16px; color: #555; text-decoration: none; font-weight: 500; padding: 10px 15px; border-radius: 8px; transition: 0.2s; }
        .sidebar-menu a i { width: 30px; font-size: 18px; margin-right: 5px; color: #888; text-align: center; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background-color: #f5f5f5; color: #333; font-weight: 700; }
        .sidebar-menu a:hover i, .sidebar-menu a.active i { color: #333; }
        .photo-circle { width: 150px; height: 150px; border-radius: 50%; background-color: #e6e6e6; margin: 0 auto 15px; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 4px solid #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .photo-circle img { width: 100%; height: 100%; object-fit: cover; }
        .btn-edit-foto { background-color: #888; color: #fff; border: none; padding: 6px 20px; border-radius: 20px; font-size: 13px; cursor: pointer; transition:0.3s; font-weight: 600; }
        .btn-edit-foto:hover { background-color: #555; }
        .custom-input { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; color: #555; background: #fff; height: 45px; }
        .custom-input:focus { border-color: #717fe0; outline: none; }
        .btn-save { background: linear-gradient(90deg, #4e8eff, #6b4eff); color: #fff; width: 100%; padding: 12px; border: none; border-radius: 50px; font-size: 16px; font-weight: 600; margin-top: 20px; cursor: pointer; transition: 0.3s; }
        .btn-save:hover { opacity: 0.9; transform: translateY(-2px); }
        .portfolio-box { width: 100%; height: 150px; border: 2px dashed #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; background: #fafafa; }
        .portfolio-box:hover { border-color: #4e8eff; background: #f0f8ff; }
        .plus-icon { font-size: 40px; color: #ccc; }
        @media (min-width: 768px) { .border-right-custom { border-right: 1px solid #eee; } }
    </style>
</head>
<body class="animsition">

    <header class="header-v4">
        <div class="container-menu-desktop">
            <div class="top-bar">
                <div class="content-topbar flex-sb-m h-full container">
                    <div class="left-top-bar">Pilih Desain Sesuai Standarmu!</div>
                    <div class="right-top-bar flex-w h-full">
                        <a href="#" class="flex-c-m trans-04 p-lr-25">Bantuan</a>
                        <a href="profil_desainer.php" class="flex-c-m trans-04 p-lr-25">Halo, <?php echo $nama_desainer_header; ?></a>
                        <a href="logout.php" class="flex-c-m trans-04 p-lr-25">Logout</a>
                    </div>
                </div>
            </div>
            <div class="wrap-menu-desktop how-shadow1">
                <nav class="limiter-menu-desktop container">
                    <a href="index.php" class="logo"><img src="images/icons/logo-01.png" alt="IMG-LOGO"></a>
                    <div class="menu-desktop">
                        <ul class="main-menu">
                            <li><a href="index.php">Beranda</a></li>
                            <li><a href="product.php">Pasar Desain</a></li>
                            <li><a href="shoping-cart.php">Pembelian</a></li>
                        </ul>
                    </div>    
                </nav>
            </div>    
        </div>
    </header>

    <div class="container p-t-50 p-b-80">
        <div class="row">
            
            <div class="col-md-3 col-lg-3 p-b-30 border-right-custom">
                <h4 class="mtext-105 cl2 p-b-30" style="font-size: 18px;">Menu Desainer</h4>
                <ul class="sidebar-menu">
                    <li><a href="profil_desainer.php" class="active"><i class="fa fa-user-circle"></i> Profil Desainer</a></li>
                    <li><a href="unggahan.php"><i class="fa fa-cloud-upload"></i> Unggahan</a></li>
                    <li><a href="penjualan.php"><i class="fa fa-shopping-basket"></i> Penjualan</a></li> 
                    <li><a href="pesan.php"><i class="fa fa-comments"></i> Pesan</a></li>
                </ul>
            </div>

            <div class="col-md-9 col-lg-9 p-l-40 p-l-15-lg">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-4 text-center p-b-30">
                            <div class="photo-circle">
                                <img id="previewFoto" src="<?php echo ($foto != 'default.jpg' && !empty($foto)) ? 'admin/uploads/'.$foto : 'images/icons/icon-header-01.png'; ?>" alt="Profil">
                            </div>
                            <button type="button" class="btn-edit-foto m-t-10" onclick="document.getElementById('inputFoto').click()">Edit Foto</button>
                            <input type="file" name="foto" id="inputFoto" style="display: none;" onchange="tampilkanPreview(this)">
                        </div>

                        <div class="col-md-8">
                            <h4 class="mtext-105 cl2 p-b-20" style="text-transform: uppercase; font-weight: 800;">EDIT PROFIL DESAINER</h4>

                            <div class="p-b-15">
                                <label class="stext-102 cl3 p-b-5">Nama Desainer</label>
                                <input class="custom-input" type="text" name="nama" value="<?php echo $nama; ?>">
                            </div>
                            <div class="p-b-15">
                                <label class="stext-102 cl3 p-b-5">No. WhatsApp</label>
                                <input class="custom-input" type="text" name="no_telp" value="<?php echo $telp; ?>">
                            </div>
                            <div class="p-b-15">
                                <label class="stext-102 cl3 p-b-5">Alamat</label>
                                <input class="custom-input" type="text" name="alamat" value="<?php echo $alamat; ?>">
                            </div>
                            <div class="p-b-15">
                                <label class="stext-102 cl3 p-b-5">Email</label>
                                <input class="custom-input" type="email" name="email" value="<?php echo $email; ?>">
                            </div>
                            <div class="p-b-15">
                                <label class="stext-102 cl3 p-b-5">Kata Sandi Baru</label>
                                <input class="custom-input" type="password" name="password" placeholder="(Kosongkan jika tidak ubah)">
                            </div>
                            
                            <div class="p-t-10">
                                <label class="stext-102 cl3 p-b-5" style="font-weight:600;">Portofolio</label>
                                <div class="portfolio-box"><span class="plus-icon">+</span></div>
                            </div>

                            <button type="submit" name="simpan_profil" class="btn-save">Simpan Perubahan</button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
    <script src="vendor/animsition/js/animsition.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>

    <script>
        function tampilkanPreview(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#previewFoto').attr('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>