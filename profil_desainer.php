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
    <link rel="stylesheet" type="text/css" href="css/account-ui.css">

    <style>
        body { background-color: #fff; font-family: 'Poppins', sans-serif; }
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
        /* Portofolio Showcase */
        .porto-flag { font-size: 12px; font-weight: 700; padding: 6px 15px; border-radius: 50px; white-space: nowrap; }
        .porto-flag.is-on { background: #1e293b; color: #facc15; box-shadow: 0 4px 10px rgba(30, 41, 59, 0.2); }
        .porto-flag.is-off { background: #f1f5f9; color: #64748b; }
        .porto-locked { display: flex; gap: 18px; align-items: center; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 16px; padding: 25px; margin-top: 20px; color: #475569; }
        .porto-locked i { font-size: 32px; color: #f59e0b; }
        .porto-locked a { color: #1e293b; font-weight: 700; text-decoration: underline; }
        .porto-upload { display: flex; flex-wrap: wrap; gap: 15px; align-items: stretch; margin: 25px 0; padding: 25px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 16px; transition: 0.3s; }
        .porto-upload input[type="file"]::file-selector-button { background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 14px; color: #475569; cursor: pointer; font-weight: 600; margin-right: 15px; transition: 0.3s; }
        .porto-upload input[type="file"]::file-selector-button:hover { background: #e2e8f0; }
        .porto-upload input[type="file"] { padding: 6px 10px; color: #64748b; font-size: 13px; line-height: 24px; }
        .porto-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 24px; }
        .porto-card { position: relative; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; background: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: 0.3s; }
        .porto-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .porto-card img { width: 100%; height: 220px; object-fit: cover; display: block; }
        .porto-judul { position: absolute; left: 0; right: 0; bottom: 0; background: linear-gradient(transparent, rgba(15, 23, 42, 0.8)); color: #fff; font-size: 14px; padding: 30px 15px 12px; font-weight: 600; text-shadow: 0 2px 4px rgba(0,0,0,0.5); }
        .porto-del { position: absolute; top: 12px; right: 12px; margin: 0; }
        .porto-del button { background: rgba(239, 68, 68, 0.9); color: #fff; border: none; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3); transition: 0.3s; }
        .porto-del button:hover { background: #dc2626; transform: scale(1.1); }
        .porto-empty { text-align: center; color: #94a3b8; padding: 50px 20px; border: 1px dashed #e2e8f0; border-radius: 16px; background: #f8fafc; }
        .porto-empty i { font-size: 50px; display: block; margin-bottom: 15px; color: #cbd5e1; }
    </style>
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
            <form action="" method="POST" enctype="multipart/form-data" class="account-panel profile-form" style="border:none; box-shadow:0 10px 40px rgba(0,0,0,0.08); border-radius:16px; padding: 40px;">
                <div class="row m-b-30">
                    <div class="col-12">
                        <h4 class="mtext-105 cl2 p-b-10" style="font-weight: 800; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 25px;">Pengaturan Profil Desainer</h4>
                    </div>
                </div>

                <div class="row">
                    <!-- FOTO PROFIL (KIRI) -->
                    <div class="col-md-4 text-center p-b-30" style="border-right: 1px solid #f1f5f9;">
                        <div class="photo-circle" style="width:160px; height:160px; box-shadow: 0 10px 25px rgba(30, 41, 59, 0.15); border: 5px solid #fff;">
                            <img id="previewFoto" src="<?php echo ($foto != 'default.jpg' && !empty($foto)) ? 'admin/uploads/'.$foto : 'images/icons/icon-header-01.png'; ?>" alt="Profil">
                        </div>
                        <button type="button" class="btn-edit-foto m-t-15" style="background:#1e293b; color:#fff; padding:8px 24px; border-radius:30px; font-weight:600; cursor:pointer; border:none;" onclick="document.getElementById('inputFoto').click()"><i class="fa fa-camera m-r-5"></i> Ubah Foto</button>
                        <input type="file" name="foto" id="inputFoto" accept="image/jpeg,image/png,image/webp" style="display: none;" onchange="tampilkanPreview(this)">
                        
                        <div class="m-t-30 text-left" style="background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0;">
                            <h5 style="font-size:14px; font-weight:700; color:#475569; margin-bottom:10px; text-align:center;">Status Verifikasi</h5>
                            <?php if ($status_verifikasi == 'unverified') : ?>
                                <div style="background:#fee2e2; color:#ef4444; padding:10px 15px; border-radius:8px; font-size:13px; font-weight:700; text-align:center;"><i class="fa fa-times-circle m-r-5"></i> Belum Verifikasi</div>
                            <?php elseif ($status_verifikasi == 'pending') : ?>
                                <div style="background:#fef3c7; color:#f59e0b; padding:10px 15px; border-radius:8px; font-size:13px; font-weight:700; text-align:center;"><i class="fa fa-clock-o m-r-5"></i> Menunggu Validasi</div>
                            <?php elseif ($status_verifikasi == 'verified') : ?>
                                <div style="background:#dcfce7; color:#22c55e; padding:10px 15px; border-radius:8px; font-size:13px; font-weight:700; text-align:center;"><i class="fa fa-check-circle m-r-5"></i> Terverifikasi</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- FORM DATA (KANAN) -->
                    <div class="col-md-8 p-l-40 p-l-15-sm">
                        <?php if ($status_verifikasi == 'unverified') : ?>
                            <div class="alert alert-danger" style="font-size:13px; border-radius:10px; border:none; background:#fee2e2; color:#b91c1c; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.1);">
                                <strong>Perhatian!</strong> Anda harus mengunggah foto KTP agar dapat berjualan.
                                <div class="m-t-15 p-t-15" style="border-top:1px solid #fca5a5;">
                                    <label class="stext-102 p-b-5" style="color:#991b1b; font-weight:bold;"><i class="fa fa-upload m-r-5"></i> Unggah KTP Sekarang</label>
                                    <input class="custom-input" type="file" name="foto_ktp" accept="image/jpeg,image/png,image/webp" style="padding-top:8px; background:#fff; border-color:#fca5a5;">
                                </div>
                            </div>
                        <?php elseif ($status_verifikasi == 'pending') : ?>
                            <div class="alert alert-warning" style="font-size:13px; border-radius:10px; border:none; background:#fef3c7; color:#b45309; box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.1);">
                                <strong>Status:</strong> Verifikasi KTP sedang diproses oleh Admin. Harap bersabar.
                            </div>
                        <?php elseif ($status_verifikasi == 'verified') : ?>
                            <div class="alert alert-success" style="font-size:13px; border-radius:10px; border:none; background:#dcfce7; color:#15803d; box-shadow: 0 4px 6px -1px rgba(34, 197, 94, 0.1);">
                                <strong>Status:</strong> KTP Terverifikasi. Anda memiliki akses penuh sebagai Desainer.
                            </div>
                        <?php endif; ?>

                        <h5 style="font-size:16px; font-weight:700; color:#1e293b; margin-bottom:20px; margin-top:10px;"><i class="fa fa-address-card-o m-r-10 text-primary" style="color:#4e8eff;"></i>Informasi Dasar</h5>
                        <div class="row">
                            <div class="col-md-6 p-b-15">
                                <label class="stext-102 cl3 p-b-5">Nama Lengkap</label>
                                <input class="custom-input" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px;" type="text" name="nama" value="<?php echo $nama; ?>">
                            </div>
                            <div class="col-md-6 p-b-15">
                                <label class="stext-102 cl3 p-b-5">Nomor KTP (NIK)</label>
                                <input class="custom-input" style="background:<?php echo ($status_verifikasi == 'verified') ? '#f1f5f9' : '#f8fafc'; ?>; border:1px solid #e2e8f0; border-radius:10px; <?php echo ($status_verifikasi == 'verified') ? 'color:#64748b;' : ''; ?>" type="text" name="nik" value="<?php echo htmlspecialchars($data['nik'] ?? ''); ?>" maxlength="16" pattern="\d{16}" title="NIK harus berupa 16 digit angka" <?php echo ($status_verifikasi == 'verified') ? 'readonly' : ''; ?> required>
                            </div>
                            <div class="col-md-6 p-b-15">
                                <label class="stext-102 cl3 p-b-5">No. WhatsApp</label>
                                <input class="custom-input" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px;" type="text" name="no_telp" value="<?php echo $telp; ?>">
                            </div>
                            <div class="col-md-6 p-b-15">
                                <label class="stext-102 cl3 p-b-5">Email</label>
                                <input class="custom-input" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px;" type="email" name="email" value="<?php echo $email; ?>">
                            </div>
                            <div class="col-md-12 p-b-20">
                                <label class="stext-102 cl3 p-b-5">Alamat Lengkap</label>
                                <input class="custom-input" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px;" type="text" name="alamat" value="<?php echo $alamat; ?>">
                            </div>
                        </div>

                        <hr style="border-top:1px dashed #cbd5e1; margin-bottom:20px;">

                        <h5 style="font-size:16px; font-weight:700; color:#1e293b; margin-bottom:20px;"><i class="fa fa-lock m-r-10 text-primary" style="color:#4e8eff;"></i>Keamanan Akun</h5>
                        <div class="row">
                            <div class="col-md-12 p-b-15">
                                <label class="stext-102 cl3 p-b-5">Kata Sandi Baru</label>
                                <input class="custom-input" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px;" type="password" name="password" placeholder="Biarkan kosong jika tidak ingin mengubah password">
                            </div>
                        </div>
                        
                        <div class="p-t-15" style="display: flex; justify-content: flex-end;">
                            <button type="submit" name="simpan_profil" class="btn-save" style="background: linear-gradient(135deg, #1e293b, #0f172a); border-radius:30px; font-size:15px; letter-spacing:0.5px; box-shadow:0 8px 20px rgba(15, 23, 42, 0.2); width:auto; padding: 0 40px;"><i class="fa fa-save m-r-5"></i> Simpan Perubahan</button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- ===== PORTOFOLIO SHOWCASE (FITUR PREMIUM) ===== -->
            <div id="portofolio" class="account-panel" style="margin-top:40px; border:none; box-shadow:0 10px 40px rgba(0,0,0,0.08); border-radius:16px; padding: 40px;">
                <div class="account-page-header" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 25px;">
                    <div>
                        <h4 style="font-size:22px; margin:0; font-weight:800; color:#1e293b;">Portofolio Showcase</h4>
                        <p style="margin:4px 0 0; color:#64748b;">Galeri karya pilihan yang tampil di toko publikmu untuk menarik klien.</p>
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
                            <p style="margin:4px 0 0;">Tampilkan karya terbaikmu di toko agar lebih dipercaya calon klien.
                                <a href="premium.php">Upgrade sekarang →</a></p>
                        </div>
                    </div>
                <?php } else { ?>
                    <!-- Form upload -->
                    <form action="proses_portofolio.php" method="POST" enctype="multipart/form-data" class="porto-upload">
                        <input type="hidden" name="upload_portofolio" value="1">
                        <div style="flex: 2; min-width:300px;">
                            <input type="text" name="judul_portofolio" class="custom-input" placeholder="Judul karya (opsional)" maxlength="150" style="background:#fff; border:1px solid #cbd5e1; width:100%; box-sizing:border-box;">
                        </div>
                        <div style="flex: 2; min-width:350px;">
                            <input class="custom-input" type="file" name="gambar_portofolio" accept="image/jpeg,image/png,image/webp" required style="background:#fff; border:1px solid #cbd5e1; width:100%; box-sizing:border-box;">
                        </div>
                        <button type="submit" class="btn-save" style="flex: 1; width:auto; min-width:180px; margin-top:0; padding:0 30px; background: linear-gradient(135deg, #4e8eff, #2563eb); border-radius:30px; box-shadow:0 6px 15px rgba(37, 99, 235, 0.2);"><i class="fa fa-plus m-r-5"></i> Tambah Karya</button>
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
