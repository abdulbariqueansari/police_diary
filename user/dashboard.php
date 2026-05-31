<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireLogin();

$page_title = 'User Dashboard';
$user = getCurrentUser($conn);

if (!$user) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// Check for mode switching
if (isset($_GET['mode'])) {
    if ($_GET['mode'] == 'admin' && $_SESSION['role'] == 'admin') {
        $_SESSION['admin_mode'] = false;
        header('Location: ../admin/dashboard.php');
        exit;
    } elseif ($_GET['mode'] == 'user' && $_SESSION['role'] == 'admin') {
        $_SESSION['admin_mode'] = true;
    }
}



$officer_name = getUserSetting($conn, $user['id'], 'officer_name', $user['full_name']);
$police_station = getUserSetting($conn, $user['id'], 'police_station', '');
$district = getUserSetting($conn, $user['id'], 'district', '');
$language = getUserSetting($conn, $user['id'], 'language', 'en');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_entry'])) {
    $entry_date = $_POST['entry_date'] ?? date('Y-m-d');
    $entry_time = $_POST['entry_time'];
    $report = trim($_POST['report']);
    $orders_remarks = trim($_POST['orders_remarks'] ?? '');
    
    if (!empty($report)) {
        $stmt = $conn->prepare("INSERT INTO diary_entries (user_id, entry_date, entry_time, report, orders_remarks) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user['id'], $entry_date, $entry_time, $report, $orders_remarks);
        if ($stmt->execute()) {
            $success = "Entry added successfully!";
        } else {
            $error = "Failed to add entry.";
        }
    } else {
        $error = "Report cannot be empty!";
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM diary_entries WHERE id = $id AND user_id = " . $user['id']);
    $success = "Entry deleted!";
}

$totalEntries = $conn->query("SELECT COUNT(*) as count FROM diary_entries WHERE user_id = " . $user['id'])->fetch_assoc()['count'];
$totalThisMonth = $conn->query("SELECT COUNT(*) as count FROM diary_entries WHERE user_id = " . $user['id'] . " AND MONTH(entry_date) = MONTH(CURDATE())")->fetch_assoc()['count'];
$totalToday = $conn->query("SELECT COUNT(*) as count FROM diary_entries WHERE user_id = " . $user['id'] . " AND entry_date = CURDATE()")->fetch_assoc()['count'];
$recentEntries = $conn->query("SELECT * FROM diary_entries WHERE user_id = " . $user['id'] . " ORDER BY entry_date DESC, entry_time DESC LIMIT 5");

include_once '../includes/header.php';
?>

<div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-bottom: 25px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="font-size: 24px; margin-bottom: 5px;">Welcome, <?php echo htmlspecialchars(explode(' ', ($user['full_name'] ?? 'User'))[0]); ?>!</h2>
            <p><i class="fas fa-calendar"></i> <?php echo date('l, d F Y'); ?></p>
        </div>
        <div style="text-align: right;">
            <p><i class="fas fa-building"></i> <?php echo htmlspecialchars($police_station ?: 'Not set'); ?></p>
            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($district ?: 'Not set'); ?></p>
        </div>
    </div>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon"><i class="fas fa-file-alt"></i></div><div class="stat-info"><h3><?php echo $totalEntries; ?></h3><p>Total Entries</p></div></div>
    <div class="stat-card"><div class="stat-icon"><i class="fas fa-calendar-week"></i></div><div class="stat-info"><h3><?php echo $totalThisMonth; ?></h3><p>This Month</p></div></div>
    <div class="stat-card"><div class="stat-icon"><i class="fas fa-calendar-day"></i></div><div class="stat-info"><h3><?php echo $totalToday; ?></h3><p>Today</p></div></div>
</div>

<div class="card">
    <h3><i class="fas fa-plus-circle"></i> Add New Diary Entry</h3>
    <form method="POST">
        <input type="hidden" name="add_entry" value="1">
        <div class="form-grid">
            <div class="form-group"><label>Date</label><input type="date" name="entry_date" value="<?php echo date('Y-m-d'); ?>" required></div>
            <div class="form-group"><label>Time</label><input type="time" name="entry_time" value="<?php echo date('H:i'); ?>" required></div>
        </div>
        <div class="form-group">
            <label>Report / Activity</label>
            <div class="input-with-voice">
                <textarea name="report" id="reportText" placeholder="Describe your activities..." rows="4" required></textarea>
                <button type="button" class="voice-btn" onclick="startVoiceRecognition('reportText')" title="Voice Typing"><i class="fas fa-microphone"></i></button>
            </div>
        </div>
        <div class="form-group">
            <label>Orders & Remarks</label>
            <div class="input-with-voice">
                <textarea name="orders_remarks" id="remarksText" placeholder="Any orders or remarks..." rows="2"></textarea>
                <button type="button" class="voice-btn" onclick="startVoiceRecognition('remarksText')" title="Voice Typing"><i class="fas fa-microphone"></i></button>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Save Entry</button>
    </form>
</div>

<?php if($recentEntries && $recentEntries->num_rows > 0): ?>
<div class="card">
    <h3><i class="fas fa-history"></i> Recent Entries</h3>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Date</th><th>Time</th><th>Report</th><th>Actions</th></tr></thead>
            <tbody>
                <?php while($row = $recentEntries->fetch_assoc()): ?>
                <tr>
                    <td><?php echo date('d-m-Y', strtotime($row['entry_date'])); ?></td>
                    <td><?php echo date('h:i A', strtotime($row['entry_time'])); ?></td>
                    <td><?php echo nl2br(htmlspecialchars(substr($row['report'], 0, 60))); ?></td>
                    <td class="action-buttons" style="display: flex; gap: 5px; flex-wrap: wrap;">
                        <a href="edit_entry.php?id=<?php echo $row['id']; ?>" class="action-icon icon-edit" title="Edit Entry"><i class="fas fa-edit"></i></a>
                        <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this entry?')" class="action-icon icon-delete" title="Delete Entry"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <div style="margin-top: 20px; text-align: center;">
        <a href="view_logs.php" class="btn btn-secondary">View All Logs</a>
        <a href="../preview_pdf.php?from_date=<?php echo date('Y-m-d', strtotime('-1 day')); ?>&to_date=<?php echo date('Y-m-d'); ?>" class="btn btn-primary" target="_blank">Last 1 Day Report</a>
    </div>
</div>
<?php endif; ?>

<style>
    .voice-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 50%;
        width: 42px;
        height: 42px;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    .voice-btn:hover { transform: scale(1.05); }
    .voice-btn.recording { background: #ef4444; animation: pulse 1.5s infinite; }
    @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.1); } 100% { transform: scale(1); } }
    .input-with-voice { display: flex; align-items: flex-start; gap: 10px; }
    .input-with-voice textarea { flex: 1; }
