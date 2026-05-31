<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireLogin();

$user = getCurrentUser($conn);
$language = getUserSetting($conn, $user['id'], 'language', 'en');

if (!$user) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

$id = (int)$_GET['id'];
$entry = $conn->query("SELECT * FROM diary_entries WHERE id = $id AND user_id = " . $user['id'])->fetch_assoc();
if (!$entry) {
    header('Location: view_logs.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $entry_date = $_POST['entry_date'];
    $entry_time = $_POST['entry_time'];
    $report = trim($_POST['report']);
    $orders_remarks = trim($_POST['orders_remarks']);
    
    if (!empty($report)) {
        $stmt = $conn->prepare("UPDATE diary_entries SET entry_date=?, entry_time=?, report=?, orders_remarks=? WHERE id=? AND user_id=?");
        $stmt->bind_param("ssssii", $entry_date, $entry_time, $report, $orders_remarks, $id, $user['id']);
        if ($stmt->execute()) {
            $success = "Entry updated!";
            $entry = $conn->query("SELECT * FROM diary_entries WHERE id = $id AND user_id = " . $user['id'])->fetch_assoc();
        } else {
            $error = "Failed to update.";
        }
    } else {
        $error = "Report cannot be empty!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Entry | Police Diary</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
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
        .action-buttons { display: flex; gap: 15px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="main-content" style="margin-left: 0; padding: 40px 20px; max-width: 800px; margin: 0 auto;">
        <div class="card">
            <h3><i class="fas fa-edit"></i> Edit Diary Entry</h3>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group"><label>Date</label><input type="date" name="entry_date" value="<?php echo $entry['entry_date']; ?>" required></div>
                    <div class="form-group"><label>Time</label><input type="time" name="entry_time" value="<?php echo $entry['entry_time']; ?>" required></div>
                </div>
                <div class="form-group">
                    <label>Report / Activity</label>
                    <div class="input-with-voice">
                        <textarea name="report" rows="5" required><?php echo htmlspecialchars($entry['report']); ?></textarea>
                        <button type="button" class="voice-btn" onclick="startVoiceRecognition('report')"><i class="fas fa-microphone"></i></button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Orders & Remarks</label>
                    <div class="input-with-voice">
                        <textarea name="orders_remarks" rows="3"><?php echo htmlspecialchars($entry['orders_remarks']); ?></textarea>
                        <button type="button" class="voice-btn" onclick="startVoiceRecognition('orders_remarks')"><i class="fas fa-microphone"></i></button>
                    </div>
                </div>
                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="view_logs.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
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
</body>
</html>