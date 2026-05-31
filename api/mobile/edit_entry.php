<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config.php';

$headers = getallheaders();
$token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');

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
$id = $input['id'] ?? $_POST['id'] ?? 0;
$entry_date = $input['entry_date'] ?? $_POST['entry_date'] ?? '';
$entry_time = $input['entry_time'] ?? $_POST['entry_time'] ?? '';
$report = $input['report'] ?? $_POST['report'] ?? '';
$orders_remarks = $input['orders_remarks'] ?? $_POST['orders_remarks'] ?? '';

if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Entry ID required']);
    exit;
}

if (empty($report)) {
    echo json_encode(['success' => false, 'message' => 'Report is required']);
    exit;
}

$stmt = $conn->prepare("UPDATE diary_entries SET entry_date = ?, entry_time = ?, report = ?, orders_remarks = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("ssssii", $entry_date, $entry_time, $report, $orders_remarks, $id, $user['id']);

if ($stmt->execute()) {
    $stmt2 = $conn->prepare("SELECT * FROM diary_entries WHERE id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $entry = $stmt2->get_result()->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => 'Entry updated successfully',
        'data' => $entry
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update entry']);
}
?>