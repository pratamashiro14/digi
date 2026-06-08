<?php

// Memuat semua file library Midtrans yang ada di folder ini
require_once dirname(__FILE__) . '/Config.php';
require_once dirname(__FILE__) . '/Transaction.php';
require_once dirname(__FILE__) . '/ApiRequestor.php';
require_once dirname(__FILE__) . '/SnapApiRequestor.php';
require_once dirname(__FILE__) . '/Notification.php';
require_once dirname(__FILE__) . '/CoreApi.php';
require_once dirname(__FILE__) . '/Snap.php';
require_once dirname(__FILE__) . '/Sanitizer.php';

// require_once dirname(__FILE__) . '/SnapBi/SnapBi.php'; // <-- Baris inilah biang keroknya, sudah kita buang.