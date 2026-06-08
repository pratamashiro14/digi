<?php
// File untuk mengecek apakah user sudah login
session_start();

// Jika belum login, redirect ke login
if(!isset($_SESSION['status']) || $_SESSION['status'] != "login"){
    echo "<script>
        alert('Anda harus login terlebih dahulu!');
        window.location.href='login.php';
    </script>";
    exit();
}

// Jika login tapi role bukan user (ada role designer), redirect
if(isset($_SESSION['role']) && $_SESSION['role'] == 'designer'){
    echo "<script>
        alert('Akun ini adalah Desainer. Silakan gunakan menu Desainer!');
        window.location.href='index.php';
    </script>";
    exit();
}
?>
