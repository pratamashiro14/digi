<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';
require_once __DIR__ . '/mou_helper.php';

// 1. CEK LOGIN KHUSUS DESAINER
require_designer();

$id_user = (int) current_id();

/** True bila pengguna benar-benar memilih berkas (bukan field yang dibiarkan kosong). */
function upload_dipilih($file) {
    if (!is_array($file)) return false;
    $err = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
    $nama = isset($file['name']) ? (string) $file['name'] : '';
    return $err !== UPLOAD_ERR_NO_FILE && $nama !== '';
}

/**
 * Validasi satu gambar unggahan lalu simpan, kembalikan NAMA FILE barunya.
 *
 * Nama file dibuat sendiri dan ekstensinya diambil dari MIME hasil
 * getimagesize() — BUKAN dari nama kiriman pengguna. Satu keputusan ini
 * menutup tiga lubang sekaligus: nama file tidak bisa lagi jadi fragmen SQL,
 * tidak bisa berakhiran .php (shell), dan '../' tidak bisa keluar folder.
 * Pola menyalin proses_portofolio.php. Menghentikan request bila tidak valid.
 */
function simpan_gambar_profil($file, $prefix, $label) {
    // Ekstensi diturunkan dari MIME, jadi map ini sekaligus jadi whitelist.
    $izin = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp');
    $folder = 'admin/uploads/';

    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        sweetalert_back('Gagal mengunggah ' . $label . '. Pastikan ukurannya di bawah 4 MB.', 'error', 'Upload Gagal');
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        sweetalert_back('Berkas ' . $label . ' tidak valid.', 'error', 'Upload Gagal');
    }
    if ($file['size'] > 4 * 1024 * 1024) {
        sweetalert_back('Ukuran ' . $label . ' maksimal 4 MB.', 'error', 'Terlalu Besar');
    }

    // Periksa ISI berkas, bukan namanya dan bukan $_FILES['type'] (keduanya
    // dikirim pengguna sehingga bisa dipalsukan).
    $info = @getimagesize($file['tmp_name']);
    $mime = isset($info['mime']) ? $info['mime'] : '';
    if (!isset($izin[$mime])) {
        sweetalert_back('Format ' . $label . ' harus JPG, PNG, atau WEBP.', 'error', 'Format Salah');
    }

    if (!is_dir($folder)) {
        @mkdir($folder, 0755, true);
    }

    // Jangan pakai '_MASTER_' pada prefix: admin/uploads/.htaccess memblokir
    // pola itu, gambarnya nanti tidak muncul (lihat catatan di unggahan.php).
    $nama = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $izin[$mime];
    if (!move_uploaded_file($file['tmp_name'], $folder . $nama)) {
        sweetalert_back('Gagal menyimpan ' . $label . '. Silakan coba lagi.', 'error', 'Upload Gagal');
    }
    @chmod($folder . $nama, 0644);

    return $nama;
}

