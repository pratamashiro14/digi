<?php
require_once __DIR__ . '/../../sweetalert.php';
include "../koneksi.php";

$id = $_GET['id'];

// hapus data berdasarkan id_premium
$query = mysqli_query($koneksi, "DELETE FROM t_premium WHERE id_premium = '$id'");

if ($query) {
    sweetalert_redirect('Data premium berhasil dihapus.', 'datapremium.php', 'success', 'Berhasil Dihapus!');
} else {
    sweetalert_redirect('Gagal menghapus data: ' . mysqli_error($koneksi), 'datapremium.php', 'error', 'Gagal!');
}
?>
