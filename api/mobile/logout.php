<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config.php';

$headers = getallheaders();
$token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');

if (!empty($token)) {
    $stmt = $conn->prepare("UPDATE users SET remember_token = NULL, token_expiry = NULL WHERE remember_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
}

echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
?>