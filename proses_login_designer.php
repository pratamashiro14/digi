<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$email_safe = mysqli_real_escape_string($koneksi, $email);

$query = mysqli_query(
    $koneksi,
    "SELECT * FROM t_user WHERE email='$email_safe' AND role IN ('designer', 'desainer')"
);
$cek = $query ? mysqli_num_rows($query) : 0;

if($cek > 0){
    $data = mysqli_fetch_assoc($query);
    $password_db = $data['password'];
    $cocok = password_verify($password, $password_db)
          || md5($password) === $password_db
          || $password === $password_db;

    if ($cocok) {
        login_as_designer($data['id_user'], $data['nama'], $data['email']);

        redirect_with_alert('Halo Desainer! Login berhasil.', 'profil_desainer.php');
    }
}

echo "<script>
    alert('Login gagal! Akun tidak ditemukan, bukan akun desainer, atau password salah.');
    window.history.back();
</script>";
exit();
?>
