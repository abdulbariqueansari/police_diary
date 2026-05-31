<?php
require_once 'config.php';

// Drop existing tables to recreate
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("DROP TABLE IF EXISTS diary_entries");
$conn->query("DROP TABLE IF EXISTS user_settings");
$conn->query("DROP TABLE IF EXISTS account_requests");
$conn->query("DROP TABLE IF EXISTS exported_documents");
$conn->query("DROP TABLE IF EXISTS users");
$conn->query("DROP TABLE IF EXISTS app_settings");
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// Create users table
$conn->query("CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(200) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'inactive',
    serial_counter INT DEFAULT 0,
    remember_token VARCHAR(255) NULL,
    token_expiry DATETIME NULL,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create diary_entries table
$conn->query("CREATE TABLE diary_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    entry_date DATE NOT NULL,
    entry_time TIME NOT NULL,
    report TEXT NOT NULL,
    orders_remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Create exported_documents table
$conn->query("CREATE TABLE exported_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tour_description VARCHAR(500) NOT NULL,
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    serial_from INT NOT NULL,
    serial_to INT NOT NULL,
    total_pages INT NOT NULL,
    total_entries INT NOT NULL,
    exported_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pdf_content LONGTEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Create app_settings table
$conn->query("CREATE TABLE app_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT
)");

// Create user_settings table
$conn->query("CREATE TABLE user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY(user_id, setting_key)
)");

// Create account_requests table
$conn->query("CREATE TABLE account_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    full_name VARCHAR(200) NOT NULL,
    police_station VARCHAR(200) NOT NULL,
    district VARCHAR(100) NOT NULL,
    message TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Insert default admin user
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$conn->query("INSERT INTO users (username, password, full_name, role, status, serial_counter, last_login) 
    VALUES ('admin', '$admin_password', 'Admin', 'admin', 'active', 0, NOW())");

// Insert default system settings
$conn->query("INSERT INTO app_settings (setting_key, setting_value) VALUES 
    ('system_name', 'Police Diary Management System')");

// Get admin ID
$admin_id = $conn->insert_id;

// Insert default user settings for admin
$conn->query("INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES 
    ($admin_id, 'officer_name', 'Admin'),
    ($admin_id, 'police_station', 'Police Headquarters'),
    ($admin_id, 'district', 'Headquarters'),
    ($admin_id, 'signature_designation', 'Admin'),
    ($admin_id, 'language', 'en')");

echo "<!DOCTYPE html>
<html>
<head>
    <title>Installation Complete</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }
        .card {
            background: white;
            border-radius: 32px;
            padding: 50px;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            animation: fadeIn 0.5s ease-out;
            max-width: 550px;
            width: 90%;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .success-icon { font-size: 80px; color: #10b981; margin-bottom: 20px; }
        h1 { font-size: 28px; color: #1e3c72; margin-bottom: 10px; }
        p { color: #6b7280; margin-bottom: 30px; line-height: 1.6; }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 35px;
            text-decoration: none;
            border-radius: 30px;
            display: inline-block;
            transition: all 0.3s;
            font-weight: 600;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102,126,234,0.4); }
        .info {
            background: linear-gradient(135deg, #f0f2f5 0%, #e5e7eb 100%);
            padding: 20px;
            border-radius: 20px;
            margin-top: 25px;
            text-align: left;
        }
        .info strong { color: #1e3c72; display: block; margin-bottom: 10px; }
        .info p { margin-bottom: 5px; font-size: 13px; }
        .checklist { text-align: left; margin-top: 20px; }
        .checklist li { color: #10b981; margin-bottom: 8px; list-style: none; display: flex; align-items: center; gap: 10px; }
    </style>
    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css\">
</head>
<body>
    <div class='card'>
        <div class='success-icon'><i class=\"fas fa-check-circle\"></i></div>
        <h1>Installation Successful!</h1>
        <p>Police Diary Management System is ready to use.</p>
        
        <div class=\"checklist\">
            <li><i class=\"fas fa-check-circle\"></i> Users table created</li>
            <li><i class=\"fas fa-check-circle\"></i> Diary entries table created</li>
            <li><i class=\"fas fa-check-circle\"></i> Exported documents table created</li>
            <li><i class=\"fas fa-check-circle\"></i> Settings tables created</li>
            <li><i class=\"fas fa-check-circle\"></i> Account requests table created</li>
            <li><i class=\"fas fa-check-circle\"></i> Admin user created</li>
        </div>
        
        <a href='login.php' class='btn'><i class=\"fas fa-sign-in-alt\"></i> Go to Login</a>
        
        <div class='info'>
            <strong><i class=\"fas fa-info-circle\"></i> Login Information:</strong>
            <p><i class=\"fas fa-user\"></i> Username: <strong>admin</strong></p>
            <p><i class=\"fas fa-lock\"></i> Password: <strong>admin123</strong></p>
            <hr style=\"margin: 10px 0; border-color: #d1d5db;\">
            <p><i class=\"fas fa-file-export\"></i> Exported documents are saved and can be re-downloaded</p>
            <p><i class=\"fas fa-microphone\"></i> Voice typing supports multiple languages</p>
            <p><i class=\"fas fa-sync-alt\"></i> Serial numbers cycle from 0000 to 9999</p>
        </div>
    </div>
</body>
</html>";
?>