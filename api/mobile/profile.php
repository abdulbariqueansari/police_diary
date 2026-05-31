<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config.php';
require_once '../../includes/auth.php';

$headers = getallheaders();
$token = str_replace('Bearer ', '', $headers['Authorization'] ?? $headers['authorization'] ?? '');

if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE remember_token = ? AND token_expiry > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
    exit;
}

$officer_name = getUserSetting($conn, $user['id'], 'officer_name', $user['full_name']);
$police_station = getUserSetting($conn, $user['id'], 'police_station', '');
$district = getUserSetting($conn, $user['id'], 'district', '');
$signature = getUserSetting($conn, $user['id'], 'signature_designation', $user['full_name']);
$serial_counter = getUserSerialCounter($conn, $user['id']);

echo json_encode([
    'success' => true,
    'data' => [
        'officer_name' => $officer_name,
        'police_station' => $police_station,
        'district' => $district,
        'signature' => $signature,
        'serial_counter' => str_pad($serial_counter, 4, '0', STR_PAD_LEFT)
    ]
]);
?>