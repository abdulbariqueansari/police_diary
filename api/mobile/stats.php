<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

$totalEntries = $conn->query("SELECT COUNT(*) as count FROM diary_entries WHERE user_id = " . $user['id'])->fetch_assoc()['count'];
$totalExports = $conn->query("SELECT COUNT(*) as count FROM exported_documents WHERE user_id = " . $user['id'])->fetch_assoc()['count'];
$monthEntries = $conn->query("SELECT COUNT(*) as count FROM diary_entries WHERE user_id = " . $user['id'] . " AND MONTH(entry_date) = MONTH(CURDATE())")->fetch_assoc()['count'];
$todayEntries = $conn->query("SELECT COUNT(*) as count FROM diary_entries WHERE user_id = " . $user['id'] . " AND entry_date = CURDATE()")->fetch_assoc()['count'];

echo json_encode([
    'success' => true,
    'total_entries' => $totalEntries,
    'total_exports' => $totalExports,
    'month_entries' => $monthEntries,
    'today_entries' => $todayEntries
]);
?>