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
        .porto-flag { font-size: 12px; font-weight: 700; padding: 6px 12px; border-radius: 50px; white-space: nowrap; }
        .porto-flag.is-on { background: #fff3cd; color: #856404; }
        .porto-flag.is-off { background: #eef2ff; color: #4338ca; }
        .porto-locked { display: flex; gap: 14px; align-items: center; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 20px; margin-top: 18px; color: #475569; }
        .porto-locked i { font-size: 26px; color: #f59e0b; }
        .porto-locked a { color: #4e8eff; font-weight: 700; text-decoration: none; }
        .porto-upload { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin: 18px 0 22px; padding: 16px; background: #f9fafb; border: 1px solid #eee; border-radius: 12px; }
        .porto-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 14px; }
        .porto-card { position: relative; border-radius: 12px; overflow: hidden; border: 1px solid #eee; background: #fff; }
        .porto-card img { width: 100%; height: 150px; object-fit: cover; display: block; }
        .porto-judul { position: absolute; left: 0; right: 0; bottom: 0; background: linear-gradient(transparent, rgba(0,0,0,0.65)); color: #fff; font-size: 12px; padding: 16px 10px 8px; }
        .porto-del { position: absolute; top: 8px; right: 8px; margin: 0; }
        .porto-del button { background: rgba(220,38,38,0.92); color: #fff; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 13px; display: flex; align-items: center; justify-content: center; }
        .porto-del button:hover { background: #b91c1c; }
        .porto-empty { text-align: center; color: #9ca3af; padding: 40px 20px; }
        .porto-empty i { font-size: 44px; display: block; margin-bottom: 10px; }
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
            <form action="" method="POST" enctype="multipart/form-data" class="account-panel profile-form">
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

                            <?php if ($status_verifikasi == 'unverified') : ?>
                                <div class="alert alert-danger" style="font-size:13px;">
                                    <strong>Perhatian!</strong> Akun Anda belum diverifikasi. Silakan unggah foto KTP Anda agar dapat mengunggah karya dan berjualan.
                                </div>
                                <div class="p-b-15">
                                    <label class="stext-102 cl3 p-b-5" style="color:red; font-weight:bold;">Unggah Foto KTP</label>
                                    <input class="custom-input" type="file" name="foto_ktp" accept="image/*" style="padding-top:8px;">
                                </div>
                            <?php elseif ($status_verifikasi == 'pending') : ?>
                                <div class="alert alert-warning" style="font-size:13px;">
                                    <strong>Status:</strong> Verifikasi KTP sedang diproses oleh Admin. Harap bersabar.
                                </div>
                            <?php elseif ($status_verifikasi == 'verified') : ?>
                                <div class="alert alert-success" style="font-size:13px;">
                                    <strong>Status:</strong> KTP Terverifikasi. Anda memiliki akses penuh sebagai Desainer.
                                </div>
                            <?php endif; ?>

                            <div class="p-b-15">
                                <label class="stext-102 cl3 p-b-5">Nama Desainer</label>
                                <input class="custom-input" type="text" name="nama" value="<?php echo $nama; ?>">
                            </div>
                            <div class="p-b-15">
                                <label class="stext-102 cl3 p-b-5">NIK (Nomor Induk Kependudukan)</label>
                                <input class="custom-input" type="text" name="nik" value="<?php echo htmlspecialchars($data['nik'] ?? ''); ?>" maxlength="16" pattern="\d{16}" title="NIK harus berupa 16 digit angka" <?php echo ($status_verifikasi == 'verified') ? 'readonly' : ''; ?> required>
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
                                <p style="font-size:12px; color:#888; margin:0;">
                                    <i class="fa fa-arrow-down"></i> Kelola galeri portofolio di bagian bawah halaman ini.
                                </p>
                            </div>

                            <button type="submit" name="simpan_profil" class="btn-save">Simpan Perubahan</button>
                        </div>
                    </div>
            </form>

            <!-- ===== PORTOFOLIO SHOWCASE (FITUR PREMIUM) ===== -->
            <div id="portofolio" class="account-panel" style="margin-top:30px;">
                <div class="account-page-header" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                    <div>
                        <h1 style="font-size:22px; margin:0;">Portofolio Showcase</h1>
                        <p style="margin:4px 0 0;">Galeri karya pilihan yang tampil di toko publikmu untuk menarik klien.</p>
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
                        <input type="text" name="judul_portofolio" class="custom-input" placeholder="Judul karya (opsional)" maxlength="150" style="flex:1; min-width:160px;">
                        <input type="file" name="gambar_portofolio" accept="image/jpeg,image/png,image/webp" required style="flex:1; min-width:160px;">
                        <button type="submit" class="btn-edit-foto" style="background:#4e8eff;">+ Tambah</button>
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
