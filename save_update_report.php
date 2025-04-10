<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (empty($input['transaction_id'])) {
        throw new Exception('Transaction ID is required');
    }

    $stmt = $pdo->prepare("
        UPDATE transactions 
        SET 
            account_number = :account_number,
            serial_number = :serial_number,
            status = :status,
            last_realesby = :last_realesby,
            updated_at = NOW()
        WHERE transaction_id = :transaction_id
    ");
    
    $success = $stmt->execute([
        ':account_number' => $input['account_number'] ?? null,
        ':serial_number' => $input['serial_number'] ?? null,
        ':status' => $input['status'] ?? null,
        ':last_realesby' => $input['last_realesby'] ?? null,
        ':transaction_id' => $input['transaction_id']
    ]);
    
    echo json_encode([
        'success' => $success,
        'updated_id' => $input['transaction_id']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>