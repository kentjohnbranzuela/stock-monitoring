<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    $query = "SELECT DISTINCT processed_by FROM transactions WHERE processed_by IS NOT NULL ORDER BY processed_by";
    $stmt = $pdo->query($query);
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($technicians);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>