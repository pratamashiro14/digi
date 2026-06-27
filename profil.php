<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';

// 1. CEK LOGIN USER BIASA — halaman khusus pembeli/user
require_user();

$id_user = (int) current_id();

// ==========================================================
// 2. LOGIKA UPDATE DATA
// ==========================================================
if (isset($_POST['simpan_profil'])) {
    $nama_baru = trim($_POST['nama'] ?? '');
    $telp_baru = trim($_POST['no_telp'] ?? '');
    $alamat_baru = trim($_POST['alamat'] ?? '');
    $pass_baru = $_POST['password'] ?? '';

    // Kolom dasar (prepared statement — cegah SQL injection)
    $sets  = ['nama = ?', 'no_telp = ?', 'alamat = ?'];
    $types = 'sss';
    $vals  = [$nama_baru, $telp_baru, $alamat_baru];

    // A. Cek Upload Foto (opsional)
    if (!empty($_FILES['foto']['name'])) {
        $nama_foto = rand(100,999) . "_" . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($_FILES['foto']['name']));
        $file_tmp = $_FILES['foto']['tmp_name'];
        $folder_simpan = "admin/uploads/";

        if (move_uploaded_file($file_tmp, $folder_simpan . $nama_foto)) {
            $sets[]  = 'foto = ?';
            $types  .= 's';
            $vals[]  = $nama_foto;
        }
    }

    // B. Cek Ganti Password (opsional)
    if (!empty($pass_baru)) {
        $pass_hash = password_hash($pass_baru, PASSWORD_DEFAULT);
        $sets[]  = 'password = ?';
        $types  .= 's';
        $vals[]  = $pass_hash;
    }

    // C. Update Database
    $types .= 'i';
    $vals[] = $id_user;
    $sql_update = "UPDATE t_user SET " . implode(', ', $sets) . " WHERE id_user = ?";
    $stmt = mysqli_prepare($koneksi, $sql_update);
    mysqli_stmt_bind_param($stmt, $types, ...$vals);
    $run_update = mysqli_stmt_execute($stmt);

    if ($run_update) {
        // Update Session Nama supaya Header langsung berubah
        $_SESSION['nama'] = $nama_baru;

        sweetalert_redirect('Profil berhasil diperbarui.', 'profil.php', 'success', 'Berhasil!');
    } else {
        sweetalert_back('Gagal memperbarui profil.', 'error', 'Gagal!');
    }
}

// 3. AMBIL DATA TERBARU
$query = mysqli_query($koneksi, "SELECT * FROM t_user WHERE id_user = '$id_user'");
$data = mysqli_fetch_assoc($query);

