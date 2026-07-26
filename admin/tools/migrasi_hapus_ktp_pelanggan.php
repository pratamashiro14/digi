<?php
/**
 * ============================================================
 *  MIGRASI SATU KALI — Hapus KTP pelanggan + rapikan data lama
 * ============================================================
 * SUDAH DIJALANKAN & SUDAH TIDAK RELEVAN: kolom t_user.nik & t_user.foto_ktp
 * kini di-drop TOTAL (lihat auto-migration di admin/koneksi.php — KTP/NIK
 * desainer digantikan Google Login + MOU). Bagian #1 skrip ini (baris
 * nik/foto_ktp) akan GAGAL bila dijalankan ulang karena kolomnya sudah
 * tidak ada. Bagian #2-#4 (normalisasi status_bid & no_telp_norm) tetap
 * aman & idempotent bila suatu saat perlu diulang.
 *
 * Jalankan SEKALI setelah revisi keamanan (verifikasi email + nomor HP
 * menggantikan wajib-KTP pelanggan) di-deploy, sebelum sistem dipakai
 * live. Aman diulang (idempotent) — baris yang sudah bersih dilewati.
 *
 * Tugas:
 *  1. Hapus foto KTP milik PELANGGAN (role='pelanggan') dari
 *     admin/uploads/, lalu kosongkan nik/foto_ktp & reset status_verifikasi.
 *     KTP DESAINER TIDAK DISENTUH — mereka tetap wajib KYC (lihat
 *     proses_daftar_desainer.php).
 *  2. Normalisasi t_bidding.status_bid lama yang bernilai '' (bukan
 *     'aktif') supaya konsisten dengan enum & filter `<> 'ditolak'` di
 *     bidding_helper.php.
 *  3. Backfill t_user.no_telp_norm dari no_telp yang sudah terisi.
 *  4. Laporkan (TANPA menghapus data) nomor HP yang dipakai lebih dari
 *     satu akun, lalu pasang UNIQUE INDEX no_telp_norm HANYA bila tidak
 *     ada duplikat — supaya DDL tidak gagal diam-diam / merusak data.
 *
 * Cara pakai:
 *   - CLI (disarankan): php admin/tools/migrasi_hapus_ktp_pelanggan.php
 *   - Browser: harus login sebagai admin.
 */

$is_cli = (php_sapi_name() === 'cli');

if ($is_cli) {
    require_once __DIR__ . '/../../admin/koneksi.php';
} else {
    require_once __DIR__ . '/../../auth.php';
    require_once __DIR__ . '/../../admin/koneksi.php';
    if (!is_admin_login()) {
        header('Content-Type: text/plain; charset=utf-8');
        die("Ditolak: skrip migrasi ini hanya boleh dijalankan oleh admin yang login.\n");
    }
    header('Content-Type: text/plain; charset=utf-8');
}

function baris($teks) {
    echo $teks . "\n";
}

baris('=== MIGRASI: Hapus KTP Pelanggan + Rapikan Data Lama ===');
baris('');

// ------------------------------------------------------------
// 1) Hapus foto KTP pelanggan + kosongkan nik/foto_ktp
// ------------------------------------------------------------
baris('--- Langkah 1: Hapus KTP pelanggan ---');

