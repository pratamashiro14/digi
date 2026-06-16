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

// Auto-migration untuk mendeteksi dan menambah kolom 'nik' jika belum ada
$check_column = mysqli_query($koneksi, "SHOW COLUMNS FROM t_user LIKE 'nik'");
if ($check_column && mysqli_num_rows($check_column) == 0) {
    mysqli_query($koneksi, "ALTER TABLE t_user ADD COLUMN nik VARCHAR(20) DEFAULT NULL AFTER status_verifikasi");
}

// Auto-migration untuk mendeteksi dan menambah kolom 'is_read' di t_chat jika belum ada
$check_chat = mysqli_query($koneksi, "SHOW COLUMNS FROM t_chat LIKE 'is_read'");
if ($check_chat && mysqli_num_rows($check_chat) == 0) {
    mysqli_query($koneksi, "ALTER TABLE t_chat ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER isi_pesan");
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
?>