<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_clean();

require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/pdf/pdf_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireLogin();

$user = getCurrentUser($conn);

// Get user settings
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

// Get date range
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');
$action = $_GET['action'] ?? '';

// Validate dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to_date)) {
    die("Invalid date range");
}

// Generate PDF
$pdf_data = generatePDFContent($conn, $user, $settings, $from_date, $to_date);

// Handle view (open in browser)
if ($action == 'view') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="police_diary_preview.pdf"');
    echo $pdf_data['content'];
    exit;
}

// Handle download
if ($action == 'download') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_download'])) {
        $tour_description = trim($_POST['tour_description']);
        if (!empty($tour_description)) {
            $serial_from = $pdf_data['currentSerial'];
            $serial_to = ($pdf_data['currentSerial'] + $pdf_data['pageCount'] - 1) % 10000;
            $pdf_base64 = base64_encode($pdf_data['content']);
            saveExportedDocument($conn, $user['id'], $tour_description, $from_date, $to_date, $serial_from, $serial_to, $pdf_data['pageCount'], $pdf_data['totalEntries'], $pdf_base64);
        }
    }
    
    updateUserSerialCounter($conn, $user['id'], $pdf_data['newCounter']);
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="police_diary_' . date('Ymd') . '.pdf"');
    echo $pdf_data['content'];
    exit;
}
?>