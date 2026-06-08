<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';

// 1. Cek Login User
require_user();

$id_user = current_id(); // ID User yang sedang login
$id_design = $_POST['id_design'];

// Bersihkan format Rupiah (Contoh: "Rp 120.000" jadi "120000")
$harga_raw = $_POST['harga_tawaran']; 
$harga_bersih = preg_replace('/[^0-9]/', '', $harga_raw);

// 2. Cek Harga Tertinggi Saat Ini di Database
$cek_max = mysqli_query($koneksi, "SELECT MAX(harga_tawaran) as max_bid FROM t_bidding WHERE id_design='$id_design'");
$data_max = mysqli_fetch_assoc($cek_max);
$tertinggi_sekarang = $data_max['max_bid'] ?? 0;

// Cek Harga Awal Produk (Jangan sampai nawar di bawah harga awal)
$cek_produk = mysqli_query($koneksi, "SELECT harga_awal FROM t_design WHERE id_design='$id_design'");
$data_produk = mysqli_fetch_assoc($cek_produk);
$harga_awal = $data_produk['harga_awal'];

// 3. Validasi: Tawaran harus lebih tinggi dari tawaran terakhir & harga awal
if ($harga_bersih <= $tertinggi_sekarang) {
    echo "<script>alert('Tawaran Anda harus lebih tinggi dari penawar tertinggi saat ini (Rp ".number_format($tertinggi_sekarang,0,',','.').")!'); window.history.back();</script>";
} elseif ($harga_bersih < $harga_awal) {
    echo "<script>alert('Tawaran tidak boleh lebih rendah dari harga awal!'); window.history.back();</script>";
} else {
    // 4. Simpan ke Database (Sesuai kolom tabel kamu: id_buyer, tanggal_bid, status_bid)
    $simpan = mysqli_query($koneksi, "INSERT INTO t_bidding (id_design, id_buyer, harga_tawaran, tanggal_bid, status_bid) 
                                      VALUES ('$id_design', '$id_user', '$harga_bersih', NOW(), 'pending')");
    
    if ($simpan) {
        echo "<script>alert('Selamat! Penawaran Anda berhasil dikirim.'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Gagal menawar: ".mysqli_error($koneksi)."'); window.history.back();</script>";
    }
}
?>