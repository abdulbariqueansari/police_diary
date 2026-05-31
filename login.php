<?php
// No output before this line
ob_start();
require_once 'config.php';

// Function to extend remember me token
function extendRememberMeToken($conn, $user_id) {
    $new_token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
    $stmt = $conn->prepare("UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?");
    $stmt->bind_param("ssi", $new_token, $expiry, $user_id);
    $stmt->execute();
    setcookie('remember_token', $new_token, time() + (86400 * 30), "/", "", false, true);
    return true;
}

// ============ CRITICAL: Cookie Check BEFORE any session start ============
// Check if user is already logged in via session
$is_logged_in = false;

// First check session (if already logged in)
if (session_status() === PHP_SESSION_ACTIVE) {
    if (isset($_SESSION['user_id'])) {
        $is_logged_in = true;
    }
} else {
    // Start session only if not started
    session_start();
    if (isset($_SESSION['user_id'])) {
        $is_logged_in = true;
    }
}

// If already logged in via session, redirect
if ($is_logged_in) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit;
}

// ============ COOKIE CHECK (Auto-login) ============
// Check remember me cookie ONLY if not already logged in
if (isset($_COOKIE['remember_token']) && !isset($_SESSION['user_id'])) {
    $token = $_COOKIE['remember_token'];
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE remember_token = ? AND token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        // Extend token for another 30 days
        extendRememberMeToken($conn, $user['id']);
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        
        // Update last login
        $conn->query("UPDATE users SET last_login = NOW() WHERE id = " . $user['id']);
        
        // Redirect based on role
        if ($user['role'] == 'admin') {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: user/dashboard.php');
        }
        exit;
    } else {
        // Invalid token - clear cookie
        setcookie('remember_token', '', time() - 3600, "/");
    }
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']) ? true : false;
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if ($user['status'] == 'inactive') {
            $error = "Your account is not approved yet! Please wait for admin approval.";
        } elseif (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            $conn->query("UPDATE users SET last_login = NOW() WHERE id = " . $user['id']);
            
            if ($remember_me) {
                extendRememberMeToken($conn, $user['id']);
            }
            
            if ($user['role'] == 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: user/dashboard.php');
            }
            exit;
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "Username not found!";
    }
}

// Handle account request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_account'])) {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $police_station = trim($_POST['police_station']);
    $district = trim($_POST['district']);
    $message = trim($_POST['message']);
    
    $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
    if ($check->num_rows > 0) {
        $error = "Username already exists!";
    } else {
        $check_req = $conn->query("SELECT id FROM account_requests WHERE username = '$username' AND status = 'pending'");
        if ($check_req->num_rows > 0) {
            $error = "You already have a pending request!";
        } else {
            $stmt = $conn->prepare("INSERT INTO account_requests (username, full_name, police_station, district, message, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("sssss", $username, $full_name, $police_station, $district, $message);
            if ($stmt->execute()) {
                $success = "Account request submitted successfully!";
            } else {
                $error = "Failed to submit request.";
            }
        }
    }
}

// Handle forgot password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['forgot_password'])) {
    $username = trim($_POST['fp_username']);
    
    $stmt = $conn->prepare("SELECT id, status FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if ($user['status'] == 'inactive') {
            $error = "Your account is not approved yet!";
        } else {
            $new_password = 'password123';
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password = '$hashed' WHERE id = " . $user['id']);
            $success = "Password reset successfully! New password: password123";
        }
    } else {
        $error = "Username not found!";
    }
}

