<?php
require_once 'config.php';

$username = $_GET['username'] ?? '';

// Check in users table
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$exists = $stmt->get_result()->num_rows > 0;

// Check in pending requests
$stmt2 = $conn->prepare("SELECT id FROM account_requests WHERE username = ? AND status = 'pending'");
$stmt2->bind_param("s", $username);
$stmt2->execute();
$pending = $stmt2->get_result()->num_rows > 0;

header('Content-Type: application/json');
echo json_encode([
    'exists' => $exists,
    'pending' => $pending
]);
?>