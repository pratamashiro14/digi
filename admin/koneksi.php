<?php
$koneksi = mysqli_connect("localhost:3306", "root", "", "dbkarya");

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>