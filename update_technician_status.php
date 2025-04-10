<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Check if required fields are present
    if (!isset($input['serial_number']) || !isset($input['status'])) {
        throw new Exception('Missing required parameters.');
    }

    $serialNumber = $input['serial_number'];
    $newStatus = $input['status'];

    // Update the technician's status in the database
    $query = "UPDATE transactions SET status = ? WHERE serial_number = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$newStatus, $serialNumber]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
