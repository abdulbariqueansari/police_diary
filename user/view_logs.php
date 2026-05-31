<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireLogin();

$page_title = 'View Logs';
$user = getCurrentUser($conn);

if (!$user) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

$query = "SELECT *, DATE_FORMAT(entry_date, '%d-%m-%Y') as formatted_date, DATE_FORMAT(entry_time, '%h:%i %p') as formatted_time FROM diary_entries WHERE user_id = ? AND entry_date BETWEEN ? AND ?";
$params = [$user['id'], $from_date, $to_date];
$types = "iss";

if (!empty($search)) {
    $query .= " AND (report LIKE ? OR orders_remarks LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

$query .= " ORDER BY entry_date DESC, entry_time DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$entries = $stmt->get_result();
$total_entries = $entries->num_rows;

include_once '../includes/header.php';
?>

<!-- Filter Card -->
<div class="filter-card">
    <form method="GET" class="filter-form">
        <div class="filter-group">
            <label>From Date</label>
            <input type="date" name="from_date" value="<?php echo $from_date; ?>">
        </div>
        <div class="filter-group">
            <label>To Date</label>
            <input type="date" name="to_date" value="<?php echo $to_date; ?>">
        </div>
        <div class="filter-group search-group">
            <label>Search</label>
            <input type="text" name="search" placeholder="Search in report/remarks..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="filter-group button-group">
            <label>&nbsp;</label>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply</button>
                <a href="view_logs.php" class="btn btn-secondary"><i class="fas fa-sync-alt"></i> Reset</a>
            </div>
        </div>
    </form>
</div>

<!-- Entries Card -->
<div class="card">
    <h3><i class="fas fa-list-ul"></i> My Diary Entries (<?php echo $total_entries; ?> records)</h3>
    
    <?php if($total_entries > 0): ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Report</th>
                    <th>Remarks</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $entries->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['formatted_date']; ?>
                    <td><?php echo $row['formatted_time']; ?>
                    <td style="max-width: 300px; word-wrap: break-word;"><?php echo nl2br(htmlspecialchars($row['report'])); ?>
                    <td style="max-width: 200px; word-wrap: break-word;"><?php echo nl2br(htmlspecialchars($row['orders_remarks'] ?: '-')); ?>
                    <td class="action-buttons" style="display: flex; gap: 5px; flex-wrap: wrap;">
                        <a href="edit_entry.php?id=<?php echo $row['id']; ?>" class="action-icon icon-edit" title="Edit Entry">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="dashboard.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this entry?')" class="action-icon icon-delete" title="Delete Entry">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Export button below entries -->.

    <div style="margin-top: 20px; text-align: right;">  
        <a href="../preview_pdf.php?from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>" class="btn btn-success">
             <i class="fas fa-file-pdf"></i> Export to PDF
        </a>
    </div>
    
    <?php else: ?>
    <div style="text-align: center; padding: 50px;">
        <i class="fas fa-inbox" style="font-size: 48px; color: #ccc;"></i>
        <p>No entries found.</p>
        <a href="dashboard.php" class="btn btn-primary" style="margin-top: 15px;"><i class="fas fa-plus"></i> Add Entry</a>
    </div>
    <?php endif; ?>
</div>

<style>
    .btn { padding: 8px 16px; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; border-radius: 8px; }
    .filter-form { display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
    .filter-group { flex: 1; min-width: 140px; }
    .filter-group label { font-size: 12px; margin-bottom: 5px; display: block; color: var(--gray); }
    .filter-group input, .filter-group select { width: 100%; padding: 8px 12px; border: 2px solid var(--border); border-radius: 8px; }
    .search-group { flex: 2; }
    .button-group { flex: 0.5; min-width: 180px; }
    .action-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; text-decoration: none; transition: all 0.2s; font-size: 14px; }
    .icon-edit { background: #f59e0b; color: white; }
    .icon-delete { background: #ef4444; color: white; }
    .action-icon:hover { transform: scale(1.05); opacity: 0.9; }
    @media (max-width: 768px) {
        .filter-form { flex-direction: column; }
        .filter-group { width: 100%; }
        .button-group { width: 100%; }
        .button-group div { flex-direction: column; }
        .btn { width: 100%; justify-content: center; }
        .action-buttons { justify-content: center; }
    }
</style>

<?php include_once '../includes/footer.php'; ?>
