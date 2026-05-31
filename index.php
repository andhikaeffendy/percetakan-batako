<?php
// index.php — Root redirect
require_once __DIR__ . '/helpers/auth.php';
if (isLoggedIn()) {
    if (isPemilik()) {
        header('Location: /pemilik/dashboard.php');
    } else {
        header('Location: /operator/index.php');
    }
} else {
    header('Location: /login.php');
}
exit;
