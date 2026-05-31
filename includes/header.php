<?php
$user = getCurrentUser($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo $page_title ?? 'Police Diary System'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include_once 'sidebar.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="top-navbar">
            <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <div class="user-info">
                <div class="user-badge">
                    <i class="fas fa-user-circle"></i> 
                    <span><?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="content-container">