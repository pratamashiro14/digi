<?php
/**
 * ============================================================
 *  midtrans_config.php — Bootstrap Midtrans terpusat
 * ============================================================
 * Sertakan SATU file ini di halaman yang butuh Midtrans:
 *     require_once __DIR__ . '/midtrans_config.php';
 *
 * File ini:
 *   - Memuat kredensial dari config.php (tidak ada secret di sini)
 *   - Memuat library Midtrans (perhatikan huruf besar 'Midtrans' agar
 *     tetap jalan di server Linux yang case-sensitive)
 *   - Mengisi konfigurasi statis Midtrans satu kali saja
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Midtrans/Midtrans.php';

\Midtrans\Config::$serverKey    = MIDTRANS_SERVER_KEY;
\Midtrans\Config::$clientKey    = MIDTRANS_CLIENT_KEY;
\Midtrans\Config::$isProduction = MIDTRANS_IS_PRODUCTION;
\Midtrans\Config::$isSanitized  = true;
\Midtrans\Config::$is3ds        = true;
