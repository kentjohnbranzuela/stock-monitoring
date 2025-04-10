<?php
function getTechnicians($pdo) {
    $stmt = $pdo->query("SELECT DISTINCT processed_by 
                        FROM technicians 
                        WHERE processed_by IS NOT NULL 
                        AND processed_by != ''
                        ORDER BY processed_by");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getConsumptionData($pdo, $technician = null) {
    $query = "SELECT * FROM drop_wire_consumption";
    $params = [];
    
    if ($technician) {
        $query .= " WHERE technician_name = ?";
        $params[] = $technician;
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