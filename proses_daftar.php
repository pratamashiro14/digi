<?php
require_once __DIR__ . '/sweetalert.php';

// 1. Koneksi Database
$servername = "localhost:3306";
$username = "root";
$password = "";
$dbname = "dbkarya"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// 2. Proses Data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass_input = mysqli_real_escape_string($conn, $_POST['password']);
    $role = 'pelanggan'; // Default role saat daftar sendiri

    if (empty(trim($_POST['nama'] ?? '')) || empty(trim($_POST['email'] ?? '')) || empty(trim($_POST['password'] ?? ''))) {
        sweetalert_redirect('Semua kolom pendaftaran wajib diisi!', 'register.php', 'error', 'Pendaftaran Gagal!');
        exit;
    }

    // Cek apakah email sudah ada?
    $check_email = "SELECT * FROM t_user WHERE email = '$email'";
    $result = $conn->query($check_email);

    if ($result->num_rows > 0) {
        sweetalert_redirect('Email sudah terdaftar. Silakan gunakan email lain atau login.', 'register.php', 'error', 'Pendaftaran Gagal!');
    } else {
        // 3. Enkripsi Password (Hashing)
        // Ini SANGAT PENTING supaya password_verify di login bekerja
        $password_hashed = password_hash($pass_input, PASSWORD_DEFAULT);

        // Generate ID random yang tidak bentrok dengan user lama
        do {
            $id_user = rand(100, 999);
            $check_id = "SELECT id_user FROM t_user WHERE id_user = '$id_user'";
            $result_id = $conn->query($check_id);
        } while ($result_id && $result_id->num_rows > 0);

        // 4. Insert Data
        $sql = "INSERT INTO t_user (id_user, nama, email, password, role, status, premium, foto) 
                VALUES ('$id_user', '$nama', '$email', '$password_hashed', '$role', 'aktif', 0, 'default.jpg')";

        if ($conn->query($sql) === TRUE) {
            sweetalert_redirect('Pendaftaran Pembeli berhasil. Silakan login dengan akun baru Anda.', 'login.php', 'success', 'Pendaftaran Berhasil!');
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}
$conn->close();
?>
