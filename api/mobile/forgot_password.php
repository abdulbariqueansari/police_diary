<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config.php';

$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? $_POST['username'] ?? '';

if (empty($username)) {
    echo json_encode(['success' => false, 'message' => 'Username required']);
    exit;
}

$stmt = $conn->prepare("SELECT id, status FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Username not found']);
    exit;
}

if ($user['status'] == 'inactive') {
    echo json_encode(['success' => false, 'message' => 'Account not approved yet']);
    exit;
}

$new_password = 'password123';
$hashed = password_hash($new_password, PASSWORD_DEFAULT);

$update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$update->bind_param("si", $hashed, $user['id']);

if ($update->execute()) {
    echo json_encode(['success' => true, 'message' => 'Password reset to password123']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to reset password']);
}
?>