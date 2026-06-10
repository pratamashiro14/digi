<?php
require_once __DIR__ . '/../../sweetalert.php';
include "../koneksi.php";

$id = $_GET['id'];

// hapus data berdasarkan id_bid
$query = mysqli_query($koneksi, "DELETE FROM t_bidding WHERE id_bid = '$id'");

if ($query) {
    sweetalert_redirect('Data bidding berhasil dihapus.', 'databidding.php', 'success', 'Berhasil Dihapus!');
} else {
    sweetalert_redirect('Gagal menghapus data: ' . mysqli_error($koneksi), 'databidding.php', 'error', 'Gagal!');
}
?>
