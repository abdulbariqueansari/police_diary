<?php
$current_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? 'user';
// Check if admin is in user mode (from session flag)
$is_admin_mode = isset($_SESSION['admin_mode']) && $_SESSION['admin_mode'] === true;
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-book"></i> Police Diary</h3>
        <button class="close-sidebar" onclick="toggleSidebar()"><i class="fas fa-times"></i></button>
    </div>
    <nav>
        <?php if($user_role == 'admin' && !$is_admin_mode): ?>
            <!-- Admin in Admin Mode - Full Admin Menu -->
            <a href="../admin/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> <span>Dashboard</span>
            </a>
            <a href="../admin/requests.php" class="<?php echo $current_page == 'requests.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-plus"></i> <span>Account Requests</span>
            </a>
            <a href="../admin/users.php" class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> <span>Manage Users</span>
            </a>
            <a href="../admin/settings.php" class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> <span>Settings</span>
            </a>
            <a href="../admin/logs.php" class="<?php echo $current_page == 'logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-eye"></i> <span>All Logs</span>
            </a>
            <a href="../user/dashboard.php?mode=user">
                <i class="fas fa-user"></i> <span>Switch to User Mode</span>
            </a>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        <?php elseif($user_role == 'admin' && $is_admin_mode): ?>
            <!-- Admin in User Mode - Shows User Menu + Admin Mode Button -->
            <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> <span>Dashboard</span>
            </a>
            <a href="view_logs.php" class="<?php echo $current_page == 'view_logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-eye"></i> <span>View Logs</span>
            </a>
            <a href="exported_docs.php" class="<?php echo $current_page == 'exported_docs.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-export"></i> <span>Exported Files</span>
            </a>
            <a href="settings.php" class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> <span>Settings</span>
            </a>
            <a href="../admin/dashboard.php?mode=admin">
                <i class="fas fa-shield-alt"></i> <span>Switch to Admin Mode</span>
            </a>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        <?php else: ?>
            <!-- Regular User Menu -->
            <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> <span>Dashboard</span>
            </a>
            <a href="view_logs.php" class="<?php echo $current_page == 'view_logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-eye"></i> <span>View Logs</span>
            </a>
            <a href="exported_docs.php" class="<?php echo $current_page == 'exported_docs.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-export"></i> <span>Exported Files</span>
            </a>
            <a href="settings.php" class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> <span>Settings</span>
            </a>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        <?php endif; ?>
    </nav>
</div>