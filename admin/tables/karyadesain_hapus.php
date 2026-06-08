<?php
include "../koneksi.php";
$id = $_GET['id'];

// hapus data berdasarkan id_design
$query = mysqli_query($koneksi, "DELETE FROM t_design WHERE id_design = '$id'");

if ($query) {
    header("Location: karyadesain.php");
    exit;
} else {
    echo "Gagal menghapus data: " . mysqli_error($koneksi);
}
?>