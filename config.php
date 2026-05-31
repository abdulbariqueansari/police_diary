<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST', 'sql212.ezyro.com');
define('DB_USER', 'ezyro_41931463');
define('DB_PASS', 'Abdul@1994');
define('DB_NAME', 'ezyro_41931463_wbpsi_pd');

define('ROOT_PATH', __DIR__);

// Define BASE_URL dynamically
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$app_root = str_replace('\\', '/', ROOT_PATH);
$base_url = str_replace($doc_root, '', $app_root);
// Ensure it starts with a slash
if (!str_starts_with($base_url, '/')) {
    $base_url = '/' . $base_url;
}
define('BASE_URL', $base_url);

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db(DB_NAME);

$conn->set_charset("utf8mb4");
date_default_timezone_set('Asia/Kolkata');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
