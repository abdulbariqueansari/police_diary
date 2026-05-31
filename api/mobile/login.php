<?php
    
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config.php';

$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? $_POST['username'] ?? '';
$password = $input['password'] ?? $_POST['password'] ?? '';
$remember_me = $input['remember_me'] ?? $_POST['remember_me'] ?? false;

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username and password required']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    if ($user['status'] == 'inactive') {
        echo json_encode(['success' => false, 'message' => 'Account not approved yet']);
        exit;
    }
    
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $update = $conn->prepare("UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?");
    $update->bind_param("ssi", $token, $expiry, $user['id']);
    $update->execute();
    
    $conn->query("UPDATE users SET last_login = NOW() WHERE id = " . $user['id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
            'status' => $user['status'],
            'serial_counter' => $user['serial_counter'],
            'last_login' => $user['last_login'],
            'created_at' => $user['created_at']
        ],
        'token' => $token
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
}
?>