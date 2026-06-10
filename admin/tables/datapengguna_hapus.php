<?php
require_once __DIR__ . '/../../sweetalert.php';
include "../koneksi.php";

$id = $_GET['id'];

// hapus data berdasarkan id_user
$query = mysqli_query($koneksi, "DELETE FROM t_user WHERE id_user = '$id'");

if ($query) {
    sweetalert_redirect('Data pengguna berhasil dihapus.', 'datapengguna.php', 'success', 'Berhasil Dihapus!');
} else {
    sweetalert_redirect('Gagal menghapus data: ' . mysqli_error($koneksi), 'datapengguna.php', 'error', 'Gagal!');
}
?>
