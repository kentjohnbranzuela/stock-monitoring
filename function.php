<?php
function gettransactions($pdo) {
    $stmt = $pdo->query("SELECT DISTINCT processed_by 
                        FROM transactions 
                        WHERE processed_by IS NOT NULL 
                        AND processed_by != ''
                        ORDER BY processed_by");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getConsumptionData($pdo, $transactions = null) {
    $query = "SELECT * FROM drop_wire_consumption";
    $params = [];
    
    if ($transactions) {
        $query .= " WHERE technician_name = ?";
        $params[] = $transactions;
    }
    
    $query .= " ORDER BY date DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function calculateTotalConsumed($data) {
    return array_reduce($data, function($sum, $row) {
        return $sum + (float)$row['drop_wire_consumed'];
    }, 0);
}