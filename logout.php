<?php
require_once __DIR__ . '/auth.php';
do_logout();
header("Location: index.php");
exit;
?>