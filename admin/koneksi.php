<?php
$koneksi = mysqli_connect("localhost:3306", "root", "", "dbkarya");

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Auto-migration untuk mendeteksi dan menambah kolom 'nik' jika belum ada
$check_column = mysqli_query($koneksi, "SHOW COLUMNS FROM t_user LIKE 'nik'");
if ($check_column && mysqli_num_rows($check_column) == 0) {
    mysqli_query($koneksi, "ALTER TABLE t_user ADD COLUMN nik VARCHAR(20) DEFAULT NULL AFTER status_verifikasi");
}
?>