<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';

// 1. Cek Login
if (!is_login() && !is_admin_login()) {
    sweetalert_redirect('Silakan login terlebih dahulu.', 'login.php', 'error', 'Akses Ditolak!');
    exit;
}

$current_user_id = current_id();
$role = current_role();

// 2. Cek Parameter ID Transaksi atau ID Design
// Kita dukung download melalui ID Transaksi (untuk pembeli) atau ID Design (untuk desainer/admin)
$id_transaksi = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$id_design = isset($_GET['id_design']) ? (int) $_GET['id_design'] : 0;

$file_master = '';
$judul = '';
$authorized = false;

if (!empty($id_transaksi)) {
    // Ambil data transaksi & design
    $stmt = mysqli_prepare($koneksi,
        "SELECT t.*, d.file_master, d.judul, d.id_designer
              FROM t_transaksi t
              JOIN t_design d ON t.id_design = d.id_design
              WHERE t.id_transaksi = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id_transaksi);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $file_master = $row['file_master'];
        $judul = $row['judul'];
        $status_pembayaran = strtolower($row['status_pembayaran']);
        
        // Cek Otorisasi
        if ($role === 'admin') {
            $authorized = true;
        } elseif ($role === 'designer' && $row['id_designer'] == $current_user_id) {
            $authorized = true;
        } elseif ($role === 'pelanggan' && $row['id_buyer'] == $current_user_id) {
            // Pembeli harus sudah melunasi pembayaran
            if (in_array($status_pembayaran, ['berhasil', 'settlement', 'capture'], true)) {
                $authorized = true;
            }
        }
    }
} elseif (!empty($id_design)) {
    // Ambil data design saja
    $stmt = mysqli_prepare($koneksi, "SELECT file_master, judul, id_designer FROM t_design WHERE id_design = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id_design);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $file_master = $row['file_master'];
        $judul = $row['judul'];
        
        // Cek Otorisasi
        if ($role === 'admin') {
            $authorized = true;
        } elseif ($role === 'designer' && $row['id_designer'] == $current_user_id) {
            $authorized = true;
        }
    }
}

if (!$authorized || empty($file_master)) {
    sweetalert_redirect('Anda tidak memiliki akses untuk mengunduh file ini atau file belum lunas.', 'riwayat.php', 'error', 'Akses Ditolak!');
    exit;
}

$file_path = __DIR__ . '/admin/uploads/' . $file_master;

if (!file_exists($file_path)) {
    sweetalert_redirect('File fisik tidak ditemukan di server. Silakan hubungi admin.', 'riwayat.php', 'error', 'File Tidak Ditemukan!');
    exit;
}

// 3. Kirim File Secara Aman
// Bersihkan output buffer jika ada space atau baris kosong sebelum tag php untuk mencegah file korup
if (ob_get_level()) {
    ob_end_clean();
}

// Tentukan MIME Type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file_path);
finfo_close($finfo);

if (!$mime_type) {
    $mime_type = "application/octet-stream";
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . basename($file_master) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));

// Baca file
readfile($file_path);
exit;
