<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . '/user/dashboard.php');
        exit;
    }
}

function getCurrentUser($conn) {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getUserSetting($conn, $user_id, $key, $default = '') {
    $stmt = $conn->prepare("SELECT setting_value FROM user_settings WHERE user_id = ? AND setting_key = ?");
    $stmt->bind_param("is", $user_id, $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return $default;
}

function setUserSetting($conn, $user_id, $key, $value) {
    $stmt = $conn->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("isss", $user_id, $key, $value, $value);
    return $stmt->execute();
}

function getUserSerialCounter($conn, $user_id) {
    $stmt = $conn->prepare("SELECT serial_counter FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return (int)$row['serial_counter'];
    }
    return 0;
}

function updateUserSerialCounter($conn, $user_id, $new_counter) {
    $cycled_counter = $new_counter % 10000;
    $stmt = $conn->prepare("UPDATE users SET serial_counter = ? WHERE id = ?");
    $stmt->bind_param("ii", $cycled_counter, $user_id);
    return $stmt->execute();
}

function resetUserSerialCounter($conn, $user_id) {
    return updateUserSerialCounter($conn, $user_id, 0);
}

function updateLastLogin($conn, $user_id) {
    $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    return $stmt->execute();
}

function extendRememberMeToken($conn, $user_id) {
    $new_token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
    $stmt = $conn->prepare("UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?");
    $stmt->bind_param("ssi", $new_token, $expiry, $user_id);
    $stmt->execute();
    setcookie('remember_token', $new_token, time() + (86400 * 30), "/", "", false, true);
    return true;
}

function saveExportedDocument($conn, $user_id, $tour_description, $from_date, $to_date, $serial_from, $serial_to, $total_pages, $total_entries, $pdf_content) {
    $stmt = $conn->prepare("INSERT INTO exported_documents (user_id, tour_description, from_date, to_date, serial_from, serial_to, total_pages, total_entries, pdf_content) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssiiiss", $user_id, $tour_description, $from_date, $to_date, $serial_from, $serial_to, $total_pages, $total_entries, $pdf_content);
    return $stmt->execute();
}

function getExportedDocuments($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM exported_documents WHERE user_id = ? ORDER BY exported_date DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}
?>