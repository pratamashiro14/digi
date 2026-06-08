<?php
include "../koneksi.php";

$id = $_GET['id'];

// hapus data berdasarkan id_premium
$query = mysqli_query($koneksi, "DELETE FROM t_premium WHERE id_premium = '$id'");

if ($query) {
    header("Location: datapremium.php");
    exit;
} else {
    echo "Gagal menghapus data: " . mysqli_error($koneksi);
}
?>