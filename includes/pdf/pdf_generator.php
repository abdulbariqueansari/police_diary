<?php
require_once ROOT_PATH . '/modules/fpdf/fpdf.php';

class DiaryPDF extends FPDF {
    public $signatureDesignation = '';
    
    function Footer() {
        $this->SetY(-50);
        
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 8, "Submitted", 0, 1, 'C');
        $this->Ln(8);
        $this->Cell(0, 6, $this->signatureDesignation, 0, 1, 'C');
        $this->Cell(0, 5, date('d-m-Y'), 0, 1, 'C');
        $this->Ln(3);
        
        $this->SetFont('Arial', 'I', 8);
        $this->MultiCell(0, 4, "*Need not be stated in Superintendent of Police's weekly confidential diaries. A complete day in this connection means a calendar day beginning and ending at 12 midnight.", 0, 'L');
        
        $this->SetY(-12);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 8, 'Page ' . $this->PageNo(), 0, 0, 'R');
       
    }
    
    function setSignatureDesignation($designation) {
        $this->signatureDesignation = $designation;
    }
    
    function getTextHeight($width, $text, $lineHeight) {
        if (empty($text)) return $lineHeight;
        $text = str_replace("\n", ' ', $text);
        $lines = ceil($this->GetStringWidth($text) / $width);
        $lines = max(1, $lines);
        return $lines * $lineHeight + 5;
    }
    
    function drawRow($x, $y, $row, $baseRowHeight) {
        $dateTimeWidth = 35;
        $reportWidth = 90;
        $remarksWidth = 50;
        
        $datetimeText = $row['datetime'];
        
        $dateTimeHeight = $this->getTextHeight($dateTimeWidth, $datetimeText, $baseRowHeight);
        $reportHeight = $this->getTextHeight($reportWidth, $row['report'], $baseRowHeight);
        $remarksHeight = $this->getTextHeight($remarksWidth, $row['remarks'], $baseRowHeight);
        $rowHeight = max($dateTimeHeight, $reportHeight, $remarksHeight);
        
        $this->SetFont('Arial', '', 10); 
        $this->SetXY($x, $y);
        $this->MultiCell($dateTimeWidth, $baseRowHeight, $datetimeText, 0, 'L');
        
        $this->SetXY($x + $dateTimeWidth, $y);
        $this->MultiCell($reportWidth, $baseRowHeight, $row['report'], 0, 'J');
        
        $this->SetXY($x + $dateTimeWidth + $reportWidth, $y);
        $this->MultiCell($remarksWidth, $baseRowHeight, $row['remarks'], 0, 'C');
        
        return $rowHeight;
    }
}
?>
