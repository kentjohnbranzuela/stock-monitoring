<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    // Get the posted data
    $input = json_decode(file_get_contents('php://input'), true);

    // Ensure all required fields are present
    if (!isset($input['transaction_id'], $input['account_number'], $input['serial_number'], $input['status'], $input['last_realesby'])) {
        throw new Exception('Missing required fields');
    }

    // Prepare the update query
    $query = "UPDATE transactions 
              SET account_number = ?, 
                  serial_number = ?, 
                  status = ?, 
                  last_realesby = ? 
              WHERE transaction_id = ?";

    // Execute the query
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        $input['account_number'],
        $input['serial_number'],
        $input['status'],
        $input['last_realesby'],
        $input['transaction_id']
    ]);

    // Respond with success
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Respond with error
    echo json_encode(['error' => $e->getMessage()]);
}
?>
