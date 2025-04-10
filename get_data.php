<?php
require_once 'config.php';
header('Content-Type: application/json');

if ($_GET['action'] == 'get_consumption') {
    // Get the technician parameter if set
    $technician = isset($_GET['technician']) ? $_GET['technician'] : '';

    // Prepare the SQL query
    $sql = "SELECT * FROM drop_wire_consumption WHERE 1";
    if ($technician) {
        $sql .= " AND technician_name = :technician";
    }

    try {
        // Prepare the SQL statement
        $stmt = $pdo->prepare($sql);

        // Bind the technician parameter if it's set
        if ($technician) {
            $stmt->bindParam(':technician', $technician, PDO::PARAM_STR);
        }

        // Execute the query
        $stmt->execute();

        // Fetch the results
        $consumptionData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // If no records are found, send a message
        if (empty($consumptionData)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No records found'
            ]);
            exit;
        }

        // Calculate the total consumed wire
        $totalConsumed = array_sum(array_column($consumptionData, 'drop_wire_consumed'));

        // Return the data as JSON
        echo json_encode([
            'status' => 'success',
            'data' => $consumptionData,
            'totalConsumed' => $totalConsumed
        ]);
    } catch (PDOException $e) {
        // Error handling for database connection or query
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}
?>
