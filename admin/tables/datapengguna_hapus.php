<?php
include "../koneksi.php";

$id = $_GET['id'];

// hapus data berdasarkan id_user
$query = mysqli_query($koneksi, "DELETE FROM t_user WHERE id_user = '$id'");

if ($query) {
    header("Location: datapengguna.php");
    exit;
} else {
    echo "Gagal menghapus data: " . mysqli_error($koneksi);
}
?>