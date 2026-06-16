<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT * FROM t_user WHERE email = ? AND role IN ('designer', 'desainer') LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$query = mysqli_stmt_get_result($stmt);
$cek = $query ? mysqli_num_rows($query) : 0;

if($cek > 0){
    $data = mysqli_fetch_assoc($query);
    $password_db = $data['password'];
    $cocok = verify_and_upgrade_password($password, $password_db, 't_user', 'id_user', (int) $data['id_user']);

    if ($cocok) {
        login_as_designer($data['id_user'], $data['nama'], $data['email']);

        redirect_with_alert('Halo Desainer! Login berhasil.', 'index.php', 'success', 'Login Berhasil!');
    }
}

sweetalert_back('Akun tidak ditemukan, bukan akun desainer, atau password salah.', 'error', 'Login Gagal!');
?>
