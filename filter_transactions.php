<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $query = "SELECT t.*, i.description as item_desc 
              FROM transactions t
              JOIN items i ON t.item_code = i.item_code
              WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if (!empty($data['fromDate'])) {
        $query .= " AND DATE(t.transaction_date) >= ?";
        $params[] = $data['fromDate'];
        $types .= 's';
    }
    
    if (!empty($data['toDate'])) {
        $query .= " AND DATE(t.transaction_date) <= ?";
        $params[] = $data['toDate'];
        $types .= 's';
    }
    
    if (!empty($data['itemFilter'])) {
        $query .= " AND t.item_code = ?";
        $params[] = $data['itemFilter'];
        $types .= 's';
    }
    
    if (!empty($data['typeFilter'])) {
        $query .= " AND t.transaction_type = ?";
        $params[] = $data['typeFilter'];
        $types .= 's';
    }
    
    $query .= " ORDER BY t.transaction_date DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $transactions
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>