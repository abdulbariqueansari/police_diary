<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireAdmin();

$page_title = 'Admin Dashboard';
$user = getCurrentUser($conn);

// Check for mode switching
if (isset($_GET['mode'])) {
    if ($_GET['mode'] == 'user' && $_SESSION['role'] == 'admin') {
        $_SESSION['admin_mode'] = true;
        header('Location: ../user/dashboard.php');
        exit;
    } elseif ($_GET['mode'] == 'admin' && $_SESSION['role'] == 'admin') {
        $_SESSION['admin_mode'] = false;
    }
}

// If admin is in user mode, redirect to user dashboard
if (isset($_SESSION['admin_mode']) && $_SESSION['admin_mode'] === true) {
    header('Location: ../user/dashboard.php');
    exit;
}


$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
$totalEntries = $conn->query("SELECT COUNT(*) as count FROM diary_entries")->fetch_assoc()['count'];
$totalThisMonth = $conn->query("SELECT COUNT(*) as count FROM diary_entries WHERE MONTH(entry_date) = MONTH(CURDATE())")->fetch_assoc()['count'];
$totalToday = $conn->query("SELECT COUNT(*) as count FROM diary_entries WHERE entry_date = CURDATE()")->fetch_assoc()['count'];
$pendingRequests = $conn->query("SELECT COUNT(*) as count FROM account_requests WHERE status = 'pending'")->fetch_assoc()['count'];
$inactiveUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'inactive' AND role = 'user'")->fetch_assoc()['count'];

$oneYearAgo = date('Y-m-d H:i:s', strtotime('-1 year'));
$longInactiveUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user' AND (last_login IS NULL OR last_login < '$oneYearAgo')")->fetch_assoc()['count'];

include_once '../includes/header.php';
?>

<div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-bottom: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <div>
            <h2 style="font-size: 28px; margin-bottom: 8px;">Welcome, Admin!</h2>
            <p><i class="fas fa-calendar"></i> <?php echo date('l, d F Y'); ?> | <i class="fas fa-clock"></i> <?php echo date('h:i A'); ?></p>
        </div>
        <div style="text-align: right;">
            <p><i class="fas fa-chart-line"></i> System Active</p>
            <p><i class="fas fa-version"></i> v2.0.0</p>
        </div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3><?php echo $totalUsers; ?></h3>
            <p>Active Users</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
        <div class="stat-info">
            <h3><?php echo $inactiveUsers; ?></h3>
            <p>Inactive Users</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-info">
            <h3><?php echo $longInactiveUsers; ?></h3>
            <p>Inactive >1 Year</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
        <div class="stat-info">
            <h3><?php echo $totalEntries; ?></h3>
            <p>Total Entries</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-calendar-week"></i></div>
        <div class="stat-info">
            <h3><?php echo $totalThisMonth; ?></h3>
            <p>This Month</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
        <div class="stat-info">
            <h3><?php echo $totalToday; ?></h3>
            <p>Today</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
        <div class="stat-info">
            <h3><?php echo $pendingRequests; ?></h3>
            <p>Pending Requests</p>
        </div>
    </div>
</div>

<div class="card">
    <h3><i class="fas fa-rocket"></i> Quick Actions</h3>
    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
        <a href="requests.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> View Requests <?php if($pendingRequests > 0): ?><span class="badge"><?php echo $pendingRequests; ?></span><?php endif; ?></a>
        <a href="users.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add New User</a>
        <a href="logs.php" class="btn btn-primary"><i class="fas fa-eye"></i> View All Logs</a>
        <a href="../preview_pdf.php" class="btn btn-primary" target="_blank"><i class="fas fa-file-pdf"></i> Generate Report</a>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
