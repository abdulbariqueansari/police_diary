<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireAdmin();

$page_title = 'All Logs';

$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');
$user_filter = $_GET['user_filter'] ?? '';
$search = $_GET['search'] ?? '';

$query = "SELECT de.*, u.username, u.full_name, DATE_FORMAT(de.entry_date, '%d-%m-%Y') as formatted_date, DATE_FORMAT(de.entry_time, '%h:%i %p') as formatted_time 
    FROM diary_entries de 
    JOIN users u ON de.user_id = u.id 
    WHERE de.entry_date BETWEEN ? AND ?";
$params = [$from_date, $to_date];
$types = "ss";

if (!empty($user_filter)) {
    $query .= " AND u.id = ?";
    $params[] = $user_filter;
    $types .= "i";
}

if (!empty($search)) {
    $query .= " AND (de.report LIKE ? OR de.orders_remarks LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

$query .= " ORDER BY de.entry_date DESC, de.entry_time DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$entries = $stmt->get_result();
$total_entries = $entries->num_rows;

$users_list = $conn->query("SELECT id, username, full_name FROM users ORDER BY username");

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
        <div class="filter-group">
            <label>User</label>
            <select name="user_filter">
                <option value="">All Users</option>
                <?php while($u = $users_list->fetch_assoc()): ?>
                    <option value="<?php echo $u['id']; ?>" <?php echo ($user_filter == $u['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['username']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="filter-group search-group">
            <label>Search</label>
            <input type="text" name="search" placeholder="Search in report/remarks..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="filter-group button-group">
            <label>&nbsp;</label>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply</button>
                <a href="logs.php" class="btn btn-secondary"><i class="fas fa-sync-alt"></i> Reset</a>
            </div>
        </div>
    </form>
</div>

<!-- Entries Card -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
        <h3><i class="fas fa-list-ul"></i> All Diary Entries (<?php echo $total_entries; ?> records)</h3>
    </div>
    
    <?php if($total_entries > 0): ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>User</th>
                    <th>Report</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $entries->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['formatted_date']; ?>
                    <td><?php echo $row['formatted_time']; ?>
                    <td><span class="user-badge-user"><?php echo htmlspecialchars($row['username']); ?></span>
                    <td style="max-width: 350px; word-wrap: break-word;"><?php echo nl2br(htmlspecialchars($row['report'])); ?>
                    <td style="max-width: 250px; word-wrap: break-word;"><?php echo nl2br(htmlspecialchars($row['orders_remarks'] ?: '-')); ?>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Export button below entries -->
    <div style="margin-top: 20px; text-align: right;">

        <a href="../preview_pdf.php?from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>" class="btn btn-success">
             <i class="fas fa-file-pdf"></i> Export to PDF
        </a>
    </div>
    
    <?php else: ?>
    <div style="text-align: center; padding: 50px;">
        <i class="fas fa-inbox" style="font-size: 48px; color: #ccc;"></i>
        <p>No entries found.</p>
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
    .user-badge-user { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 4px 12px; border-radius: 20px; font-size: 11px; display: inline-block; }
    @media (max-width: 768px) {
        .filter-form { flex-direction: column; }
        .filter-group { width: 100%; }
        .button-group { width: 100%; }
        .button-group div { flex-direction: column; }
        .btn { width: 100%; justify-content: center; }
    }
</style>

<?php include_once '../includes/footer.php'; ?>
