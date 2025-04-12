<?php
require_once 'config.php';
checkAuth();

function getDropWireConsumption($pdo, $technician = null, $startDate = null, $endDate = null) {
    $query = "
        SELECT dwc.*, t.account_number, t.serial_number, t.processed_by AS technician_name
        FROM drop_wire_consumption AS dwc
        JOIN transactions AS t ON dwc.account_number = t.account_number
        WHERE 1=1
    ";

    $params = [];

    // Filter by technician if specified
    if ($technician) {
        $query .= " AND t.processed_by = ?";
        $params[] = $technician;
    }

    // Filter by date range if specified
    if ($startDate && $endDate) {
        $query .= " AND dwc.date BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
    }

    $query .= " ORDER BY dwc.date DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Main execution block
try {
    // Get selected technician and date range from URL query parameters (AJAX request)
    $selectedTech = $_GET['technician'] ?? '';
    $startDate = $_GET['start_date'] ?? null; // Start date from the calendar
    $endDate = $_GET['end_date'] ?? null; // End date from the calendar

    // Get the list of all technicians (processed_by) for the dropdown
    $technicians = getTechnicians($pdo);

    // Get drop wire consumption data based on the selected technician and date range
    $consumptionData = getDropWireConsumption($pdo, $selectedTech, $startDate, $endDate);

    // Calculate total consumed wire
    $totalConsumed = array_sum(array_column($consumptionData, 'drop_wire_consumed'));

    // If it's an AJAX request, return JSON
    if (isset($_GET['technician'])) {
        header('Content-Type: application/json');
        echo json_encode($consumptionData);
        exit();
    }
} catch (Exception $e) {
    // Handle any exceptions (e.g., invalid date format, database connection errors)
    echo json_encode(['error' => $e->getMessage()]);
    exit();
} catch (PDOException $e) {
    // Handle database-specific exceptions
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit();
}
?>