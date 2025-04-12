<?php
require 'config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

// Basic validation
if (
    empty($data['account_number']) ||
    empty($data['serial_number']) ||
    empty($data['processed_by']) ||
    empty($data['transaction_date']) ||
    !isset($data['drop_wire_consumed']) ||
    !is_numeric($data['drop_wire_consumed'])
) {
    echo json_encode(['success' => false, 'error' => 'Missing or invalid data']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO drop_wire_consumption 
        (account_number, serial_number, technician_name, date, drop_wire_consumed) 
        VALUES (?, ?, ?, ?, ?)");

    $success = $stmt->execute([
        $data['account_number'],
        $data['serial_number'],
        $data['processed_by'],
        $data['transaction_date'],
        $data['drop_wire_consumed']
    ]);

    echo json_encode(['success' => $success]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
