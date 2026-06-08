<?php
session_start();

// CEK LOGIN TERLEBIH DAHULU
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    echo "<script>alert('Silakan login terlebih dahulu!'); window.location.href='login.php';</script>";
    exit();
}

// 1. Ambil ID Produk dari Link
$id_design = isset($_GET['id']) ? $_GET['id'] : null;

// 2. Validasi
if($id_design) {
    // Jika keranjang belum ada, buat array baru
    if(!isset($_SESSION['keranjang'])) {
        $_SESSION['keranjang'] = [];
    }

    // Jika produk sudah ada, tambah jumlahnya
    if(isset($_SESSION['keranjang'][$id_design])) {
        $_SESSION['keranjang'][$id_design] += 1;
    } 
    // Jika belum ada, masukkan dengan jumlah 1
    else {
        $_SESSION['keranjang'][$id_design] = 1;
    }
    
    // Redirect balik ke halaman sebelumnya (index)
    echo "<script>
        alert('Produk berhasil ditambahkan ke keranjang!');
        window.history.back();
    </script>";
} else {
    echo "<script>window.history.back();</script>";
}
?>