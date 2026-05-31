<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config.php';

$headers = getallheaders();
$token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');

if (empty($token)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE remember_token = ? AND token_expiry > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if (empty($id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Export ID required']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM exported_documents WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user['id']);
$stmt->execute();
$export = $stmt->get_result()->fetch_assoc();

if (!$export || empty($export['pdf_content'])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'PDF not found']);
    exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="exported_diary_' . $export['id'] . '.pdf"');
echo base64_decode($export['pdf_content']);
?>