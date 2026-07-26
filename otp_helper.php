<?php
/**
 * ============================================================
 *  OTP_HELPER.PHP — Kode verifikasi email (pengganti wajib-KTP pelanggan)
 * ============================================================
 * Dipakai saat registrasi pembeli & desainer: setelah akun dibuat,
 * email_terverifikasi=0 sampai pemilik memasukkan kode 6 digit yang
 * dikirim ke emailnya. Sama sekali tidak menyentuh SMS/WA — cukup email,
 * gratis dan bisa didemokan offline lewat MODE DEMO (OTP_DEV_SHOW).
 *
 * Kode disimpan sebagai password_hash(), TIDAK PERNAH plaintext di DB.
 * Tenggat (expired_at) selalu dihitung MySQL (NOW() + INTERVAL ...), bukan
 * strtotime()/time() PHP — mengikuti aturan zona waktu di pembayaran_helper.php.
 */

require_once __DIR__ . '/mailer.php';

const OTP_MASA_BERLAKU_MENIT = 10;
const OTP_MAKS_SALAH         = 5;
const OTP_JEDA_KIRIM_DETIK   = 60;

/**
 * Buat kode OTP baru untuk $email dan kirim lewat email.
 *
 * @return array{ok:bool, terkirim:bool, kode_dev:?string, error:?string, tunggu:int}
 *   ok=false + tunggu>0  -> masih dalam jeda anti-spam, belum ada kode baru dibuat.
 *   ok=true              -> kode baru dibuat; 'terkirim' menandai sukses/tidaknya SMTP;
 *                            'kode_dev' berisi kode asli HANYA bila OTP_DEV_SHOW aktif.
 */
function otp_buat_dan_kirim($koneksi, $email, $nama, $tujuan = 'registrasi') {
    $email = trim(strtolower((string) $email));
    $nama  = trim((string) $nama) !== '' ? trim((string) $nama) : 'Pengguna';

    // 1) Anti-spam: tolak bila kode terakhir untuk email+tujuan ini baru saja dibuat.
    $stmt = mysqli_prepare($koneksi,
        "SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) AS umur
           FROM t_otp_email
          WHERE email = ? AND tujuan = ?
          ORDER BY id_otp DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ss', $email, $tujuan);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($row && $row['umur'] !== null && (int) $row['umur'] < OTP_JEDA_KIRIM_DETIK) {
        $sisa = OTP_JEDA_KIRIM_DETIK - (int) $row['umur'];
        return ['ok' => false, 'terkirim' => false, 'kode_dev' => null, 'error' => null, 'tunggu' => $sisa];
    }

    // 2) Invalidasi kode lama yang belum dipakai supaya hanya kode terbaru yang sah.
    $stmt = mysqli_prepare($koneksi,
        "UPDATE t_otp_email SET dipakai = 1 WHERE email = ? AND tujuan = ? AND dipakai = 0");
    mysqli_stmt_bind_param($stmt, 'ss', $email, $tujuan);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 3) Buat kode baru.
    $kode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $hash = password_hash($kode, PASSWORD_DEFAULT);
    $menit = (int) OTP_MASA_BERLAKU_MENIT;

    $stmt = mysqli_prepare($koneksi,
        "INSERT INTO t_otp_email (email, kode_hash, tujuan, expired_at)
         VALUES (?, ?, ?, NOW() + INTERVAL {$menit} MINUTE)");
    mysqli_stmt_bind_param($stmt, 'sss', $email, $hash, $tujuan);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 4) Kirim email (boleh gagal — MODE DEMO menyelamatkan alur di localhost).
    $judul = 'Kode Verifikasi DensCreative';
    $isi = "<p>Halo <strong>" . htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') . "</strong>,</p>
        <p>Gunakan kode berikut untuk memverifikasi email Anda:</p>
        <p style='font-size:32px;font-weight:700;letter-spacing:6px;color:#4e60ff;text-align:center;
                   background:#f0f2ff;padding:16px;border-radius:10px;'>{$kode}</p>
        <p>Kode berlaku selama " . OTP_MASA_BERLAKU_MENIT . " menit. Jangan bagikan kode ini kepada siapa pun.</p>
        <p style='color:#666;font-size:12px;'>Abaikan email ini jika Anda tidak merasa mendaftar di DensCreative.</p>";

    $hasil = kirim_email($email, $judul, email_template($judul, $isi));

    return [
        'ok'       => true,
        'terkirim' => $hasil['ok'],
        'kode_dev' => OTP_DEV_SHOW ? $kode : null,
        'error'    => $hasil['error'],
        'tunggu'   => 0,
    ];
}

/**
 * Verifikasi kode OTP yang diinput user terhadap kode terbaru yang belum dipakai.
 *
 * @return array{ok:bool, code:string}
 *   code: 'ok' | 'tidak_ada' | 'expired' | 'salah' | 'habis'
 */
function otp_verifikasi($koneksi, $email, $kode_input, $tujuan = 'registrasi') {
    $email      = trim(strtolower((string) $email));
    $kode_input = trim((string) $kode_input);

    $stmt = mysqli_prepare($koneksi,
        "SELECT id_otp, kode_hash, percobaan, (expired_at < NOW()) AS kadaluwarsa
           FROM t_otp_email
          WHERE email = ? AND tujuan = ? AND dipakai = 0
          ORDER BY id_otp DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ss', $email, $tujuan);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$row) {
        return ['ok' => false, 'code' => 'tidak_ada'];
    }
    if ((int) $row['percobaan'] >= OTP_MAKS_SALAH) {
        return ['ok' => false, 'code' => 'habis'];
    }
    if ((int) $row['kadaluwarsa'] === 1) {
        return ['ok' => false, 'code' => 'expired'];
    }

    if (password_verify($kode_input, $row['kode_hash'])) {
        $stmt = mysqli_prepare($koneksi, "UPDATE t_otp_email SET dipakai = 1 WHERE id_otp = ?");
        mysqli_stmt_bind_param($stmt, 'i', $row['id_otp']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return ['ok' => true, 'code' => 'ok'];
    }

    $stmt = mysqli_prepare($koneksi, "UPDATE t_otp_email SET percobaan = percobaan + 1 WHERE id_otp = ?");
    mysqli_stmt_bind_param($stmt, 'i', $row['id_otp']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return ['ok' => false, 'code' => 'salah'];
}

/**
 * Bersihkan baris OTP yang sudah lama basi (kadaluwarsa/terpakai > 1 hari).
 * Lazy GC — dipanggil dari jalankan_pemeliharaan_lelang(), bukan cron.
 */
function otp_bersihkan($koneksi) {
    mysqli_query($koneksi,
        "DELETE FROM t_otp_email WHERE (dipakai = 1 OR expired_at < NOW()) AND created_at < NOW() - INTERVAL 1 DAY");
}
