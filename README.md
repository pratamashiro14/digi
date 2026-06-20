# DensCreative — Marketplace Desain

Aplikasi web marketplace desain grafis: tempat **desainer** menjual karya dan mengikuti lelang,
serta **pembeli** membeli desain, melakukan pembayaran (Midtrans), dan berkomunikasi lewat chat.
Dibangun dengan **PHP native + MySQL**.

> Project kuliah — kolaborasi tim.

---

## ✨ Fitur per Role

| Role | Fitur utama |
|------|-------------|
| **User / Pembeli** | Daftar & login, lihat & beli desain, keranjang, lelang (bidding), pembayaran Midtrans, riwayat transaksi, chat, beli premium |
| **Desainer** | Daftar & login desainer, unggah karya, kelola penjualan, profil desainer, chat |
| **Admin** | Panel admin (`/admin`): kelola pengguna, karya, transaksi, premium, bidding, chat |

---

## 🧰 Teknologi

- **Backend:** PHP native (tanpa framework)
- **Database:** MySQL / MariaDB (`dbkarya`)
- **Frontend:** HTML, CSS, Bootstrap, JavaScript
- **Pembayaran:** Midtrans (mode sandbox)
- **Server lokal:** XAMPP (Apache + MySQL)

---

## 🚀 Cara Menjalankan (Setup Lokal)

### 1. Prasyarat
- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP 8)
- [Git](https://git-scm.com/)

### 2. Clone ke folder XAMPP
```bash
cd C:\xampp\htdocs
git clone https://github.com/pratamashiro14/digi.git
```

### 3. Import database
1. Nyalakan **Apache** & **MySQL** dari XAMPP Control Panel.
2. Buka <http://localhost/phpmyadmin>.
3. Buat database baru bernama **`dbkarya`**.
4. Pilih `dbkarya` → tab **Import** → pilih file [`database/dbkarya.sql`](database/dbkarya.sql) → **Go**.

### 4. (Opsional) Install library Composer
Hanya jika muncul error terkait Midtrans:
```bash
cd C:\xampp\htdocs\digi
composer install
```

### 5. Buka di browser
| Bagian | URL |
|--------|-----|
| Halaman utama | <http://localhost/digi/> |
| Login | <http://localhost/digi/login.php> |
| Panel Admin | <http://localhost/digi/admin/> |

---

## 📁 Struktur Folder

```
digi/
├── index.php, product.php, ...   # Halaman utama (user/pembeli)
├── proses_*.php                  # Proses backend (login, daftar, bayar, bidding)
├── auth.php                      # Pusat session & role (login, guard halaman)
├── admin/                        # Panel admin
├── Midtrans/                     # Library pembayaran Midtrans (manual)
├── css/ js/ images/ fonts/       # Aset frontend
├── uploads/                      # File upload user
├── database/dbkarya.sql          # Dump database
├── docs/                         # Dokumentasi tambahan
└── _arsip/                       # File lama yang diarsipkan
```

---

## 🔐 Sistem Login & Role (`auth.php`)

Seluruh pengelolaan session dipusatkan di **`auth.php`**:

- `require_user()` — halaman khusus pembeli/user
- `require_designer()` — halaman khusus desainer
- `require_login()` — halaman untuk user **atau** desainer
- `current_id()`, `current_name()`, `current_role()` — data akun yang sedang login

Cukup tambahkan di baris paling atas tiap halaman:
```php
<?php require_once __DIR__ . '/auth.php'; ?>
```

---

## 👥 Alur Kerja Kolaborasi (Git)

```bash
git pull                       # ambil update terbaru SEBELUM mulai kerja
# ... edit file ...
git add -A
git commit -m "deskripsi perubahan"
git push                       # kirim ke GitHub
```

> 💡 Biasakan `git pull` setiap mulai bekerja agar tidak terjadi konflik dengan perubahan rekan tim.

---

## ⚠️ Catatan

- Kunci Midtrans pada repo ini adalah **sandbox** (untuk keperluan tugas/pengembangan). Jangan gunakan untuk transaksi asli.
- File `vendor/` dan `.env` sengaja tidak diikutkan ke repo — jalankan `composer install` jika diperlukan.
