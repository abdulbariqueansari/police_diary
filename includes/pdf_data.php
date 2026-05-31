<?php
function prepareDisplayRows($dates) {
    $displayRows = [];
    foreach ($dates as $date => $entries) {
        foreach ($entries as $e) {
            $displayRows[] = [
                'datetime' => date('d-m-y', strtotime($e['entry_date'])) . " " . date('H:i', strtotime($e['entry_time'])),
                'report' => $e['report'],
                'remarks' => $e['orders_remarks'] ?: '-'
            ];
        }
    }
    return $displayRows;
}

function processEntries($rows) {
    $dates = [];
    foreach ($rows as $r) {
        $date = $r['entry_date'];
        if (!isset($dates[$date])) $dates[$date] = [];
        $dates[$date][] = $r;
    }
    
    foreach ($dates as $date => &$entries) {
        $hasOpen = false; 
        $hasClose = false;
        foreach ($entries as $e) {
            if ($e['entry_time'] == '00:00:00') $hasOpen = true;
            if ($e['entry_time'] == '23:59:00') $hasClose = true;
        }
        if (!$hasOpen) {
            array_unshift($entries, [
                'entry_date' => $date, 
                'entry_time' => '00:00:00', 
                'report' => 'Open the PD for thr next 24 hrs.', 
                'orders_remarks' => '-'
            ]);
        }
        if (!$hasClose) {
            array_push($entries, [
                'entry_date' => $date, 
                'entry_time' => '23:59:00', 
                'report' => 'Close the PD for the last 24 hrs.', 
                'orders_remarks' => '-'
            ]);
        }
    }
    unset($entries);
    
    return $dates;
}

function getYearTotalEntries($conn, $user_id) {
    $result = $conn->query("SELECT COUNT(DISTINCT entry_date) as total FROM diary_entries 
        WHERE user_id = " . $user_id . " AND YEAR(entry_date) = YEAR(CURDATE())");
    return $result->fetch_assoc()['total'];
}

function getPDFData($conn, $user, $settings, $from_date, $to_date) {
    $stmt = $conn->prepare("SELECT entry_date, entry_time, report, orders_remarks FROM diary_entries WHERE user_id = ? AND entry_date BETWEEN ? AND ? ORDER BY entry_date ASC, entry_time ASC");
    $stmt->bind_param("iss", $user['id'], $from_date, $to_date);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $dates = processEntries($rows);
    $displayRows = prepareDisplayRows($dates);
    $yearTotal = getYearTotalEntries($conn, $user['id']);
    $currentSerial = getUserSerialCounter($conn, $user['id']);
    
    return [
        'displayRows' => $displayRows,
        'yearTotal' => $yearTotal,
        'currentSerial' => $currentSerial,
        'totalEntries' => count($displayRows)
    ];
}
?>
