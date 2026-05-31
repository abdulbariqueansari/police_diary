<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireAdmin();

$page_title = 'System Settings';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_settings'])) {
        $system_name = $conn->real_escape_string($_POST['system_name']);
        $conn->query("UPDATE app_settings SET setting_value = '$system_name' WHERE setting_key = 'system_name'");
        $success = "System settings updated successfully!";
    }
}

$system_name = $conn->query("SELECT setting_value FROM app_settings WHERE setting_key = 'system_name'")->fetch_assoc()['setting_value'];

include_once '../includes/header.php';
?>

<div class="card">
    <h3><i class="fas fa-cog"></i> General Settings</h3>
    <?php if(isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label><i class="fas fa-globe"></i> System Name</label>
            <input type="text" name="system_name" value="<?php echo htmlspecialchars($system_name); ?>">
            <small>This name appears throughout the system</small>
        </div>
        <button type="submit" name="update_settings" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
    </form>
</div>

<div class="card">
    <h3><i class="fas fa-info-circle"></i> System Information</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
        <div><p style="color: var(--gray);">PHP Version</p><p><strong><?php echo phpversion(); ?></strong></p></div>
        <div><p style="color: var(--gray);">Database</p><p><strong>MySQL / MariaDB</strong></p></div>
        <div><p style="color: var(--gray);">Server Time</p><p><strong><?php echo date('d-m-Y h:i A'); ?></strong></p></div>
        <div><p style="color: var(--gray);">System Version</p><p><strong>v2.0.0</strong></p></div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>