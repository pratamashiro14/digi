<?php
/**
 * ============================================================
 *  VERIFIKASI_EMAIL.PHP — Verifikasi OTP registrasi
 * ============================================================
 * Menggantikan verifikasi KTP untuk pelanggan. Dipakai baik oleh alur
 * registrasi pembeli maupun desainer (KTP desainer tetap wajib & diproses
 * terpisah oleh admin — ini HANYA soal kepemilikan alamat email).
 *
 * Alur:
 *  - proses_daftar.php / proses_daftar_desainer.php membuat akun dengan
 *    email_terverifikasi=0, mengirim OTP, lalu set $_SESSION['otp_email']
 *    dan redirect ke sini.
 *  - proses_login.php mengarahkan ke sini juga bila akun yg login belum
 *    terverifikasi (mis. user menutup browser sebelum sempat verifikasi).
 *  - Sukses verifikasi -> auto-login sesuai role, redirect ke beranda peran.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin/koneksi.php';
require_once __DIR__ . '/otp_helper.php';

const OTP_TUJUAN = 'registrasi';

$email = trim((string) ($_SESSION['otp_email'] ?? ''));
$nama  = trim((string) ($_SESSION['otp_nama'] ?? ''));

if ($email === '') {
    redirect_with_alert('Sesi verifikasi tidak ditemukan. Silakan login untuk melanjutkan verifikasi email.', 'login.php', 'info', 'Sesi Berakhir');
}

$pesan = '';
$tipe_pesan = '';
$kode_dev = null;

// Kode dari pengiriman awal saat baru daftar (lihat proses_daftar.php /
// proses_daftar_desainer.php) — ditampilkan sekali di kunjungan pertama
// halaman ini, lalu dibuang dari session supaya refresh tidak mengulanginya.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_SESSION['otp_kode_dev'])) {
    $kode_dev = $_SESSION['otp_kode_dev'];
    unset($_SESSION['otp_kode_dev']);
}

// ------------------------------------------------------------
// AKSI: verifikasi kode
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'verifikasi') {
    $kode = trim((string) ($_POST['kode'] ?? ''));

    if ($kode === '' || !ctype_digit($kode) || strlen($kode) !== 6) {
        $pesan = 'Masukkan kode 6 digit yang benar.';
        $tipe_pesan = 'error';
    } else {
        $hasil = otp_verifikasi($koneksi, $email, $kode, OTP_TUJUAN);

        if ($hasil['ok']) {
            $stmt = mysqli_prepare($koneksi, "UPDATE t_user SET email_terverifikasi = 1 WHERE email = ?");
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare($koneksi, "SELECT id_user, nama, email, role FROM t_user WHERE email = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $u = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            unset($_SESSION['otp_email'], $_SESSION['otp_nama']);

            if ($u) {
                $role = strtolower(trim($u['role'] ?? ''));
                if ($role === 'designer' || $role === 'desainer') {
                    login_as_designer($u['id_user'], $u['nama'], $u['email']);
                    sweetalert_redirect('Email berhasil diverifikasi! Selamat datang, ' . $u['nama'] . '.', 'index.php', 'success', 'Verifikasi Berhasil!');
                } else {
                    login_as_user($u['id_user'], $u['nama'], $u['email']);
                    sweetalert_redirect('Email berhasil diverifikasi! Selamat datang, ' . $u['nama'] . '.', 'product.php', 'success', 'Verifikasi Berhasil!');
                }
            }
            sweetalert_redirect('Email berhasil diverifikasi. Silakan login.', 'login.php', 'success', 'Verifikasi Berhasil!');
        }

        switch ($hasil['code']) {
            case 'salah':
                $pesan = 'Kode yang Anda masukkan salah. Coba lagi.';
                break;
            case 'expired':
                $pesan = 'Kode sudah kedaluwarsa. Klik "Kirim ulang kode" untuk mendapatkan kode baru.';
                break;
            case 'habis':
                $pesan = 'Anda salah memasukkan kode terlalu banyak kali. Klik "Kirim ulang kode" untuk mendapatkan kode baru.';
                break;
            default:
                $pesan = 'Kode tidak ditemukan. Klik "Kirim ulang kode" untuk mendapatkan kode baru.';
        }
        $tipe_pesan = 'error';
    }
}

// ------------------------------------------------------------
// AKSI: kirim ulang kode
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'kirim_ulang') {
    $hasil = otp_buat_dan_kirim($koneksi, $email, $nama, OTP_TUJUAN);

    if (!$hasil['ok']) {
        $pesan = 'Tunggu ' . $hasil['tunggu'] . ' detik lagi sebelum meminta kode baru.';
        $tipe_pesan = 'error';
    } else {
        $kode_dev = $hasil['kode_dev'];
        $pesan = $hasil['terkirim']
            ? 'Kode baru telah dikirim ke ' . htmlspecialchars($email) . '.'
            : 'Kode baru berhasil dibuat.';
        $tipe_pesan = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email - DensCreative</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --brand: #4e60ff; --brand-dark: #3b4dcc; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #eef1ff 0%, #f8f9ff 100%);
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh; margin: 0; padding: 20px;
        }
        .auth-card {
            width: 100%; max-width: 420px; background: #fff;
            border-radius: 18px; padding: 36px 32px;
            box-shadow: 0 12px 40px rgba(78,96,255,.12);
        }
        .brand-title { text-align: center; font-weight: 700; color: var(--brand); margin-bottom: 4px; }
        .brand-sub { text-align: center; color: #888; font-size: 13px; margin-bottom: 26px; }
        .icon-wrapper { text-align: center; margin-bottom: 16px; }
        .icon-wrapper i { font-size: 48px; color: var(--brand); opacity: .85; }
        .kode-input {
            width: 100%; text-align: center; font-size: 30px; letter-spacing: 12px;
            border-radius: 10px; padding: 12px 0 12px 12px; border: 1px solid #d8d8e8;
            font-weight: 700; color: #333;
        }
        .kode-input:focus { outline: none; box-shadow: none; border-color: var(--brand); }
        .btn-brand {
            background: var(--brand); color: #fff; font-weight: 600; font-size: 15px;
            border-radius: 50px; padding: 11px; width: 100%; border: none; transition: .25s; margin-top: 14px;
        }
        .btn-brand:hover { background: var(--brand-dark); color: #fff; }
        .btn-link-resend {
            background: none; border: none; color: var(--brand); font-weight: 600;
            font-size: 13.5px; width: 100%; text-align: center; margin-top: 14px; padding: 6px;
        }
        .btn-link-resend:hover { text-decoration: underline; }
        .back-link { text-align: center; margin-top: 18px; font-size: 13px; }
        .back-link a { color: var(--brand); text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
        .dev-panel {
            background: #fff8e1; border: 1px dashed #e0b400; border-radius: 10px;
            padding: 12px 14px; font-size: 12.5px; color: #7a5c00; margin-bottom: 18px;
        }
        .dev-panel strong { font-size: 20px; letter-spacing: 4px; display: block; margin-top: 4px; }
    </style>
</head>
<body>
<div class="auth-card">
    <div class="icon-wrapper"><i class="fa-solid fa-envelope-circle-check"></i></div>
    <h3 class="brand-title">DensCreative</h3>
    <p class="brand-sub">Verifikasi email <strong><?= htmlspecialchars($email) ?></strong></p>

    <?php if ($kode_dev !== null): ?>
    <div class="dev-panel">
        <i class="fa-solid fa-flask"></i> <strong>MODE DEMO</strong> — SMTP belum/tidak aktif di lingkungan ini,
        kode ditampilkan langsung supaya tetap bisa diuji:
        <strong><?= htmlspecialchars($kode_dev) ?></strong>
    </div>
    <?php endif; ?>

    <?php if ($pesan !== ''): ?>
        <div class="alert alert-<?= $tipe_pesan === 'success' ? 'success' : 'danger' ?>" role="alert">
            <i class="fa-solid fa-<?= $tipe_pesan === 'success' ? 'circle-check' : 'circle-xmark' ?> me-1"></i>
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <p style="font-size:13.5px;color:#555;text-align:center;margin-bottom:18px;">
        Masukkan 6 digit kode yang kami kirim ke email Anda. Kode berlaku <?= (int) OTP_MASA_BERLAKU_MENIT ?> menit.
    </p>

    <form method="POST">
        <input type="hidden" name="aksi" value="verifikasi">
        <input type="text" name="kode" class="kode-input" maxlength="6" inputmode="numeric"
               pattern="\d{6}" autocomplete="one-time-code" placeholder="------" autofocus required>
        <button type="submit" class="btn-brand">
            <i class="fa-solid fa-check me-1"></i> Verifikasi
        </button>
    </form>

    <form method="POST">
        <input type="hidden" name="aksi" value="kirim_ulang">
        <button type="submit" class="btn-link-resend">
            <i class="fa-solid fa-rotate-right me-1"></i> Kirim ulang kode
        </button>
    </form>

    <div class="back-link">
        <a href="login.php"><i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Login</a>
    </div>
</div>
</body>
</html>
