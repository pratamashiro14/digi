<?php
/**
 * ============================================================
 *  config.example.php — TEMPLATE konfigurasi (AMAN di-commit)
 * ============================================================
 * Cara pakai di server / komputer baru:
 *   1. Salin file ini menjadi  config.php
 *   2. Isi nilai aslinya (kredensial DB & key Midtrans)
 *   3. config.php TIDAK ikut ke Git (sudah di .gitignore)
 *
 * Di hosting yang mendukung environment variable, cukup set variabel
 * berikut di dashboard; tak perlu menaruh nilai asli di file.
 */

if (!function_exists('env_or')) {
    function env_or($key, $default)
    {
        $v = getenv($key);
        return ($v === false || $v === '') ? $default : $v;
    }
}

// ------------------------------------------------------------
// 1. DATABASE
// ------------------------------------------------------------
define('DB_HOST', env_or('DB_HOST', 'localhost:3306'));
define('DB_USER', env_or('DB_USER', 'NAMA_USER_DB'));
define('DB_PASS', env_or('DB_PASS', 'PASSWORD_DB'));
define('DB_NAME', env_or('DB_NAME', 'NAMA_DATABASE'));

// ------------------------------------------------------------
// 1b. BASE URL / PATH APLIKASI
// ------------------------------------------------------------
// Prefix path tempat aplikasi diakses dari web root.
//   - Hosting root domain : ''        (https://domainanda.com)  <- default
//   - Subfolder           : '/digi'   (https://domainanda.com/digi)
//   - XAMPP lokal         : '/digi'
define('BASE_URL', rtrim(env_or('BASE_URL', ''), '/'));

// ------------------------------------------------------------
// 2. MIDTRANS  (ambil dari Dashboard Midtrans -> Settings -> Access Keys)
// ------------------------------------------------------------
define('MIDTRANS_SERVER_KEY', env_or('MIDTRANS_SERVER_KEY', 'ISI_SERVER_KEY_MIDTRANS'));
define('MIDTRANS_CLIENT_KEY', env_or('MIDTRANS_CLIENT_KEY', 'ISI_CLIENT_KEY_MIDTRANS'));
define('MIDTRANS_IS_PRODUCTION', filter_var(env_or('MIDTRANS_IS_PRODUCTION', 'false'), FILTER_VALIDATE_BOOLEAN));

// ------------------------------------------------------------
// 3. ENVIRONMENT APLIKASI ('local' atau 'production')
// ------------------------------------------------------------
define('APP_ENV', env_or('APP_ENV', 'local'));

if (APP_ENV === 'production') {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    @ini_set('display_errors', '0');
    @ini_set('log_errors', '1');
} else {
    // Local: tampilkan error spt default XAMPP, TANPA banjir "Deprecated"/"Strict"
    // dari kode lama saat jalan di PHP 8.x (samakan dgn error_reporting bawaan = 22527).
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    @ini_set('display_errors', '1');
}

if (!function_exists('app_url')) {
    /** Bangun URL absolut aplikasi (scheme + host + BASE_URL + path). */
    function app_url($path = '')
    {
        $https  = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
               || (($_SERVER['SERVER_PORT'] ?? '') == 443)
               || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $scheme = $https ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        if ($path !== '' && $path[0] !== '/') $path = '/' . $path;
        return $scheme . '://' . $host . BASE_URL . $path;
    }
}
