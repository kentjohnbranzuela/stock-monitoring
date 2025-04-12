<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing data',
        'received' => file_get_contents("php://input") // debug line
    ]);
    exit;
}

// Sample lang ng expected data handling
$id = $data['id'] ?? null;
$accountNumber = $data['accountNumber'] ?? null;
$serialNumber = $data['serialNumber'] ?? null;
$wireConsumed = $data['wireConsumed'] ?? null;

if (!$id || !$accountNumber || !$serialNumber || $wireConsumed === null) {
    echo json_encode([
        'success' => false,
        'error' => 'Some required fields are missing.',
        'received' => $data
    ]);
    exit;
}

// Include config
require 'config.php';

try {
    $stmt = $pdo->prepare("UPDATE drop_wire_consumption SET account_number = ?, serial_number = ?, drop_wire_consumed = ? WHERE id = ?");
    $stmt->execute([$accountNumber, $serialNumber, $wireConsumed, $id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Update failed: ' . $e->getMessage()
    ]);
}
