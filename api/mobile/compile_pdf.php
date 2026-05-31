<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once '../../config.php';
require_once '../../includes/auth.php';
require_once '../../includes/pdf/pdf_helper.php';

$headers = getallheaders();
$token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');

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

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
$from_date = $input['from_date'] ?? '';
$to_date = $input['to_date'] ?? '';
$tour_description = trim($input['tour_description'] ?? '');

if (empty($from_date) || empty($to_date) || empty($tour_description)) {
    echo json_encode(['success' => false, 'message' => 'From date, to date, and tour description are required']);
    exit;
}

// Validate dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date range format (YYYY-MM-DD)']);
    exit;
}

// Load settings
$officer_name = getUserSetting($conn, $user['id'], 'officer_name', $user['full_name']);
$police_station = getUserSetting($conn, $user['id'], 'police_station', 'CAPITAL BA, KOLBA');
$district = getUserSetting($conn, $user['id'], 'district', 'KOLBA');
$signature = getUserSetting($conn, $user['id'], 'signature_designation', $user['full_name']);

$settings = [
    'officer_name' => $officer_name ?: $user['full_name'],
    'police_station' => $police_station ?: 'CAPITAL BA, KOLBA',
    'district' => $district ?: 'KOLBA',
    'signature_designation' => $signature ?: $user['full_name']
];

// Generate PDF
$pdf_data = generatePDFContent($conn, $user, $settings, $from_date, $to_date);

if (!$pdf_data || $pdf_data['totalEntries'] == 0) {
    echo json_encode(['success' => false, 'message' => 'No diary entries found within this date range to export!']);
    exit;
}

$serial_from = $pdf_data['currentSerial'];
$serial_to = ($pdf_data['currentSerial'] + $pdf_data['pageCount'] - 1) % 10000;
$pdf_base64 = base64_encode($pdf_data['content']);

$saved = saveExportedDocument($conn, $user['id'], $tour_description, $from_date, $to_date, $serial_from, $serial_to, $pdf_data['pageCount'], $pdf_data['totalEntries'], $pdf_base64);

if ($saved) {
    // Update serial counter
    updateUserSerialCounter($conn, $user['id'], $pdf_data['newCounter']);
    echo json_encode(['success' => true, 'message' => 'PDF compiled and exported successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save exported document in database.']);
}
?>
