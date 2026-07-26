<?php
/**
 * ============================================================
 *  GOOGLE_AUTH_HELPER.PHP — Login/Daftar Desainer via Google
 * ============================================================
 * OAuth 2.0 Authorization Code flow diimplementasikan manual lewat cURL,
 * TANPA Composer/library resmi (google/apiclient) — project ini tidak
 * memakai Composer sama sekali (lihat cara PHPMailer di-vendor manual di
 * lib/PHPMailer/). Verifikasi id_token dilakukan lewat endpoint
 * tokeninfo Google (bukan verifikasi signature JWT lokal) supaya tidak
 * perlu library JWT/crypto tambahan.
 *
 * Menggantikan syarat KTP/NIK desainer: Google sudah memverifikasi
 * kepemilikan email pendaftar, jadi email_terverifikasi bisa langsung 1
 * tanpa OTP. Identitas (KYC) tidak lagi dicek di sini — jangkar anti-fraud
 * berpindah ke cross-check manual admin saat pencairan dana (keputusan
 * produk, lihat komentar di bidding_helper.php).
 */

require_once __DIR__ . '/identitas_helper.php';

if (!defined('GOOGLE_AUTH_URL'))  define('GOOGLE_AUTH_URL', 'https://accounts.google.com/o/oauth2/v2/auth');
if (!defined('GOOGLE_TOKEN_URL')) define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
if (!defined('GOOGLE_TOKENINFO_URL')) define('GOOGLE_TOKENINFO_URL', 'https://oauth2.googleapis.com/tokeninfo');

/** True bila kredensial Google sudah diisi (tombol Google baru ditampilkan kalau ini true). */
function google_login_tersedia() {
    return GOOGLE_CLIENT_ID !== '' && GOOGLE_CLIENT_SECRET !== '';
}

/** Buat & simpan token state anti-CSRF baru (dipanggil sebelum redirect ke Google). */
function google_state_baru() {
    $state = bin2hex(random_bytes(32));
    $_SESSION['google_oauth_state'] = $state;
    return $state;
}

/** Cocokkan state dari callback dengan yang disimpan di session, lalu buang (sekali pakai). */
function google_state_valid($state) {
    $tersimpan = $_SESSION['google_oauth_state'] ?? '';
    unset($_SESSION['google_oauth_state']);
    return $tersimpan !== '' && is_string($state) && hash_equals($tersimpan, $state);
}

/**
 * Bangun URL redirect ke halaman consent Google. $state dipakai sebagai
 * proteksi CSRF (dicocokkan lagi di callback) sekaligus membawa asal
 * request (login vs daftar) bila diperlukan nanti.
 */
function google_build_auth_url($state) {
    $params = [
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $state,
        'prompt'        => 'select_account',
    ];
    return GOOGLE_AUTH_URL . '?' . http_build_query($params);
}

/**
 * Panggil cURL dengan pola defensif seragam: TIDAK PERNAH throw ke
 * pemanggil, selalu kembalikan array {ok, data|error} (meniru gaya
 * mailer.php/otp_helper.php di project ini).
 */
function _google_curl_post($url, $fields) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) {
        return ['ok' => false, 'error' => 'Gagal menghubungi Google: ' . $err];
    }
    $json = json_decode($body, true);
    if ($code !== 200 || !is_array($json)) {
        $pesan = is_array($json) && isset($json['error_description']) ? $json['error_description'] : 'HTTP ' . $code;
        return ['ok' => false, 'error' => 'Google menolak permintaan: ' . $pesan];
    }
    return ['ok' => true, 'data' => $json];
}

function _google_curl_get($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) {
        return ['ok' => false, 'error' => 'Gagal menghubungi Google: ' . $err];
    }
    $json = json_decode($body, true);
    if ($code !== 200 || !is_array($json)) {
        return ['ok' => false, 'error' => 'Token tidak valid menurut Google (HTTP ' . $code . ').'];
    }
    return ['ok' => true, 'data' => $json];
}

/**
 * Tukar authorization code jadi token, lalu verifikasi id_token via
 * endpoint tokeninfo Google (server Google yang memverifikasi signature +
 * audience, bukan kode lokal). Return array
 *   {ok:true, email, email_verified, nama}  atau  {ok:false, error}
 */
function google_tukar_code_dan_verifikasi($code) {
    $tukar = _google_curl_post(GOOGLE_TOKEN_URL, [
        'code'          => $code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code',
    ]);
    if (!$tukar['ok']) return $tukar;

    $id_token = $tukar['data']['id_token'] ?? '';
    if ($id_token === '') {
        return ['ok' => false, 'error' => 'Google tidak mengembalikan id_token.'];
    }

    $info = _google_curl_get(GOOGLE_TOKENINFO_URL . '?id_token=' . urlencode($id_token));
    if (!$info['ok']) return $info;

    $claim = $info['data'];
    // aud HARUS cocok client kita sendiri — mencegah id_token milik aplikasi lain dipakai di sini.
    if (($claim['aud'] ?? '') !== GOOGLE_CLIENT_ID) {
        return ['ok' => false, 'error' => 'Token Google tidak cocok untuk aplikasi ini.'];
    }
    if (($claim['email_verified'] ?? 'false') !== 'true') {
        return ['ok' => false, 'error' => 'Email akun Google Anda belum diverifikasi oleh Google.'];
    }
    $email = trim(strtolower((string) ($claim['email'] ?? '')));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Google tidak mengembalikan email yang valid.'];
    }

    return [
        'ok'    => true,
        'email' => $email,
        'nama'  => trim((string) ($claim['name'] ?? $email)),
    ];
}

/**
 * Cari/​buat akun DESAINER dari data Google, lalu login. Mengembalikan
 * array {ok:true} atau {ok:false, error} — pemanggil (google_callback.php)
 * yang menangani redirect+alert supaya helper ini tetap murni logika.
 */
function google_resolve_akun_desainer($koneksi, $email, $nama) {
    if (identitas_diblokir($koneksi, 'email', $email)) {
        return ['ok' => false, 'error' => 'Email ini diblokir permanen oleh admin dan tidak dapat digunakan.'];
    }

    $stmt = mysqli_prepare($koneksi, "SELECT id_user, nama, role FROM t_user WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($existing) {
        if ($existing['role'] !== 'designer' && $existing['role'] !== 'desainer') {
            return ['ok' => false, 'error' => 'Email ini sudah terdaftar sebagai akun Pembeli. Gunakan email lain untuk mendaftar sebagai Desainer.'];
        }
        login_as_designer((int) $existing['id_user'], $existing['nama'], $email);
        return ['ok' => true];
    }

    // Akun baru: id_user pola MAX+1 (sama seperti proses_daftar_desainer.php),
    // password NULL (akun ini hanya bisa login lewat Google — cek
    // verify_and_upgrade_password() di auth.php menolak hash kosong/null).
    $row = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT GREATEST(COALESCE(MAX(id_user), 0) + 1, 100) AS next_id FROM t_user"));
    $id_user = (int) $row['next_id'];

    $stmt = mysqli_prepare($koneksi, "INSERT INTO t_user
        (id_user, nama, email, password, role, status, premium, foto, status_verifikasi, email_terverifikasi)
        VALUES (?, ?, ?, NULL, 'designer', 'aktif', 0, 'default.jpg', 'unverified', 1)");
    mysqli_stmt_bind_param($stmt, 'iss', $id_user, $nama, $email);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return ['ok' => false, 'error' => 'Gagal membuat akun. Silakan coba lagi.'];
    }
    mysqli_stmt_close($stmt);

    login_as_designer($id_user, $nama, $email);
    return ['ok' => true];
}
