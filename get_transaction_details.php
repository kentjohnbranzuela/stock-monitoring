<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'config.php';

if (!isset($_GET['serial_number'])) {
    echo json_encode(['success' => false, 'error' => 'Missing serial_number']);
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
        'processed_by' => $data['processed_by'],
        'transaction_date' => $data['transaction_date']
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'No matching transaction']);
}
?>
