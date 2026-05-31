<?php
require_once ROOT_PATH . '/includes/pdf/pdf_generator.php';
require_once ROOT_PATH . '/includes/pdf/pdf_header.php';

function generatePDFContent($conn, $user, $settings, $from_date, $to_date) {
    require_once ROOT_PATH . '/includes/pdf/pdf_data.php';
    
    $data = getPDFData($conn, $user, $settings, $from_date, $to_date);
    $displayRows = $data['displayRows'];
    $yearTotal = $data['yearTotal'];
    $currentSerial = $data['currentSerial'];
    
    $pdf = new DiaryPDF('P', 'mm', 'A4');
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 35);
    $pdf->setSignatureDesignation($settings['signature_designation']);
    $pdf->AddPage();
    
    drawPDFHeader($pdf, $settings, $from_date, $to_date, $currentSerial, $yearTotal);
    
    $baseRowHeight = 5;
    $pageCount = 1;
    $currentY = $pdf->GetY();
    $x = 15;
    
    for ($i = 0; $i < count($displayRows); $i++) {
        $row = $displayRows[$i];
        
        $reportLines = ceil($pdf->GetStringWidth($row['report']) / 90);
        $remarksLines = ceil($pdf->GetStringWidth($row['remarks']) / 50);
        $datetimeLines = ceil($pdf->GetStringWidth($row['datetime']) / 35);
        $maxLines = max($reportLines, $remarksLines, $datetimeLines);
        $requiredHeight = $maxLines * $baseRowHeight + 3;
        
        if ($currentY + $requiredHeight > 250) {
            $pdf->AddPage();
            $pageCount++;
            drawPDFHeader($pdf, $settings, $from_date, $to_date, ($currentSerial + $pageCount - 1) % 10000, $yearTotal);
            $currentY = $pdf->GetY();
        }
        
        $pdf->drawRow($x, $currentY, $row, $baseRowHeight);
        
        $currentY += $requiredHeight;
        $pdf->SetY($currentY);
    }
    
    $newCounter = ($currentSerial + $pageCount) % 10000;
    
    return [
        'content' => $pdf->Output('S'),
        'pageCount' => $pageCount,
        'currentSerial' => $currentSerial,
        'newCounter' => $newCounter,
        'totalEntries' => count($displayRows)
    ];
}
?>
