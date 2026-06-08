<?php
/**
 * SISTEM INTEGRASI LOGIN DAN AKSES HALAMAN
 * ============================================
 * 
 * File ini menjelaskan alur login dan halaman yang dapat diakses setelah login
 * 
 * SESSION VARIABLES:
 * - $_SESSION['status'] = "login" (Untuk user biasa)
 * - $_SESSION['id_user'] = User ID
 * - $_SESSION['nama'] = Nama User
 * - $_SESSION['email'] = Email User
 * - $_SESSION['role'] = "user" (Untuk user biasa)
 * 
 * HALAMAN YANG MEMERLUKAN LOGIN (User Biasa):
 * 1. profil.php - Profil User
 * 2. pesan.php - Chat dengan Desainer
 * 3. unggahan.php - Unggahan Desain
 * 4. riwayat.php - Riwayat Pembelian
 * 5. riwayat_bidding.php - Riwayat Lelang
 * 6. shoping-cart.php - Keranjang Belanja
 * 7. proses_bayar.php - Proses Pembayaran
 * 8. proses_beli_premium.php - Beli Premium
 * 9. tambah_keranjang.php - Tambah ke Keranjang
 * 
 * HALAMAN YANG BISA DIAKSES TANPA LOGIN:
 * 1. index.php - Halaman Utama
 * 2. product.php - Pasar Desain
 * 3. premium.php - Fitur Unggulan
 * 4. contact.php - Hubungi Kami
 * 5. login.php - Halaman Login
 * 6. proses_login.php - Proses Login
 * 7. proses_daftar.php - Proses Pendaftaran
 * 
 * ALUR LOGIN:
 * 1. User mengakses login.php
 * 2. User mengisi form login dan submit
 * 3. Form dikirim ke proses_login.php
 * 4. Sistem cek email dan password di database (t_user)
 * 5. Jika berhasil:
 *    - Set session $_SESSION['status'] = "login"
 *    - Set $_SESSION['id_user'], $_SESSION['nama'], $_SESSION['email']
 *    - Redirect ke index.php
 * 6. Jika gagal:
 *    - Tampilkan alert error
 *    - Kembali ke halaman sebelumnya
 * 
 * SETELAH LOGIN:
 * - User akan melihat nama mereka di navbar
 * - Link "Akun saya" berubah menjadi nama user dengan link ke profil
 * - User bisa mengakses halaman-halaman yang memerlukan login
 * 
 * LOGOUT:
 * - User klik "Logout" 
 * - Redirect ke logout.php
 * - Session dihapus
 * - Redirect kembali ke index.php
 */
?>