// Handle account request status check
if (isset($_GET['check_status'])) {
    $username = $_GET['username'] ?? '';
    $stmt = $conn->prepare("SELECT status FROM account_requests WHERE username = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($req = $result->fetch_assoc()) {
        echo json_encode(['status' => $req['status']]);
    } else {
        echo json_encode(['status' => 'none']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Police Diary System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .login-container { width: 100%; max-width: 500px; }
        .login-card {
            background: white;
            border-radius: 32px;
            padding: 40px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo i { font-size: 70px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .logo h1 { font-size: 28px; margin-top: 12px; color: #1e3c72; }
        .logo p { color: #6b7280; font-size: 14px; margin-top: 5px; }
        .input-group { position: relative; margin-bottom: 20px; }
        .input-group i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #667eea; font-size: 18px; }
        input, textarea {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }
        textarea { padding-left: 48px; resize: vertical; min-height: 80px; }
        input:focus, textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 4px rgba(102,126,234,0.1); }
        .checkbox-group { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
        .checkbox-group input { width: auto; padding: 0; margin: 0; }
        .checkbox-group label { color: #6b7280; font-size: 14px; cursor: pointer; }
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102,126,234,0.4); }
        .btn-outline { background: transparent; border: 2px solid #667eea; color: #667eea; margin-top: 10px; }
        .btn-outline:hover { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .error { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .success { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .divider { text-align: center; margin: 25px 0; position: relative; }
        .divider::before { content: ''; position: absolute; left: 0; top: 50%; width: 100%; height: 1px; background: #e5e7eb; }
        .divider span { background: white; padding: 0 15px; position: relative; color: #6b7280; font-size: 13px; }
        .toggle-btn { text-align: center; margin-top: 20px; }
        .toggle-btn a { color: #667eea; text-decoration: none; font-weight: 600; cursor: pointer; }
        .footer { text-align: center; margin-top: 25px; font-size: 12px; color: #999; }
        .status-check { font-size: 12px; margin-top: 8px; display: flex; align-items: center; gap: 8px; }
        .username-available { color: #10b981; }
        .username-taken { color: #ef4444; }
        .spinner-small { width: 16px; height: 16px; border: 2px solid #e5e7eb; border-top-color: #667eea; border-radius: 50%; animation: spin 1s linear infinite; display: inline-block; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .info-text { font-size: 12px; color: #6b7280; margin-top: 10px; text-align: center; }
        .button-group { display: flex; gap: 10px; margin-top: 10px; }
        .button-group .btn { flex: 1; margin-top: 0; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <i class="fas fa-user-shield"></i>
                <h1>Police Diary System</h1>
                <p>Secure Digital Police Diary Management</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div id="loginForm">
                <form method="POST" action="">
                    <input type="hidden" name="login" value="1">
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="Username" required autofocus>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" name="remember_me" id="remember_me">
                        <label for="remember_me"><i class="fas fa-check-circle"></i> Keep me logged in (30 days)</label>
                    </div>
                    <button type="submit" class="btn"><i class="fas fa-sign-in-alt"></i> Login</button>
                </form>
                <div class="button-group">
                    <button onclick="showForgotPassword()" class="btn btn-outline"><i class="fas fa-key"></i> Forgot Password?</button>
                    <button onclick="checkRequestStatus()" class="btn btn-outline"><i class="fas fa-question-circle"></i> Check Request Status</button>
                </div>
                <div class="divider"><span>OR</span></div>
                <div class="toggle-btn">
                    <a onclick="showRequestForm()"><i class="fas fa-user-plus"></i> Request New Account →</a>
                </div>
            </div>
            
            <div id="requestForm" style="display: none;">
                <form method="POST" action="">
                    <input type="hidden" name="request_account" value="1">
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" id="req_username" placeholder="Desired Username" required>
                        <div id="usernameStatus" class="status-check"></div>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-user-circle"></i>
                        <input type="text" name="full_name" placeholder="Full Name" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-building"></i>
                        <input type="text" name="police_station" placeholder="Police Station" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" name="district" placeholder="District" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-comment"></i>
                        <textarea name="message" placeholder="Any additional information..."></textarea>
                    </div>
                    <button type="submit" class="btn">Submit Request</button>
                </form>
                <div class="toggle-btn">
                    <a onclick="showLoginForm()">← Back to Login</a>
                </div>
            </div>
            
            <div id="forgotPasswordForm" style="display: none;">
                <form method="POST" action="">
                    <input type="hidden" name="forgot_password" value="1">
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="fp_username" placeholder="Enter your username" required>
                    </div>
                    <button type="submit" class="btn"><i class="fas fa-sync-alt"></i> Reset Password</button>
                </form>
                <div class="info-text">
                    <i class="fas fa-info-circle"></i> Password will be reset to: <strong>password123</strong>
                </div>
                <div class="toggle-btn">
                    <a onclick="showLoginForm()">← Back to Login</a>
                </div>
            </div>
            
            <div id="statusCheckForm" style="display: none;">
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="status_username" placeholder="Enter your username">
                </div>
                <button onclick="checkStatus()" class="btn"><i class="fas fa-search"></i> Check Status</button>
                <div id="statusResult" style="margin-top: 15px; display: none;"></div>
                <div class="toggle-btn">
                    <a onclick="showLoginForm()">← Back to Login</a>
                </div>
            </div>
            
            <div class="footer">
                <p><i class="fas fa-code"></i> Created and maintained by <strong>Abdul Barique Ansari</strong></p>
            </div>
        </div>
    </div>
    
    <script>
        let usernameTimeout;
        
        function showLoginForm() {
            document.getElementById('loginForm').style.display = 'block';
            document.getElementById('requestForm').style.display = 'none';
            document.getElementById('forgotPasswordForm').style.display = 'none';
            document.getElementById('statusCheckForm').style.display = 'none';
        }
        
        function showRequestForm() {
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('requestForm').style.display = 'block';
            document.getElementById('forgotPasswordForm').style.display = 'none';
            document.getElementById('statusCheckForm').style.display = 'none';
        }
        
        function showForgotPassword() {
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('requestForm').style.display = 'none';
            document.getElementById('forgotPasswordForm').style.display = 'block';
            document.getElementById('statusCheckForm').style.display = 'none';
        }
        
        function checkRequestStatus() {
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('requestForm').style.display = 'none';
            document.getElementById('forgotPasswordForm').style.display = 'none';
            document.getElementById('statusCheckForm').style.display = 'block';
            document.getElementById('statusResult').style.display = 'none';
        }
        
        function checkStatus() {
            const username = document.getElementById('status_username').value.trim();
            const resultDiv = document.getElementById('statusResult');
            
            if (username.length < 3) {
                resultDiv.innerHTML = '<div class="error" style="margin-top: 10px;">Please enter a valid username</div>';
                resultDiv.style.display = 'block';
                return;
            }
            
            resultDiv.innerHTML = '<div class="info-text"><span class="spinner-small"></span> Checking...</div>';
            resultDiv.style.display = 'block';
            
            fetch(`login.php?check_status=1&username=${encodeURIComponent(username)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'pending') {
                        resultDiv.innerHTML = '<div class="info-text" style="background: #fef3c7; color: #92400e; padding: 12px; border-radius: 12px;"><i class="fas fa-clock"></i> Your request is pending. Please wait for admin approval.</div>';
                    } else if (data.status === 'approved') {
                        resultDiv.innerHTML = '<div class="success"><i class="fas fa-check-circle"></i> Your request has been approved! You can now login.</div>';
                    } else if (data.status === 'rejected') {
                        resultDiv.innerHTML = '<div class="error"><i class="fas fa-times-circle"></i> Your request has been rejected. Please contact admin.</div>';
                    } else {
                        resultDiv.innerHTML = '<div class="error"><i class="fas fa-search"></i> No request found with this username.</div>';
                    }
                })
                .catch(() => {
                    resultDiv.innerHTML = '<div class="error">Error checking status. Please try again.</div>';
                });
        }
        
        const usernameInput = document.getElementById('req_username');
        if (usernameInput) {
            usernameInput.addEventListener('input', function() {
                const username = this.value.trim();
                const statusDiv = document.getElementById('usernameStatus');
                
                if (username.length < 3) {
                    statusDiv.innerHTML = '<span style="color: #6b7280;"><i class="fas fa-info-circle"></i> Username must be at least 3 characters</span>';
                    return;
                }
                
                statusDiv.innerHTML = '<span class="spinner-small"></span> Checking availability...';
                
                clearTimeout(usernameTimeout);
                usernameTimeout = setTimeout(function() {
                    fetch(`check_username.php?username=${encodeURIComponent(username)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.exists) {
                                statusDiv.innerHTML = '<span class="username-taken"><i class="fas fa-times-circle"></i> Username already taken!</span>';
                            } else {
                                statusDiv.innerHTML = '<span class="username-available"><i class="fas fa-check-circle"></i> Username available!</span>';
                            }
                        })
                        .catch(() => {
                            statusDiv.innerHTML = '<span style="color: #f59e0b;"><i class="fas fa-exclamation-triangle"></i> Error checking</span>';
                        });
                }, 500);
            });
        }
    </script>
</body>
</html>