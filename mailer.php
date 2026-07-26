<?php
/**
 * ============================================================
 *  MAILER.PHP — Satu-satunya pintu keluar SMTP (PHPMailer)
 * ============================================================
 * Dipakai oleh otp_helper.php (kode OTP registrasi) dan forgot_password.php
 * (link reset password). PHPMailer dipasang manual di lib/PHPMailer/src
 * (proyek ini tidak pakai Composer — lihat vendor/ yang isinya aset
 * front-end, bukan paket PHP).
 *
 * kirim_email() TIDAK PERNAH melempar exception ke pemanggil — selalu
 * mengembalikan array {ok, error} supaya alur registrasi/reset password
 * tetap bisa lanjut (lewat MODE DEMO / OTP_DEV_SHOW) walau SMTP mati,
 * misalnya saat didemokan offline di localhost tanpa internet.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/PHPMailer/src/Exception.php';
require_once __DIR__ . '/lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/lib/PHPMailer/src/SMTP.php';

/**
 * Kirim satu email HTML lewat SMTP.
 *
 * @return array{ok:bool, error:?string}
 */
function kirim_email($to, $subject, $html, $alt = '') {
    if (!MAIL_ENABLED || MAIL_USERNAME === '' || MAIL_PASSWORD === '') {
        return ['ok' => false, 'error' => 'SMTP_NONAKTIF'];
    }

    // FQN sengaja dipakai (bukan `use ... ;`) — proyek ini tidak lewat
    // Composer autoload, dan `use` hanya sah bila diletakkan di baris
    // paling atas file, bukan di dalam fungsi setelah require_once.
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_SECURE !== '' ? MAIL_SECURE : false;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = MAIL_TIMEOUT;
        $mail->SMTPKeepAlive = false;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = $alt !== '' ? $alt : strip_tags($html);

        $mail->send();
        return ['ok' => true, 'error' => null];
    } catch (\Throwable $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Bungkus konten ke template HTML DensCreative (dipakai OTP & reset password
 * agar tampilan email konsisten).
 */
function email_template($judul, $isi_html) {
    $judul_safe = htmlspecialchars($judul, ENT_QUOTES, 'UTF-8');
    return <<<HTML
<html>
<body style="font-family:Arial,sans-serif;margin:0;padding:0;background:#f5f5f5;">
    <div style="max-width:600px;margin:0 auto;padding:24px;">
        <div style="background:#fff;border-radius:14px;padding:32px 28px;box-shadow:0 4px 16px rgba(78,96,255,.08);">
            <h2 style="color:#4e60ff;margin-top:0;">{$judul_safe}</h2>
            {$isi_html}
            <p style="color:#999;font-size:11px;margin-top:32px;border-top:1px solid #eee;padding-top:16px;">
                Email otomatis dari DensCreative. Jangan balas email ini.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
}
