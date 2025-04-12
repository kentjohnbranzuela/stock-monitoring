<?php
require 'config.php';

if (!isset($_GET['serial_number'])) {
    echo json_encode(['success' => false]);
    exit;
}

$serial = $_GET['serial_number'];

$stmt = $pdo->prepare("SELECT * FROM transactions WHERE serial_number = ? ORDER BY transaction_date DESC LIMIT 1");
$stmt->execute([$serial]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($data) {
    echo json_encode([
        'success' => true,
        'account_number' => $data['account_number'],
        'processed_by' => $data['Processed_by'],
        'transaction_date' => $data['transaction_date']
    ]);
} else {
    echo json_encode(['success' => false]);
}
?>
