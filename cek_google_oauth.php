<?php
/**
 * ============================================================
 *  CEK_GOOGLE_OAUTH.PHP — Diagnostik konfigurasi Google Login
 * ============================================================
 * ALAT SEMENTARA. Buka sebagai ADMIN untuk melihat nilai persis yang
 * dikirim ke Google saat tombol "Masuk dengan Google" diklik, supaya
 * error seperti "invalid_request / doesn't comply with OAuth 2.0 policy"
 * bisa dilacak tanpa menebak. HAPUS file ini setelah masalah selesai.
 *
 * Client ID & redirect URI memang nilai PUBLIK (keduanya tampil di URL
 * browser saat OAuth berjalan), jadi aman ditampilkan. Client SECRET
 * TIDAK PERNAH ditampilkan — hanya dilaporkan terisi/kosong.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/google_auth_helper.php';

if (!is_admin_login()) {
    redirect_with_alert('Halaman ini hanya untuk admin.', 'admin/', 'error', 'Ditolak');
}

$client_id   = GOOGLE_CLIENT_ID;
$secret_len  = strlen(GOOGLE_CLIENT_SECRET);
$uri_config  = GOOGLE_REDIRECT_URI;
$uri_dipakai = google_redirect_uri();

$masalah = [];
if ($client_id === '') {
    $masalah[] = 'GOOGLE_CLIENT_ID masih KOSONG di config.php server ini.';
} elseif (substr($client_id, -strlen('.apps.googleusercontent.com')) !== '.apps.googleusercontent.com') {
    $masalah[] = 'GOOGLE_CLIENT_ID tidak berakhiran ".apps.googleusercontent.com" — kemungkinan yang disalin bukan OAuth Client ID (mungkin API key atau ada karakter/spasi ikut tersalin).';
}
if ($client_id !== trim($client_id)) {
    $masalah[] = 'GOOGLE_CLIENT_ID punya spasi/baris baru di awal atau akhir.';
}
if ($secret_len === 0) {
    $masalah[] = 'GOOGLE_CLIENT_SECRET masih KOSONG di config.php server ini.';
}
if (strpos($uri_dipakai, 'https://') !== 0 && strpos($uri_dipakai, 'http://localhost') !== 0 && strpos($uri_dipakai, 'http://127.0.0.1') !== 0) {
    $masalah[] = 'Redirect URI bukan HTTPS. Google MENOLAK redirect_uri http:// untuk domain publik — inilah penyebab error "doesn\'t comply with Google\'s OAuth 2.0 policy".';
}
if ($uri_config !== $uri_dipakai) {
    $masalah[] = 'Redirect URI dari config berbeda dengan yang dipakai (skema dinaikkan otomatis ke https). Pastikan yang TERDAFTAR di Google Cloud Console adalah versi https.';
}

$auth_url = $client_id === '' ? '(tidak bisa dibangun, client_id kosong)' : google_build_auth_url('CONTOH_STATE');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Diagnostik Google OAuth</title>
    <style>
        body { font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; max-width: 900px; margin: 40px auto; padding: 0 20px; line-height: 1.6; color: #1f2937; }
        h1 { font-size: 22px; }
        table { border-collapse: collapse; width: 100%; margin: 18px 0; }
        th, td { border: 1px solid #e5e7eb; padding: 10px 12px; text-align: left; vertical-align: top; font-size: 14px; }
        th { background: #f9fafb; width: 210px; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; word-break: break-all; font-size: 13px; }
        .ok { color: #15803d; font-weight: 600; }
        .bad { color: #b91c1c; font-weight: 600; }
        .box { border-left: 4px solid #b91c1c; background: #fef2f2; padding: 14px 16px; margin: 18px 0; border-radius: 4px; }
        .box.ok { border-left-color: #15803d; background: #f0fdf4; color: #14532d; font-weight: 400; }
        ol li { margin-bottom: 6px; }
        .warn { border-left: 4px solid #b45309; background: #fffbeb; padding: 14px 16px; margin: 24px 0; border-radius: 4px; font-size: 14px; }
    </style>
</head>
<body>
    <h1>Diagnostik Konfigurasi Google Login</h1>

    <?php if ($masalah) { ?>
        <div class="box">
            <strong>Ditemukan <?php echo count($masalah); ?> masalah:</strong>
            <ol>
                <?php foreach ($masalah as $m) { ?><li><?php echo htmlspecialchars($m); ?></li><?php } ?>
            </ol>
        </div>
    <?php } else { ?>
        <div class="box ok"><strong>Konfigurasi terlihat wajar.</strong> Kalau masih error, cocokkan Redirect URI di bawah dengan yang terdaftar di Google Cloud Console — harus sama karakter per karakter.</div>
    <?php } ?>

    <table>
        <tr>
            <th>Client ID</th>
            <td><?php echo $client_id === '' ? '<span class="bad">KOSONG</span>' : '<code>' . htmlspecialchars($client_id) . '</code>'; ?></td>
        </tr>
        <tr>
            <th>Client Secret</th>
            <td><?php echo $secret_len === 0 ? '<span class="bad">KOSONG</span>' : '<span class="ok">terisi</span> (' . $secret_len . ' karakter, nilainya sengaja tidak ditampilkan)'; ?></td>
        </tr>
        <tr>
            <th>Redirect URI (dari config)</th>
            <td><code><?php echo htmlspecialchars($uri_config); ?></code></td>
        </tr>
        <tr>
            <th>Redirect URI (dikirim ke Google)</th>
            <td>
                <code><?php echo htmlspecialchars($uri_dipakai); ?></code><br>
                <small>Nilai INI yang harus didaftarkan di Google Cloud Console &rarr; Authorized redirect URIs.</small>
            </td>
        </tr>
        <tr>
            <th>BASE_URL</th>
            <td><code><?php echo htmlspecialchars(BASE_URL === '' ? '(kosong / root domain)' : BASE_URL); ?></code></td>
        </tr>
        <tr>
            <th>Skema terdeteksi PHP</th>
            <td>
                HTTPS=<code><?php echo htmlspecialchars($_SERVER['HTTPS'] ?? '(tidak diset)'); ?></code>,
                SERVER_PORT=<code><?php echo htmlspecialchars($_SERVER['SERVER_PORT'] ?? '?'); ?></code>,
                X-Forwarded-Proto=<code><?php echo htmlspecialchars($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '(tidak diset)'); ?></code>
            </td>
        </tr>
        <tr>
            <th>URL otorisasi lengkap</th>
            <td><code><?php echo htmlspecialchars($auth_url); ?></code></td>
        </tr>
    </table>

    <div class="warn">
        <strong>Yang harus dicocokkan di Google Cloud Console</strong> (APIs &amp; Services &rarr; Credentials &rarr; OAuth client ID Anda):
        <ol>
            <li>Tipe client harus <strong>Web application</strong> (bukan Desktop app / API key).</li>
            <li><strong>Authorized redirect URIs</strong> memuat persis: <code><?php echo htmlspecialchars($uri_dipakai); ?></code></li>
            <li><strong>OAuth consent screen</strong> sudah diisi. Kalau statusnya masih <em>Testing</em>, email yang dipakai untuk login harus ditambahkan sebagai <strong>Test user</strong>, kalau tidak akan ditolak.</li>
        </ol>
        Perubahan di Google Cloud Console kadang perlu beberapa menit untuk aktif.
    </div>

    <p style="font-size:13px;color:#6b7280;">Setelah masalah selesai, hapus file <code>cek_google_oauth.php</code> dari server.</p>
</body>
</html>
