# Sistem Lupa Password Admin DensCreative

## Deskripsi Fitur
Sistem lupa password memungkinkan admin untuk mereset password mereka melalui email verification dengan token yang aman.

## File-File yang Dibuat/Dimodifikasi

### 1. `admin/forgot_password.php` (BARU)
- Form input email admin
- Validasi email di database
- Generate token random 32-byte
- Simpan token ke tabel `t_password_reset` dengan waktu expiry 1 jam
- Kirim email dengan link reset password

### 2. `admin/reset_password.php` (BARU)
- Validasi token dari URL parameter
- Cek token masih berlaku (belum expired)
- Form input password baru
- Validasi password minimal 6 karakter
- Konfirmasi password harus cocok
- Hash password menggunakan `password_hash()` dengan algoritma BCRYPT
- Update password di tabel `t_admin`
- Hapus token dari `t_password_reset` setelah berhasil

### 3. `admin/index.php` (DIMODIFIKASI)
- Ubah link "Lupa Kata Sandi" dari `#` ke `forgot_password.php`

### 4. Database: Tabel `t_password_reset` (DIBUAT)
```sql
CREATE TABLE t_password_reset (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expiry DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email)
);
```

## Alur Kerja Sistem

### Step 1: Admin Klik "Lupa Kata Sandi"
```
Admin klik "Lupa Kata Sandi" di halaman login admin
↓
Menuju: /admin/forgot_password.php
```

### Step 2: Input Email dan Kirim Reset Link
```
Admin input email → Sistem cek email di t_admin
↓
Jika email ada:
  - Generate token random (32 byte hex string)
  - Simpan token + email ke t_password_reset dengan expiry 1 jam
  - Kirim email ke admin dengan link reset
    Link: http://localhost/xampp/digi/admin/reset_password.php?token=xxx
  - Tampilkan pesan: "Email reset password telah dikirim!"
  
Jika email tidak ada:
  - Tampilkan pesan: "Email tidak terdaftar di sistem."
```

### Step 3: Admin Buka Email dan Klik Link
```
Email diterima di inbox admin dengan format HTML
Konten email:
  - Judul: "Reset Password Admin DensCreative"
  - Isi: Penjelasan dan tombol "Reset Password"
  - Link backup: URL lengkap untuk copy-paste
```

### Step 4: Reset Password
```
Admin klik link di email
↓
Menuju: /admin/reset_password.php?token=xxx_token_xxx

Sistem validasi:
  ✓ Token ada di database
  ✓ Token belum expired (expiry > NOW)
  
Jika valid:
  - Tampilkan form input password baru
  - Tampilkan email (disabled, display only)
  - Input password baru (minimal 6 karakter)
  - Input ulangi password
  
Jika tidak valid:
  - Tampilkan error: "Token tidak valid atau sudah kadaluarsa (1 jam)."
  - Tombol: Kembali ke Login
```

### Step 5: Verifikasi dan Update Password
```
Admin input password baru → Submit form

Validasi:
  ✓ Password tidak boleh kosong
  ✓ Password baru = Password ulangi
  ✓ Password minimal 6 karakter
  ✓ Token masih berlaku

Jika valid:
  - Hash password menggunakan password_hash() + BCRYPT
  - UPDATE t_admin SET password='hashed_password' WHERE email='xxx'
  - DELETE token dari t_password_reset
  - Tampilkan: "Password berhasil diperbarui!"
  - Tombol: Ke Halaman Login
  
Jika tidak valid:
  - Tampilkan error message sesuai masalah
  - Tetap di form
```

### Step 6: Login dengan Password Baru
```
Admin kembali ke halaman login
↓
Input email + password baru
↓
Password diverifikasi dengan password_hash() menggunakan password_verify()
↓
Login berhasil → Redirect ke /admin/beranda.php
```

## Keamanan

### Token Security
- Token di-generate menggunakan `random_bytes(32)` (cryptographically secure)
- Token disimpan sebagai string hex 64 karakter
- Token unik per email (UNIQUE constraint di database)
- Token otomatis expired setelah 1 jam

### Password Security
- Password di-hash menggunakan `password_hash()` dengan algoritma BCRYPT
- Password lama tidak disimpan/tercatat
- Admin harus input ulang password untuk validasi
- Validasi panjang minimal 6 karakter

### Email Security
- Email header dengan MIME-Type dan Content-Type
- Gunakan From yang valid
- Email content dalam format HTML dengan styling

## Catatan Penting

### Email Configuration
Sistem menggunakan PHP `mail()` function. Pastikan XAMPP sudah dikonfigurasi untuk kirim email:

**Option 1: Menggunakan Sendmail (Linux/Mac)**
Edit `php.ini`:
```
sendmail_path = "/usr/sbin/sendmail -t -i"
```

**Option 2: Menggunakan SMTP (Windows)**
Edit `php.ini`:
```
SMTP = smtp.gmail.com
smtp_port = 587
sendmail_from = "admin@denscreative.com"
```

**Option 3: Testing Tanpa Email Real**
Untuk development, edit `forgot_password.php` dan `reset_password.php`:
- Comment baris `mail()` function
- Tampilkan token/link di halaman untuk testing
- Contoh: "Link reset: http://localhost/xampp/digi/admin/reset_password.php?token=xxx"

### Database Check
Verifikasi tabel sudah dibuat:
```sql
DESCRIBE t_password_reset;
```

Output yang diharapkan:
```
| Field      | Type         | Null | Key | Default           | Extra          |
|------------|-------------|------|-----|-------------------|-----------------|
| id         | int         | NO   | PRI | NULL              | auto_increment  |
| email      | varchar(100)| NO   | MUL | NULL              |                 |
| token      | varchar(255)| NO   | UNI | NULL              |                 |
| expiry     | datetime    | NO   |     | NULL              |                 |
| created_at | timestamp   | NO   |     | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
```

### Testing Checklist
- [ ] Klik "Lupa Kata Sandi" di login admin
- [ ] Input email yang terdaftar → Email terkirim (atau lihat link di screen jika testing mode)
- [ ] Klik link di email
- [ ] Halaman reset password terbuka dengan email ter-display
- [ ] Input password baru
- [ ] Input ulangi password (harus cocok)
- [ ] Submit → Password update di database
- [ ] Login kembali dengan password baru → Berhasil masuk

## Troubleshooting

### Email Tidak Terkirim
- Cek konfigurasi SMTP di `php.ini`
- Cek firewall/antivirus blok port SMTP
- Untuk testing, buat halaman debug untuk lihat token/link

### "Token Tidak Valid atau Sudah Kadaluarsa"
- Token berlaku hanya 1 jam setelah dibuat
- Bisa ubah waktu di line `strtotime('+1 hour')` menjadi `'+24 hours'` dll
- Cek zona waktu server di `php.ini` timezone setting

### "Email Tidak Terdaftar"
- Verifikasi email benar-benar ada di tabel `t_admin`
- Gunakan phpMyAdmin untuk check

### Password Tidak Bisa Login Setelah Reset
- Password hash baru harus kompatibel dengan login verification
- Admin login sudah support 3 format: plaintext, MD5, password_hash()
- Pastikan tidak ada error saat UPDATE ke database

## Future Improvements (Optional)
- [ ] Email template yang lebih cantik dengan logo
- [ ] Admin bisa ubah password dari admin panel
- [ ] Log aktivitas password reset
- [ ] Rate limiting untuk prevent brute force
- [ ] Kirim notifikasi email saat password berhasil direset
- [ ] Support multiple email domains untuk kirim
- [ ] SMS verification sebagai alternatif email

