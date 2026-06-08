<?php
// Script untuk membuat tabel t_password_reset
// Jalankan sekali: http://localhost/xampp/digi/admin/create_table.php

include 'koneksi.php';

$sql = "CREATE TABLE IF NOT EXISTS t_password_reset (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expiry DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email)
)";

if (mysqli_query($koneksi, $sql)) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px; font-family: Arial;'>
        ✅ <strong>SUCCESS!</strong> Tabel t_password_reset berhasil dibuat.<br>
        <small>Sekarang Anda bisa <a href='index.php'>kembali ke login</a> dan test sistem lupa password.</small>
    </div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px; font-family: Arial;'>
        ❌ <strong>ERROR!</strong> Gagal membuat tabel.<br>
        <small>" . mysqli_error($koneksi) . "</small>
    </div>";
}

mysqli_close($koneksi);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Setup Tabel Password Reset</title>
</head>
<body style='font-family: Arial; padding: 20px; background: #f5f5f5;'>
    <h2>Setup Tabel Password Reset</h2>
    <p>Jika Anda melihat pesan SUCCESS di atas, tabel sudah siap digunakan.</p>
    <p>Jika ada ERROR, hubungi administrator atau cek file koneksi.php</p>
</body>
</html>
