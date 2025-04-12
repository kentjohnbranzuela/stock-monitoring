<?php
require_once 'config.php';
checkAuth();

// Get current user
$current_user = $_SESSION['full_name'];

// Add this in your PHP section (before the HTML)
$reportType = $_GET['reportType'] ?? 'stock';
$category = $_GET['reportCategory'] ?? '';

// Only get dates for movement reports
$fromDate = $toDate = '';
if ($reportType === 'movement') {
    $fromDate = $_GET['reportFrom'] ?? date('Y-m-01');
    $toDate = $_GET['reportTo'] ?? date('Y-m-t');
}

// Build query based on report type
switch ($reportType) {
    case 'movement':
        $query = "SELECT t.*, i.description as item_name 
                 FROM transactions t
                 JOIN items i ON t.item_code = i.item_code
                 WHERE t.transaction_date BETWEEN ? AND ?";
        $params = [$fromDate, $toDate];
        break;
        
    case 'low':
        $query = "SELECT * FROM items WHERE current_stock <= min_stock";
        $params = [];
        break;
        
    case 'stock':
    default:
        $query = "SELECT i.* FROM items i";
        $params = [];
}

// Add category filter if specified
if (!empty($category)) {
    $query .= (strpos($query, 'WHERE') !== false ? ' AND ' : ' WHERE ');
    $query .= "category = ?";
    $params[] = $category;
}

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reportData = $stmt->fetchAll();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_transaction'])) {
        $trans_date = $_POST['trans_date'];
        $item_code = $_POST['item_code'];
        $trans_type = $_POST['trans_type'];
        $processed_by = $_POST['processed_by'];
        $notes = $_POST['notes'];
        $released_by = $_POST['released_by']; // 🆕 Get released_by from form

        // Get quantity either from count of serials or quantity field
        $serials = isset($_POST['serial_numbers']) ? $_POST['serial_numbers'] : [];
        $isMultiple = count($serials) > 0;

        $stmt = $pdo->prepare("INSERT INTO transactions 
            (transaction_date, item_code, transaction_type, quantity, processed_by, notes, serial_number, last_realesby) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        if ($isMultiple) {
            foreach ($serials as $serial) {
                $stmt->execute([
                    $trans_date,
                    $item_code,
                    $trans_type,
                    1, // 1 per serial
                    $processed_by,
                    $notes,
                    $serial,
                    $released_by // 🆕 Save released_by as last_realesby
                ]);
            }
            $qty = count($serials);
        } else {
            // Single transaction
            $qty = $_POST['quantity'];
            $stmt->execute([
                $trans_date,
                $item_code,
                $trans_type,
                $qty,
                $processed_by,
                $notes,
                !empty($_POST['serial_number']) ? $_POST['serial_number'] : NULL,
                $released_by // 🆕 Save released_by as last_realesby
            ]);
        }

        // Update stock
        $change = $trans_type === 'in' ? $qty : -$qty;
        $stmt = $pdo->prepare("UPDATE items SET current_stock = current_stock + ? WHERE item_code = ?");
        $stmt->execute([$change, $item_code]);

        header("Location: index.php?success=Transaction added successfully");
        exit();
    }

    // ... your add_item part remains unchanged
}


// Get current tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Get items for dropdowns
$items = $pdo->query("SELECT * FROM items ORDER BY description")->fetchAll();