</style>

<script>
let recognition = null;
let currentTextareaId = null;

if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
    const SpeechRecognition = window.webkitSpeechRecognition || window.SpeechRecognition;
    recognition = new SpeechRecognition();
    recognition.continuous = false;
    recognition.interimResults = false;
    
    const userLang = '<?php echo $language; ?>';
    if (userLang === 'hi') recognition.lang = 'hi-IN';
    else if (userLang === 'bn') recognition.lang = 'bn-BD';
    else if (userLang === 'ta') recognition.lang = 'ta-IN';
    else if (userLang === 'te') recognition.lang = 'te-IN';
    else if (userLang === 'mr') recognition.lang = 'mr-IN';
    else if (userLang === 'gu') recognition.lang = 'gu-IN';
    else if (userLang === 'kn') recognition.lang = 'kn-IN';
    else if (userLang === 'ml') recognition.lang = 'ml-IN';
    else if (userLang === 'pa') recognition.lang = 'pa-IN';
    else recognition.lang = 'en-IN';
    
    recognition.onresult = async function(event) {
        const transcript = event.results[0][0].transcript;
        const textarea = document.getElementById(currentTextareaId) || document.getElementsByName(currentTextareaId)[0];
        const voiceBtn = document.querySelector('.voice-btn.recording');
        
        try {
            const sourceLang = recognition.lang.split('-')[0];
            let translatedText = transcript;
            
            if (sourceLang !== 'en') {
                const response = await fetch(`https://translate.googleapis.com/translate_a/single?client=gtx&sl=${sourceLang}&tl=en&dt=t&q=${encodeURIComponent(transcript)}`);
                const data = await response.json();
                translatedText = data[0].map(item => item[0]).join('');
            }
            
            if (textarea) {
                textarea.value = textarea.value ? textarea.value + ' ' + translatedText : translatedText;
            }
        } catch (error) {
            console.error("Translation error:", error);
            if (textarea) {
                textarea.value = textarea.value ? textarea.value + ' ' + transcript : transcript;
            }
        } finally {
            if (voiceBtn) voiceBtn.classList.remove('recording');
        }
    };
    
    recognition.onerror = function() {
        const voiceBtn = document.querySelector('.voice-btn.recording');
        if (voiceBtn) voiceBtn.classList.remove('recording');
        alert('Voice recognition error.');
    };
    
    recognition.onend = function() {
        const voiceBtn = document.querySelector('.voice-btn.recording');
        if (voiceBtn) voiceBtn.classList.remove('recording');
    };
}

function startVoiceRecognition(textareaId) {
    if (!recognition) {
        alert('Voice recognition not supported. Please use Google Chrome.');
        return;
    }
    currentTextareaId = textareaId;
    const voiceBtn = event.currentTarget;
    voiceBtn.classList.add('recording');
    recognition.start();
}
</script>
<style>
    .action-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; text-decoration: none; transition: all 0.2s; font-size: 14px; }
    .icon-edit { background: #f59e0b; color: white; }
    .icon-delete { background: #ef4444; color: white; }
    .action-icon:hover { transform: scale(1.05); opacity: 0.9; }
</style>

<?php include_once '../includes/footer.php'; ?>
