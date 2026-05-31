<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config.php';
require_once '../../includes/auth.php';

$headers = getallheaders();
$token = str_replace('Bearer ', '', $headers['Authorization'] ?? $headers['authorization'] ?? '');

if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE remember_token = ? AND token_expiry > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$officer_name = $input['officer_name'] ?? $_POST['officer_name'] ?? '';
$police_station = $input['police_station'] ?? $_POST['police_station'] ?? '';
$district = $input['district'] ?? $_POST['district'] ?? '';
$signature = $input['signature_designation'] ?? $_POST['signature_designation'] ?? '';

if (empty($officer_name)) {
    echo json_encode(['success' => false, 'message' => 'Officer name is required']);
    exit;
}

setUserSetting($conn, $user['id'], 'officer_name', $officer_name);
setUserSetting($conn, $user['id'], 'police_station', $police_station);
setUserSetting($conn, $user['id'], 'district', $district);
setUserSetting($conn, $user['id'], 'signature_designation', $signature);

echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
?>