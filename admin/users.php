<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireAdmin();

$page_title = 'Manage Users';

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);
    
    $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
    if ($check->num_rows > 0) {
        $error = "Username already exists!";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role, status, serial_counter) VALUES (?, ?, ?, 'user', 'inactive', 0)");
        $stmt->bind_param("sss", $username, $hashed, $full_name);
        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            setUserSetting($conn, $user_id, 'officer_name', $full_name);
            $success = "User created! Username: $username, Password: $password";
        } else {
            $error = "Failed to create user.";
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM users WHERE id = $id AND role != 'admin'");
    $success = "User deleted!";
}

// Handle status toggle
if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    $user_check = $conn->query("SELECT status, role FROM users WHERE id = $id")->fetch_assoc();
    if ($user_check && $user_check['role'] != 'admin') {
        $new_status = ($user_check['status'] == 'active') ? 'inactive' : 'active';
        $conn->query("UPDATE users SET status = '$new_status' WHERE id = $id");
        $success = "User " . ($new_status == 'active' ? "activated" : "deactivated");
    }
}

$oneYearAgo = date('Y-m-d H:i:s', strtotime('-1 year'));
$users = $conn->query("SELECT *, 
    CASE WHEN last_login IS NULL OR last_login < '$oneYearAgo' THEN 1 ELSE 0 END as is_long_inactive,
    DATE_FORMAT(last_login, '%d-%m-%Y %h:%i %p') as formatted_last_login 
    FROM users ORDER BY FIELD(status, 'active', 'inactive'), role, username");
$total_users = $users->num_rows;

include_once '../includes/header.php';
?>

<div class="card">
    <h3><i class="fas fa-user-plus"></i> Create New User</h3>
    <form method="POST">
        <div class="form-grid">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="text" name="full_name" placeholder="Full Name" required>
        </div>
        <button type="submit" name="create_user" class="btn btn-primary">Create User</button>
    </form>
    <small style="color: var(--gray); margin-top: 10px; display: block;">
        <i class="fas fa-info-circle"></i> New users are created with 'User' role and 'Inactive' status.
    </small>
</div>

<div class="card">
    <h3><i class="fas fa-users"></i> All Users <span style="font-size: 14px; color: var(--gray);">(Total: <?php echo $total_users; ?> users)</span></h3>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Status</th>
                    <th>Serial</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($user = $users->fetch_assoc()): ?>
                <tr class="<?php echo ($user['is_long_inactive'] && $user['role'] != 'admin') ? 'long-inactive' : ''; ?>">
                    <td>
                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                        <br><small><?php echo htmlspecialchars($user['full_name']); ?></small>
                        <?php if($user['is_long_inactive'] && $user['role'] != 'admin'): ?>
                            <span class="warning-badge"><i class="fas fa-exclamation-triangle"></i> 1Y+</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-<?php echo $user['status']; ?>">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </td>
                    <td><span class="serial-badge"><?php echo str_pad($user['serial_counter'], 4, '0', STR_PAD_LEFT); ?></span>
                    <td><small><?php echo $user['formatted_last_login'] ?: 'Never'; ?></small>
                    <td class="action-icons" style="display: flex; gap: 5px; flex-wrap: wrap;">
                        <?php if($user['role'] != 'admin'): ?>
                            <?php if($user['status'] == 'active'): ?>
                                <a href="?toggle_status=<?php echo $user['id']; ?>" class="action-icon icon-deactivate" title="Deactivate User" onclick="return confirm('Deactivate <?php echo htmlspecialchars($user['username']); ?>?')">
                                    <i class="fas fa-ban"></i>
                                </a>
                            <?php else: ?>
                                <a href="?toggle_status=<?php echo $user['id']; ?>" class="action-icon icon-activate" title="Activate User" onclick="return confirm('Activate <?php echo htmlspecialchars($user['username']); ?>?')">
                                    <i class="fas fa-check"></i>
                                </a>
                            <?php endif; ?>
                            <a href="?delete=<?php echo $user['id']; ?>" class="action-icon icon-delete" title="Delete User" onclick="return confirm('Delete <?php echo htmlspecialchars($user['username']); ?>?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        <?php else: ?>
                            <span class="badge protected">Protected</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .status-active { background: #10b981; color: white; padding: 4px 12px; border-radius: 20px; font-size: 11px; display: inline-block; }
    .status-inactive { background: #ef4444; color: white; padding: 4px 12px; border-radius: 20px; font-size: 11px; display: inline-block; }
    .serial-badge { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-family: monospace; display: inline-block; }
    .warning-badge { background: #f59e0b; color: white; padding: 2px 6px; border-radius: 10px; font-size: 9px; margin-left: 5px; display: inline-block; }
    .long-inactive { background: #fffbeb; }
    .action-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; text-decoration: none; transition: all 0.2s; border: none; cursor: pointer; font-size: 14px; }
    .icon-activate { background: #10b981; color: white; }
    .icon-deactivate { background: #ef4444; color: white; }
    .icon-delete { background: #dc2626; color: white; }
    .action-icon:hover { transform: scale(1.05); opacity: 0.9; }
    .protected { background: #6b7280; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
</style>

<?php include_once '../includes/footer.php'; ?>
