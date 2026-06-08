<?php
session_start();

if (empty($_SESSION['admin'])) {
    header('Location: ../logout.php');
    exit;
}

header('Location: dataadmin.php');
exit;
