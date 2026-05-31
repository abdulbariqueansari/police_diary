<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireAdmin();

$page_title = 'Account Requests';

// Handle approve request (accept) - Check if username already exists
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $req = $conn->query("SELECT * FROM account_requests WHERE id = $id")->fetch_assoc();
    if ($req && $req['status'] == 'pending') {
        // Check if username already exists in users table
        $check = $conn->query("SELECT id FROM users WHERE username = '{$req['username']}'");
        if ($check->num_rows > 0) {
            $error = "Username '{$req['username']}' already exists in users! Cannot approve. Please reject this request.";
        } else {
            $password = 'password123';
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role, status, serial_counter, last_login) VALUES (?, ?, ?, 'user', 'active', 0, NOW())");
            $stmt->bind_param("sss", $req['username'], $hashed, $req['full_name']);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                setUserSetting($conn, $user_id, 'police_station', $req['police_station']);
                setUserSetting($conn, $user_id, 'district', $req['district']);
                setUserSetting($conn, $user_id, 'officer_name', $req['full_name']);
                setUserSetting($conn, $user_id, 'signature_designation', $req['full_name']);
                $conn->query("UPDATE account_requests SET status = 'approved' WHERE id = $id");
                $success = "User approved! Username: {$req['username']}, Password: password123";
            } else {
                $error = "Failed to create user.";
            }
        }
    }
}

// Handle reject request
if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    $conn->query("UPDATE account_requests SET status = 'rejected' WHERE id = $id");
    $success = "Request rejected!";
}

// Handle remove request (delete from list)
if (isset($_GET['remove'])) {
    $id = (int)$_GET['remove'];
    $conn->query("DELETE FROM account_requests WHERE id = $id");
    $success = "Request removed from list!";
}

// Get all requests
$requests = $conn->query("SELECT * FROM account_requests ORDER BY 
    CASE status 
        WHEN 'pending' THEN 1 
        WHEN 'approved' THEN 2 
        ELSE 3 
    END, created_at DESC");

include_once '../includes/header.php';
?>

<div class="card">
    <h3><i class="fas fa-user-clock"></i> Account Requests Management</h3>
    <p style="margin-bottom: 20px; color: var(--gray);">Review and manage user account requests. Approved users get password: <strong>password123</strong></p>
    
    <?php if($requests->num_rows > 0): ?>
        <?php while($req = $requests->fetch_assoc()): 
            $status_color = $req['status'] == 'pending' ? '#f59e0b' : ($req['status'] == 'approved' ? '#10b981' : '#ef4444');
            $bg_color = $req['status'] == 'pending' ? '#fffbeb' : ($req['status'] == 'approved' ? '#f0fdf4' : '#fef2f2');
        ?>
            <div class="request-card" style="background: <?php echo $bg_color; ?>; border-left: 4px solid <?php echo $status_color; ?>; border-radius: 16px; padding: 20px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <h4 style="color: #1e3c72;"><?php echo htmlspecialchars($req['full_name']); ?> (@<?php echo htmlspecialchars($req['username']); ?>)</h4>
                        <p style="font-size: 12px; color: #6b7280;"><i class="fas fa-calendar"></i> Requested: <?php echo date('d-m-Y h:i A', strtotime($req['created_at'])); ?></p>
                    </div>
                    <div>
                        <span style="background: <?php echo $status_color; ?>; color: white; padding: 4px 12px; border-radius: 20px; font-size: 11px;">
                            <?php echo ucfirst($req['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 10px;">
                    <div style="background: rgba(255,255,255,0.8); padding: 8px 12px; border-radius: 10px;">
                        <label style="font-size: 11px; color: #6b7280;">Police Station</label>
                        <p style="font-size: 14px; font-weight: 500;"><?php echo htmlspecialchars($req['police_station']); ?></p>
                    </div>
                    <div style="background: rgba(255,255,255,0.8); padding: 8px 12px; border-radius: 10px;">
                        <label style="font-size: 11px; color: #6b7280;">District</label>
                        <p style="font-size: 14px; font-weight: 500;"><?php echo htmlspecialchars($req['district']); ?></p>
                    </div>
                    <?php if(!empty($req['message'])): ?>
                    <div style="background: rgba(255,255,255,0.8); padding: 8px 12px; border-radius: 10px; grid-column: span 2;">
                        <label style="font-size: 11px; color: #6b7280;">Message</label>
                        <p style="font-size: 14px;"><?php echo nl2br(htmlspecialchars($req['message'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php if($req['status'] == 'pending'): ?>
                        <a href="?approve=<?php echo $req['id']; ?>" onclick="return confirm('Approve this request?')" class="btn btn-success"><i class="fas fa-check"></i> Accept</a>
                        <a href="?reject=<?php echo $req['id']; ?>" onclick="return confirm('Reject this request?')" class="btn btn-danger"><i class="fas fa-times"></i> Reject</a>
                    <?php else: ?>
                        <a href="?remove=<?php echo $req['id']; ?>" onclick="return confirm('Remove this request from list?')" class="btn btn-secondary"><i class="fas fa-trash"></i> Remove</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 50px;">
            <i class="fas fa-inbox" style="font-size: 48px; color: #ccc;"></i>
            <p>No account requests found.</p>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>