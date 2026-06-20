# Panduan Setup DensCreative (untuk komputer baru / teman)

Panduan ini untuk menjalankan proyek **DensCreative** di komputer baru setelah `git clone`.
Aplikasi ini berbasis **PHP + MySQL** dan dijalankan lewat **XAMPP**.

> **Penting:** File `config.php` (berisi kredensial database & key Midtrans) **TIDAK ikut**
> ke GitHub karena rahasia. Jadi setelah clone, kamu wajib membuat `config.php` sendiri
> (langkah 3). Ini normal dan disengaja.

---

## 1. Syarat (yang harus terpasang)

- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL/MariaDB + PHP 8.x)
- Git
- Browser

---

## 2. Clone proyek ke folder htdocs

Buka terminal/Git Bash di folder `htdocs` XAMPP (biasanya `C:\xampp\htdocs`):

```bash
cd C:/xampp/htdocs
git clone https://github.com/pratamashiro14/digi.git
cd digi
```

Pastikan folder akhirnya bernama `digi` di dalam `htdocs`, sehingga aplikasi
bisa dibuka di: **http://localhost/digi**

---

## 3. Buat file `config.php`

Salin template `config.example.php` menjadi `config.php`:

```bash
cp config.example.php config.php
```

> Di Windows tanpa terminal: copy-paste `config.example.php`, lalu rename salinannya
> menjadi `config.php`.

Lalu buka `config.php` dan isi nilai aslinya:

```php
// DATABASE
define('DB_HOST', 'localhost:3306');
define('DB_USER', 'root');        // default XAMPP
define('DB_PASS', '');            // default XAMPP biasanya kosong
define('DB_NAME', 'dbkarya');

// MIDTRANS (minta key ke pemilik proyek lewat chat pribadi — JANGAN lewat GitHub)
define('MIDTRANS_SERVER_KEY', 'isi_server_key');
define('MIDTRANS_CLIENT_KEY', 'isi_client_key');
define('MIDTRANS_IS_PRODUCTION', false); // false = mode sandbox/testing
```

> **Key Midtrans dari mana?**
> - Untuk testing bareng: minta key ke pemilik proyek lewat **chat pribadi** (WhatsApp/DM),
>   **bukan** di-commit ke GitHub.
> - Atau bikin akun Midtrans sandbox sendiri di https://dashboard.sandbox.midtrans.com
>   (Settings → Access Keys).
>
> **Catatan:** Server Key dan Client Key harus dari lingkungan yang sama
> (dua-duanya sandbox, atau dua-duanya produksi). Jangan dicampur.

`config.php` yang baru kamu buat otomatis **tidak akan** ikut ter-commit (sudah di `.gitignore`).

---

## 4. Buat & import database

1. Nyalakan **Apache** dan **MySQL** dari XAMPP Control Panel.
2. Buka **phpMyAdmin**: http://localhost/phpmyadmin
3. Buat database baru bernama **`dbkarya`**.
4. Pilih database `dbkarya` → tab **Import** → pilih file:
   - `database/dbkarya.sql`  → klik **Go/Kirim**
5. (Opsional, fitur lupa password admin) import juga:
   - `admin/SETUP_PASSWORD_RESET.sql`

> Beberapa tabel/kolom tambahan (mis. `nik`, `t_favorit_desainer`) dibuat otomatis
> oleh aplikasi saat pertama dijalankan, jadi tidak perlu khawatir kalau belum ada.

---

## 5. Jalankan aplikasi

Buka browser:

- Halaman utama: **http://localhost/digi**
- Halaman admin: **http://localhost/digi/admin**

---

## 6. Checklist kalau error

| Gejala | Kemungkinan penyebab |
|--------|----------------------|
| `require config.php failed` / halaman putih | Belum membuat `config.php` (ulangi langkah 3) |
| `Koneksi gagal` | `DB_USER`/`DB_PASS`/`DB_NAME` salah, atau MySQL belum nyala |
| `Unknown database 'dbkarya'` | Database belum dibuat / belum di-import (langkah 4) |
| Pembayaran Midtrans gagal | Key salah, atau Server & Client Key beda lingkungan (sandbox vs produksi) |

---

## Aturan keamanan (penting!)

- **JANGAN PERNAH** commit `config.php` ke GitHub.
- Kirim key Midtrans & kredensial lewat jalur pribadi (chat), bukan lewat repo.
- Kalau key tidak sengaja ter-commit, segera **rotasi (buat ulang)** key di Dashboard Midtrans.
