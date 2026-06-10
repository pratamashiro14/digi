<?php
/**
 * SweetAlert response helpers for process/login/CRUD endpoints.
 */

function sweetalert_response($title, $message, $icon = 'info', $redirect = null, $go_back = false) {
    $title_json = json_encode($title, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $message_json = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $icon_json = json_encode($icon, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $redirect_json = json_encode($redirect, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $go_back_json = $go_back ? 'true' : 'false';

    echo <<<HTML
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title}</title>
</head>
<body>
    <script src="/digi/vendor/sweetalert/sweetalert.min.js"></script>
    <script>
        swal({
            title: {$title_json},
            text: {$message_json},
            icon: {$icon_json},
            button: "OK"
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

function sweetalert_back($message, $icon = 'error', $title = null) {
    if ($title === null) {
        $title = $icon === 'error' ? 'Gagal!' : 'Informasi';
    }

    sweetalert_response($title, $message, $icon, null, true);
}
