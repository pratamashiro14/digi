<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';

// 1. CEK LOGIN USER BIASA — halaman khusus pembeli/user
require_user();

$id_user = current_id();

// ==========================================================
// 2. LOGIKA UPDATE DATA
// ==========================================================
if (isset($_POST['simpan_profil'])) {
    $nama_baru = $_POST['nama'];
    $telp_baru = $_POST['no_telp'];
    $alamat_baru = $_POST['alamat'];
    // Email biasanya read-only agar tidak merusak login, tapi kalau mau diubah bisa dibuka
    // $email_baru = $_POST['email']; 
    $pass_baru = $_POST['password'];

    // A. Cek Upload Foto
    $query_foto = "";
    if (!empty($_FILES['foto']['name'])) {
        $nama_foto = rand(100,999) . "_" . $_FILES['foto']['name'];
        $file_tmp = $_FILES['foto']['tmp_name'];
        $folder_simpan = "admin/uploads/"; 
        
        if (move_uploaded_file($file_tmp, $folder_simpan . $nama_foto)) {
            $query_foto = ", foto = '$nama_foto'";
            // Update session foto jika perlu
        }
    }

    // B. Cek Ganti Password
    $query_pass = "";
    if (!empty($pass_baru)) {
        // Kita gunakan password_hash agar lebih aman (sesuai standar registrasi user kamu)
        $pass_hash = password_hash($pass_baru, PASSWORD_DEFAULT);
        $query_pass = ", password = '$pass_hash'";
    }

    // C. Update Database
    $sql_update = "UPDATE t_user SET 
                   nama = '$nama_baru', 
                   no_telp = '$telp_baru', 
                   alamat = '$alamat_baru'
                   $query_foto
                   $query_pass
                   WHERE id_user = '$id_user'";

    $run_update = mysqli_query($koneksi, $sql_update);

    if ($run_update) {
        // Update Session Nama supaya Header langsung berubah
        $_SESSION['nama'] = $nama_baru;

        sweetalert_redirect('Profil berhasil diperbarui.', 'profil.php', 'success', 'Berhasil!');
    } else {
        sweetalert_back('Gagal memperbarui profil: ' . mysqli_error($koneksi), 'error', 'Gagal!');
    }
}

// 3. AMBIL DATA TERBARU
$query = mysqli_query($koneksi, "SELECT * FROM t_user WHERE id_user = '$id_user'");
$data = mysqli_fetch_assoc($query);

