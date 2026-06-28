<?php
/**
 * SweetAlert response helpers for process/login/CRUD endpoints.
 */

require_once __DIR__ . '/config.php'; // BASE_URL untuk path aset yang benar di lokal & produksi

function sweetalert_response($title, $message, $icon = 'info', $redirect = null, $go_back = false, $auto_close_ms = null) {
    $base = defined('BASE_URL') ? BASE_URL : '';
    $title_json = json_encode($title, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $message_json = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $icon_json = json_encode($icon, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $redirect_json = json_encode($redirect, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $go_back_json = $go_back ? 'true' : 'false';
    $button_json = $auto_close_ms === null ? '"OK"' : 'false';
    $timer_json = $auto_close_ms === null ? 'null' : (string) max(0, (int) $auto_close_ms);

    echo <<<HTML
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f3e8ff 0%, #e0e7ff 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(124, 58, 237, 0.1) 0%, rgba(255, 255, 255, 0) 70%);
            top: -200px;
            left: -200px;
            border-radius: 50%;
            z-index: 0;
        }
        body::after {
            content: '';
            position: absolute;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(79, 70, 229, 0.1) 0%, rgba(255, 255, 255, 0) 70%);
            bottom: -300px;
            right: -200px;
            border-radius: 50%;
            z-index: 0;
        }
        .sweet-alert {
            border-radius: 24px !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15) !important;
            padding: 40px 30px !important;
            animation: popIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards !important;
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            z-index: 10 !important;
        }
        @keyframes popIn {
            0% { transform: scale(0.9) translateY(20px); opacity: 0; }
            100% { transform: scale(1) translateY(0); opacity: 1; }
        }
        .sweet-alert h2 {
            font-family: 'Poppins', sans-serif !important;
            font-weight: 800 !important;
            color: #1e293b !important;
            margin-top: 24px !important;
            font-size: 26px !important;
        }
        .sweet-alert p {
            font-family: 'Poppins', sans-serif !important;
            color: #64748b !important;
            font-size: 15px !important;
            font-weight: 500 !important;
            line-height: 1.6 !important;
            margin-top: 10px !important;
        }
        .sweet-alert .sa-button-container {
            margin-top: 30px !important;
        }
        .sweet-alert .sa-button-container button {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%) !important;
            border-radius: 12px !important;
            font-weight: 700 !important;
            padding: 14px 28px !important;
            font-size: 15px !important;
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4) !important;
            transition: all 0.3s ease !important;
            font-family: 'Poppins', sans-serif !important;
            letter-spacing: 0.5px;
        }
        .sweet-alert .sa-button-container button:hover {
            transform: translateY(-3px) !important;
            box-shadow: 0 15px 25px -5px rgba(99, 102, 241, 0.5) !important;
        }
        .sweet-alert .sa-icon {
            margin-bottom: 20px !important;
            transform: scale(1.1);
        }
        .sweet-overlay {
            background-color: rgba(15, 23, 42, 0.4) !important;
            backdrop-filter: blur(4px);
        }
    </style>
</head>
<body>
    <script src="{$base}/vendor/sweetalert/sweetalert.min.js"></script>
    <script>
        swal({
            title: {$title_json},
            text: {$message_json},
            icon: {$icon_json},
            button: {$button_json},
            timer: {$timer_json},
            closeOnClickOutside: false,
            closeOnEsc: false
        }).then(function () {
            var redirect = {$redirect_json};
            if (redirect) {
                window.location.href = redirect;
            } else if ({$go_back_json}) {
                window.history.back();
            }
        });
    </script>
</body>
</html>
HTML;
    exit();
}

function sweetalert_redirect($message, $redirect, $icon = 'success', $title = null) {
    if ($title === null) {
        $title = $icon === 'success' ? 'Berhasil!' : ($icon === 'error' ? 'Gagal!' : 'Informasi');
    }

    sweetalert_response($title, $message, $icon, $redirect);
}

function sweetalert_redirect_auto($message, $redirect, $icon = 'success', $title = null, $delay_ms = 3000) {
    if ($title === null) {
        $title = $icon === 'success' ? 'Berhasil!' : ($icon === 'error' ? 'Gagal!' : 'Informasi');
    }

    sweetalert_response($title, $message, $icon, $redirect, false, $delay_ms);
}

function sweetalert_back($message, $icon = 'error', $title = null) {
    if ($title === null) {
        $title = $icon === 'error' ? 'Gagal!' : 'Informasi';
    }

    sweetalert_response($title, $message, $icon, null, true);
}