// Get stock levels
$stockLevels = $pdo->query("
    SELECT i.*, 
           CASE 
               WHEN i.current_stock <= 0 THEN 'Out of Stock'
               WHEN i.current_stock <= i.min_stock THEN 'Low Stock'
               ELSE 'In Stock'
           END AS status
    FROM items i
    ORDER BY i.description
")->fetchAll();

// Get recent transactions
$transactions = $pdo->query("
    SELECT t.*, i.description as item_desc 
    FROM transactions t
    JOIN items i ON t.item_code = i.item_code
    ORDER BY t.transaction_date DESC
    LIMIT 50
")->fetchAll();

// Calculate summary
$summary = $pdo->query("
    SELECT 
        COUNT(*) as total_items,
        SUM(CASE WHEN current_stock <= 0 THEN 1 ELSE 0 END) as out_of_stock,
        SUM(CASE WHEN current_stock > 0 AND current_stock <= min_stock THEN 1 ELSE 0 END) as low_stock,
        SUM(CASE WHEN current_stock > min_stock THEN 1 ELSE 0 END) as in_stock
    FROM items
")->fetch();
// Add this right before your transaction INSERT code
if (!empty($_POST['serial_number'])) {
    $serial = trim($_POST['serial_number']);
    $itemCode = $_POST['item_code'];
    
    // Check for duplicate serial for the same item type
    $checkSerial = $pdo->prepare("
        SELECT t.id, t.transaction_type, i.description 
        FROM transactions t
        JOIN items i ON t.item_code = i.item_code
        WHERE t.serial_number = ? AND t.item_code = ?
        ORDER BY t.transaction_date DESC 
        LIMIT 1
    ");
    $checkSerial->execute([$serial, $itemCode]);
    $existing = $checkSerial->fetch();
    
    if ($existing) {
        // Custom error message with more details
        $errorMessage = sprintf(
            "Serial Number Conflict!<br><br>
            <b>%s</b> already exists for item: <b>%s</b><br>
            Last transaction type: <b>%s</b>",
            htmlspecialchars($serial),
            htmlspecialchars($existing['description']),
            strtoupper($existing['transaction_type'])
        );
        
        $_SESSION['error'] = $errorMessage;
        header("Location: index.php?tab=dashboard&success=Transaction added");
exit();
    }
    
}

// Proceed with transaction if no duplicate
$stmt = $pdo->prepare("INSERT INTO transactions (...) VALUES (...)");


// Function to get consumption data filtered by technician
// Function to get unique technician names (processed_by from transactions table)
// Function to get drop wire consumption data based on the selected technician (processed_by)
// Function to get drop wire consumption data based on the selected technician (processed_by) and date range
function getTechnicians($pdo) {
    $stmt = $pdo->query("SELECT DISTINCT processed_by
                           FROM transactions
                           WHERE processed_by IS NOT NULL AND processed_by != ''
                           ORDER BY processed_by");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Function to get drop wire consumption data from the transactions and drop_wire_consumption tables
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

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    $technician = $_GET['technician'] ?? '';
    $start = $_GET['start_date'] ?? '';
    $end = $_GET['end_date'] ?? '';

    // Example filter logic, update with your real DB call
    $filtered = array_filter($consumptionData, function ($row) use ($technician, $start, $end) {
        $matchTech = !$technician || $row['technician_name'] === $technician;
        $matchDate = true;

        if ($start && $end) {
            $matchDate = $row['date'] >= $start && $row['date'] <= $end;
        }

        return $matchTech && $matchDate;
    });

    echo json_encode(array_values($filtered));
    exit;
}
$releasedUsers = $pdo->query("SELECT name FROM released_by_users WHERE is_active = TRUE ORDER BY name")->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <script src="script.js"></script>
    <script>
  const phpSelf = "<?= $_SERVER['PHP_SELF'] ?>";
</script>
    <title>Advanced Stock Monitoring</title>
</head>
    <div class="container">
        <div class="header">
    <h1>ADVANCED STOCK MONITORING SYSTEM</h1>
    <div class="user-info">
        <span>
            Welcome, 
            <a href="user_profile.php" class="user-link">
                <?php echo htmlspecialchars($current_user); ?>
            </a>
        </span>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab <?php echo $tab === 'dashboard' ? 'active' : ''; ?>" onclick="switchTab('dashboard')">Dashboard</div>
            <div class="tab <?php echo $tab === 'items' ? 'active' : ''; ?>" onclick="switchTab('items')">Items Masterlist</div>
             <div class="tab <?= $tab === 'processors' ? 'active' : '' ?>" onclick="switchTab('processors')">Processors</div>
            <div class="tab <?php echo $tab === 'reports' ? 'active' : ''; ?>" onclick="switchTab('reports')">Reports</div>
             <div class="tab <?php echo $tab === 'technicians' ? 'active' : ''; ?>" onclick="switchTab('technicians')">Per Technicians</div>
            <div class="tab <?php echo $tab === 'dropwire' ? 'active' : ''; ?>" onclick="switchTab('dropwire')">Drop Wire Monitoring</div>

        </div>
        
        <!-- Dashboard Tab -->
        <div id="dashboard" class="tab-content <?php echo $tab === 'dashboard' ? 'active' : ''; ?>">
    <div class="dashboard">
        <div class="card">
            <div class="card-title">STOCK MOVEMENT</div>
            <form method="POST" action="index.php">
                <input type="hidden" name="add_transaction" value="1">

                <div class="form-row">
                    <div class="form-group">
                        <label>Date</label>
                        <input type="datetime-local" name="trans_date" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Item</label>
                        <select name="item_code" required>
                            <option value="">Select Item</option>
                            <?php foreach ($items as $item): ?>
                                <option value="<?php echo htmlspecialchars($item['item_code']); ?>">
                                    <?php echo htmlspecialchars($item['description']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Transaction Type</label>
                        <select name="trans_type" required>
                            <option value="in">Stock In (Delivery)</option>
                            <option value="out">Stock Out</option>
                            <option value="adjust">Adjustment</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantity" id="quantityInput" min="1" required>
                    </div>
                </div>

                <div class="form-group">
    <label>Scan Serial Numbers</label>
    <div id="serial-wrapper">
        <input type="text" name="serial_numbers[]" class="serial-input" placeholder="Scan serial...">
    </div>
    <button type="button" onclick="addSerialInput()" class="btn btn-sm btn-primary">Add Another</button>
</div>

                <div class="form-group">
                    <label>Processed By</label>
                    <select name="processed_by" required>
                        <option value="">Select Processor</option>
                        <?php 
                        $processors = $pdo->query("SELECT * FROM processors WHERE is_active = TRUE ORDER BY name")->fetchAll();
                        foreach ($processors as $processor): ?>
                            <option value="<?= htmlspecialchars($processor['name']) ?>">
                                <?= htmlspecialchars($processor['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
    <label>Released By</label>
   <select name="released_by" required>
    <option value="">Select Released By</option>
    <?php 
    $releasedUsers = $pdo->query("SELECT name FROM released_by_users ORDER BY name")->fetchAll();
    foreach ($releasedUsers as $user): ?>
        <option value="<?= htmlspecialchars($user['name']) ?>">
            <?= htmlspecialchars($user['name']) ?>
        </option>
    <?php endforeach; ?>
</select>

</div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" rows="2"></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-success">RECORD TRANSACTION</button>
            </form>
        </div>

                
                <div class="card">
                    <div class="card-title">CURRENT STOCK LEVELS</div>
                    <div class="current-stock">
                        Total Items: <span id="totalItems"><?php echo $summary['total_items']; ?></span> | 
                        Low Stock: <span id="lowStock" class="low-stock"><?php echo $summary['low_stock']; ?></span> | 
                        In Stock: <span id="inStock" class="high-stock"><?php echo $summary['in_stock']; ?></span>
                    </div>
                    <table id="stockLevels">
                        <thead>
                            <tr>
                                <th>Item Code</th>
                                <th>Description</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stockLevels as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                                    <td class="stock-level <?php 
                                        echo $item['current_stock'] <= 0 ? 'low-stock' : 
                                            ($item['current_stock'] <= $item['min_stock'] ? 'medium-stock' : 'high-stock'); 
                                    ?>">
                                        <?php echo $item['current_stock']; ?>
                                    </td>
                                    <td class="<?php 
                                        echo $item['current_stock'] <= 0 ? 'low-stock' : 
                                            ($item['current_stock'] <= $item['min_stock'] ? 'medium-stock' : 'high-stock'); 
                                    ?>">
                                        <?php echo $item['status']; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
           <div class="card">
    <div class="card-title">RECENT TRANSACTIONS</div>
    <div class="search-filter">
        <div class="form-row">
            <div class="form-group">
                <label>From Date</label>
                <input type="date" id="fromDate" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label>To Date</label>
                <input type="date" id="toDate" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label>Item Filter</label>
                <select id="itemFilter">
                    <option value="">All Items</option>
                    <?php foreach ($items as $item): ?>
                        <option value="<?= htmlspecialchars($item['item_code']) ?>">
                            <?= htmlspecialchars($item['description']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Type</label>
                <select id="typeFilter">
                    <option value="">All Types</option>
                    <option value="in">Stock In</option>
                    <option value="out">Stock Out</option>
                </select>
            </div>
        </div>
        <button class="btn" id="applyFilter">APPLY FILTER</button>
    </div>
    
    <table id="transHistory">
        <thead>
            <tr>
                <th>Date</th>
                <th>Item</th>
                <th>Type</th>
                <th>Qty</th>
                <th>By</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody id="transactions-body">
            <!-- Dynamic content will be loaded here -->
        </tbody>
    </table>
</div>
        </div>
        
        <!-- Items Masterlist Tab -->
        <div id="items" class="tab-content <?php echo $tab === 'items' ? 'active' : ''; ?>">
            <div class="card">
                <div class="card-title">ITEMS MASTERLIST</div>
                <?php if (isAdmin()): ?>
                    <button class="btn" onclick="showAddItemModal()">ADD NEW ITEM</button>
                <?php endif; ?>
                <table id="itemsList">
                    <thead>
                        <tr>
                            <th>Item Code</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Min Stock</th>
                            <th>Current Stock</th>
                            <?php if (isAdmin()): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stockLevels as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                                <td><?php echo htmlspecialchars($item['description']); ?></td>
                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                <td><?php echo $item['min_stock']; ?></td>
                                <td class="stock-level <?php 
                                    echo $item['current_stock'] <= 0 ? 'low-stock' : 
                                        ($item['current_stock'] <= $item['min_stock'] ? 'medium-stock' : 'high-stock'); 
                                ?>">
                                    <?php echo $item['current_stock']; ?>
                                </td>
                                <?php if (isAdmin()): ?>
                                    <td>
                                        <button class="btn" onclick="showEditItemModal('<?php echo $item['item_code']; ?>')">Edit</button>
                                        <button class="btn btn-danger" onclick="confirmDeleteItem('<?php echo $item['item_code']; ?>')">Delete</button>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Processors Tab -->
         <div id="processors" class="tab-content <?= $tab === 'processors' ? 'active' : '' ?>">
    <div class="card">
        <div class="card-title">PROCESSORS MANAGEMENT</div>
        <?php if (isAdmin()): ?>
            <button class="btn" onclick="showAddProcessorModal()">ADD NEW PROCESSOR</button>
        <?php endif; ?>
        <table id="processorsList">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Processor Name</th>
                    <th>Status</th>
                    <?php if (isAdmin()): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                $processors = $pdo->query("SELECT * FROM processors ORDER BY name")->fetchAll();
                foreach ($processors as $processor): ?>
                    <tr>
                        <td><?= $processor['id'] ?></td>
                        <td><?= htmlspecialchars($processor['name']) ?></td>
                        <td><?= $processor['is_active'] ? 'Active' : 'Inactive' ?></td>
                        <?php if (isAdmin()): ?>
                            <td>
                                <button class="btn" onclick="showEditProcessorModal(<?= $processor['id'] ?>)">Edit</button>
                                <button class="btn btn-danger" onclick="confirmDeleteProcessor(<?= $processor['id'] ?>)">Delete</button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

 <!-- Add Processor Modal -->
<div id="addProcessorModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: white; padding: 25px; border-radius: 8px; width: 450px; max-width: 90%; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <!-- Modal Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f0f0f0; padding-bottom: 15px;">
            <h2 style="margin: 0; font-size: 1.4rem; color: #333;">Add New Processor</h2>
            <button onclick="hideAddProcessorModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #666;">&times;</button>
        </div>
        
        <!-- Processor Form -->
        <form method="POST" action="manage_processor.php">
            <input type="hidden" name="action" value="add">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">Processor Name</label>
                <input type="text" name="name" required style="
                    width: 100%;
                    padding: 10px 12px;
                    border: 1px solid #ddd;
                    border-radius: 6px;
                    font-size: 1rem;
                    transition: border 0.3s;
                ">
            </div>
            
            <div style="margin-bottom: 25px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">Status</label>
                <select name="is_active" required style="
                    width: 100%;
                    padding: 10px 12px;
                    border: 1px solid #ddd;
                    border-radius: 6px;
                    font-size: 1rem;
                    background-color: white;
                ">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            
            <!-- Form Buttons -->
            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" onclick="hideAddProcessorModal()" style="
                    padding: 10px 20px;
                    background: #f0f0f0;
                    color: #333;
                    border: none;
                    border-radius: 6px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.2s;
                ">
                    Cancel
                </button>
                <button type="submit" style="
                    padding: 10px 20px;
                    background: #3b82f6;
                    color: white;
                    border: none;
                    border-radius: 6px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.2s;
                ">
                    Save Processor
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function hideAddProcessorModal() {
    document.getElementById('addProcessorModal').style.display = 'none';
}
</script>

<!-- Edit Processor Modal -->
<div id="editProcessorModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: white; padding: 20px; border-radius: 8px; width: 500px; max-width: 90%;">
        <h2>Edit Processor</h2>
        <form method="POST" action="manage_processor.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editProcessorId">
            <div class="form-group">
                <label>Processor Name</label>
                <input type="text" name="name" id="editProcessorName" required>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="is_active" id="editProcessorStatus" required>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-success">UPDATE</button>
                <button type="button" class="btn btn-danger" onclick="hideEditProcessorModal()">CANCEL</button>
            </div>
        </form>
    </div>
</div>
      <!-- Reports Tab -->
<div id="reports" class="tab-content <?php echo $tab === 'reports' ? 'active' : ''; ?>">
    <!-- Reports Form -->
    <form method="GET" action="index.php">
        <input type="hidden" name="tab" value="reports">
        <input type="hidden" name="generate_report" value="1">
        
        <div class="form-row">
            <div class="form-group">
                <label>Report Type</label>
                <select name="reportType" id="reportType">
                    <option value="stock" <?= ($reportType ?? '') === 'stock' ? 'selected' : '' ?>>Stock Levels</option>
                    <option value="movement" <?= ($reportType ?? '') === 'movement' ? 'selected' : '' ?>>Stock Movement</option>
                    <option value="low" <?= ($reportType ?? '') === 'low' ? 'selected' : '' ?>>Low Stock</option>
                </select>
            </div>
            
            <div class="form-group date-field-group">
                <label>From Date</label>
                <input type="date" name="reportFrom" value="<?= $fromDate ?? date('Y-m-01') ?>">
            </div>
            
            <div class="form-group date-field-group">
                <label>To Date</label>
                <input type="date" name="reportTo" value="<?= $toDate ?? date('Y-m-t') ?>">
            </div>
            
            <div class="form-group">
                <label>Category</label>
                <select name="reportCategory">
                    <option value="">All Categories</option>
                    <?php 
                    $categories = $pdo->query("SELECT DISTINCT category FROM items")->fetchAll();
                    foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category'] ?>" 
                            <?= ($category ?? '') === $cat['category'] ? 'selected' : '' ?>>
                            <?= $cat['category'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn">GENERATE REPORT</button>
        <button type="button" class="btn btn-success" onclick="exportToExcel()">EXPORT TO EXCEL</button>
    </form>

    <!-- Report Results -->
    <div id="reportResults" style="margin-top: 20px;">
        <?php if (isset($_GET['generate_report']) && !empty($reportData)): ?>
            <h3>
                <?= match($reportType) {
                    'stock' => 'Current Stock Levels',
                    'movement' => 'Stock Movement Report',
                    'low' => 'Low Stock Alert'
                } ?>
                
                <?php if ($reportType === 'movement'): ?>
                    (<?= date('M d, Y', strtotime($fromDate)) ?> to <?= date('M d, Y', strtotime($toDate)) ?>)
                <?php endif; ?>
                
                <?php if (!empty($category)): ?>
                    - Category: <?= htmlspecialchars($category) ?>
                <?php endif; ?>
            </h3>
            <div class="inventory-summary">
    <a href="modem_inventory.php" class="btn modem-btn">
        <i class="fas fa-wifi"></i> View All MODEM Inventory 
        (<?= $pdo->query("SELECT COUNT(DISTINCT serial_number) FROM transactions WHERE serial_number IS NOT NULL")->fetchColumn() ?> units)
    </a>
</div>
            <table class="report-table" border="1" style="width:100%; border-collapse: collapse; margin-top: 15px;">
                <thead>
                    <tr style="background-color: #3498db; color: white;">
                        <?php if ($reportType === 'movement'): ?>
                            <th>Date</th>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Qty</th>
                            <th>Processed By</th>
                             <th>Serial Number</th>
                        <?php else: ?>
                            <th>Code</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Current</th>
                            <th>Min</th>
                            <th>ModemDetail</th>
                            <th>Status</th>
                        <?php endif; ?>
                    </tr>
                </thead>
               <tbody>
    <?php 
    // Define modem identifiers (ilagay sa itaas ng PHP file)
    $modemKeywords = ['MODEM', 'GPON', 'ONU', 'ONT', 'ROUTER'];
    
    foreach ($reportData as $row): 
        // Check if item is modem
        $isModem = false;
        $description = strtoupper($row['description'] ?? '');
        foreach ($modemKeywords as $keyword) {
            if (strpos($description, $keyword) !== false) {
                $isModem = true;
                break;
            }
        }
    ?>
    <tr>
        
        <?php if ($reportType === 'movement'): ?>
            <!-- Movement Report Columns -->
            <td><?= date('m/d/Y', strtotime($row['transaction_date'])) ?></td>
            <td>
                <?php if ($isModem && !empty($row['serial_number'])) : ?>
                    <a href="javascript:void(0)" onclick="showModemDetails('<?= $row['serial_number'] ?>')" 
                       class="modem-link" title="View modem details">
                        <?= htmlspecialchars($row['item_name']) ?>
                    </a>
                <?php else : ?>
                    <?= htmlspecialchars($row['item_name']) ?>
                <?php endif; ?>
            </td>
            <td><?= strtoupper($row['transaction_type']) ?></td>
            <td><?= $row['quantity'] ?></td>
            <td><?= htmlspecialchars($row['processed_by']) ?></td>
            <td>
                <?php if (!empty($row['serial_number'])) : ?>
                    <span class="serial-number" onclick="copySerial('<?= $row['serial_number'] ?>')">
                        <?= htmlspecialchars($row['serial_number']) ?>
                    </span>
                <?php else : ?>
                    N/A
                <?php endif; ?>
            </td>
        <?php else: ?>
            
            <!-- Stock Report Columns -->
            <td><?= $row['item_code'] ?></td>
            <td>
                <?php if ($isModem && !empty($row['serial_number'])) : ?>
                    <a href="javascript:void(0)" onclick="showModemDetails('<?= $row['serial_number'] ?>')" 
                       class="modem-link" title="View modem details">
                        <?= htmlspecialchars($row['description']) ?>
                    </a>
                <?php else : ?>
                    <?= htmlspecialchars($row['description']) ?>
                <?php endif; ?>
            </td>
            
            <td><?= htmlspecialchars($row['category']) ?></td>
            
            <td><?= $row['current_stock'] ?></td>
            <td><?= $row['min_stock'] ?></td>
            <td>
                <?php if (!empty($row['serial_number'])) : ?>
                    <span class="serial-number" onclick="copySerial('<?= $row['serial_number'] ?>')">
                        <?= htmlspecialchars($row['serial_number']) ?>
                    </span>
                <?php else : ?>
                    N/A
                <?php endif; ?>
            </td>
            <td>
                <?= $row['current_stock'] <= 0 ? 'Out of Stock' : 
                   ($row['current_stock'] <= $row['min_stock'] ? 'Low Stock' : 'In Stock') ?>
            </td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
</tbody>
            </table>
            
            <p style="margin-top: 15px; font-style: italic;">
                Report generated on <?= date('Y-m-d H:i:s') ?>
            </p>
        <?php elseif (isset($_GET['generate_report'])): ?>
            <div class="alert alert-danger">No data found for the selected filters</div>
        <?php endif; ?>
    </div>
</div>

    <!-- Add Item Modal -->
    <div id="addItemModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: white; padding: 20px; border-radius: 8px; width: 500px; max-width: 90%;">
            <h2>Add New Item</h2>
            <form method="POST" action="index.php?tab=items">
                <input type="hidden" name="add_item" value="1">
                <div class="form-group">
                    <label>Item Code</label>
                    <input type="text" name="new_item_code" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="new_item_desc" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="new_item_category" required>
                </div>
                <div class="form-group">
                    <label>Initial Stock</label>
                    <input type="number" name="new_item_stock" min="0" value="0" required>
                </div>
                <div class="form-group">
                    <label>Minimum Stock Level</label>
                    <input type="number" name="new_item_min_stock" min="0" value="0" required>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">SAVE</button>
                    <button type="button" class="btn btn-danger" onclick="hideAddItemModal()">CANCEL</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div id="editItemModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: white; padding: 20px; border-radius: 8px; width: 500px; max-width: 90%;">
            <h2>Edit Item</h2>
            <form id="editItemForm" method="POST" action="update_item.php">
                <input type="hidden" name="item_code" id="editItemCode">
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" id="editItemDesc" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" id="editItemCategory" required>
                </div>
                <div class="form-group">
                    <label>Current Stock</label>
                    <input type="number" name="current_stock" id="editItemStock" min="0" required>
                </div>
                <div class="form-group">
                    <label>Minimum Stock Level</label>
                    <input type="number" name="min_stock" id="editItemMinStock" min="0" required>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">UPDATE</button>
                    <button type="button" class="btn btn-danger" onclick="hideEditItemModal()">CANCEL</button>
                </div>
            </form>
        </div>
    </div>
    <?php if ($tab === 'technicians'): ?>
   <!-- Filters Section (outside tab content) -->
<div class="search-filter">
    <div class="form-row">
        <div class="form-group">
            <label>From Date</label>
            <input type="date" id="techFromDate" value="<?= date('Y-m-01') ?>">
        </div>
        <div class="form-group">
            <label>To Date</label>
            <input type="date" id="techToDate" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
            <label>Processor Type</label>
            <select id="techTypeFilter" onchange="updateTechnicianDropdown()">
                <option value="">All</option>
                <option value="SLR">SLR</option>
                <option value="SLI">SLI</option>
            </select>
        </div>
        <div class="form-group">
            <label>Specific Technician</label>
            <select id="specificTechFilter">
                <option value="">All Technicians</option>
            </select>
        </div>
    </div>
    <button class="btn" onclick="loadTechnicianReport(event)">GENERATE REPORT</button>
</div>

<!-- Results Section (inside tab content) -->

    <!-- Results Section (inside tab content) -->
    <div id="technicians" class="tab-content active">
        <!-- Results Table -->
        <div id="techReportResults">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Date Received</th>
                        <th>Technician Name</th>
                        <th>Account Number</th>
                        <th>Serial Number</th>
                        <th>Status</th>
                        <th>RealesBy</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="techReportBody">
                    <?php
                    // Database Query
                    $query = "SELECT transaction_id, transaction_date, processed_by, account_number, serial_number, status, last_realesby FROM transactions ORDER BY transaction_id DESC";
                    $stmt = $pdo->query($query);
                    
                    // Check if any rows are returned
                    if ($stmt->rowCount() > 0) {
                        // Loop through the rows and display data
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo '<tr data-id="' . $row['transaction_id'] . '">';
                            echo '<td>' . $row['transaction_id'] . '</td>';
                            echo '<td>' . $row['transaction_date'] . '</td>';
                            echo '<td>' . $row['processed_by'] . '</td>';
                            echo '<td><input type="text" class="account-input" value="' . $row['account_number'] . '"></td>';
                            echo '<td><input type="text" class="serial-input" value="' . $row['serial_number'] . '"></td>';
                            echo '<td><select class="status-dropdown">';
                            echo '<option value="ACTIVATED" ' . ($row['status'] == 'ACTIVATED' ? 'selected' : '') . '>ACTIVATED</option>';
                            echo '<option value="INSTALLED / ICS" ' . ($row['status'] == 'INSTALLED / ICS' ? 'selected' : '') . '>INSTALLED / ICS</option>';
                            echo '<option value="PENDING FOR ACTIVATION" ' . ($row['status'] == 'PENDING FOR ACTIVATION' ? 'selected' : '') . '>PENDING FOR ACTIVATION</option>';
                            echo '<option value="ON HAND" ' . ($row['status'] == 'ON HAND' ? 'selected' : '') . '>ON HAND</option>';
                            echo '<option value="ASSIGN TECH" ' . ($row['status'] == 'ASSIGN TECH' ? 'selected' : '') . '>ASSIGN TECH</option>';
                            echo '<option value="DEFECTIVE" ' . ($row['status'] == 'DEFECTIVE' ? 'selected' : '') . '>DEFECTIVE</option>';
                            echo '<option value="RETURN" ' . ($row['status'] == 'RETURN' ? 'selected' : '') . '>RETURN</option>';
                            echo '</select></td>';
                            echo '<td><input type="text" class="realesby-dropdown" value="' . $row['last_realesby'] . '"></td>';
                            echo '<td><button class="save-btn">Save</button></td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="8" class="text-center">No data available. Generate report first.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
    <?php if ($tab === 'dropwire'): ?>

         <div class="calendar-container">
            
   <div style="position: fixed; bottom: 20px; right: 20px; display: flex; gap: 12px;">
    <!-- Calendar Button -->
    <button id="show-calendar-btn" style="
        padding: 12px 20px;
        background: white;
        color: #3b82f6;
        border: 2px solid #3b82f6;
        border-radius: 30px;
        font-weight: 500;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: all 0.2s;
    ">
        Calendar
    </button>
    
    <!-- Create Button -->
    <button id="open-create-form" style="
        width: 50px;
        height: 50px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 50%;
        font-size: 1.5rem;
        cursor: pointer;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        transition: all 0.2s;
    ">
        +
    </button>
</div>

<div id="create-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h3>Create Drop Wire Record</h3>
        <form id="create-form" style="display: flex; flex-direction: column; gap: 0.4rem;">
            
            <label>Serial Number:
                <select id="serial-number" required>
                    <option value="">-- Select --</option>
                    <?php
                        $stmt = $pdo->query("SELECT DISTINCT serial_number FROM transactions");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value=\"" . htmlspecialchars($row['serial_number']) . "\">" . htmlspecialchars($row['serial_number']) . "</option>";
                        }
                    ?>
                </select>
            </label>

            <label>Account Number: <input type="text" id="account-number" readonly></label>
            <label>Processed By: <input type="text" id="processed-by" readonly></label>
            <label>Transaction Date: <input type="date" id="transaction-date"></label> <!-- Editable now -->
            <label>Drop Wire Consumed: <input type="number" step="0.01" id="drop-wire" required></label>

            <button type="submit" style="margin-top: 0.5rem;">Submit</button>
        </form>
    </div>
</div>


    <div class="mini-calendar" id="calendar" style="display: none;">
        <div class="calendar-header">
            <span id="calendar-month-year"></span>
            <div>
                <button id="prev-month">&lt;</button>
                <button id="next-month">&gt;</button>
            </div>
        </div>
        <div class="calendar-days-header">
            <span>Su</span>
            <span>Mo</span>
            <span>Tu</span>
            <span>We</span>
            <span>Th</span>
            <span>Fr</span>
            <span>Sa</span>
        </div>
        <div id="calendar-dates">
        </div>
    </div>
</div>
         
<div id="dropwire">
    
        <div class="card">
            
            <div class="card-header">
                     <h2>DROP WIRE MONITORING</h2>
                <form id="technician-form">
                    <label>Select Technician:
                        <select name="technician">
                            <option value="">-- All Technicians --</option>
                            <?php foreach ($technicians as $tech): ?>
                            <option value="<?= htmlspecialchars($tech) ?>">
                                <?= htmlspecialchars($tech) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </form>
            </div>

            <table id="dropwire-table">
                <thead style="display: table-header-group; background: #f8f9fa;">
                    <tr>
                        <th>Date</th>
                        <th>Account Number</th>
                        <th>Serial Number</th>
                        <th>Technician</th>
                        <th>Wire Consumed (meters)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($consumptionData)): ?>
                    <tr>
                        <td colspan="6">No data found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($consumptionData as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['date']) ?></td>
                        <td><?= htmlspecialchars($row['account_number']) ?></td>
                        <td><?= htmlspecialchars($row['serial_number']) ?></td>
                        <td><?= htmlspecialchars($row['technician_name']) ?></td>
                        <td><?= number_format($row['drop_wire_consumed'], 2) ?></td>
                       <td><a href="#" class="edit-button" data-id="<?= $row['id'] ?>">Edit</a></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="card-footer">
                Total: <?= number_format($totalConsumed, 2) ?> meters
            </div>
        </div>
    </div>
         <?php endif; ?>
           
    <script>
        
      document.getElementById('serial-number').addEventListener('change', function () {
    const serial = this.value;

    if (!serial) return;

    fetch('get_transaction_details.php?serial_number=' + encodeURIComponent(serial))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('account-number').value = data.account_number;
                document.getElementById('processed-by').value = data.processed_by;
                document.getElementById('transaction-date').value = data.transaction_date;
            } else {
                document.getElementById('account-number').value = '';
                document.getElementById('processed-by').value = '';
                document.getElementById('transaction-date').value = '';
                alert('No transaction data found for this serial number.');
            }
        })
        .catch(error => {
            console.error('Error fetching transaction details:', error);
        });
});

// ✅ SUBMIT handler para sa form
document.getElementById('create-form').addEventListener('submit', function (e) {
    e.preventDefault(); // 💥 Para di mag reload!

    const serialNumber = document.getElementById('serial-number').value;
    const accountNumber = document.getElementById('account-number').value;
    const processedBy = document.getElementById('processed-by').value;
    const transactionDate = document.getElementById('transaction-date').value;
    const dropWire = document.getElementById('drop-wire').value;

    fetch('save_dropwire.php', { // ✅ make sure it's 'save_dropwire.php'
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            serial_number: serialNumber,
            account_number: accountNumber,
            processed_by: processedBy,
            transaction_date: transactionDate,
            drop_wire_consumed: dropWire
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Drop wire record created successfully!');
            document.getElementById('create-form').reset();
            document.getElementById('create-modal').style.display = 'none';
            location.reload(); // Optional: reload to show new record
        } else {
            alert('Submission failed: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error submitting form:', error);
        alert('An error occurred while submitting the form.');
    });
});

// Open modal
document.getElementById("open-create-form").addEventListener("click", () => {
    document.getElementById("create-modal").style.display = "flex";
});

// Close modal
document.querySelector(".close-btn").addEventListener("click", () => {
    document.getElementById("create-modal").style.display = "none";
});

// document.getElementById("account-number").addEventListener("change", function () {
//     const accountNumber = this.value;
//     if (!accountNumber) return;

//     fetch('get_transaction_info.php?account_number=' + encodeURIComponent(accountNumber))
//         .then(res => res.json())
//         .then(data => {
//             if (data.success) {
//                 document.getElementById("serial-number").value = data.serial_number;
//                 document.getElementById("processed-by").value = data.processed_by;
//                 document.getElementById("transaction-date").value = data.transaction_date;
//             } else {
//                 alert('Transaction data not found.');
//             }
//         })
//         .catch(() => alert('Error fetching data.'));
// });
        
      document.addEventListener('DOMContentLoaded', function () {
    const technicianDropdown = document.querySelector('#technician-form select[name="technician"]');
    const dropwireTableBody = document.querySelector('#dropwire-table tbody');
    const showCalendarBtn = document.getElementById('show-calendar-btn');
    const calendarContainer = document.querySelector('.calendar-container');
    const calendar = document.getElementById('calendar');
    const calendarMonthYear = document.getElementById('calendar-month-year');
    const calendarDates = document.getElementById('calendar-dates');
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');

    let currentDate = new Date();
    let selectedDate = null;
    let calendarVisible = false;

    function fetchAndRenderData() {
        const technician = technicianDropdown.value;
        let url = phpSelf + '?ajax=1';

        if (technician) url += `&technician=${encodeURIComponent(technician)}`;
        if (selectedDate) {
            url += `&start_date=${selectedDate.start}&end_date=${selectedDate.end}`;
        }

        fetch(url)
            .then(res => res.json())
            .then(updateTable)
            .catch(err => console.error('Fetch error:', err));
    }

    technicianDropdown.addEventListener('change', fetchAndRenderData);

    function renderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        const monthNames = ["January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"];
        calendarMonthYear.textContent = `${monthNames[month]} ${year}`;
        calendarDates.innerHTML = '';

        for (let i = 0; i < firstDay; i++) {
            const empty = document.createElement('span');
            empty.classList.add('empty');
            calendarDates.appendChild(empty);
        }

        for (let i = 1; i <= daysInMonth; i++) {
            const dateCell = document.createElement('span');
            const fullDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
            dateCell.textContent = i;

            if (fullDate === new Date().toISOString().slice(0, 10)) {
                dateCell.classList.add('today');
            }

            if (selectedDate && fullDate >= selectedDate.start && fullDate <= selectedDate.end) {
                dateCell.classList.add('selected');
            }

            dateCell.addEventListener('click', () => {
                const clickedDate = new Date(fullDate);
                const day = clickedDate.getDay();
                const monday = new Date(clickedDate);
                monday.setDate(clickedDate.getDate() - (day === 0 ? 6 : day - 1));
                const sunday = new Date(monday);
                sunday.setDate(monday.getDate() + 6);

                selectedDate = {
                    start: monday.toISOString().slice(0, 10),
                    end: sunday.toISOString().slice(0, 10)
                };

                renderCalendar();
                fetchAndRenderData();
            });

            calendarDates.appendChild(dateCell);
        }
    }

    function updateTable(data) {
        dropwireTableBody.innerHTML = '';
        if (data.length > 0) {
    // generate table rows
    updateTotal(data);
} else {
    // show "no data found"
    updateTotal([]); // sets total to 0.00 meters
}

        if (data.length === 0) {
            const row = dropwireTableBody.insertRow();
            const cell = row.insertCell();
            cell.colSpan = 6;
            cell.textContent = 'No data found';
            return;
        }

        data.forEach(row => {
    const newRow = dropwireTableBody.insertRow();

    newRow.insertCell().textContent = row.date;
    newRow.insertCell().textContent = row.account_number;
    newRow.insertCell().textContent = row.serial_number;
    newRow.insertCell().textContent = row.technician_name;
    newRow.insertCell().textContent = parseFloat(row.drop_wire_consumed).toFixed(2);

    const actionsCell = newRow.insertCell();
    const button = document.createElement('button');
    button.textContent = 'Edit';
    button.classList.add('edit-button');
    button.dataset.id = row.id; // very important para sa fetch
    actionsCell.appendChild(button);
});
    }

    showCalendarBtn.addEventListener('click', function (event) {
        event.stopPropagation();
        calendar.style.display = calendarVisible ? 'none' : 'block';
        calendarVisible = !calendarVisible;
    });

    prevMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    });

    nextMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    });

    document.addEventListener('click', function (event) {
        if (calendarVisible && !calendarContainer.contains(event.target) && event.target !== showCalendarBtn) {
            calendar.style.display = 'none';
            calendarVisible = false;
        }
    });

    renderCalendar();
});
function updateTotal(data) {
    const total = data.reduce((sum, row) => sum + parseFloat(row.drop_wire_consumed || 0), 0);
    const footer = document.querySelector('.card-footer');
    footer.textContent = `Total: ${total.toFixed(2)} meters`;
}

document.addEventListener('DOMContentLoaded', function () {
    const table = document.querySelector('#dropwire-table');

    table.addEventListener('click', function (e) {
        const target = e.target;

        // ==== EDIT MODE ====
        if (target.classList.contains('edit-button')) {
            const row = target.closest('tr');
            const id = target.dataset.id;

            const wireCell = row.children[4]; // Only wire cell editable
            const wireValue = wireCell.textContent.trim();

            // Replace just wire cell with input
            wireCell.innerHTML = `<input type="number" step="0.01" value="${wireValue}" style="width: 80px;">`;

            // Change Edit to Save
            target.textContent = 'Save';
            target.classList.remove('edit-button');
            target.classList.add('save-button');
        }

        // ==== SAVE MODE ====
        else if (target.classList.contains('save-button')) {
            const row = target.closest('tr');
            const id = target.dataset.id;

            const wireInput = row.children[4].querySelector('input');
            const wireConsumed = wireInput.value.trim();

            fetch('update_dropwire.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: id,
                    wireConsumed: wireConsumed
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Replace input with updated value
                    row.children[4].textContent = parseFloat(wireConsumed).toFixed(2);

                    // Switch Save back to Edit
                    target.textContent = 'Edit';
                    target.classList.remove('save-button');
                    target.classList.add('edit-button');
                } else {
                    console.error(result);
                    alert('Update failed: ' + (result.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Something went wrong while saving.');
            });
        }
    });
});
function addSerialInput() {
    const wrapper = document.getElementById('serial-wrapper');
    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'serial_numbers[]';
    input.classList.add('serial-input');
    input.placeholder = 'Scan serial...';
    input.required = true;
    wrapper.appendChild(document.createElement('br'));
    wrapper.appendChild(input);
}
    </script>
</body>
</html>