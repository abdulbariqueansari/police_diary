<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireLogin();

$page_title = 'Settings';
$user = getCurrentUser($conn);

if (!$user) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

$message = '';
$error = '';

// Handle language change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_language'])) {
    $language = $_POST['language'];
    setUserSetting($conn, $user['id'], 'language', $language);
    $message = "Language saved successfully!";
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $check_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $check_stmt->bind_param("i", $user['id']);
    $check_stmt->execute();
    $user_data = $check_stmt->get_result()->fetch_assoc();
    
    if (password_verify($current_password, $user_data['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $conn->query("UPDATE users SET password = '$hashed' WHERE id = " . $user['id']);
                $message = "Password changed successfully!";
            } else {
                $error = "Password must be at least 6 characters!";
            }
        } else {
            $error = "Passwords do not match!";
        }
    } else {
        $error = "Current password is incorrect!";
    }
}

// Handle serial reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_serial'])) {
    resetUserSerialCounter($conn, $user['id']);
    $message = "Serial counter reset to 0000!";
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    setUserSetting($conn, $user['id'], 'officer_name', $_POST['officer_name']);
    setUserSetting($conn, $user['id'], 'police_station', $_POST['police_station']);
    setUserSetting($conn, $user['id'], 'district', $_POST['district']);
    setUserSetting($conn, $user['id'], 'signature_designation', $_POST['signature_designation']);
    $message = "Profile updated successfully!";
}

$officer_name = getUserSetting($conn, $user['id'], 'officer_name', $user['full_name']);
$police_station = getUserSetting($conn, $user['id'], 'police_station', '');
$district = getUserSetting($conn, $user['id'], 'district', '');
$signature = getUserSetting($conn, $user['id'], 'signature_designation', $user['full_name']);
$serial_counter = getUserSerialCounter($conn, $user['id']);
$language = getUserSetting($conn, $user['id'], 'language', 'en');

include_once '../includes/header.php';
?>

