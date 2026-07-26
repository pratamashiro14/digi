<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/designer_layout.php';
include 'admin/koneksi.php';
require_once __DIR__ . '/wanprestasi_helper.php'; // sweep wanprestasi & tenggat
require_once __DIR__ . '/mou_helper.php';

// 1. CEK LOGIN KHUSUS DESAINER
require_designer();

// 1b. MOU Kemitraan — satu-satunya syarat sebelum bisa mengunggah/mengelola
// karya sejak KTP dihapus (Google Login menggantikan verifikasi identitas).
require_mou_disetujui();

// Halaman biasa — cukup throttled per sesi. Desainer perlu tahu bila
// pemenang lelangnya wanprestasi / karyanya sudah dipromosikan ke penawar lain.
jalankan_pemeliharaan_lelang($koneksi);

$id_desainer = (int) current_id();
$nama_desainer = current_name();
$works_view = $_GET['view'] ?? 'auction-create';
if (!in_array($works_view, ['all', 'auction-create', 'active'], true)) {
    $works_view = 'auction-create';
}

// 2. LOGIKA HAPUS KARYA
if (isset($_GET['hapus'])) {
    $id_hapus = (int) $_GET['hapus'];
    $is_ajax_delete = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    $delete_error = null;
    $row = null;
    
    // Ambil info gambar & file master untuk dihapus fisiknya
    $q_data = mysqli_query($koneksi, "SELECT gambar, file_master FROM t_design WHERE id_design='$id_hapus' AND id_designer='$id_desainer'");

    if ($q_data) {
        $row = mysqli_fetch_assoc($q_data);
    }

    if (!$row) {
        $delete_error = 'Karya tidak ditemukan atau sudah dihapus.';
    } else {
        mysqli_begin_transaction($koneksi);
        $delete_bidding = mysqli_query($koneksi, "DELETE FROM t_bidding WHERE id_design='$id_hapus'");
        $delete_transaksi = mysqli_query($koneksi, "DELETE FROM t_transaksi WHERE id_design='$id_hapus'");
        $delete_design = mysqli_query($koneksi, "DELETE FROM t_design WHERE id_design='$id_hapus' AND id_designer='$id_desainer'");

        if ($delete_bidding && $delete_transaksi && $delete_design && mysqli_affected_rows($koneksi) === 1) {
            mysqli_commit($koneksi);

            $path_img = "admin/uploads/" . $row['gambar'];
            if (is_file($path_img)) { unlink($path_img); }

            if (!empty($row['file_master'])) {
                $path_master = "admin/uploads/" . $row['file_master'];
                if (is_file($path_master)) { unlink($path_master); }
            }
        } else {
            mysqli_rollback($koneksi);
            $delete_error = 'Karya gagal dihapus. Silakan coba lagi.';
        }
    }

    if ($is_ajax_delete) {
        http_response_code($delete_error === null ? 200 : 422);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $delete_error === null,
            'message' => $delete_error ?? 'Karya dan riwayat lelang berhasil dihapus.'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($delete_error === null) {
        header('Location: unggahan.php?view=all&pesan=hapus_sukses#daftar-karya');
        exit;
    }

    sweetalert_redirect($delete_error, 'unggahan.php?view=all#daftar-karya', 'error', 'Gagal Menghapus');
}

// 2b. LOGIKA HENTIKAN LELANG LEBIH AWAL
// Desainer boleh menutup lelang kapan saja bila harga bid dirasa sudah cukup.
// Penawar tertinggi saat ini (dengan prioritas premium) langsung jadi pemenang
// dan diberi notifikasi untuk membayar — tanpa menunggu batas waktu.
if (isset($_POST['hentikan_lelang'])) {
    require_once __DIR__ . '/bidding_helper.php';
    require_once __DIR__ . '/notifikasi_helper.php';
    $id_lelang = (int) ($_POST['id_design'] ?? 0);
    $is_ajax_stop = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    $stop_response = function ($success, $message, $icon, $title, $redirect = null) use ($is_ajax_stop) {
        if ($is_ajax_stop) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => (bool) $success,
                'message' => $message,
                'icon' => $icon,
                'title' => $title
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        if ($redirect !== null) {
            sweetalert_redirect($message, $redirect, $icon, $title);
        }
        sweetalert_back($message, $icon, $title);
    };

    $q_l = mysqli_query($koneksi, "SELECT judul, status, waktu_berakhir
                                   FROM t_design
                                   WHERE id_design='$id_lelang' AND id_designer='$id_desainer' LIMIT 1");
    $dl = $q_l ? mysqli_fetch_assoc($q_l) : null;

    if (!$dl) {
        $stop_response(false, 'Karya tidak ditemukan.', 'error', 'Gagal');
    } elseif ($dl['status'] === 'sold') {
        $stop_response(false, 'Karya ini sudah terjual.', 'info', 'Tidak Bisa Dihentikan');
    } elseif (empty($dl['waktu_berakhir']) || strtotime($dl['waktu_berakhir']) <= time()) {
        $stop_response(false, 'Lelang ini sudah berakhir.', 'info', 'Sudah Berakhir');
    } else {
        $winner_id = bid_leader_id($koneksi, $id_lelang);
        if (!$winner_id) {
            $stop_response(false, 'Belum ada penawaran masuk, jadi lelang belum bisa dihentikan.', 'warning', 'Belum Ada Penawaran');
        } else {
            // Akhiri lelang sekarang juga (set waktu berakhir = sekarang) DAN
            // langsung set tenggat bayar + tandai notif sudah terkirim, supaya
            // sweep otomatis (jalankan_pemeliharaan_lelang) tidak mengirim
            // notifikasi dobel maupun menghitung tenggat dari waktu yang salah.
            $jam = (int) BATAS_BAYAR_PEMENANG_JAM;
            $stmt_end = mysqli_prepare($koneksi, "UPDATE t_design
                                                  SET waktu_berakhir = NOW(),
                                                      batas_bayar_pemenang = NOW() + INTERVAL {$jam} HOUR,
                                                      notif_menang_terkirim = 1
                                                  WHERE id_design=? AND id_designer=?");
            mysqli_stmt_bind_param($stmt_end, 'ii', $id_lelang, $id_desainer);
            mysqli_stmt_execute($stmt_end);
            mysqli_stmt_close($stmt_end);

            // Beri tahu pemenang agar segera membayar.
            kirim_notifikasi(
                $koneksi,
                $winner_id,
                'Selamat! Kamu memenangkan lelang "' . $dl['judul'] . '". Segera selesaikan pembayaran dalam ' . BATAS_BAYAR_PEMENANG_JAM . ' jam.',
                'riwayat_bidding.php',
                'menang_lelang'
            );
            $stop_response(true, 'Lelang dihentikan. Pemenang sudah diberi tahu untuk melakukan pembayaran.',
                           'success', 'Lelang Diselesaikan', 'unggahan.php?view=active#daftar-karya');
        }
    }
}

// 3. PROSES UPLOAD KARYA BARU
// Kita pakai hidden input 'simpan_karya' karena tombol submit akan di-intercept oleh JS
if (isset($_POST['simpan_karya'])) {
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $kategori_pilihan = trim($_POST['kategori'] ?? '');
    if ($kategori_pilihan === '__custom__') {
        $kategori_pilihan = trim(strip_tags($_POST['kategori_custom'] ?? ''));
    }
    $kategori_pilihan = preg_replace('/[\x00-\x1F\x7F]/u', '', $kategori_pilihan);
    if ($kategori_pilihan === '') {
        sweetalert_back('Kategori karya wajib diisi.', 'warning', 'Kategori Belum Diisi');
    }
    $panjang_kategori = function_exists('mb_strlen') ? mb_strlen($kategori_pilihan, 'UTF-8') : strlen($kategori_pilihan);
    if ($panjang_kategori > 50) {
        sweetalert_back('Nama kategori maksimal 50 karakter.', 'warning', 'Kategori Terlalu Panjang');
    }
    $kategori = mysqli_real_escape_string($koneksi, $kategori_pilihan);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    // Bersihkan format ribuan (titik) & karakter non-digit, simpan angka murni
    $harga = preg_replace('/\D/', '', $_POST['harga']);
    $harga = ($harga === '') ? 0 : $harga;
    $harga_beli_langsung = preg_replace('/\D/', '', $_POST['harga_beli_langsung'] ?? '');
    $harga_beli_langsung = ($harga_beli_langsung === '') ? 0 : $harga_beli_langsung;
    $waktu_berakhir = $_POST['waktu_berakhir'];

    // Upload Gambar
    $nama_gambar = "default.jpg";
    if (!empty($_FILES['gambar']['name'])) {
        $nama_file = $_FILES['gambar']['name'];
        $tmp_file = $_FILES['gambar']['tmp_name'];
        $ekstensi_gambar = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
        $ekstensi_gambar = preg_replace('/[^a-z0-9]/', '', $ekstensi_gambar);
        // Nama preview dibuat independen dari nama asli agar teks "_MASTER_"
        // pada nama file pengguna tidak ikut terblokir oleh .htaccess.
        $nama_gambar = rand(1000,9999) . "_PREVIEW_" . bin2hex(random_bytes(6));
        if ($ekstensi_gambar !== '') {
            $nama_gambar .= "." . $ekstensi_gambar;
        }
        if (!move_uploaded_file($tmp_file, "admin/uploads/" . $nama_gambar)) {
            sweetalert_back('Gambar preview gagal diunggah. Silakan coba lagi.', 'error', 'Upload Gagal!');
        }
    }

    // Upload File Master
    $nama_file_master = "";
    if (!empty($_FILES['file_master']['name'])) {
        $nama_master = $_FILES['file_master']['name'];
        $tmp_master = $_FILES['file_master']['tmp_name'];
        $ekstensi_master = strtolower(pathinfo($nama_master, PATHINFO_EXTENSION));
        $ekstensi_master = preg_replace('/[^a-z0-9]/', '', $ekstensi_master);
        $nama_file_master = rand(1000,9999) . "_MASTER_" . bin2hex(random_bytes(6));
        if ($ekstensi_master !== '') {
            $nama_file_master .= "." . $ekstensi_master;
        }
        if (!move_uploaded_file($tmp_master, "admin/uploads/" . $nama_file_master)) {
            sweetalert_back('File master gagal diunggah. Silakan coba lagi.', 'error', 'Upload Gagal!');
        }
    }

    // Simpan ke Database
    $query_insert = "INSERT INTO t_design 
        (id_designer, judul, deskripsi, kategori, harga_awal, harga_beli_langsung, gambar, tanggal_upload, status, waktu_berakhir, file_master) 
        VALUES 
        ('$id_desainer', '$judul', '$deskripsi', '$kategori', '$harga', '$harga_beli_langsung', '$nama_gambar', NOW(), 'approved', '$waktu_berakhir', '$nama_file_master')";

    if (mysqli_query($koneksi, $query_insert)) {
        // Notifikasi Custom (premium): beri tahu pembeli premium yang memfavoritkan desainer ini.
        $id_design_baru = mysqli_insert_id($koneksi);
        require_once __DIR__ . '/notifikasi_helper.php';
        notif_karya_baru_ke_favorit($koneksi, $id_desainer, current_name(), $id_design_baru, trim($_POST['judul'] ?? ''));

        // Post/Redirect/Get: tampilkan halaman Karya Saya lebih dulu agar
        // notifikasi sukses tidak muncul di atas halaman putih.
        header('Location: unggahan.php?view=all&pesan=upload_sukses#daftar-karya');
        exit;
    } else {
        sweetalert_back('Gagal mengunggah karya: ' . mysqli_error($koneksi), 'error', 'Upload Gagal!');
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Unggahan Desainer -  Mulai Lelang</title>
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
        .input-rp { position: relative; }
        .input-rp .rp-prefix { position: absolute; left: 12px; top: 12px; color: #888; font-size: 14px; pointer-events: none; }
        .input-rp input { padding-left: 36px; }

        .btn-simpan-upload { background: linear-gradient(90deg, #4e8eff, #6b4eff); color: #fff; padding: 12px 30px; border: none; border-radius: 50px; font-size: 16px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 10px; transition: 0.3s; }
        .btn-simpan-upload:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(78, 142, 255, 0.4); }

        /* TABEL */
        .list-karya-title { font-size: 18px; font-weight: 700; color: #333; margin-bottom: 20px; border-left: 5px solid #4e8eff; padding-left: 15px; }
        .table-karya { width: 100%; border: 1px solid #eee; }
        .table-karya th { background: #f9f9f9; padding: 15px; text-align: center; font-size: 13px; font-weight: 700; color: #555; text-transform: uppercase; }
        .table-karya td { padding: 15px; vertical-align: middle; text-align: center; border-bottom: 1px solid #eee; font-size: 14px; color: #666; }
        .thumb-small { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #eee; }
        .table-karya th.action-heading,
        .table-karya td.action-cell { width: 230px; min-width: 230px; }
        .table-action-group { display: flex; align-items: center; justify-content: flex-start; gap: 8px; min-height: 38px; }
        .table-action-form { display: inline-flex; margin: 0; padding: 0; }
        .account-page .btn-hapus,
        .account-page .btn-stop {
            height: 36px; min-height: 36px; padding: 0 13px; border-radius: 9px;
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            box-sizing: border-box; font-size: 12px; font-weight: 600; line-height: 1;
            text-decoration: none; white-space: nowrap; cursor: pointer;
            transition: background-color .2s ease, border-color .2s ease, color .2s ease, transform .2s ease, box-shadow .2s ease;
        }
        .account-page .btn-hapus { min-width: 78px; border: 1px solid #fecaca; background: #fff1f2; color: #dc2626; }
        .account-page .btn-hapus:hover { border-color: #fca5a5; background: #fee2e2; color: #b91c1c; transform: translateY(-1px); box-shadow: 0 5px 12px rgba(220,38,38,.10); }
        .account-page .btn-stop { min-width: 132px; border: 1px solid #fed7aa; background: #fff7ed; color: #c2410c; }
        .account-page .btn-stop:hover { border-color: #fdba74; background: #ffedd5; color: #9a3412; transform: translateY(-1px); box-shadow: 0 5px 12px rgba(194,65,12,.10); }
        .account-page .btn-hapus i,
        .account-page .btn-stop i { width: 13px; font-size: 12px; line-height: 1; text-align: center; }
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
                                    <select name="kategori" id="kategoriSelect" class="form-input-custom" required>
                                        <option value="ilustrasi">Ilustrasi</option>
                                        <option value="tipografi">Tipografi</option>
                                        <option value="mockup">Mockup</option>
                                        <option value="uiux">UI/UX</option>
                                        <option value="__custom__">+ Tambah kategori sendiri</option>
                                    </select>
                                    <input type="text" name="kategori_custom" id="kategoriCustom" class="form-input-custom m-t-10" maxlength="50" placeholder="Tulis nama kategori baru" style="display:none;">
                                    <small id="kategoriCustomHint" style="display:none; color:#64748b; font-size:11px; margin-top:5px;">Maksimal 50 karakter.</small>
                                </div>
                            </div>

                            <div class="m-b-15">
                                <label class="stext-102 cl3 p-b-5">Deskripsi</label>
                                <textarea name="deskripsi" class="form-input-custom" rows="3" placeholder="Jelaskan detail karyamu..." required></textarea>
                            </div>

                            <div class="row m-b-15">
                                <div class="col-md-4">
                                    <label class="stext-102 cl3 p-b-5" style="font-weight:bold; color:#4e8eff;">Harga Awal (Open Bid)</label>
                                    <div class="input-rp">
                                        <span class="rp-prefix">Rp</span>
                                        <input type="text" name="harga" class="form-input-custom input-harga" inputmode="numeric" placeholder="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="stext-102 cl3 p-b-5" style="font-weight:bold; color:#f39c12;">Harga Beli Langsung</label>
                                    <div class="input-rp">
                                        <span class="rp-prefix">Rp</span>
                                        <input type="text" name="harga_beli_langsung" class="form-input-custom input-harga" inputmode="numeric" placeholder="Opsional">
                                    </div>
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
                                    <th class="action-heading">Aksi</th>
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
                                        $lelang_aktif = ($k['status'] === 'approved'
                                            && !empty($k['waktu_berakhir'])
                                            && strtotime($k['waktu_berakhir']) > time());
                                ?>
                                <tr data-work-row="<?php echo (int) $k['id_design']; ?>">
                                    <td><?php echo $no++; ?></td>
                                    <td><img src="admin/uploads/<?php echo $k['gambar']; ?>" class="thumb-small"></td>
                                    <td style="text-align:left;">
                                        <strong style="color:#333;"><?php echo $k['judul']; ?></strong><br>
                                        <span class="badge badge-light"><?php echo $k['kategori']; ?></span>
                                    </td>
                                    <td>Rp <?php echo number_format($k['harga_awal'],0,',','.'); ?></td>
                                    <td><?php echo $k['harga_beli_langsung'] > 0 ? 'Rp ' . number_format($k['harga_beli_langsung'],0,',','.') : '-'; ?></td>
                                    <td style="color: #e74c3c; font-weight: 500;"><?php echo $deadline; ?></td>
                                    <td class="action-cell">
                                        <div class="table-action-group">
                                            <?php if ($lelang_aktif) { ?>
                                                <form action="unggahan.php?view=<?php echo htmlspecialchars($works_view); ?>" method="POST" class="table-action-form">
                                                    <input type="hidden" name="hentikan_lelang" value="1">
                                                    <input type="hidden" name="id_design" value="<?php echo $k['id_design']; ?>">
                                                    <button type="submit" class="btn-stop" data-confirm-stop><i class="fa fa-gavel"></i><span>Hentikan Lelang</span></button>
                                                </form>
                                            <?php } ?>
                                            <a href="unggahan.php?view=<?php echo urlencode($works_view); ?>&amp;hapus=<?php echo $k['id_design']; ?>" class="btn-hapus" data-swal-confirm="Karya dan riwayat lelang ini akan dihapus permanen." data-swal-ajax><i class="fa fa-trash"></i><span>Hapus</span></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                } else {
                                    echo "<tr><td colspan='7' style='padding:30px;'>" . ($works_view === 'active' ? 'Tidak ada lelang yang sedang aktif.' : 'Belum ada karya yang diunggah.') . "</td></tr>";
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
        // Konfirmasi sebelum menghentikan lelang lebih awal (POST form).
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-confirm-stop]');
            if (!btn || typeof swal !== 'function') return;
            e.preventDefault();
            var form = btn.closest('form');
            swal({
                title: 'Hentikan lelang sekarang?',
                text: 'Penawar tertinggi saat ini akan langsung menjadi pemenang dan diminta membayar. Tindakan ini tidak bisa dibatalkan.',
                icon: 'warning',
                buttons: ['Batal', 'Ya, Hentikan'],
                dangerMode: true
            }).then(function (ok) {
                if (!ok || !form) return;

                if (typeof window.fetch !== 'function') {
                    form.submit();
                    return;
                }

                btn.disabled = true;
                window.fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(function (response) {
                    return response.json().then(function (data) {
                        if (!response.ok) {
                            throw new Error(data.message || 'Lelang gagal dihentikan.');
                        }
                        return data;
                    });
                })
                .then(function (data) {
                    if (data.success) {
                        var row = form.closest('tr');
                        var deadlineCell = row ? row.querySelector('td:nth-child(6)') : null;
                        form.remove();
                        if (deadlineCell) {
                            deadlineCell.textContent = 'Lelang dihentikan';
                            deadlineCell.style.color = '#64748b';
                        }
                    } else {
                        btn.disabled = false;
                    }

                    swal({
                        title: data.title,
                        text: data.message,
                        icon: data.icon,
                        button: false,
                        timer: 3000,
                        closeOnClickOutside: false
                    });
                })
                .catch(function (error) {
                    btn.disabled = false;
                    swal({
                        title: 'Gagal',
                        text: error.message || 'Lelang gagal dihentikan.',
                        icon: 'error',
                        button: false,
                        timer: 3000
                    });
                });
            });
        });

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

        // --- FORMAT HARGA: tambahkan titik pemisah ribuan saat mengetik ---
        function formatRibuan(el) {
            var digits = el.value.replace(/\D/g, '');
            el.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
        document.querySelectorAll('.input-harga').forEach(function(el) {
            el.addEventListener('input', function() { formatRibuan(el); });
        });

        // Tampilkan input ketika desainer memilih kategori buatan sendiri.
        var kategoriSelect = document.getElementById('kategoriSelect');
        var kategoriCustom = document.getElementById('kategoriCustom');
        var kategoriCustomHint = document.getElementById('kategoriCustomHint');
        function toggleKategoriCustom() {
            if (!kategoriSelect || !kategoriCustom) return;
            var isCustom = kategoriSelect.value === '__custom__';
            kategoriCustom.style.display = isCustom ? 'block' : 'none';
            kategoriCustom.required = isCustom;
            if (kategoriCustomHint) kategoriCustomHint.style.display = isCustom ? 'block' : 'none';
            if (isCustom) kategoriCustom.focus();
        }
        if (kategoriSelect) {
            kategoriSelect.addEventListener('change', toggleKategoriCustom);
            toggleKategoriCustom();
        }

        // Buang titik sebelum form dikirim, supaya backend menerima angka murni
        function bersihkanHarga(form) {
            form.querySelectorAll('.input-harga').forEach(function(el) {
                el.value = el.value.replace(/\D/g, '');
            });
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
                    // Kalau user klik 'Ya', bersihkan titik lalu kirim form-nya secara manual
                    bersihkanHarga(this);
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
                title: "Upload Berhasil!",
                text: "Karya kamu sudah tayang dan lelang telah dimulai!",
                icon: "success",
                button: false,
                timer: 3000,
                closeOnClickOutside: false
            });
            window.history.replaceState(null, null, 'unggahan.php?view=all#daftar-karya');
        } 
        else if(pesan === 'hapus_sukses'){
            swal({
                title: "Terhapus!",
                text: "Data karya dan riwayat lelang berhasil dihapus.",
                icon: "success",
                button: false,
                timer: 3000,
                closeOnClickOutside: false
            });
            window.history.replaceState(null, null, 'unggahan.php?view=all#daftar-karya');
        }
    </script>

</body>
</html>
