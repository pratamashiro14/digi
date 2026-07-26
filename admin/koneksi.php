<?php
require_once __DIR__ . '/../config.php';

$koneksi = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$koneksi) {
    // Jangan bocorkan detail koneksi ke publik di produksi
    if (APP_ENV === 'production') {
        die("Layanan sedang bermasalah. Coba lagi nanti.");
    }
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Auto-migration: hapus kolom 'nik' & 'foto_ktp' dari t_user. KTP tidak lagi
// jadi syarat desainer (digantikan Google Login + persetujuan MOU) — lihat
// mou_helper.php::mou_simpan() yang sekarang men-set status_verifikasi
// otomatis begitu MOU disetujui, tanpa perlu KTP/NIK sama sekali.
$check_nik = mysqli_query($koneksi, "SHOW COLUMNS FROM t_user LIKE 'nik'");
if ($check_nik && mysqli_num_rows($check_nik) > 0) {
    mysqli_query($koneksi, "ALTER TABLE t_user DROP COLUMN nik");
}
$check_foto_ktp = mysqli_query($koneksi, "SHOW COLUMNS FROM t_user LIKE 'foto_ktp'");
if ($check_foto_ktp && mysqli_num_rows($check_foto_ktp) > 0) {
    mysqli_query($koneksi, "ALTER TABLE t_user DROP COLUMN foto_ktp");
}

// Auto-migration untuk mendeteksi dan menambah kolom 'is_read' di t_chat jika belum ada
$check_chat = mysqli_query($koneksi, "SHOW COLUMNS FROM t_chat LIKE 'is_read'");
if ($check_chat && mysqli_num_rows($check_chat) == 0) {
    mysqli_query($koneksi, "ALTER TABLE t_chat ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER isi_pesan");
}

// Auto-migration: kolom 'batas_pembayaran' di t_transaksi (tenggat status pending).
// Dipakai untuk limit waktu "Menunggu Bayar" & mencegah transaksi pending ganda.
$check_batas = mysqli_query($koneksi, "SHOW COLUMNS FROM t_transaksi LIKE 'batas_pembayaran'");
if ($check_batas && mysqli_num_rows($check_batas) == 0) {
    mysqli_query($koneksi, "ALTER TABLE t_transaksi ADD COLUMN batas_pembayaran DATETIME DEFAULT NULL AFTER tanggal_transaksi");
}

// Auto-migration: tabel favorit desainer (pembeli premium mem-follow desainer)
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS t_favorit_desainer (
    id_favorit INT(11) NOT NULL AUTO_INCREMENT,
    id_buyer INT(11) NOT NULL,
    id_designer INT(11) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_favorit),
    UNIQUE KEY uniq_fav (id_buyer, id_designer),
    KEY id_buyer (id_buyer),
    KEY id_designer (id_designer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// Auto-migration: tabel pencairan dana desainer (escrow — dana ditahan platform
// sampai desainer mengajukan tarik & admin memproses transfer manual).
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS t_pencairan (
    id_pencairan INT(11) NOT NULL AUTO_INCREMENT,
    id_designer INT(11) NOT NULL,
    jumlah DECIMAL(12,2) NOT NULL,
    fee DECIMAL(12,2) NOT NULL DEFAULT 0,
    jumlah_diterima DECIMAL(12,2) NOT NULL DEFAULT 0,
    bank VARCHAR(50) NOT NULL,
    no_rekening VARCHAR(40) NOT NULL,
    nama_pemilik_rek VARCHAR(100) NOT NULL,
    status ENUM('pending','diproses','selesai','ditolak') DEFAULT 'pending',
    catatan_admin VARCHAR(255) DEFAULT NULL,
    tanggal_request DATETIME DEFAULT CURRENT_TIMESTAMP,
    tanggal_proses DATETIME DEFAULT NULL,
    PRIMARY KEY (id_pencairan),
    KEY id_designer (id_designer),
    CONSTRAINT t_pencairan_ibfk_1 FOREIGN KEY (id_designer) REFERENCES t_user (id_user) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
// Untuk instalasi lama: tambahkan kolom fee per-penarikan bila belum ada.
mysqli_query($koneksi, "ALTER TABLE t_pencairan ADD COLUMN IF NOT EXISTS fee DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER jumlah");
mysqli_query($koneksi, "ALTER TABLE t_pencairan ADD COLUMN IF NOT EXISTS jumlah_diterima DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER fee");

// Auto-migration: daftar blokir NIK. Ban berbasis NIK agar pelaku yang dibanned
// tetap tidak bisa ikut lelang walau membuat akun baru dengan NIK yang sama.
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS t_blokir_nik (
    id_blokir INT(11) NOT NULL AUTO_INCREMENT,
    nik VARCHAR(20) NOT NULL,
    alasan VARCHAR(255) DEFAULT NULL,
    id_admin INT(11) DEFAULT NULL,
    tanggal_blokir DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_blokir),
    UNIQUE KEY uniq_nik (nik)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// Auto-migration: portofolio desainer (fitur premium "Portofolio Showcase").
// Galeri karya non-lelang yang tampil di toko desainer untuk menarik klien.
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS t_portofolio (
    id_portofolio INT(11) NOT NULL AUTO_INCREMENT,
    id_designer INT(11) NOT NULL,
    gambar VARCHAR(255) NOT NULL,
    judul VARCHAR(150) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_portofolio),
    KEY id_designer (id_designer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// Auto-migration: notifikasi in-app (fitur premium "Notifikasi Custom").
// Dipakai mis. untuk memberi tahu pembeli premium saat desainer favoritnya
// mengunggah karya baru. Skema mengikuti tabel t_notifikasi yang sudah ada
// (id_notifikasi/tipe_notifikasi/url/dibaca) agar konsisten di fresh-install.
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS t_notifikasi (
    id_notifikasi BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_user INT(10) UNSIGNED NOT NULL,
    tipe_notifikasi VARCHAR(50) NOT NULL DEFAULT 'info',
    pesan TEXT NOT NULL,
    url VARCHAR(255) DEFAULT NULL,
    dibaca TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_notifikasi),
    KEY id_user (id_user),
    KEY dibaca (dibaca)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// Auto-migration: riwayat penarikan dana PERUSAHAAN (platform) ke rekening
// perusahaan. Sifatnya ledger/pencatatan internal — uang asli di-settle Midtrans
// otomatis ke rekening terdaftar; tabel ini melacak siapa menarik berapa & kapan
// (kontrol kejujuran admin).
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS t_penarikan_perusahaan (
    id_penarikan INT(11) NOT NULL AUTO_INCREMENT,
    id_admin INT(11) DEFAULT NULL,
    jumlah DECIMAL(14,2) NOT NULL,
    bank VARCHAR(50) NOT NULL,
    no_rekening VARCHAR(40) NOT NULL,
    nama_pemilik VARCHAR(100) NOT NULL,
    catatan VARCHAR(255) DEFAULT NULL,
    tanggal_penarikan DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_penarikan),
    KEY id_admin (id_admin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// ============================================================
// REVISI KEAMANAN: verifikasi email + kontrol perilaku pelanggan
// (menggantikan wajib-KTP untuk pelanggan; KTP tetap wajib utk desainer)
// ============================================================

// Auto-migration: t_user.email_terverifikasi (gerbang OTP registrasi).
// PENTING: backfill 1 untuk akun LAMA harus di dalam blok "kolom belum ada"
// ini, supaya hanya jalan sekali saat kolom pertama kali dibuat — akun lama
// (termasuk akun demo) tidak boleh ikut terkunci oleh gerbang verifikasi baru.
$check_email_verif = mysqli_query($koneksi, "SHOW COLUMNS FROM t_user LIKE 'email_terverifikasi'");
if ($check_email_verif && mysqli_num_rows($check_email_verif) == 0) {
    mysqli_query($koneksi, "ALTER TABLE t_user ADD COLUMN email_terverifikasi TINYINT(1) NOT NULL DEFAULT 0 AFTER email");
    mysqli_query($koneksi, "UPDATE t_user SET email_terverifikasi = 1");
}

// Auto-migration: t_user.suspend_sampai (suspend BERJANGKA hasil strike
// wanprestasi). NULL + status='nonaktif' = suspend PERMANEN oleh admin.
$check_suspend = mysqli_query($koneksi, "SHOW COLUMNS FROM t_user LIKE 'suspend_sampai'");
if ($check_suspend && mysqli_num_rows($check_suspend) == 0) {
    mysqli_query($koneksi, "ALTER TABLE t_user ADD COLUMN suspend_sampai DATETIME DEFAULT NULL AFTER status");
}

// Auto-migration: t_user.no_telp_norm (nomor HP ternormalisasi 08xxxxxxxxxx).
// Dipakai untuk aturan "1 nomor HP = 1 akun" sebagai pengganti KTP pelanggan.
// Index NON-unique dulu di sini — upgrade ke UNIQUE hanya lewat skrip
// migrasi satu-kali (admin/tools/migrasi_hapus_ktp_pelanggan.php) setelah
// duplikat data lama dibereskan, supaya DDL ini tidak gagal diam-diam
// setiap request bila ternyata masih ada duplikat.
$check_telp_norm = mysqli_query($koneksi, "SHOW COLUMNS FROM t_user LIKE 'no_telp_norm'");
if ($check_telp_norm && mysqli_num_rows($check_telp_norm) == 0) {
    mysqli_query($koneksi, "ALTER TABLE t_user ADD COLUMN no_telp_norm VARCHAR(20) DEFAULT NULL AFTER no_telp");
    mysqli_query($koneksi, "CREATE INDEX idx_telp_norm ON t_user (no_telp_norm)");
}

// Auto-migration: t_design.batas_bayar_pemenang + notif_menang_terkirim.
// batas_bayar_pemenang: tenggat pemenang lelang menyelesaikan pembayaran
// (di-set ulang tiap kali ada promosi runner-up). NULL = pakai
// waktu_berakhir + BATAS_BAYAR_PEMENANG_JAM (lihat wanprestasi_helper.php).
// notif_menang_terkirim: penanda anti-notifikasi ganda saat lelang berakhir.
$check_batas_menang = mysqli_query($koneksi, "SHOW COLUMNS FROM t_design LIKE 'batas_bayar_pemenang'");
if ($check_batas_menang && mysqli_num_rows($check_batas_menang) == 0) {
    mysqli_query($koneksi, "ALTER TABLE t_design ADD COLUMN batas_bayar_pemenang DATETIME DEFAULT NULL AFTER waktu_berakhir");
    mysqli_query($koneksi, "ALTER TABLE t_design ADD COLUMN notif_menang_terkirim TINYINT(1) NOT NULL DEFAULT 0 AFTER batas_bayar_pemenang");
}

// Auto-migration BUG FIX: kolom harga_beli_langsung dipakai di unggahan.php,
// shoping-cart.php, product-detail.php & product.php tapi tidak pernah ada
// di database/dbkarya.sql maupun auto-migration — fresh install error tanpa ini.
$check_beli_langsung = mysqli_query($koneksi, "SHOW COLUMNS FROM t_design LIKE 'harga_beli_langsung'");
if ($check_beli_langsung && mysqli_num_rows($check_beli_langsung) == 0) {
    mysqli_query($koneksi, "ALTER TABLE t_design ADD COLUMN harga_beli_langsung DECIMAL(12,2) DEFAULT 0 AFTER harga_awal");
}

// Index untuk sweep lazy wanprestasi (jalankan_pemeliharaan_lelang) supaya
// query kandidat "lelang berakhir tapi belum sold" tetap murah tanpa cron.
// CATATAN: "CREATE INDEX IF NOT EXISTS" adalah sintaks MariaDB-only (sama
// seperti jebakan ADD COLUMN IF NOT EXISTS di atas) — jadi cek manual lewat
// information_schema agar aman di MySQL murni maupun MariaDB.
$check_idx_sweep = mysqli_query($koneksi, "SELECT 1 FROM information_schema.STATISTICS
    WHERE table_schema = DATABASE() AND table_name = 't_design' AND index_name = 'idx_sweep' LIMIT 1");
if ($check_idx_sweep && mysqli_num_rows($check_idx_sweep) == 0) {
    mysqli_query($koneksi, "CREATE INDEX idx_sweep ON t_design (status, waktu_berakhir)");
}
$check_idx_bidder = mysqli_query($koneksi, "SELECT 1 FROM information_schema.STATISTICS
    WHERE table_schema = DATABASE() AND table_name = 't_bidding' AND index_name = 'idx_design_buyer' LIMIT 1");
if ($check_idx_bidder && mysqli_num_rows($check_idx_bidder) == 0) {
    mysqli_query($koneksi, "CREATE INDEX idx_design_buyer ON t_bidding (id_design, id_buyer, id_bid)");
}

// Auto-migration: t_otp_email — kode OTP verifikasi email (registrasi pelanggan
// & desainer). Kode disimpan ter-hash (password_hash), tidak pernah plaintext.
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS t_otp_email (
    id_otp INT(11) NOT NULL AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    kode_hash VARCHAR(255) NOT NULL,
    tujuan VARCHAR(30) NOT NULL DEFAULT 'registrasi',
    percobaan TINYINT(1) NOT NULL DEFAULT 0,
    dipakai TINYINT(1) NOT NULL DEFAULT 0,
    expired_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_otp),
    KEY idx_email_tujuan (email, tujuan, dipakai),
    KEY idx_expired (expired_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// Auto-migration: t_wanprestasi — jejak pemenang lelang yang tidak
// menyelesaikan pembayaran sampai tenggat (dasar sistem strike/suspend).
// UNIQUE(id_user,id_design) menegakkan aturan "strike dihitung per KARYA
// BERBEDA" SEKALIGUS membuat sweep tanpa-cron aman dijalankan berkali-kali
// (INSERT IGNORE) oleh pengunjung berbeda tanpa pernah dobel strike.
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS t_wanprestasi (
    id_wanprestasi INT(11) NOT NULL AUTO_INCREMENT,
    id_user INT(11) NOT NULL,
    id_design INT(11) NOT NULL,
    id_bid INT(11) DEFAULT NULL,
    harga_tawaran DECIMAL(12,2) DEFAULT NULL,
    batas_bayar DATETIME NOT NULL,
    alasan VARCHAR(255) DEFAULT NULL,
    dimaafkan TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_wanprestasi),
    UNIQUE KEY uniq_strike (id_user, id_design),
    KEY idx_user (id_user, dimaafkan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// Auto-migration: t_blokir_identitas — generalisasi t_blokir_nik agar admin
// bisa mem-blokir permanen berdasar NIK (desainer), nomor HP, atau email
// (pelanggan) sekaligus. t_blokir_nik LAMA tidak dihapus (aman utk rollback
// & data lama tetap ada), hanya berhenti ditulis mulai revisi ini.
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS t_blokir_identitas (
    id_blokir INT(11) NOT NULL AUTO_INCREMENT,
    tipe ENUM('nik','telp','email') NOT NULL,
    nilai VARCHAR(100) NOT NULL,
    alasan VARCHAR(255) DEFAULT NULL,
    id_admin INT(11) DEFAULT NULL,
    tanggal_blokir DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_blokir),
    UNIQUE KEY uniq_identitas (tipe, nilai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// Seed idempotent: pindahkan data blokir NIK lama ke tabel generik supaya
// ban yang sudah dijatuhkan admin sebelum revisi ini tetap berlaku.
mysqli_query($koneksi, "INSERT IGNORE INTO t_blokir_identitas (tipe, nilai, alasan, id_admin, tanggal_blokir)
    SELECT 'nik', nik, alasan, id_admin, tanggal_blokir FROM t_blokir_nik");
?>