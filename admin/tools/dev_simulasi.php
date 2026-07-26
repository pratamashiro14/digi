<?php
/**
 * ============================================================
 *  DEV_SIMULASI.PHP — Alat bantu demo/pengujian (BUKAN untuk produksi)
 * ============================================================
 * Semua deteksi tenggat di revisi ini bersifat LAZY (tanpa cron) — artinya
 * untuk menguji "pemenang tidak bayar dalam 24 jam" secara nyata butuh
 * menunggu 24 jam sungguhan. Alat ini memampatkan waktu tunggu itu jadi
 * satu klik: majukan tenggat ke masa lalu, lalu paksa jalankan sweep.
 *
 * DOUBLE-GUARD: mati total di produksi (APP_ENV) + wajib sesi admin.
 */
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../../wanprestasi_helper.php';
require_once __DIR__ . '/../../otp_helper.php';

if (APP_ENV === 'production' || empty($_SESSION['admin'])) {
    http_response_code(404);
    die('Halaman tidak ditemukan.');
}

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'majukan_lelang') {
        $id = (int) ($_POST['id_design'] ?? 0);
        mysqli_query($koneksi, "UPDATE t_design SET waktu_berakhir = NOW() - INTERVAL 1 MINUTE WHERE id_design = $id");
        $pesan = "Lelang #$id dimajukan: waktu_berakhir = sekarang - 1 menit.";
    } elseif ($aksi === 'lewatkan_tenggat_pemenang') {
        $id = (int) ($_POST['id_design'] ?? 0);
        mysqli_query($koneksi, "UPDATE t_design SET batas_bayar_pemenang = NOW() - INTERVAL 1 MINUTE WHERE id_design = $id");
        $pesan = "Tenggat bayar pemenang lelang #$id dilewatkan: batas_bayar_pemenang = sekarang - 1 menit.";
    } elseif ($aksi === 'reset_strike') {
        $id = (int) ($_POST['id_user'] ?? 0);
        mysqli_query($koneksi, "DELETE FROM t_wanprestasi WHERE id_user = $id");
        mysqli_query($koneksi, "UPDATE t_user SET status = 'aktif', suspend_sampai = NULL WHERE id_user = $id");
        $pesan = "Semua strike & suspend user #$id direset.";
    } elseif ($aksi === 'kembalikan_bid_gugur') {
        $id = (int) ($_POST['id_design'] ?? 0);
        mysqli_query($koneksi, "UPDATE t_bidding SET status_bid = 'aktif' WHERE id_design = $id AND status_bid = 'ditolak'");
        mysqli_query($koneksi, "UPDATE t_design SET batas_bayar_pemenang = NULL, notif_menang_terkirim = 0 WHERE id_design = $id");
        mysqli_query($koneksi, "DELETE FROM t_wanprestasi WHERE id_design = $id");
        $pesan = "Bid gugur di lelang #$id dikembalikan aktif & tenggat direset.";
    } elseif ($aksi === 'paksa_sweep') {
        $t0 = microtime(true);
        jalankan_pemeliharaan_lelang($koneksi, true);
        $ms = round((microtime(true) - $t0) * 1000, 1);
        $pesan = "Sweep pemeliharaan dijalankan paksa. Waktu eksekusi: {$ms} ms.";
    } elseif ($aksi === 'buat_ulang_otp') {
        $email = trim($_POST['email'] ?? '');
        $hasil = otp_buat_dan_kirim($koneksi, $email, 'Pengguna', 'registrasi');
        $pesan = $hasil['ok']
            ? 'Kode baru untuk ' . htmlspecialchars($email) . ': ' . htmlspecialchars($hasil['kode_dev'] ?? '(tersembunyi, OTP_DEV_SHOW mati)')
            : 'Gagal: tunggu ' . $hasil['tunggu'] . ' detik lagi.';
    }
}

