<?php
include "../koneksi.php";

$id = $_GET['id'];

// hapus data berdasarkan id_bid
$query = mysqli_query($koneksi, "DELETE FROM t_bidding WHERE id_bid = '$id'");

if ($query) {
    header("Location: databidding.php");
    exit;
} else {
    echo "Gagal menghapus data: " . mysqli_error($koneksi);
}
?>