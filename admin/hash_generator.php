<?php
$password_baru = 'passwordyangdikehendaki'; // GANTI dengan password yang ingin Anda gunakan (misalnya, 'persib')
$hashed_password = password_hash($password_baru, PASSWORD_BCRYPT);
echo $hashed_password; 
?>