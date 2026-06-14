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

// Auto-migration untuk mendeteksi dan menambah kolom 'is_read' di t_chat jika belum ada
$check_chat = mysqli_query($koneksi, "SHOW COLUMNS FROM t_chat LIKE 'is_read'");
if ($check_chat && mysqli_num_rows($check_chat) == 0) {
    mysqli_query($koneksi, "ALTER TABLE t_chat ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER isi_pesan");
}
?>