<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';
require_once __DIR__ . '/mou_helper.php';

// Hanya desainer yang boleh menandatangani MOU
require_designer();

$id_desainer = (int) current_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sweetalert_redirect('Akses tidak sah.', 'mou.php', 'error', 'Ditolak');
    exit;
}

// Anti-duplikat: satu desainer hanya bisa menandatangani sekali (kolom
// id_user di t_mou juga UNIQUE sebagai penjaga terakhir di level DB).
if (mou_sudah_disetujui($koneksi, $id_desainer)) {
    sweetalert_redirect('MOU sudah pernah Anda setujui sebelumnya.', 'mou.php', 'info', 'Sudah Disetujui');
    exit;
}

if (!mou_csrf_valid($_POST['csrf_token'] ?? '')) {
    sweetalert_back('Sesi tidak valid atau sudah kedaluwarsa. Silakan muat ulang halaman dan coba lagi.', 'error', 'Gagal');
}

$nama_mitra     = trim((string) ($_POST['nama_mitra'] ?? ''));
$instansi_mitra = trim((string) ($_POST['instansi_mitra'] ?? ''));
$alamat_mitra   = trim((string) ($_POST['alamat_mitra'] ?? ''));
$telp_mitra     = trim((string) ($_POST['telp_mitra'] ?? ''));
$nama_ttd       = trim((string) ($_POST['nama_ttd'] ?? ''));
$setuju         = isset($_POST['setuju']) && $_POST['setuju'] === '1';

if ($instansi_mitra === '') {
    $instansi_mitra = '-';
}

if ($nama_mitra === '' || mb_strlen($nama_mitra) > 150) {
    sweetalert_back('Nama lengkap mitra wajib diisi, maksimal 150 karakter.', 'error', 'Data Tidak Valid');
} elseif ($alamat_mitra === '' || mb_strlen($alamat_mitra) > 500) {
    sweetalert_back('Alamat wajib diisi, maksimal 500 karakter.', 'error', 'Data Tidak Valid');
} elseif ($telp_mitra === '' || mb_strlen($telp_mitra) > 30) {
    sweetalert_back('Nomor telepon wajib diisi, maksimal 30 karakter.', 'error', 'Data Tidak Valid');
} elseif (mb_strlen($instansi_mitra) > 150) {
    sweetalert_back('Instansi maksimal 150 karakter.', 'error', 'Data Tidak Valid');
} elseif ($nama_ttd === '' || mb_strlen($nama_ttd) > 150) {
    sweetalert_back('Nama tanda tangan wajib diisi, maksimal 150 karakter.', 'error', 'Data Tidak Valid');
} elseif (!$setuju) {
    sweetalert_back('Anda harus mencentang persetujuan sebelum menandatangani MOU.', 'error', 'Belum Disetujui');
} else {
    $data = [
        'nomor'          => MOU_NOMOR,
        'versi'          => MOU_VERSI,
        'nama_mitra'     => $nama_mitra,
        'pj_mitra'       => $nama_mitra, // form tunggal: nama mitra sekaligus penanggung jawab
        'instansi_mitra' => $instansi_mitra,
        'alamat_mitra'   => $alamat_mitra,
        'telp_mitra'     => $telp_mitra,
        'email_mitra'    => current_email(),
        'nama_ttd'       => $nama_ttd,
        'ip_setuju'      => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        'user_agent'     => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ];

    if (mou_simpan($koneksi, $id_desainer, $data)) {
        unset($_SESSION['mou_csrf']); // token sekali pakai
        sweetalert_redirect('MOU berhasil ditandatangani. Terima kasih telah bergabung sebagai mitra desainer DENSCREATIVE.', 'mou.php', 'success', 'Berhasil Ditandatangani!');
    } elseif (mou_sudah_disetujui($koneksi, $id_desainer)) {
        // Race kondisi: dua submit nyaris bersamaan (mis. klik ganda) — yang
        // satu lolos duluan, punya kita ditolak UNIQUE KEY. Bukan error nyata.
        sweetalert_redirect('MOU sudah pernah Anda setujui sebelumnya.', 'mou.php', 'info', 'Sudah Disetujui');
    } else {
        sweetalert_back('Gagal menyimpan persetujuan MOU: ' . mysqli_error($koneksi), 'error', 'Gagal!');
    }
}
