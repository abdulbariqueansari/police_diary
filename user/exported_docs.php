<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireLogin();

$page_title = 'Exported Documents';
$user = getCurrentUser($conn);

if (!$user) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// Handle re-download
if (isset($_GET['download_id'])) {
    $id = (int)$_GET['download_id'];
    $result = $conn->query("SELECT * FROM exported_documents WHERE id = $id AND user_id = " . $user['id']);
    if ($result && $doc = $result->fetch_assoc()) {
        if ($doc['pdf_content']) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="exported_diary_' . $doc['id'] . '.pdf"');
            echo base64_decode($doc['pdf_content']);
            exit;
        }
    }
    header('Location: exported_docs.php?error=1');
    exit;
}

// Handle preview
if (isset($_GET['preview_id'])) {
    $id = (int)$_GET['preview_id'];
    $result = $conn->query("SELECT * FROM exported_documents WHERE id = $id AND user_id = " . $user['id']);
    if ($result && $doc = $result->fetch_assoc()) {
        if ($doc['pdf_content']) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="preview.pdf"');
            echo base64_decode($doc['pdf_content']);
            exit;
        }
    }
    header('Location: exported_docs.php?error=1');
    exit;
}

// Get exported documents
$documents = $conn->query("SELECT * FROM exported_documents WHERE user_id = " . $user['id'] . " ORDER BY exported_date DESC");

include_once '../includes/header.php';
?>

<div class="card">
    <h3><i class="fas fa-file-export"></i> Exported Documents</h3>
    <p style="margin-bottom: 20px; color: var(--gray);">All your exported PDF documents are saved here. Click download to get them again.</p>
    
    <?php if($documents && $documents->num_rows > 0): ?>
        <?php while($doc = $documents->fetch_assoc()): 
            $date_display = (date('Y-m-d', strtotime($doc['from_date'])) == date('Y-m-d', strtotime($doc['to_date']))) 
                ? date('d-m-Y', strtotime($doc['from_date'])) 
                : date('d-m-Y', strtotime($doc['from_date'])) . ' - ' . date('d-m-Y', strtotime($doc['to_date']));
            
            $serial_display = ($doc['serial_from'] == $doc['serial_to']) 
                ? str_pad($doc['serial_from'], 4, '0', STR_PAD_LEFT)
                : str_pad($doc['serial_from'], 4, '0', STR_PAD_LEFT) . ' - ' . str_pad($doc['serial_to'], 4, '0', STR_PAD_LEFT);
        ?>
            <div class="doc-card" style="background: white; border-radius: 16px; padding: 20px; margin-bottom: 15px; border-left: 4px solid #667eea; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <div class="doc-title" style="font-size: 16px; font-weight: 700; color: #1e3c72; margin-bottom: 10px;">
                    <i class="fas fa-file-pdf" style="color: #ef4444;"></i> 
                    <?php echo htmlspecialchars($doc['tour_description']); ?>
                </div>
                <div class="doc-details" style="display: flex; gap: 20px; flex-wrap: wrap; font-size: 12px; color: #6b7280; margin-bottom: 15px;">
                    <span><i class="fas fa-calendar"></i> <?php echo $date_display; ?></span>
                    <span><i class="fas fa-hashtag"></i> Serial: <?php echo $serial_display; ?></span>
                    <span><i class="fas fa-file-alt"></i> Pages: <?php echo $doc['total_pages']; ?></span>
                    <span><i class="fas fa-list-ul"></i> Entries: <?php echo $doc['total_entries']; ?></span>
                    <span><i class="fas fa-clock"></i> Exported: <?php echo date('d-m-Y H:i', strtotime($doc['exported_date'])); ?></span>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="?preview_id=<?php echo $doc['id']; ?>" target="_blank" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i> Preview</a>
                    <a href="?download_id=<?php echo $doc['id']; ?>" class="btn btn-success btn-sm"><i class="fas fa-download"></i> Download</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 50px;">
            <i class="fas fa-file-export" style="font-size: 48px; color: #ccc; margin-bottom: 15px; display: block;"></i>
            <p>No exported documents yet.</p>
            <p style="font-size: 12px;">When you export a PDF, it will be saved here for future access.</p>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>