// ==========================================================
// 2. LOGIKA UPDATE DATA (DIGABUNG DI SINI)
// ==========================================================
if (isset($_POST['simpan_profil'])) {
    // Input dibaca MENTAH — quoting sepenuhnya ditangani prepared statement
    // di bawah. Dulu di sini ada mysqli_real_escape_string, dan escaping
    // ganda itulah yang membuat nama ber-apostrof tersimpan sebagai
    // "O\'Brien" di session.
    $nama_baru   = trim((string) ($_POST['nama']    ?? ''));
    $telp_baru   = trim((string) ($_POST['no_telp'] ?? ''));
    $alamat_baru = trim((string) ($_POST['alamat']  ?? ''));
    $pass_baru   = (string) ($_POST['password']     ?? '');
    // $_POST['email'] sengaja TIDAK dibaca: email adalah kunci login &
    // verifikasi OTP, jadi read-only seperti di halaman pembeli (profil.php).
    // Penegakannya ada di sini, bukan pada atribut readonly di form.

    if ($nama_baru === '' || mb_strlen($nama_baru) > 100) {
        sweetalert_back('Nama wajib diisi, maksimal 100 karakter.', 'error', 'Data Tidak Valid');
    }
    if (mb_strlen($alamat_baru) > 500) {
        sweetalert_back('Alamat maksimal 500 karakter.', 'error', 'Data Tidak Valid');
    }

    // A. Upload foto profil (opsional)
    $foto_baru = null;
    if (upload_dipilih($_FILES['foto'] ?? null)) {
        $foto_baru = simpan_gambar_profil($_FILES['foto'], 'USR' . $id_user . '_FOTO', 'foto profil');
    }

    // B. Update database — prepared statement dengan kolom opsional.
    // Nama kolom SELALU literal di kode; hanya nilai yang lewat placeholder.
    // Pola menyalin profil.php yang sudah jalan di produksi.
    $sets  = array('nama = ?', 'no_telp = ?', 'alamat = ?');
    $types = 'sss';
    $vals  = array($nama_baru, $telp_baru, $alamat_baru);

    if ($foto_baru !== null) {
        $sets[] = 'foto = ?';
        $types .= 's';
        $vals[] = $foto_baru;
    }
    if ($pass_baru !== '') {
        $sets[] = 'password = ?';
        $types .= 's';
        $vals[] = password_hash($pass_baru, PASSWORD_DEFAULT);
    }

    $types .= 'i';
    $vals[] = $id_user;

    $sql_update = 'UPDATE t_user SET ' . implode(', ', $sets) . ' WHERE id_user = ?';
    $stmt_update = mysqli_prepare($koneksi, $sql_update);
    $run_update = false;
    if ($stmt_update) {
        mysqli_stmt_bind_param($stmt_update, $types, ...$vals);
        $run_update = mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
    }

    if ($run_update) {
        // Session diisi nilai mentah supaya header langsung berubah tanpa
        // logout, dan tanpa backslash sisa escaping.
        $_SESSION['nama_desainer'] = $nama_baru;
        $_SESSION['nama_designer'] = $nama_baru; // Jaga-jaga kalau pake ejaan ini
        $_SESSION['nama'] = $nama_baru;
        // Session email tidak disentuh — email tidak pernah berubah di sini.

        sweetalert_redirect('Profil desainer berhasil diperbarui.', 'profil_desainer.php', 'success', 'Berhasil!');
    } else {
        // Berkas yang sudah masuk tapi gagal tercatat di DB jangan ditinggal
        // jadi sampah.
        if ($foto_baru !== null && is_file('admin/uploads/' . $foto_baru)) {
            @unlink('admin/uploads/' . $foto_baru);
        }
        // Pesan generik: jangan bocorkan mysqli_error() ke pengguna.
        sweetalert_back('Gagal memperbarui profil. Silakan coba lagi.', 'error', 'Gagal!');
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
$mou_disetujui = mou_sudah_disetujui($koneksi, $id_user);
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
                            <img id="previewFoto" src="<?php echo ($foto != 'default.jpg' && !empty($foto)) ? 'admin/uploads/'.htmlspecialchars($foto) : 'images/icons/icon-header-01.png'; ?>" alt="Profil">
                        </div>
                        <button type="button" class="btn-edit-foto m-t-15" onclick="document.getElementById('inputFoto').click()"><i class="fa fa-camera m-r-5"></i> Ubah Foto</button>
                        <input type="file" name="foto" id="inputFoto" class="sr-file-input" accept="image/jpeg,image/png,image/webp" onchange="tampilkanPreview(this)">
                        
                        <div class="designer-status-card m-t-30">
                            <h5>Status Kemitraan</h5>
                            <?php if ($mou_disetujui) : ?>
                                <div class="designer-status-badge is-success"><i class="fa fa-check-circle m-r-5"></i> Terverifikasi</div>
                            <?php else : ?>
                                <div class="designer-status-badge is-warning"><i class="fa fa-clock-o m-r-5"></i> Belum Setuju MOU</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- FORM DATA (KANAN) -->
                    <div class="col-md-8 p-l-40 p-l-15-sm">
                        <?php if (!$mou_disetujui) : ?>
                            <div class="designer-alert is-danger">
                                <strong>Perhatian!</strong> Anda harus membaca &amp; menyetujui Perjanjian Kerja Sama Mitra Desainer (MOU)
                                sebelum dapat mengunggah karya. <a href="mou.php"><strong>Baca &amp; setujui MOU sekarang &rarr;</strong></a>
                            </div>
                        <?php else : ?>
                            <div class="designer-alert is-success">
                                <strong>Status:</strong> MOU sudah disetujui. Anda memiliki akses penuh sebagai Desainer.
                            </div>
                        <?php endif; ?>

                        <h5 class="designer-section-title"><i class="fa fa-address-card-o m-r-10"></i>Informasi Dasar</h5>
                        <div class="row">
                            <div class="col-md-6 p-b-15">
                                <label class="stext-102 cl3 p-b-5">Nama Lengkap</label>
                                <input class="custom-input" type="text" name="nama" value="<?php echo htmlspecialchars($nama); ?>">
                            </div>
                            <div class="col-md-6 p-b-15">
                                <label class="stext-102 cl3 p-b-5">No. WhatsApp</label>
                                <input class="custom-input" type="text" name="no_telp" value="<?php echo htmlspecialchars($telp); ?>">
                            </div>
                            <div class="col-md-6 p-b-15">
                                <label class="stext-102 cl3 p-b-5">Email</label>
                                <input class="custom-input is-readonly" type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
                                <small class="stext-107 cl6">Email adalah kunci login &amp; verifikasi. Hubungi admin untuk mengubahnya.</small>
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
