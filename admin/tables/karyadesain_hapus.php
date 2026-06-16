<?php
require_once __DIR__ . '/../admin_guard.php';
require_once __DIR__ . '/../../sweetalert.php';
include "../koneksi.php";
$id = (int) ($_GET['id'] ?? 0);

// hapus data berdasarkan id_design
$query = mysqli_query($koneksi, "DELETE FROM t_design WHERE id_design = '$id'");

if ($query) {
    sweetalert_redirect('Karya desain berhasil dihapus.', 'karyadesain.php', 'success', 'Berhasil Dihapus!');
} else {
    sweetalert_redirect('Gagal menghapus karya: ' . mysqli_error($koneksi), 'karyadesain.php', 'error', 'Gagal!');
}
?>
