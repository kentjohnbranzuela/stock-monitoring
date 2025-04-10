<?php
require_once 'config.php';
checkAuth();

$query = "SELECT i.description as model, t.serial_number, 
          COUNT(*) as total, MAX(t.transaction_date) as last_transaction
          FROM transactions t
          JOIN items i ON t.item_code = i.item_code
          WHERE i.category = 'MODEM' AND t.serial_number IS NOT NULL
          GROUP BY i.description, t.serial_number
          ORDER BY i.description, t.serial_number";

$modems = $pdo->query($query)->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>MODEM Inventory</title>
    <style>
        .modem-table {
            width: 100%;
            border-collapse: collapse;
        }
        .modem-table th, .modem-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .modem-table th {
            background-color: #3498db;
            color: white;
        }
        .modem-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .total-row {
            font-weight: bold;
            background-color: #e7f3fe !important;
        }
    </style>
</head>
<body>
    <h1>MODEM Inventory Report</h1>
    <table class="modem-table">
        <thead>
            <tr>
                <th>Model</th>
                <th>Serial Number</th>
                <th>Last Transaction Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $currentModel = '';
            $modelCount = 0;
            $totalModems = 0;
            
            foreach ($modems as $modem): 
                if ($currentModel != $modem['model']) {
                    if ($currentModel != '') {
                        echo "<tr class='total-row'>
                                <td colspan='2'>Total for $currentModel</td>
                                <td colspan='2'>$modelCount units</td>
                              </tr>";
                    }
                    $currentModel = $modem['model'];
                    $modelCount = 0;
                }
                $modelCount++;
                $totalModems++;
            ?>
            <tr>
                <td><?= htmlspecialchars($modem['model']) ?></td>
                <td><?= htmlspecialchars($modem['serial_number']) ?></td>
                <td><?= date('m/d/Y H:i', strtotime($modem['last_transaction'])) ?></td>
                <td>In Stock</td>
            </tr>
            <?php endforeach; ?>
            <?php if (!empty($modems)): ?>
                <tr class='total-row'>
                    <td colspan='2'>GRAND TOTAL</td>
                    <td colspan='2'><?= $totalModems ?> MODEM units</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>