// Variabel Data
$nama = $data['nama'] ?? '';
$telp = $data['no_telp'] ?? '';
$alamat = $data['alamat'] ?? '';
$email = $data['email'] ?? '';
$foto = $data['foto'] ?? 'default.jpg'; 
$nama_header = $_SESSION['nama'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Profil Saya</title>
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
        
        /* SIDEBAR STYLE (Sama dengan Desainer) */
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-menu li { margin-bottom: 12px; }
        .sidebar-menu a { display: flex; align-items: center; font-size: 16px; color: #555; text-decoration: none; font-weight: 500; padding: 10px 15px; border-radius: 8px; transition: 0.2s; }
        .sidebar-menu a i { width: 30px; font-size: 18px; margin-right: 5px; color: #888; text-align: center; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background-color: #f5f5f5; color: #333; font-weight: 700; }
        .sidebar-menu a:hover i, .sidebar-menu a.active i { color: #333; }
        
        /* FOTO PROFIL STYLE */
        .photo-circle { width: 150px; height: 150px; border-radius: 50%; background-color: #e6e6e6; margin: 0 auto 15px; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 4px solid #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .photo-circle img { width: 100%; height: 100%; object-fit: cover; }
        .btn-edit-foto { background-color: #888; color: #fff; border: none; padding: 6px 20px; border-radius: 20px; font-size: 13px; cursor: pointer; transition:0.3s; font-weight: 600; }
        .btn-edit-foto:hover { background-color: #555; }
        
        /* FORM INPUT STYLE */
        .custom-input { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; color: #555; background: #fff; height: 45px; }
        .custom-input:focus { border-color: #717fe0; outline: none; }
        .custom-input[readonly] { background-color: #f9f9f9; color: #999; }
        
        /* TOMBOL SIMPAN */
        .btn-save { background: linear-gradient(90deg, #4e8eff, #6b4eff); color: #fff; width: 100%; padding: 12px; border: none; border-radius: 50px; font-size: 16px; font-weight: 600; margin-top: 20px; cursor: pointer; transition: 0.3s; }
        .btn-save:hover { opacity: 0.9; transform: translateY(-2px); }
        
        @media (min-width: 768px) { .border-right-custom { border-right: 1px solid #eee; } }
    </style>
</head>
<body class="animsition account-page">

    <?php
    $active_page = 'profil';
    include 'navbar.php';
    ?>

    <div class="container account-shell">
        <div class="row account-layout">
            
            <div class="col-md-3 col-lg-3 p-b-30">
                <aside class="account-sidebar">
                <h4 class="account-sidebar-title">Menu Pembeli</h4>
                <ul class="sidebar-menu">
                    <li><a href="profil.php" class="active"><i class="fa fa-user-circle"></i> Profil Saya</a></li>
                    <li><a href="riwayat.php"><i class="fa fa-shopping-bag"></i> Riwayat Pembelian</a></li>
                    <li><a href="pesan.php"><i class="fa fa-comments"></i> Pesan</a></li>
                    <li><a href="logout.php"><i class="fa fa-sign-out"></i> Logout</a></li>
                </ul>
                </aside>
            </div>

            <div class="col-md-9 col-lg-9 account-content">
                <div class="account-page-header">
                    <div><h1>Profil Saya</h1><p>Kelola informasi akun dan keamanan profil pembeli.</p></div>
                </div>
                <form action="" method="POST" enctype="multipart/form-data" class="account-panel profile-form">
                    <div class="row">
                        
                        <div class="col-md-4 text-center p-b-30">
                            <div class="profile-photo-panel">
                            <div class="photo-circle">
                                <img id="previewFoto" src="<?php echo ($foto != 'default.jpg' && !empty($foto)) ? 'admin/uploads/'.$foto : 'images/icons/icon-header-01.png'; ?>" alt="Profil">
                            </div>
                            <button type="button" class="btn-edit-foto m-t-10" onclick="document.getElementById('inputFoto').click()">Edit Foto</button>
                            <input type="file" name="foto" id="inputFoto" accept="image/jpeg,image/png,image/webp" style="display: none;" onchange="tampilkanPreview(this)">
                            </div>
                        </div>

                        <div class="col-md-8">
                            <div class="p-b-15">
                                <label class="stext-102 cl3 p-b-5">Nama Lengkap</label>
                                <input class="custom-input" type="text" name="nama" value="<?php echo $nama; ?>">
                            </div>
                            <div class="p-b-15">
                                <label class="stext-102 cl3 p-b-5">No. WhatsApp / Telepon</label>
                                <input class="custom-input" type="text" name="no_telp" value="<?php echo $telp; ?>" placeholder="Contoh: 08123456789">
                            </div>
                            <div class="p-b-15">
                                <label class="stext-102 cl3 p-b-5">Alamat Lengkap</label>
                                <input class="custom-input" type="text" name="alamat" value="<?php echo $alamat; ?>" placeholder="Masukkan alamat pengiriman...">
                            </div>
                            <div class="p-b-15">
                                <label class="stext-102 cl3 p-b-5">Email</label>
                                <input class="custom-input" type="email" name="email" value="<?php echo $email; ?>" readonly>
                            </div>
                            <div class="p-b-15">
                                <label class="stext-102 cl3 p-b-5">Kata Sandi Baru</label>
                                <input class="custom-input" type="password" name="password" placeholder="(Kosongkan jika tidak ingin mengubah)">
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