<style>
    .settings-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 25px;
        border-bottom: 2px solid var(--border);
        flex-wrap: wrap;
    }
    .tab-btn {
        padding: 10px 20px;
        background: none;
        border: none;
        cursor: pointer;
        font-weight: 600;
        color: var(--gray);
        transition: all 0.3s;
        border-radius: 8px 8px 0 0;
    }
    .tab-btn.active {
        color: var(--primary);
        border-bottom: 2px solid var(--primary);
        background: rgba(102,126,234,0.1);
    }
    .tab-pane {
        display: none;
        animation: fadeIn 0.3s ease;
    }
    .tab-pane.active {
        display: block;
    }
    .serial-box {
        background: linear-gradient(135deg, #f0f2f5 0%, #e5e7eb 100%);
        padding: 20px;
        border-radius: 16px;
        text-align: center;
        margin-bottom: 20px;
    }
    .serial-number {
        font-size: 48px;
        font-weight: 800;
        color: var(--primary);
        letter-spacing: 8px;
        font-family: monospace;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .btn-block {
        width: 100%;
        justify-content: center;
    }
</style>

<div class="card">
    <div class="settings-tabs">
        <button class="tab-btn active" onclick="showTab('profile')"><i class="fas fa-user"></i> Profile</button>
        <button class="tab-btn" onclick="showTab('language')"><i class="fas fa-language"></i> Language</button>
        <button class="tab-btn" onclick="showTab('security')"><i class="fas fa-lock"></i> Security</button>
        <button class="tab-btn" onclick="showTab('serial')"><i class="fas fa-hashtag"></i> Serial Counter</button>
    </div>
    
    <!-- Profile Tab -->
    <div id="profileTab" class="tab-pane active">
        <h3><i class="fas fa-user-cog"></i> Profile Information</h3>
        <form method="POST">
            <input type="hidden" name="update_profile" value="1">
            <div class="form-group">
                <label>Officer Name</label>
                <input type="text" name="officer_name" value="<?php echo htmlspecialchars($officer_name); ?>" required>
            </div>
            <div class="form-group">
                <label>Police Station</label>
                <input type="text" name="police_station" value="<?php echo htmlspecialchars($police_station); ?>" required>
            </div>
            <div class="form-group">
                <label>District</label>
                <input type="text" name="district" value="<?php echo htmlspecialchars($district); ?>" required>
            </div>
            <div class="form-group">
                <label>Signature Designation</label>
                <input type="text" name="signature_designation" value="<?php echo htmlspecialchars($signature); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Update Profile</button>
        </form>
    </div>
    
    <!-- Language Tab -->
    <div id="languageTab" class="tab-pane">
        <h3><i class="fas fa-language"></i> Language Settings</h3>
        <form method="POST">
            <input type="hidden" name="update_language" value="1">
            <div class="form-group">
                <label>Select Language</label>
                <select name="language" class="form-control">
                    <option value="en" <?php echo $language == 'en' ? 'selected' : ''; ?>>English</option>
                    <option value="hi" <?php echo $language == 'hi' ? 'selected' : ''; ?>>हिंदी (Hindi)</option>
                    <option value="bn" <?php echo $language == 'bn' ? 'selected' : ''; ?>>বাংলা (Bengali)</option>
                    <option value="ta" <?php echo $language == 'ta' ? 'selected' : ''; ?>>தமிழ் (Tamil)</option>
                    <option value="te" <?php echo $language == 'te' ? 'selected' : ''; ?>>తెలుగు (Telugu)</option>
                    <option value="mr" <?php echo $language == 'mr' ? 'selected' : ''; ?>>मराठी (Marathi)</option>
                    <option value="gu" <?php echo $language == 'gu' ? 'selected' : ''; ?>>ગુજરાતી (Gujarati)</option>
                    <option value="kn" <?php echo $language == 'kn' ? 'selected' : ''; ?>>ಕನ್ನಡ (Kannada)</option>
                    <option value="ml" <?php echo $language == 'ml' ? 'selected' : ''; ?>>മലയാളം (Malayalam)</option>
                    <option value="pa" <?php echo $language == 'pa' ? 'selected' : ''; ?>>ਪੰਜਾਬੀ (Punjabi)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Save Language</button>
        </form>
    </div>
    
    <!-- Security Tab -->
    <div id="securityTab" class="tab-pane">
        <h3><i class="fas fa-key"></i> Change Password</h3>
        <form method="POST">
            <input type="hidden" name="change_password" value="1">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required>
                <small>Password must be at least 6 characters</small>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Change Password</button>
        </form>
    </div>
    
    <!-- Serial Counter Tab -->
    <div id="serialTab" class="tab-pane">
        <h3><i class="fas fa-hashtag"></i> Serial Counter Management</h3>
        <div class="serial-box">
            <p style="color: var(--gray); margin-bottom: 10px;">Current Serial Counter Value</p>
            <div class="serial-number"><?php echo str_pad($serial_counter, 4, '0', STR_PAD_LEFT); ?></div>
            <p style="font-size: 12px; margin-top: 10px;">This number increments each time you generate a PDF</p>
        </div>
        <form method="POST">
            <input type="hidden" name="reset_serial" value="1">
            <button type="submit" class="btn btn-warning btn-block" onclick="return confirm('Reset your serial counter to 0000? This action cannot be undone!')">
                <i class="fas fa-sync-alt"></i> Reset Serial Counter to 0000
            </button>
        </form>
    </div>
</div>

<div class="card" style="margin-top: 20px;">
    <h3><i class="fas fa-info-circle"></i> Account Information</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <div>
            <p style="color: var(--gray);">Username</p>
            <p><strong><?php echo htmlspecialchars($user['username']); ?></strong></p>
        </div>
        <div>
            <p style="color: var(--gray);">Full Name</p>
            <p><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></p>
        </div>
        <div>
            <p style="color: var(--gray);">Role</p>
            <p><strong><span class="user-badge-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></strong></p>
        </div>
        <div>
            <p style="color: var(--gray);">Member Since</p>
            <p><strong><?php echo date('d-m-Y', strtotime($user['created_at'])); ?></strong></p>
        </div>
        <div>
            <p style="color: var(--gray);">Last Login</p>
            <p><strong><?php echo $user['last_login'] ? date('d-m-Y H:i:s', strtotime($user['last_login'])) : 'Never'; ?></strong></p>
        </div>
    </div>
</div>

<script>
function showTab(tab) {
    // Hide all tabs
    document.getElementById('profileTab').classList.remove('active');
    document.getElementById('languageTab').classList.remove('active');
    document.getElementById('securityTab').classList.remove('active');
    document.getElementById('serialTab').classList.remove('active');
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    
    // Show selected tab
    if (tab === 'profile') {
        document.getElementById('profileTab').classList.add('active');
        document.querySelector('.tab-btn:first-child').classList.add('active');
    } else if (tab === 'language') {
        document.getElementById('languageTab').classList.add('active');
        document.querySelector('.tab-btn:nth-child(2)').classList.add('active');
    } else if (tab === 'security') {
        document.getElementById('securityTab').classList.add('active');
        document.querySelector('.tab-btn:nth-child(3)').classList.add('active');
    } else if (tab === 'serial') {
        document.getElementById('serialTab').classList.add('active');
        document.querySelector('.tab-btn:nth-child(4)').classList.add('active');
    }
}
</script>

<?php include_once '../includes/footer.php'; ?>