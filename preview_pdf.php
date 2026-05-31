<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_clean();

require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/pdf/pdf_data.php';

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

// Validate dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to_date)) {
    die("Invalid date range");
}

// Get PDF data for display info
$pdf_info = getPDFData($conn, $user, $settings, $from_date, $to_date);
$currentSerial = $pdf_info['currentSerial'];
$totalEntries = $pdf_info['totalEntries'];

// Calculate page count estimate (for display only)
$pageCount = ceil($totalEntries / 15) ?: 1;
$newCounter = ($currentSerial + $pageCount) % 10000;

$backUrl = ($user['role'] == 'admin') ? 'admin/dashboard.php' : 'user/dashboard.php';

// Format display values
$date_display = (date('Y-m-d', strtotime($from_date)) == date('Y-m-d', strtotime($to_date))) 
    ? date('d-m-Y', strtotime($from_date)) 
    : date('d-m-Y', strtotime($from_date)) . ' - ' . date('d-m-Y', strtotime($to_date));

$serial_display = ($currentSerial == (($newCounter - 1 + 10000) % 10000)) 
    ? str_pad($currentSerial, 4, '0', STR_PAD_LEFT)
    : str_pad($currentSerial, 4, '0', STR_PAD_LEFT) . ' - ' . str_pad(($newCounter - 1 + 10000) % 10000, 4, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Preview | Police Diary</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .preview-container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 20px;
        }
        .toolbar {
            background: white;
            border-radius: 20px;
            padding: 20px 25px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: var(--shadow-lg);
        }
        .toolbar h2 {
            font-size: 20px;
            color: #1e3c72;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .stats {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 8px;
        }
        .stat-badge {
            background: #f0f2f5;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            color: #6b7280;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .button-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-success {
            background: #10b981;
            color: white;
        }
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        .btn-primary:hover, .btn-success:hover, .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .pdf-viewer {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            height: 80vh;
        }
        .pdf-frame {
            width: 100%;
            height: 100%;
            border: none;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white;
            border-radius: 24px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            animation: fadeIn 0.3s ease;
        }
        .modal-content h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #1e3c72;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .modal-content textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 20px;
            min-height: 100px;
            font-family: 'Inter', sans-serif;
        }
        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .footer {
            background: white;
            border-radius: 20px;
            padding: 12px 20px;
            margin-top: 20px;
            text-align: center;
        }
        .footer p {
            color: #6b7280;
            font-size: 12px;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 768px) {
            .preview-container { padding: 15px; }
            .toolbar { flex-direction: column; text-align: center; }
            .button-group { justify-content: center; }
            .pdf-viewer { height: 60vh; }
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <div class="toolbar">
            <div>
                <h2>
                    <i class="fas fa-file-pdf" style="color: #ef4444;"></i> 
                    Police Diary PDF Preview
                </h2>
                <div class="stats">
                    <span class="stat-badge"><i class="fas fa-calendar"></i> <?php echo $date_display; ?></span>
                    <span class="stat-badge"><i class="fas fa-hashtag"></i> Serial: <?php echo $serial_display; ?></span>
                    <span class="stat-badge"><i class="fas fa-file-alt"></i> Pages: <?php echo $pageCount; ?></span>
                    <span class="stat-badge"><i class="fas fa-list-ul"></i> Entries: <?php echo $totalEntries; ?></span>
                </div>
            </div>
            <div class="button-group">
                <a href="<?php echo $backUrl; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <button onclick="showDownloadModal()" class="btn btn-success">
                    <i class="fas fa-download"></i> Download PDF
                </button>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
        
        <!-- Embedded PDF Viewer -->
        <div class="pdf-viewer">
            <iframe class="pdf-frame" src="pdf_action.php?from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&action=view"></iframe>
        </div>
        
        <div class="footer">
            <p><i class="fas fa-code"></i> Created and maintained by <strong>Abdul Barique Ansari</strong> | © <?php echo date('Y'); ?> Police Diary System</p>
        </div>
    </div>
    
    <!-- Download Modal -->
    <div id="downloadModal" class="modal">
        <div class="modal-content">
            <h3>
                <i class="fas fa-file-export"></i> 
                Export Document
            </h3>
            <p style="margin-bottom: 15px; color: #6b7280;">Please enter a tour description for this export:</p>
            <form method="POST" action="pdf_action.php?from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&action=download">
                <input type="hidden" name="from_date" value="<?php echo $from_date; ?>">
                <input type="hidden" name="to_date" value="<?php echo $to_date; ?>">
                <textarea name="tour_description" placeholder="Enter tour description (e.g., Weekly Patrolling Report, Investigation Report, Monthly Summary...)" required></textarea>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="confirm_download" class="btn btn-success">Confirm Download</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showDownloadModal() {
            document.getElementById('downloadModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('downloadModal').classList.remove('active');
        }
        
        document.getElementById('downloadModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>