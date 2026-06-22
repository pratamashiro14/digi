<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';
require_once __DIR__ . '/bidding_helper.php';

// 1. Cek Login User
require_user();

$id_user = (int) current_id(); // ID User yang sedang login

// 1b. GERBANG KELAYAKAN BID: harus aktif, KTP verified, NIK tidak diblokir.
//     Wajib dicek di server agar gate tidak bisa dilewati lewat endpoint langsung.
$kelayakan = status_bid_user($koneksi, $id_user);
if (!$kelayakan['ok']) {
    sweetalert_back($kelayakan['reason'], 'warning', 'Tidak Dapat Menawar');
    exit;
}

$id_design = (int) ($_POST['id_design'] ?? 0);

// PRIORITAS BIDDING (premium): pembeli premium boleh MENYAMAI tawaran tertinggi
// dan langsung memimpin; pembeli biasa tetap wajib menawar lebih tinggi.
$is_prem_buyer = is_premium_buyer();

// Bersihkan format Rupiah (Contoh: "Rp 120.000" jadi "120000")
$harga_raw = $_POST['harga_tawaran'] ?? '';
$harga_bersih = (int) preg_replace('/[^0-9]/', '', $harga_raw);

// 2. Cek Harga Tertinggi Saat Ini di Database
$cek_max = mysqli_prepare($koneksi, "SELECT MAX(harga_tawaran) as max_bid FROM t_bidding WHERE id_design = ?");
mysqli_stmt_bind_param($cek_max, 'i', $id_design);
mysqli_stmt_execute($cek_max);
$data_max = mysqli_fetch_assoc(mysqli_stmt_get_result($cek_max));
$tertinggi_sekarang = $data_max['max_bid'] ?? 0;

// Cek Harga Awal Produk, status terjual & Waktu Berakhir (Jangan sampai nawar setelah waktu habis)
$cek_produk = mysqli_prepare($koneksi, "SELECT d.harga_awal, d.waktu_berakhir, d.status,
            (d.waktu_berakhir IS NOT NULL AND d.waktu_berakhir < NOW()) AS lelang_berakhir,
            EXISTS (
                SELECT 1
                FROM t_transaksi t
                WHERE t.id_design = d.id_design
                  AND t.status_pembayaran IN ('berhasil', 'settlement', 'capture')
            ) AS sudah_terjual
        FROM t_design d
        WHERE d.id_design = ?");
mysqli_stmt_bind_param($cek_produk, 'i', $id_design);
mysqli_stmt_execute($cek_produk);
$data_produk = mysqli_fetch_assoc(mysqli_stmt_get_result($cek_produk));
$harga_awal = $data_produk['harga_awal'] ?? 0;
$waktu_berakhir = $data_produk['waktu_berakhir'] ?? '';
$status_design = $data_produk['status'] ?? '';
$sudah_terjual = !empty($data_produk['sudah_terjual']);
$lelang_berakhir = !empty($data_produk['lelang_berakhir']);

// 3. Validasi: Pengecekan tenggat waktu lelang & nominal tawaran
if (!$data_produk) {
    sweetalert_back('Karya tidak ditemukan.', 'error', 'Tawaran Ditolak!');
} elseif ($status_design === 'sold' || $sudah_terjual) {
    sweetalert_back('Karya ini sudah terjual. Anda tidak dapat mengajukan penawaran lagi.', 'error', 'Tawaran Ditolak!');
} elseif ($lelang_berakhir) {
    sweetalert_back('Lelang untuk karya ini sudah berakhir. Anda tidak dapat mengajukan penawaran lagi.', 'error', 'Tawaran Ditolak!');
} elseif ($is_prem_buyer ? ($harga_bersih < $tertinggi_sekarang) : ($harga_bersih <= $tertinggi_sekarang)) {
    $pesan_tolak = $is_prem_buyer
        ? 'Sebagai pembeli Premium, tawaran minimal harus SAMA dengan penawar tertinggi saat ini (Rp ' . number_format($tertinggi_sekarang, 0, ',', '.') . ') untuk langsung memimpin.'
        : 'Tawaran harus lebih tinggi dari penawar tertinggi saat ini (Rp ' . number_format($tertinggi_sekarang, 0, ',', '.') . ').';
    sweetalert_back($pesan_tolak, 'error', 'Tawaran Ditolak!');
} elseif ($harga_bersih < $harga_awal) {
    sweetalert_back('Tawaran tidak boleh lebih rendah dari harga awal.', 'error', 'Tawaran Ditolak!');
} else {
    // 4. Simpan ke Database (Sesuai kolom tabel kamu: id_buyer, tanggal_bid, status_bid)
    $simpan_stmt = mysqli_prepare($koneksi, "INSERT INTO t_bidding (id_design, id_buyer, harga_tawaran, tanggal_bid, status_bid)
                                      VALUES (?, ?, ?, NOW(), 'aktif')");
    mysqli_stmt_bind_param($simpan_stmt, 'iid', $id_design, $id_user, $harga_bersih);
    $simpan = mysqli_stmt_execute($simpan_stmt);

    if ($simpan) {
        sweetalert_redirect('Penawaran Anda berhasil dikirim.', 'product.php?show_bid=' . $id_design, 'success', 'Tawaran Berhasil!');
    } else {
        sweetalert_back('Gagal menawar: ' . mysqli_error($koneksi), 'error', 'Tawaran Gagal!');
    }
}
?>
