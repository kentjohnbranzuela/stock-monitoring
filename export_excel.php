<?php
require_once 'config.php';
checkAuth();

// Get parameters
$reportType = $_GET['reportType'] ?? 'stock';
$fromDate = $_GET['reportFrom'] ?? date('Y-m-01');
$toDate = $_GET['reportTo'] ?? date('Y-m-t');
$category = $_GET['reportCategory'] ?? '';

// Use the same query logic as above
// ...

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="stock_report_'.date('Ymd_His').'.xls"');

echo "<table border='1'>";
// Output the same table structure as the HTML version
// ...
echo "</table>";