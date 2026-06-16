<?php
require_once __DIR__ . '/../admin_guard.php';
require_once __DIR__ . '/../../sweetalert.php';
include "../koneksi.php";

$id = (int) ($_GET['id'] ?? 0);

// hapus data berdasarkan id_premium
$query = mysqli_query($koneksi, "DELETE FROM t_premium WHERE id_premium = '$id'");

if ($query) {
    sweetalert_redirect('Data premium berhasil dihapus.', 'datapremium.php', 'success', 'Berhasil Dihapus!');
} else {
    sweetalert_redirect('Gagal menghapus data: ' . mysqli_error($koneksi), 'datapremium.php', 'error', 'Gagal!');
}
?>
