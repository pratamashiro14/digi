<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';
require_once __DIR__ . '/bidding_helper.php';

// 1. Cek Login User
require_user();

$id_user = (int) current_id(); // ID User yang sedang login
$is_ajax_bid = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
$bid_response = function ($success, $message, $icon, $title, $redirect = null) use ($is_ajax_bid) {
    if ($is_ajax_bid) {
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

// 1b. GERBANG KELAYAKAN BID: akun aktif, email terverifikasi, nomor HP
//     terisi, tidak suspend/diblokir. Wajib dicek di server agar gate
//     tidak bisa dilewati lewat endpoint langsung (lihat bidding_helper.php).
$kelayakan = status_bid_user($koneksi, $id_user);
if (!$kelayakan['ok']) {
    $bid_response(false, $kelayakan['reason'], 'warning', 'Tidak Dapat Menawar');
}

$id_design = (int) ($_POST['id_design'] ?? 0);

// 1c. RATE LIMIT: maksimal BID_MAKS_BERUNTUN tawaran beruntun tanpa
//     diselingi penawar lain, mencegah spam-menaikkan-tawaran-sendiri.
$batas_beruntun = cek_rate_limit_bid($koneksi, $id_design, $id_user);
if (!$batas_beruntun['ok']) {
    $bid_response(false, $batas_beruntun['reason'], 'warning', 'Batas Penawaran Tercapai');
}

// PRIORITAS BIDDING (premium): pembeli premium boleh MENYAMAI tawaran tertinggi
// dan langsung memimpin; pembeli biasa tetap wajib menawar lebih tinggi.
$is_prem_buyer = is_premium_buyer();

// Bersihkan format Rupiah (Contoh: "Rp 120.000" jadi "120000")
$harga_raw = $_POST['harga_tawaran'] ?? '';
$harga_bersih = (int) preg_replace('/[^0-9]/', '', $harga_raw);

// 2. VALIDASI + SIMPAN dalam SATU TRANSAKSI dengan SELECT ... FOR UPDATE.
// Tanpa ini, dua bid nyaris bersamaan bisa sama-sama membaca MAX(harga_tawaran)
// SEBELUM salah satunya ter-INSERT, sehingga keduanya lolos dengan nominal
// yang sama (race condition) — FOR UPDATE mengunci baris t_design sehingga
// bid kedua menunggu bid pertama commit dulu baru membaca ulang data terbaru.
mysqli_begin_transaction($koneksi);

$cek_produk = mysqli_prepare($koneksi, "SELECT d.harga_awal, d.waktu_berakhir, d.status,
            (d.waktu_berakhir IS NOT NULL AND d.waktu_berakhir < NOW()) AS lelang_berakhir,
            EXISTS (
                SELECT 1
                FROM t_transaksi t
                WHERE t.id_design = d.id_design
                  AND t.status_pembayaran IN ('berhasil', 'settlement', 'capture')
            ) AS sudah_terjual
        FROM t_design d
        WHERE d.id_design = ?
        FOR UPDATE");
mysqli_stmt_bind_param($cek_produk, 'i', $id_design);
mysqli_stmt_execute($cek_produk);
$data_produk = mysqli_fetch_assoc(mysqli_stmt_get_result($cek_produk));
$harga_awal = $data_produk['harga_awal'] ?? 0;
$status_design = $data_produk['status'] ?? '';
$sudah_terjual = !empty($data_produk['sudah_terjual']);
$lelang_berakhir = !empty($data_produk['lelang_berakhir']);

// Dibaca SETELAH baris t_design terkunci (FOR UPDATE) supaya nilai
// tertinggi yang dilihat selalu yang paling baru saat validasi nominal.
$cek_max = mysqli_prepare($koneksi, "SELECT MAX(harga_tawaran) as max_bid FROM t_bidding WHERE id_design = ?");
mysqli_stmt_bind_param($cek_max, 'i', $id_design);
mysqli_stmt_execute($cek_max);
$data_max = mysqli_fetch_assoc(mysqli_stmt_get_result($cek_max));
$tertinggi_sekarang = $data_max['max_bid'] ?? 0;

// 3. Validasi: Pengecekan tenggat waktu lelang & nominal tawaran
if (!$data_produk) {
    mysqli_rollback($koneksi);
    $bid_response(false, 'Karya tidak ditemukan.', 'error', 'Tawaran Ditolak!');
} elseif ($status_design === 'sold' || $sudah_terjual) {
    mysqli_rollback($koneksi);
    $bid_response(false, 'Karya ini sudah terjual. Anda tidak dapat mengajukan penawaran lagi.', 'error', 'Tawaran Ditolak!');
} elseif ($lelang_berakhir) {
    mysqli_rollback($koneksi);
    $bid_response(false, 'Lelang untuk karya ini sudah berakhir. Anda tidak dapat mengajukan penawaran lagi.', 'error', 'Tawaran Ditolak!');
} elseif ($is_prem_buyer ? ($harga_bersih < $tertinggi_sekarang) : ($harga_bersih <= $tertinggi_sekarang)) {
    mysqli_rollback($koneksi);
    $pesan_tolak = $is_prem_buyer
        ? 'Sebagai pembeli Premium, tawaran minimal harus SAMA dengan penawar tertinggi saat ini (Rp ' . number_format($tertinggi_sekarang, 0, ',', '.') . ') untuk langsung memimpin.'
        : 'Tawaran harus lebih tinggi dari penawar tertinggi saat ini (Rp ' . number_format($tertinggi_sekarang, 0, ',', '.') . ').';
    $bid_response(false, $pesan_tolak, 'error', 'Tawaran Ditolak!');
} elseif ($harga_bersih < $harga_awal) {
    mysqli_rollback($koneksi);
    $bid_response(false, 'Tawaran tidak boleh lebih rendah dari harga awal.', 'error', 'Tawaran Ditolak!');
} else {
    // 4. Simpan ke Database (Sesuai kolom tabel kamu: id_buyer, tanggal_bid, status_bid)
    $simpan_stmt = mysqli_prepare($koneksi, "INSERT INTO t_bidding (id_design, id_buyer, harga_tawaran, tanggal_bid, status_bid)
                                      VALUES (?, ?, ?, NOW(), 'aktif')");
    mysqli_stmt_bind_param($simpan_stmt, 'iid', $id_design, $id_user, $harga_bersih);
    $simpan = mysqli_stmt_execute($simpan_stmt);

    if ($simpan) {
        mysqli_commit($koneksi);
        $bid_response(true, 'Penawaran Anda berhasil dikirim.', 'success', 'Tawaran Berhasil!', 'product-detail.php?id=' . $id_design);
    } else {
        $pesan_gagal = mysqli_error($koneksi);
        mysqli_rollback($koneksi);
        $bid_response(false, 'Gagal menawar: ' . $pesan_gagal, 'error', 'Tawaran Gagal!');
    }
}
?>