$res = mysqli_query($koneksi,
    "SELECT id_user, nik, foto_ktp FROM t_user
      WHERE role = 'pelanggan' AND (
            (nik IS NOT NULL AND nik <> '')
         OR (foto_ktp IS NOT NULL AND foto_ktp <> '')
      )");

$folder_uploads = __DIR__ . '/../uploads/';
$jumlah_dibersihkan = 0;
$jumlah_file_dihapus = 0;

while ($row = mysqli_fetch_assoc($res)) {
    $foto = $row['foto_ktp'];
    if (!empty($foto)) {
        $path = $folder_uploads . $foto;
        if (is_file($path)) {
            if (@unlink($path)) {
                $jumlah_file_dihapus++;
                baris("  - Hapus file KTP: {$foto} (id_user={$row['id_user']})");
            } else {
                baris("  ! GAGAL hapus file: {$foto} (id_user={$row['id_user']}) — cek permission");
            }
        } else {
            baris("  - File KTP sudah tidak ada di disk: {$foto} (id_user={$row['id_user']})");
        }
    }

    $stmt = mysqli_prepare($koneksi,
        "UPDATE t_user SET nik = NULL, foto_ktp = NULL, status_verifikasi = 'unverified' WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, 'i', $row['id_user']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $jumlah_dibersihkan++;
}

baris("Selesai: {$jumlah_dibersihkan} akun pelanggan dibersihkan, {$jumlah_file_dihapus} file KTP dihapus dari disk.");
baris('');

// ------------------------------------------------------------
// 2) Normalisasi t_bidding.status_bid lama
// ------------------------------------------------------------
baris('--- Langkah 2: Normalisasi t_bidding.status_bid ---');
mysqli_query($koneksi,
    "UPDATE t_bidding SET status_bid = 'aktif'
      WHERE status_bid IS NULL OR status_bid NOT IN ('aktif','ditolak','diterima','selesai')");
baris('Baris terdampak: ' . mysqli_affected_rows($koneksi));
baris('');

// ------------------------------------------------------------
// 3) Backfill no_telp_norm dari no_telp yang sudah terisi
// ------------------------------------------------------------
baris('--- Langkah 3: Backfill no_telp_norm ---');
$res = mysqli_query($koneksi,
    "SELECT id_user, no_telp FROM t_user
      WHERE no_telp IS NOT NULL AND no_telp <> ''
        AND (no_telp_norm IS NULL OR no_telp_norm = '')");

require_once __DIR__ . '/../../identitas_helper.php';
$jumlah_backfill = 0;
$jumlah_invalid = 0;
while ($row = mysqli_fetch_assoc($res)) {
    $norm = normalisasi_telp($row['no_telp']);
    if ($norm === '' || !telp_valid($norm)) {
        $jumlah_invalid++;
        baris("  - Lewati (format tidak valid): id_user={$row['id_user']} no_telp='{$row['no_telp']}'");
        continue;
    }
    $stmt = mysqli_prepare($koneksi, "UPDATE t_user SET no_telp_norm = ? WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, 'si', $norm, $row['id_user']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $jumlah_backfill++;
}
baris("Selesai: {$jumlah_backfill} nomor HP dinormalisasi, {$jumlah_invalid} dilewati (format tidak valid, perlu diisi ulang manual oleh pemiliknya).");
baris('');

// ------------------------------------------------------------
// 4) Laporkan duplikat no_telp_norm, lalu pasang UNIQUE INDEX bila aman
// ------------------------------------------------------------
baris('--- Langkah 4: Cek duplikat no_telp_norm & pasang UNIQUE INDEX ---');
$res = mysqli_query($koneksi,
    "SELECT no_telp_norm, COUNT(*) AS jml, GROUP_CONCAT(id_user) AS id_user_list
       FROM t_user
      WHERE no_telp_norm IS NOT NULL AND no_telp_norm <> ''
      GROUP BY no_telp_norm
     HAVING COUNT(*) > 1");

$duplikat = [];
while ($row = mysqli_fetch_assoc($res)) {
    $duplikat[] = $row;
}

if (count($duplikat) > 0) {
    baris('DITEMUKAN DUPLIKAT — UNIQUE INDEX TIDAK dipasang otomatis. Selesaikan manual dulu:');
    foreach ($duplikat as $d) {
        baris("  - {$d['no_telp_norm']} dipakai {$d['jml']} akun: id_user {$d['id_user_list']}");
    }
    baris('Setelah duplikat dibereskan (kosongkan salah satu no_telp di t_user), jalankan ulang skrip ini.');
} else {
    $cek_idx = mysqli_query($koneksi, "SELECT 1 FROM information_schema.STATISTICS
        WHERE table_schema = DATABASE() AND table_name = 't_user'
          AND index_name = 'uniq_telp_norm' LIMIT 1");
    if ($cek_idx && mysqli_num_rows($cek_idx) > 0) {
        baris('UNIQUE INDEX uniq_telp_norm sudah terpasang sebelumnya. Tidak ada yang perlu dilakukan.');
    } else {
        // Index non-unique lama (idx_telp_norm, dari admin/koneksi.php) harus
        // dilepas dulu supaya tidak dobel dengan UNIQUE index yang baru.
        $cek_idx_lama = mysqli_query($koneksi, "SELECT 1 FROM information_schema.STATISTICS
            WHERE table_schema = DATABASE() AND table_name = 't_user'
              AND index_name = 'idx_telp_norm' LIMIT 1");
        if ($cek_idx_lama && mysqli_num_rows($cek_idx_lama) > 0) {
            mysqli_query($koneksi, "DROP INDEX idx_telp_norm ON t_user");
        }
        if (mysqli_query($koneksi, "CREATE UNIQUE INDEX uniq_telp_norm ON t_user (no_telp_norm)")) {
            baris('Tidak ada duplikat. UNIQUE INDEX uniq_telp_norm berhasil dipasang — aturan "1 nomor HP = 1 akun" kini ditegakkan juga oleh database.');
        } else {
            baris('GAGAL memasang UNIQUE INDEX: ' . mysqli_error($koneksi));
        }
    }
}

baris('');
baris('=== MIGRASI SELESAI ===');
