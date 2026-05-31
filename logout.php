<?php
require_once 'config.php';

// Clear remember me cookie and database token
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $conn->query("UPDATE users SET remember_token = NULL, token_expiry = NULL WHERE remember_token = '$token'");
    setcookie('remember_token', '', time() - 3600, "/", "", false, true);
}

// Start session and destroy
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_destroy();
header('Location: login.php');
exit;
?>