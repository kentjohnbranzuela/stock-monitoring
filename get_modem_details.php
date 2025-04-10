<?php
require_once 'config.php';

$serial = $_GET['serial'] ?? '';
$response = ['success' => false];

if (!empty($serial)) {
    $stmt = $pdo->prepare("
        SELECT t.*, i.description as model 
        FROM transactions t
        JOIN items i ON t.item_code = i.item_code
        WHERE t.serial_number = ?
        ORDER BY t.transaction_date DESC
        LIMIT 1
    ");
    $stmt->execute([$serial]);
    $data = $stmt->fetch();
    
    if ($data) {
        $response = [
            'success' => true,
            'model' => $data['model'],
            'last_transaction_date' => date('Y-m-d H:i', strtotime($data['transaction_date'])),
            'status' => ($data['transaction_type'] === 'in') ? 'In Stock' : 'Deployed'
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>
<!-- Bootstrap Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">MODEM Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modal-body">
                Loading details...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<style>
.modem-link {
    color: #0066cc;
    cursor: pointer;
    text-decoration: none;
    font-weight: 500;
}

.modem-link:hover {
    text-decoration: underline;
    color: #004499;
}
</style>
    