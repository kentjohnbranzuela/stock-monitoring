<?php
require_once 'config.php';
checkAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_transaction'])) {
        // Get POST data
        $trans_date = $_POST['trans_date'];
        $item_code = $_POST['item_code'];
        $trans_type = $_POST['trans_type'];
        $processed_by = $_POST['processed_by'];
        $notes = $_POST['notes'];
        $released_by = $_POST['released_by']; // Get released_by from form

        // Get serial numbers or quantity
        $serials = isset($_POST['serial_numbers']) ? $_POST['serial_numbers'] : [];
        $isMultiple = count($serials) > 0;
        $qty = $isMultiple ? count($serials) : $_POST['quantity'];

        try {
            // Start transaction
            $pdo->beginTransaction();

            // Prepare the insert statement
            $stmt = $pdo->prepare("INSERT INTO transactions 
                (transaction_date, item_code, transaction_type, quantity, processed_by, notes, serial_number, last_realesby) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            // Handle multiple serial numbers
            if ($isMultiple) {
                foreach ($serials as $serial) {
                    $stmt->execute([
                        $trans_date,
                        $item_code,
                        $trans_type,
                        1, // Quantity is 1 per serial
                        $processed_by,
                        $notes,
                        $serial,
                        $released_by
                    ]);
                }
            } else {
                // Single transaction
                $stmt->execute([
                    $trans_date,
                    $item_code,
                    $trans_type,
                    $qty,
                    $processed_by,
                    $notes,
                    !empty($_POST['serial_number']) ? $_POST['serial_number'] : NULL,
                    $released_by
                ]);
            }

            // Update stock level
            $change = $trans_type === 'in' ? $qty : -$qty;
            $updateStockStmt = $pdo->prepare("UPDATE items SET current_stock = current_stock + ? WHERE item_code = ?");
            $updateStockStmt->execute([$change, $item_code]);

            // Commit the transaction
            $pdo->commit();

            header("Location: index.php?success=Transaction added successfully");
            exit();

        } catch (PDOException $e) {
            // Rollback in case of error
            $pdo->rollBack();
            header("Location: index.php?error=Error occurred: " . $e->getMessage());
            exit();
        }
    }

    // ... your add_item part remains unchanged
}
