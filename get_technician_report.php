<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Build query - CORRECTED VERSION
    $query = "SELECT 
            transaction_id,
            DATE(transaction_date) AS date_received,
            processed_by,
            serial_number,
            account_number,
            status,
            last_realesby
          FROM transactions
          WHERE 1=1";
    
    $params = [];
    
    // Add filters
    if (!empty($input['fromDate'])) {
        $query .= " AND DATE(transaction_date) >= ?";
        $params[] = $input['fromDate'];
    }
    
    if (!empty($input['toDate'])) {
        $query .= " AND DATE(transaction_date) <= ?";
        $params[] = $input['toDate'];
    }
    
    if (!empty($input['typeFilter'])) {
        $query .= " AND processed_by LIKE ?";
        $params[] = $input['typeFilter'] . '%';
    }
    
    if (!empty($input['specificTech'])) {
        $query .= " AND processed_by = ?";
        $params[] = $input['specificTech'];
    }
    
    // REMOVED THE GROUP BY as it might cause issues with transaction_id
    $query .= " ORDER BY transaction_date DESC";
    
    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $results
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'query' => $query // Helps debug the actual query
    ]);
}
if (!$pdo) {
    throw new Exception("Database connection failed");
}

// Test connection
$pdo->query("SELECT 1")->fetch();
?>