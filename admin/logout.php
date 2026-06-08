<?php
session_start();

// Hapus semua data sesi
session_unset();

// Hancurkan sesi
session_destroy();

// Arahkan kembali ke halaman login admin
header("Location: index.php");
exit;
?>