$query_premium = mysqli_prepare($koneksi, "
    SELECT status, tanggal_berakhir
    FROM t_premium
    WHERE id_user = ?
      AND tipe_premium IN ('pengguna', 'buyer')
      AND status = 'aktif'
      AND tanggal_berakhir >= CURDATE()
    ORDER BY tanggal_berakhir DESC
    LIMIT 1
");
mysqli_stmt_bind_param($query_premium, "i", $id_user);
mysqli_stmt_execute($query_premium);
$premium_result = mysqli_stmt_get_result($query_premium);
$premium_data = mysqli_fetch_assoc($premium_result);
mysqli_stmt_close($query_premium);

// Variabel Data
$nama = $data['nama'] ?? '';
$telp = $data['no_telp'] ?? '';
$alamat = $data['alamat'] ?? '';
$email = $data['email'] ?? '';
$nik = $data['nik'] ?? '';
$foto = $data['foto'] ?? 'default.jpg'; 
$nama_header = $_SESSION['nama'] ?? 'User';
$is_premium = !empty($premium_data) || (($data['premium'] ?? 0) == 1) || (($data['status_member'] ?? 'free') === 'premium');
$status_premium = $is_premium ? 'Premium' : 'Belum Premium';
$premium_expired = !empty($premium_data['tanggal_berakhir']) ? date('d M Y', strtotime($premium_data['tanggal_berakhir'])) : '';
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

</head>
<body class="animsition account-page">

    <?php
    $active_page = 'profil';
    include 'navbar.php';
    ?>

    <div class="container account-shell">
        <div class="row account-layout">
            
            <div class="col-md-4 col-lg-3 p-b-30">
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

            <div class="col-md-8 col-lg-9 account-content">
                <div class="account-page-header">
                    <div><h1>Profil Saya</h1><p>Kelola informasi akun dan keamanan profil pembeli.</p></div>
                </div>
                <form action="" method="POST" enctype="multipart/form-data" class="account-panel customer-profile-panel profile-form">
                    <div class="row m-b-30">
                        <div class="col-12">
                            <h4 class="account-panel-title">Pengaturan Profil Pembeli</h4>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 text-center p-b-30 customer-profile-side">
                            <div class="photo-circle account-photo-circle">
                                <img id="previewFoto" src="<?php echo ($foto != 'default.jpg' && !empty($foto)) ? 'admin/uploads/'.htmlspecialchars($foto) : 'images/icons/icon-header-01.png'; ?>" alt="Profil">
                            </div>
                            <button type="button" class="btn-edit-foto m-t-15" onclick="document.getElementById('inputFoto').click()"><i class="fa fa-camera m-r-5"></i> Ubah Foto</button>
                            <input type="file" name="foto" id="inputFoto" class="sr-file-input" accept="image/jpeg,image/png,image/webp" onchange="tampilkanPreview(this)">
                        </div>

                        <div class="col-md-8 p-l-40 p-l-15-sm">
                            <h5 class="account-section-title"><i class="fa fa-address-card-o m-r-10"></i>Informasi Dasar</h5>
                            <div class="row">
                                <div class="col-md-6 p-b-15">
                                    <label class="stext-102 cl3 p-b-5">Nama Lengkap</label>
                                    <input class="custom-input" type="text" name="nama" value="<?php echo htmlspecialchars($nama); ?>">
                                </div>
                                <div class="col-md-6 p-b-15">
                                    <label class="stext-102 cl3 p-b-5">No. WhatsApp / Telepon</label>
                                    <input class="custom-input" type="text" name="no_telp" value="<?php echo htmlspecialchars($telp); ?>" placeholder="Contoh: 08123456789">
                                </div>
                                <div class="col-md-6 p-b-15">
                                    <label class="stext-102 cl3 p-b-5">Email</label>
                                    <input class="custom-input is-readonly" type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
                                </div>
                                <div class="col-md-6 p-b-15">
                                    <label class="stext-102 cl3 p-b-5">NIK</label>
                                    <input class="custom-input is-readonly" type="text" value="<?php echo htmlspecialchars($nik ?: 'Belum diisi'); ?>" disabled>
                                </div>
                                <div class="col-md-6 p-b-15">
                                    <label class="stext-102 cl3 p-b-5">Status Premium</label>
                                    <div class="status-member-box">
                                        <span class="status-member-badge <?php echo $is_premium ? 'is-premium' : 'is-free'; ?>">
                                            <i class="fa <?php echo $is_premium ? 'fa-star' : 'fa-user-o'; ?> m-r-5"></i>
                                            <?php echo $status_premium; ?>
                                        </span>
                                        <?php if ($is_premium && $premium_expired): ?>
                                            <small>sampai <?php echo $premium_expired; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-12 p-b-20">
                                    <label class="stext-102 cl3 p-b-5">Alamat Lengkap</label>
                                    <input class="custom-input" type="text" name="alamat" value="<?php echo htmlspecialchars($alamat); ?>" placeholder="Masukkan alamat pengiriman...">
                                </div>
                            </div>

                            <hr class="account-form-divider">

                            <h5 class="account-section-title"><i class="fa fa-lock m-r-10"></i>Keamanan Akun</h5>
                            <div class="row">
                                <div class="col-md-12 p-b-15">
                                    <label class="stext-102 cl3 p-b-5">Kata Sandi Baru</label>
                                    <input class="custom-input" type="password" name="password" placeholder="Biarkan kosong jika tidak ingin mengubah password">
                                </div>
                            </div>
                            
                            <div class="account-form-actions">
                                <button type="submit" name="simpan_profil" class="btn-save"><i class="fa fa-save m-r-5"></i> Simpan Perubahan</button>
                            </div>
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
