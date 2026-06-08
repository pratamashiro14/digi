<?php
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

    // Cek apakah email sudah ada?
    $check_email = "SELECT * FROM t_user WHERE email = '$email'";
    $result = $conn->query($check_email);

    if ($result->num_rows > 0) {
        echo "<script>alert('Email sudah terdaftar! Silakan gunakan email lain atau Login.'); window.location='index.php';</script>";
    } else {
        // 3. Enkripsi Password (Hashing)
        // Ini SANGAT PENTING supaya password_verify di login bekerja
        $password_hashed = password_hash($pass_input, PASSWORD_DEFAULT);

        // Generate ID Random (Karena data contohmu ID-nya acak spt 202, 303)
        $id_user = rand(100, 999); 

        // 4. Insert Data
        $sql = "INSERT INTO t_user (id_user, nama, email, password, role, status, premium, foto) 
                VALUES ('$id_user', '$nama', '$email', '$password_hashed', '$role', 'aktif', 0, 'default.jpg')";

        if ($conn->query($sql) === TRUE) {
            echo "<script>alert('Pendaftaran Berhasil! Silakan Login dengan akun baru Anda.'); window.location='index.php';</script>";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}
$conn->close();
?>