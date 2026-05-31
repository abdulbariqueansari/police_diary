<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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
$entry_date = $input['entry_date'] ?? $_POST['entry_date'] ?? date('Y-m-d');
$entry_time = $input['entry_time'] ?? $_POST['entry_time'] ?? date('H:i');
$report = $input['report'] ?? $_POST['report'] ?? '';
$orders_remarks = $input['orders_remarks'] ?? $_POST['orders_remarks'] ?? '';

if (empty($report)) {
    echo json_encode(['success' => false, 'message' => 'Report is required']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO diary_entries (user_id, entry_date, entry_time, report, orders_remarks) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issss", $user['id'], $entry_date, $entry_time, $report, $orders_remarks);

if ($stmt->execute()) {
    $entry_id = $conn->insert_id;
    
    $stmt2 = $conn->prepare("SELECT * FROM diary_entries WHERE id = ?");
    $stmt2->bind_param("i", $entry_id);
    $stmt2->execute();
    $entry = $stmt2->get_result()->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => 'Entry added successfully',
        'data' => $entry
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add entry']);
}
?>