// --- Data bantu untuk dropdown/lookup cepat ---
$daftar_design = mysqli_query($koneksi, "SELECT id_design, judul, status, waktu_berakhir, batas_bayar_pemenang, notif_menang_terkirim
                                          FROM t_design ORDER BY id_design DESC LIMIT 15");
$daftar_strike = mysqli_query($koneksi, "SELECT u.id_user, u.nama, u.status, u.suspend_sampai, COUNT(w.id_wanprestasi) AS strike
                                          FROM t_wanprestasi w JOIN t_user u ON u.id_user = w.id_user
                                          WHERE w.dimaafkan = 0 GROUP BY u.id_user ORDER BY strike DESC LIMIT 15");
$daftar_otp = mysqli_query($koneksi, "SELECT email, tujuan, percobaan, dipakai, expired_at, created_at
                                       FROM t_otp_email ORDER BY id_otp DESC LIMIT 15");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dev Simulasi — Alat Demo (Non-Produksi)</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f8; margin: 0; padding: 24px; color: #222; }
        .banner { background: #fff3cd; border: 1px solid #ffe08a; color: #7a5c00; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px; }
        .card { background: #fff; border-radius: 10px; padding: 16px 18px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .card h3 { margin-top: 0; font-size: 15px; }
        form.inline { display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
        input[type=text], input[type=number] { padding: 6px 8px; border: 1px solid #ccc; border-radius: 6px; font-size: 13px; flex: 1; min-width: 100px; }
        button { padding: 6px 12px; border: none; border-radius: 6px; background: #4e60ff; color: #fff; font-size: 13px; cursor: pointer; }
        button:hover { background: #3b4dcc; }
        button.danger { background: #dc3545; }
        button.secondary { background: #6c757d; }
        table { width: 100%; border-collapse: collapse; font-size: 12.5px; margin-top: 10px; }
        th, td { text-align: left; padding: 5px 6px; border-bottom: 1px solid #eee; }
        .pesan { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; font-size: 13.5px; }
        .muted { color: #888; font-size: 12px; }
    </style>
</head>
<body>
    <div class="banner">
        <strong>Alat internal untuk demo/pengujian.</strong> Mati otomatis di produksi (APP_ENV=production)
        dan wajib login admin. Dipakai untuk memampatkan tenggat 24 jam menjadi satu klik saat sidang/uji.
    </div>
    <h1>Dev Simulasi — Lelang &amp; Wanprestasi</h1>
    <p class="muted">APP_ENV = <?= htmlspecialchars(APP_ENV) ?> &middot; OTP_DEV_SHOW = <?= OTP_DEV_SHOW ? 'aktif' : 'mati' ?></p>

    <?php if ($pesan !== ''): ?>
        <div class="pesan"><?= $pesan ?></div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <h3>Majukan Lelang</h3>
            <p class="muted">Set waktu_berakhir ke masa lalu, supaya lelang dianggap sudah berakhir.</p>
            <form class="inline" method="POST">
                <input type="hidden" name="aksi" value="majukan_lelang">
                <input type="number" name="id_design" placeholder="id_design" required>
                <button type="submit">Majukan</button>
            </form>
        </div>

        <div class="card">
            <h3>Lewatkan Tenggat Bayar Pemenang</h3>
            <p class="muted">Set batas_bayar_pemenang ke masa lalu supaya pemenang dianggap telat bayar.</p>
            <form class="inline" method="POST">
                <input type="hidden" name="aksi" value="lewatkan_tenggat_pemenang">
                <input type="number" name="id_design" placeholder="id_design" required>
                <button type="submit" class="danger">Lewatkan</button>
            </form>
        </div>

        <div class="card">
            <h3>Paksa Jalankan Sweep</h3>
            <p class="muted">Jalankan jalankan_pemeliharaan_lelang() sekarang juga (bukan menunggu throttle).</p>
            <form class="inline" method="POST">
                <input type="hidden" name="aksi" value="paksa_sweep">
                <button type="submit">Jalankan Sweep</button>
            </form>
        </div>

        <div class="card">
            <h3>Reset Strike &amp; Suspend User</h3>
            <p class="muted">Hapus semua baris t_wanprestasi milik user &amp; kembalikan akun jadi aktif.</p>
            <form class="inline" method="POST">
                <input type="hidden" name="aksi" value="reset_strike">
                <input type="number" name="id_user" placeholder="id_user" required>
                <button type="submit" class="secondary">Reset</button>
            </form>
        </div>

        <div class="card">
            <h3>Kembalikan Bid Gugur</h3>
            <p class="muted">Set status_bid kembali 'aktif' utk lelang ini &amp; hapus jejak wanprestasinya.</p>
            <form class="inline" method="POST">
                <input type="hidden" name="aksi" value="kembalikan_bid_gugur">
                <input type="number" name="id_design" placeholder="id_design" required>
                <button type="submit" class="secondary">Kembalikan</button>
            </form>
        </div>

        <div class="card">
            <h3>Buat Ulang Kode OTP</h3>
            <p class="muted">Kirim/buat kode OTP baru utk sebuah email (tujuan: registrasi).</p>
            <form class="inline" method="POST">
                <input type="hidden" name="aksi" value="buat_ulang_otp">
                <input type="text" name="email" placeholder="email@contoh.com" required>
                <button type="submit">Kirim</button>
            </form>
        </div>
    </div>

    <div class="grid" style="margin-top:20px;">
        <div class="card">
            <h3>15 Lelang Terbaru</h3>
            <table>
                <tr><th>ID</th><th>Judul</th><th>Status</th><th>Berakhir</th><th>Tenggat Bayar</th><th>Notif</th></tr>
                <?php while ($d = mysqli_fetch_assoc($daftar_design)): ?>
                <tr>
                    <td><?= (int) $d['id_design'] ?></td>
                    <td><?= htmlspecialchars($d['judul']) ?></td>
                    <td><?= htmlspecialchars($d['status']) ?></td>
                    <td><?= htmlspecialchars($d['waktu_berakhir'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($d['batas_bayar_pemenang'] ?? '-') ?></td>
                    <td><?= $d['notif_menang_terkirim'] ? 'terkirim' : '-' ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <div class="card">
            <h3>User dengan Strike Aktif</h3>
            <table>
                <tr><th>ID</th><th>Nama</th><th>Strike</th><th>Status</th><th>Suspend Sampai</th></tr>
                <?php while ($s = mysqli_fetch_assoc($daftar_strike)): ?>
                <tr>
                    <td><?= (int) $s['id_user'] ?></td>
                    <td><?= htmlspecialchars($s['nama']) ?></td>
                    <td><?= (int) $s['strike'] ?></td>
                    <td><?= htmlspecialchars($s['status']) ?></td>
                    <td><?= htmlspecialchars($s['suspend_sampai'] ?? '-') ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($daftar_strike) === 0): ?>
                <tr><td colspan="5" class="muted">Tidak ada strike aktif.</td></tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="card">
            <h3>15 Kode OTP Terbaru</h3>
            <p class="muted">Kode ASLI tidak ditampilkan di sini (tersimpan ter-hash) — gunakan panel MODE DEMO
                di halaman verifikasi, atau tombol "Buat Ulang Kode OTP" di atas.</p>
            <table>
                <tr><th>Email</th><th>Tujuan</th><th>Percobaan</th><th>Dipakai</th><th>Kedaluwarsa</th></tr>
                <?php while ($o = mysqli_fetch_assoc($daftar_otp)): ?>
                <tr>
                    <td><?= htmlspecialchars($o['email']) ?></td>
                    <td><?= htmlspecialchars($o['tujuan']) ?></td>
                    <td><?= (int) $o['percobaan'] ?></td>
                    <td><?= $o['dipakai'] ? 'ya' : 'belum' ?></td>
                    <td><?= htmlspecialchars($o['expired_at']) ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</body>
</html>
