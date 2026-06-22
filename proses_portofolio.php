<?php
// ==========================================================
// PROSES PORTOFOLIO — upload & hapus item portofolio desainer
// Fitur PREMIUM "Portofolio Showcase" (khusus desainer premium).
// ==========================================================
require_once __DIR__ . '/auth.php';
require_designer();
include 'admin/koneksi.php';

$id_designer = (int) current_id();

// Gerbang premium: hanya desainer premium yang boleh mengelola portofolio.
if (!is_premium_designer()) {
    sweetalert_redirect('Portofolio Showcase adalah fitur khusus Desainer Premium. Yuk upgrade dulu!',
                        'premium.php', 'info', 'Khusus Premium');
}

$folder = "admin/uploads/";

// ---- HAPUS ITEM ----
$id_hapus = (int) ($_POST['hapus'] ?? $_GET['hapus'] ?? 0);
if ($id_hapus) {
    // Pastikan item milik desainer ini sebelum dihapus.
    $stmt = mysqli_prepare($koneksi, "SELECT gambar FROM t_portofolio WHERE id_portofolio=? AND id_designer=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ii', $id_hapus, $id_designer);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($row) {
        $del = mysqli_prepare($koneksi, "DELETE FROM t_portofolio WHERE id_portofolio=? AND id_designer=?");
        mysqli_stmt_bind_param($del, 'ii', $id_hapus, $id_designer);
        mysqli_stmt_execute($del);
        mysqli_stmt_close($del);
        // Hapus file fisik bila tidak dipakai baris lain.
        if (!empty($row['gambar'])) {
            $cek = mysqli_query($koneksi, "SELECT 1 FROM t_portofolio WHERE gambar='" . mysqli_real_escape_string($koneksi, $row['gambar']) . "' LIMIT 1");
            if ($cek && mysqli_num_rows($cek) === 0) {
                $path = $folder . $row['gambar'];
                if (is_file($path)) @unlink($path);
            }
        }
    }
    header('Location: profil_desainer.php#portofolio');
    exit;
}

// ---- UPLOAD ITEM ----
if (isset($_POST['upload_portofolio'])) {
    if (empty($_FILES['gambar_portofolio']['name'])) {
        sweetalert_redirect('Pilih gambar dulu untuk diunggah ke portofolio.', 'profil_desainer.php#portofolio', 'warning', 'Belum Ada Gambar');
    }

    $judul = trim($_POST['judul_portofolio'] ?? '');
    $tmp   = $_FILES['gambar_portofolio']['tmp_name'];
    $asli  = $_FILES['gambar_portofolio']['name'];

    // Validasi: harus benar-benar gambar (mime), batasi jenis & ukuran.
    $izin = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $info = @getimagesize($tmp);
    $mime = $info['mime'] ?? '';
    if (!isset($izin[$mime])) {
        sweetalert_redirect('Format gambar harus JPG, PNG, atau WEBP.', 'profil_desainer.php#portofolio', 'error', 'Format Salah');
    }
    if ($_FILES['gambar_portofolio']['size'] > 5 * 1024 * 1024) {
        sweetalert_redirect('Ukuran gambar maksimal 5 MB.', 'profil_desainer.php#portofolio', 'error', 'Terlalu Besar');
    }

    // Nama file unik & aman (abaikan nama asli untuk ekstensi, pakai dari mime).
    $ext  = $izin[$mime];
    $nama = 'PORTO_' . $id_designer . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;

    if (move_uploaded_file($tmp, $folder . $nama)) {
        $judul_db = ($judul === '') ? null : mb_substr($judul, 0, 150);
        $stmt = mysqli_prepare($koneksi, "INSERT INTO t_portofolio (id_designer, gambar, judul) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'iss', $id_designer, $nama, $judul_db);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        sweetalert_redirect('Karya berhasil ditambahkan ke portofolio.', 'profil_desainer.php#portofolio', 'success', 'Tersimpan!');
    } else {
        sweetalert_redirect('Gagal menyimpan gambar. Coba lagi.', 'profil_desainer.php#portofolio', 'error', 'Gagal');
    }
}

// Akses langsung tanpa aksi valid -> balik ke profil.
header('Location: profil_desainer.php#portofolio');
exit;
