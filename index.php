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
        // Add transaction
      $stmt = $pdo->prepare("INSERT INTO transactions 
    (transaction_date, item_code, transaction_type, quantity, processed_by, notes, serial_number) 
    VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $_POST['trans_date'],
    $_POST['item_code'],
    $_POST['trans_type'],
    $_POST['quantity'],
    $_POST['processed_by'], // This captures the selected processor
    $_POST['notes'],
    !empty($_POST['serial_number']) ? $_POST['serial_number'] : NULL
]);
        
        // Update stock
    $change = $_POST['trans_type'] === 'in' ? $_POST['quantity'] : -$_POST['quantity'];
    $stmt = $pdo->prepare("UPDATE items SET current_stock = current_stock + ? WHERE item_code = ?");
    $stmt->execute([$change, $_POST['item_code']]);
    
    header("Location: index.php?success=Transaction added successfully");
    exit();
}
    elseif (isset($_POST['add_item'])) {
        if (!isAdmin()) {
            header("Location: index.php?error=Only admin can add items");
            exit();
        }
        
        $stmt = $pdo->prepare("INSERT INTO items (item_code, description, category, current_stock, min_stock) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['new_item_code'],
            $_POST['new_item_desc'],
            $_POST['new_item_category'],
            $_POST['new_item_stock'],
            $_POST['new_item_min_stock']
        ]);
        
        header("Location: index.php?tab=items&success=Item added successfully");
        exit();
    }
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
function getTransactions($pdo) {
    $stmt = $pdo->query("
        SELECT DISTINCT processed_by 
        FROM transactions 
        WHERE processed_by IS NOT NULL AND processed_by != '' 
        ORDER BY processed_by
    ");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Function: Get drop wire consumption joined with transactions
function getDropWireConsumption($pdo, $technician = null) {
    $query = "
        SELECT dwc.*, t.account_number, t.serial_number, t.processed_by AS technician_name
        FROM drop_wire_consumption AS dwc
        JOIN transactions AS t ON dwc.account_number = t.account_number
        WHERE 1=1
    ";

    $params = [];

    if ($technician) {
        $query .= " AND t.processed_by = ?";
        $params[] = $technician;
    }

    $query .= " ORDER BY dwc.date DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Main Execution
try {
    // Get selected technician from URL (e.g. ?transactions=SLI)
    $selectedTech = $_GET['transactions'] ?? '';

    // Get all technician options for dropdown
    $transactions = getTransactions($pdo);

    // Get filtered or all drop wire consumption
    $consumptionData = getDropWireConsumption($pdo, $selectedTech);

    // Total drop wire consumed
    $totalConsumed = array_sum(array_column($consumptionData, 'drop_wire_consumed'));

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <script src="script.js"></script>
    <title>Advanced Stock Monitoring</title>
</head>
    <div class="container">
        <div class="header">
            <h1>ADVANCED STOCK MONITORING SYSTEM</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($current_user); ?></span>
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
                                <input type="datetime-local" name="trans_date" value="<?php date('Y-m-d\TH:i'); ?>" required>
                                
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
                            <label>Serial Number (Optional)</label>
    <div class="serial-input-group">
        <input type="text" name="serial_number" id="serialNumberInput" placeholder="Scan modem serial">
        <button type="button" class="btn" onclick="startSerialScanner()">
            <i class="fas fa-barcode"></i> Scan
        </button>
    </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Quantity</label>
                                <input type="number" name="quantity" min="1" required>
                            </div>
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
    <div style="background: white; padding: 20px; border-radius: 8px; width: 500px; max-width: 90%;">
        <h2>Add New Processor</h2>
        <form method="POST" action="manage_processor.php">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Processor Name</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="is_active" required>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-success">SAVE</button>
                <button type="button" class="btn btn-danger" onclick="hideAddProcessorModal()">CANCEL</button>
            </div>
        </form>
    </div>
</div>

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
<div id="dropwire">
    <div class="card">
        <div class="card-title">DROP WIRE MONITORING</div>

        <div class="filter">
            <form method="get" action="" id="technician-form">
                <label for="technician">Select Technician:</label>
<select name="technician" id="technician" onchange="this.form.submit()">
                    <option value="">-- All Technicians --</option>
                    <?php foreach ($transactions as $tech): ?>
                        <option value="<?= htmlspecialchars($tech) ?>" 
                            <?= $tech === $selectedTech ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tech) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <!-- Data Table -->
        <table id="dropwire-table">
            <thead>
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
                        <td colspan="6" class="text-center">No data found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($consumptionData as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['date']) ?></td>
                        <td><?= htmlspecialchars($row['account_number']) ?></td>
                        <td><?= htmlspecialchars($row['serial_number']) ?></td>
                        <td><?= htmlspecialchars($row['technician_name']) ?></td>
                        <td><?= number_format($row['drop_wire_consumed'], 2) ?></td>
                        <td>
                            <a href="edit_dropwire.php?id=<?= $row['id'] ?>">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Total -->
        <div class="total">
            Total Drop Wire Consumed: <?= number_format($totalConsumed, 2) ?> meters
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    loadData(); // Load data on page load
    document.getElementById('technician').addEventListener('change', loadData); // Load data when technician is selected
});

function loadData() {
    const tech = document.getElementById('technician').value;
    let url = 'get_data.php?action=get_consumption';
    if (tech) {
        url += `&technician=${encodeURIComponent(tech)}`;
    }

    const tableBody = document.getElementById('table-body');
    tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Loading data...</td></tr>';

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.data.length > 0) {
                    renderTable(data.data); // Render the table with the data
                    updateTotal(data.totalConsumed); // Update the total consumed value
                } else {
                    tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No records found</td></tr>';
                    document.getElementById('total-span').textContent = '0';
                }
            } else {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Error loading data</td></tr>';
                document.getElementById('total-span').textContent = '0';
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Error loading data</td></tr>';
        });
}

function renderTable(data) {
    const tableBody = document.getElementById('table-body');
    tableBody.innerHTML = ''; // Clear any existing rows

    data.forEach(row => {
        const tr = document.createElement('tr');
        
        tr.innerHTML = `
            <td>${formatDate(row.date)}</td>
            <td>${row.account_number}</td>
            <td>${row.serial_number}</td>
            <td>${row.technician_name}</td>
            <td>${row.drop_wire_consumed.toFixed(2)}</td>
            <td><a href="edit_dropwire.php?id=${row.id}">Edit</a></td>
        `;
        
        tableBody.appendChild(tr);
    });
}

function updateTotal(total) {
    document.getElementById('total-span').textContent = total.toFixed(2);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString(); // Format date as per your locale
}
</script>
</body>
</html>