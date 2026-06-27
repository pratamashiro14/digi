<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';

// 1. CEK LOGIN KHUSUS DESAINER
require_designer();

$id_user = (int) current_id();

// Ambil status verifikasi saat ini dari DB sebelum memproses POST
$query_verif = mysqli_query($koneksi, "SELECT status_verifikasi FROM t_user WHERE id_user = '$id_user'");
$data_verif = mysqli_fetch_assoc($query_verif);
$status_verifikasi_awal = $data_verif['status_verifikasi'] ?? 'unverified';

// ==========================================================
// 2. LOGIKA UPDATE DATA (DIGABUNG DI SINI)
// ==========================================================
if (isset($_POST['simpan_profil'])) {
    $nama_baru = mysqli_real_escape_string($koneksi, $_POST['nama'] ?? '');
    $telp_baru = mysqli_real_escape_string($koneksi, $_POST['no_telp'] ?? '');
    $alamat_baru = mysqli_real_escape_string($koneksi, $_POST['alamat'] ?? '');
    $email_baru = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
    $pass_baru = $_POST['password'];

    // B. Cek NIK (hanya diupdate jika belum terverifikasi)
    $query_nik = "";
    if ($status_verifikasi_awal !== 'verified' && isset($_POST['nik'])) {
        $nik_baru = mysqli_real_escape_string($koneksi, $_POST['nik'] ?? '');
        $query_nik = ", nik = '$nik_baru'";
    }

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

    // B. Cek Ganti Password
    $query_pass = "";
    if (!empty($pass_baru)) {
        $pass_hash = password_hash($pass_baru, PASSWORD_DEFAULT);
        $query_pass = ", password = '$pass_hash'";
    }

    // D. Cek Upload KTP
    $query_ktp = "";
    if (!empty($_FILES['foto_ktp']['name'])) {
        $nama_ktp = $id_user . "_KTP_" . time() . "_" . $_FILES['foto_ktp']['name'];
        $file_tmp_ktp = $_FILES['foto_ktp']['tmp_name'];
        $folder_simpan_ktp = "admin/uploads/"; 
        
        if (move_uploaded_file($file_tmp_ktp, $folder_simpan_ktp . $nama_ktp)) {
            $query_ktp = ", foto_ktp = '$nama_ktp', status_verifikasi = 'pending'";
        }
    }

    // C. Update Database
    $sql_update = "UPDATE t_user SET 
                   nama = '$nama_baru', 
                   no_telp = '$telp_baru', 
                   alamat = '$alamat_baru', 
                   email = '$email_baru'
                   $query_nik
                   $query_foto
                   $query_pass
                   $query_ktp
                   WHERE id_user = '$id_user'";

    $run_update = mysqli_query($koneksi, $sql_update);

    if ($run_update) {
        // Update Session Nama supaya Header langsung berubah tanpa logout
        $_SESSION['nama_desainer'] = $nama_baru;
        $_SESSION['nama_designer'] = $nama_baru; // Jaga-jaga kalau pake ejaan ini
        $_SESSION['nama'] = $nama_baru;
        $_SESSION['email'] = $email_baru;
        $_SESSION['email_designer'] = $email_baru;

        sweetalert_redirect('Profil desainer berhasil diperbarui.', 'profil_desainer.php', 'success', 'Berhasil!');
    } else {
        sweetalert_back('Gagal memperbarui profil: ' . mysqli_error($koneksi), 'error', 'Gagal!');
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
$status_verifikasi = $data['status_verifikasi'] ?? 'unverified';
$nama_desainer_header = $_SESSION['nama_desainer'] ?? 'Desainer';

// Portofolio Showcase (fitur premium) — ambil item & status premium desainer
$is_prem_designer = is_premium_designer();
$portofolio = [];
$q_porto = mysqli_query($koneksi, "SELECT * FROM t_portofolio WHERE id_designer='$id_user' ORDER BY id_portofolio DESC");
if ($q_porto) { while ($p = mysqli_fetch_assoc($q_porto)) $portofolio[] = $p; }
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
    <link rel="stylesheet" type="text/css" href="vendor/css-hamburgers/hamburgers.min.css">
    <link rel="stylesheet" type="text/css" href="css/util.css">
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <link rel="stylesheet" type="text/css" href="css/account-ui.css?v=<?php echo filemtime(__DIR__ . '/css/account-ui.css'); ?>">

</head>
<body class="animsition account-page">

    <?php
    $active_page = 'designer-profile';
    include 'navbar.php';
    ?>

    <div class="container account-shell">
        <main class="designer-full-content">
            <div class="account-page-header">
                <div><h1>Profil Desainer</h1><p>Kelola identitas publik, verifikasi, dan keamanan akunmu.</p></div>
            </div>
            <form action="" method="POST" enctype="multipart/form-data" class="account-panel designer-profile-panel profile-form">
                <div class="row m-b-30">
                    <div class="col-12">
                        <h4 class="designer-panel-title">Pengaturan Profil Desainer</h4>
                    </div>
                </div>

                <div class="row">
                    <!-- FOTO PROFIL (KIRI) -->
                    <div class="col-md-4 text-center p-b-30 designer-profile-side">
                        <div class="photo-circle designer-photo-circle">
                            <img id="previewFoto" src="<?php echo ($foto != 'default.jpg' && !empty($foto)) ? 'admin/uploads/'.$foto : 'images/icons/icon-header-01.png'; ?>" alt="Profil">
                        </div>
                        <button type="button" class="btn-edit-foto m-t-15" onclick="document.getElementById('inputFoto').click()"><i class="fa fa-camera m-r-5"></i> Ubah Foto</button>
                        <input type="file" name="foto" id="inputFoto" class="sr-file-input" accept="image/jpeg,image/png,image/webp" onchange="tampilkanPreview(this)">
                        
                        <div class="designer-status-card m-t-30">
                            <h5>Status Verifikasi</h5>
                            <?php if ($status_verifikasi == 'unverified') : ?>
                                <div class="designer-status-badge is-danger"><i class="fa fa-times-circle m-r-5"></i> Belum Verifikasi</div>
                            <?php elseif ($status_verifikasi == 'pending') : ?>
                                <div class="designer-status-badge is-warning"><i class="fa fa-clock-o m-r-5"></i> Menunggu Validasi</div>
                            <?php elseif ($status_verifikasi == 'verified') : ?>
                                <div class="designer-status-badge is-success"><i class="fa fa-check-circle m-r-5"></i> Terverifikasi</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- FORM DATA (KANAN) -->
                    <div class="col-md-8 p-l-40 p-l-15-sm">
                        <?php if ($status_verifikasi == 'unverified') : ?>
                            <div class="designer-alert is-danger">
                                <strong>Perhatian!</strong> Anda harus mengunggah foto KTP agar dapat berjualan.
                                <div class="designer-ktp-upload">
                                    <label class="stext-102 p-b-5"><i class="fa fa-upload m-r-5"></i> Unggah KTP Sekarang</label>
                                    <input class="custom-input" type="file" name="foto_ktp" accept="image/jpeg,image/png,image/webp">
                                </div>
                            </div>
                        <?php elseif ($status_verifikasi == 'pending') : ?>
                            <div class="designer-alert is-warning">
                                <strong>Status:</strong> Verifikasi KTP sedang diproses oleh Admin. Harap bersabar.
                            </div>
                        <?php elseif ($status_verifikasi == 'verified') : ?>
                            <div class="designer-alert is-success">
                                <strong>Status:</strong> KTP Terverifikasi. Anda memiliki akses penuh sebagai Desainer.
                            </div>
                        <?php endif; ?>

                        <h5 class="designer-section-title"><i class="fa fa-address-card-o m-r-10"></i>Informasi Dasar</h5>
                        <div class="row">
                            <div class="col-md-6 p-b-15">
                                <label class="stext-102 cl3 p-b-5">Nama Lengkap</label>
                                <input class="custom-input" type="text" name="nama" value="<?php echo htmlspecialchars($nama); ?>">
                            </div>
                            <div class="col-md-6 p-b-15">
                                <label class="stext-102 cl3 p-b-5">Nomor KTP (NIK)</label>
                                <input class="custom-input <?php echo ($status_verifikasi == 'verified') ? 'is-readonly' : ''; ?>" type="text" name="nik" value="<?php echo htmlspecialchars($data['nik'] ?? ''); ?>" maxlength="16" pattern="\d{16}" title="NIK harus berupa 16 digit angka" <?php echo ($status_verifikasi == 'verified') ? 'readonly' : ''; ?> required>
                            </div>
                            <div class="col-md-6 p-b-15">
                                <label class="stext-102 cl3 p-b-5">No. WhatsApp</label>
                                <input class="custom-input" type="text" name="no_telp" value="<?php echo htmlspecialchars($telp); ?>">
                            </div>
                            <div class="col-md-6 p-b-15">
                                <label class="stext-102 cl3 p-b-5">Email</label>
                                <input class="custom-input" type="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                            </div>
                            <div class="col-md-12 p-b-20">
                                <label class="stext-102 cl3 p-b-5">Alamat Lengkap</label>
                                <input class="custom-input" type="text" name="alamat" value="<?php echo htmlspecialchars($alamat); ?>">
                            </div>
                        </div>

                        <hr class="designer-form-divider">

                        <h5 class="designer-section-title"><i class="fa fa-lock m-r-10"></i>Keamanan Akun</h5>
                        <div class="row">
                            <div class="col-md-12 p-b-15">
                                <label class="stext-102 cl3 p-b-5">Kata Sandi Baru</label>
                                <input class="custom-input" type="password" name="password" placeholder="Biarkan kosong jika tidak ingin mengubah password">
                            </div>
                        </div>
                        
                        <div class="designer-form-actions">
                            <button type="submit" name="simpan_profil" class="btn-save"><i class="fa fa-save m-r-5"></i> Simpan Perubahan</button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- ===== PORTOFOLIO SHOWCASE (FITUR PREMIUM) ===== -->
            <div id="portofolio" class="account-panel designer-portfolio-panel">
                <div class="designer-portfolio-header">
                    <div>
                        <h4>Portofolio Showcase</h4>
                        <p>Galeri karya pilihan yang tampil di toko publikmu untuk menarik klien.</p>
                    </div>
                    <span class="porto-flag <?php echo $is_prem_designer ? 'is-on' : 'is-off'; ?>">
                        <i class="fa fa-star"></i> <?php echo $is_prem_designer ? 'Premium Aktif' : 'Khusus Premium'; ?>
                    </span>
                </div>

                <?php if (!$is_prem_designer) { ?>
                    <div class="porto-locked">
                        <i class="fa fa-lock"></i>
                        <div>
                            <b>Portofolio Showcase adalah fitur Premium Desainer.</b>
                            <p>Tampilkan karya terbaikmu di toko agar lebih dipercaya calon klien.
                                <a href="premium.php">Upgrade sekarang →</a></p>
                        </div>
                    </div>
                <?php } else { ?>
                    <!-- Form upload -->
                    <form action="proses_portofolio.php" method="POST" enctype="multipart/form-data" class="porto-upload">
                        <input type="hidden" name="upload_portofolio" value="1">
                        <div class="porto-upload-field">
                            <input type="text" name="judul_portofolio" class="custom-input" placeholder="Judul karya (opsional)" maxlength="150">
                        </div>
                        <div class="porto-upload-file">
                            <input class="custom-input" type="file" name="gambar_portofolio" accept="image/jpeg,image/png,image/webp" required>
                        </div>
                        <button type="submit" class="btn-save porto-upload-btn"><i class="fa fa-plus m-r-5"></i> Tambah Karya</button>
                    </form>

                    <!-- Galeri -->
                    <?php if (count($portofolio) > 0) { ?>
                        <div class="porto-grid">
                            <?php foreach ($portofolio as $p) { ?>
                                <div class="porto-card">
                                    <img src="admin/uploads/<?php echo htmlspecialchars($p['gambar']); ?>"
                                         onerror="this.src='images/item-cart-04.jpg'; this.onerror=null;"
                                         alt="<?php echo htmlspecialchars($p['judul'] ?? 'Portofolio'); ?>">
                                    <?php if (!empty($p['judul'])) { ?>
                                        <span class="porto-judul"><?php echo htmlspecialchars($p['judul']); ?></span>
                                    <?php } ?>
                                    <form action="proses_portofolio.php" method="POST" class="porto-del"
                                          onsubmit="return confirm('Hapus karya ini dari portofolio?');">
                                        <input type="hidden" name="hapus" value="<?php echo (int) $p['id_portofolio']; ?>">
                                        <button type="submit" title="Hapus"><i class="fa fa-trash"></i></button>
                                    </form>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } else { ?>
                        <div class="porto-empty">
                            <i class="zmdi zmdi-collection-image-o"></i>
                            <p>Belum ada karya di portofolio. Unggah karya pertamamu di atas.</p>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </main>
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
