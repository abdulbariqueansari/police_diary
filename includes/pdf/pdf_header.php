<?php
function drawPDFHeader($pdf, $settings, $from_date, $to_date, $serial, $yearTotal) {
    // Top left form numbers
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY(15, 15);
    $pdf->Cell(100, 6, 'West Bengal Form No. 5355', 0, 1);
    $pdf->SetX(15);
    $pdf->Cell(100, 5, 'W.B.P. Form No. 18', 0, 1);
    
    // Top right serial number
    $pdf->SetXY(150, 15);
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 8, str_pad($serial, 4, '0', STR_PAD_LEFT), 0, 0, 'R');
    $pdf->Ln(12);
    
    // Officer name
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 8, "PERSONAL DIARY OF " . strtoupper($settings['officer_name']), 0, 1, 'C');
    $pdf->Ln(4);
    
    // ============ POLICE STATION LINE ============
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    
    // Police Station Left, FOR in middle
    $police_text = "POLICE STATION: " . $settings['police_station'];
    $for_text = "FOR: " . date('d-m-Y', strtotime($from_date)) . " to " . date('d-m-Y', strtotime($to_date));
    
    $left_x = 15;
    $center_x = 100;
    $right_x = 180;
    
    // Draw Police Station on left
    $pdf->SetXY($left_x, $pdf->GetY());
    $pdf->Cell(80, 6, $police_text, 0, 0, 'L');
    
    // Draw FOR in center
    $pdf->SetX($center_x);
    $pdf->Cell(80, 6, $for_text, 0, 1, 'L');
    $pdf->Ln(2);
    
    // ============ DISTRICT LINE (Multi-line) ============
    $district_address = $settings['district'];
    $district_label = "DISTRICT:";
    
    // Split district address into multiple lines if needed
    $max_width = 170; // Maximum width for district text
    $district_lines = explode("\n", wordwrap($district_address, 45, "\n"));
    
    $current_y = $pdf->GetY();
    
    // Draw DISTRICT label on left
    $pdf->SetXY($left_x, $current_y);
    $pdf->Cell(25, 5, $district_label, 0, 0, 'L');
    
    // Draw district address (multi-line)
    $x_start = $left_x + 25;
    $line_height = 5;
    
    foreach ($district_lines as $index => $line) {
        $pdf->SetXY($x_start, $current_y + ($index * $line_height));
        $pdf->Cell($max_width, $line_height, $line, 0, 0, 'L');
    }
    
    // Move Y position after district lines
    $total_district_height = count($district_lines) * $line_height;
    $pdf->SetY($current_y + $total_district_height);
    $pdf->Ln(2);
    
    // ============ FOR WEEK ENDING and DESPATCHED in same line ============
    $week_ending_label = "FOR WEEK ENDING:";
    $week_ending_value = date('d-m-Y', strtotime($to_date));
    $despatched_label = "DESPATCHED:";
    $despatched_value = date('d-m-Y');
    
    $current_y = $pdf->GetY();
    
    // Draw FOR WEEK ENDING on left-center
    $pdf->SetXY($left_x, $current_y);
    $pdf->Cell(40, 5, $week_ending_label, 0, 0, 'L');
    $pdf->SetX($left_x + 40);
    $pdf->Cell(35, 5, $week_ending_value, 0, 0, 'L');
    
    // Draw DESPATCHED on right
    $pdf->SetX(110);
    $pdf->Cell(30, 5, $despatched_label, 0, 0, 'L');
    $pdf->SetX(140);
    $pdf->Cell(35, 5, $despatched_value, 0, 1, 'L');
    
    $pdf->Ln(3);
    
    // ============ REGULATIONS ============
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->Cell(0, 5, "(Regulations 197, 209, 558, 655 and 897.)", 0, 1, 'C');
    $pdf->Ln(1);
    
    // ============ TOTAL DAYS ============
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 5, "*Total Number of complete days spent on tour during the year: " . $yearTotal, 0, 1, 'C');
    $pdf->Ln(3);
    
    // ============ TABLE HEADERS ============
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Cell(35, 8, "Time and Date", 0, 0, 'L', true);
    $pdf->Cell(90, 8, "Report", 0, 0, 'L', true);
    $pdf->Cell(50, 8, "Orders and Remarks", 0, 1, 'L', true);
    $pdf->Ln(2);
}
?>
