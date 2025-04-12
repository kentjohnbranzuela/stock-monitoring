<?php
require 'config.php';
$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$wireConsumed = $data['wireConsumed'] ?? null;

if (!$id || $wireConsumed === null) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields.'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE drop_wire_consumption SET drop_wire_consumed = ? WHERE id = ?");
    $stmt->execute([$wireConsumed, $id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
