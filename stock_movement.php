<?php
// Include necessary database connection and logic here
require_once 'config.php';

// Assuming you fetch items from the database for selection
$items = $pdo->query("SELECT * FROM items ORDER BY description")->fetchAll();

// Assuming you fetch processors and released_by users
$processors = $pdo->query("SELECT * FROM processors WHERE is_active = 1 ORDER BY name")->fetchAll();
$releasedByUsers = $pdo->query("SELECT * FROM released_by_users WHERE is_active = 1 ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Movement</title>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 40px 15px;
            color: #333;
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            max-width: 900px;
            margin: 0 auto;
            padding: 25px;
        }

        .card-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            text-align: center;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
            min-width: 250px;
            max-width: 350px;
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--success-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s;
            width: 100%;
            margin-top: 10px;
        }

        .btn:hover {
            background-color: #27ae60;
        }

        .btn-danger {
            background-color: var(--danger-color);
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .btn-primary {
            background-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .form-row button {
            width: auto;
            padding: 8px 20px;
            margin-top: 20px;
        }

        .selected-items {
            margin-top: 20px;
            background: #f7f7f7;
            padding: 10px;
            border-radius: var(--border-radius);
            border: 1px solid #ddd;
        }

        .selected-items ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .selected-items li {
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .selected-items li:last-child {
            border-bottom: none;
        }

        .remove-btn {
            background: var(--danger-color);
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .remove-btn:hover {
            background: #c0392b;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }

            .card {
                padding: 15px;
            }

            .card-title {
                flex-direction: column;
                text-align: left;
            }
        }
    </style>
    <script>
    let selectedItems = [];

    // Handle item selection change
    function toggleItemFields() {
        const itemSelect = document.getElementById('item-select');
        const reelField = document.getElementById('reel-field');
        const serialField = document.getElementById('serial-field');
        const selectedItem = itemSelect.options[itemSelect.selectedIndex].text;

        // Show the Reel Number field only if the selected item is "Drop Cable"
        if (selectedItem.toLowerCase().includes("drop cable")) {
            reelField.style.display = 'block';
            serialField.style.display = 'none'; // Hide serial number field for drop cable
        } else {
            reelField.style.display = 'none';
            serialField.style.display = 'block'; // Show serial number field for other items
        }
    }

    // Add selected item to the list of selected items
    function addItemField() {
        const itemSelect = document.getElementById('item-select');
        const quantityInput = document.getElementById('quantity');
        const reelInput = document.getElementById('reel_number');
        const serialInput = document.getElementById('serial_number');
        const selectedItemText = itemSelect.options[itemSelect.selectedIndex].text;
        const quantity = quantityInput.value;

        // Handle input validation
        if (!selectedItemText || !quantity) {
            alert("Please select an item and specify a quantity.");
            return;
        }

        // Add item based on its type (Drop Cable or other)
        const item = {
            description: selectedItemText,
            quantity: quantity,
            serial_number: serialInput.value,
            reel_number: reelInput.value || null // Reel number is optional for non-drop cable items
        };

        // Add to the selected items array
        selectedItems.push(item);

        // Update the display of selected items
        updateSelectedItems();

        // Clear input fields
        itemSelect.selectedIndex = 0;
        quantityInput.value = '';
        serialInput.value = ''; // Reset serial number input
        reelInput.value = ''; // Reset reel number input
    }

    // Update the UI with selected items
    function updateSelectedItems() {
        const selectedItemsContainer = document.getElementById('selected-items-container');
        selectedItemsContainer.innerHTML = ''; // Clear existing list

        selectedItems.forEach((item, index) => {
            const listItem = document.createElement('li');
            listItem.innerHTML = `
                <span>${item.description} - Quantity: ${item.quantity}</span>
                ${item.reel_number ? `<span> - Reel: ${item.reel_number}</span>` : ''}
                ${item.serial_number ? `<span> - Serial: ${item.serial_number}</span>` : ''}
                <button type="button" class="remove-btn" onclick="removeItem(${index})">Remove</button>
            `;
            selectedItemsContainer.appendChild(listItem);
        });
    }

    // Remove an item from the selected list
    function removeItem(index) {
        selectedItems.splice(index, 1);
        updateSelectedItems();
    }

    // Confirm selection
    function confirmSelection() {
        if (selectedItems.length === 0) {
            alert('Please select at least one item.');
            return;
        }
        document.getElementById('submit-btn').style.display = 'block';
    }
</script>

</head>
<body>
    <div class="card">
        <div class="card-title">
            <span>STOCK MOVEMENT</span>
        </div>
        <form method="POST" action="process_stock_movement.php">
            <input type="hidden" name="add_transaction" value="1">

            <!-- Date Field -->
            <div class="form-row">
                <div class="form-group">
                    <label for="trans_date">Date</label>
                    <input type="datetime-local" id="trans_date" name="trans_date" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                </div>
            </div>

            <!-- Item and Transaction Type Fields -->
            <div class="form-row">
    <div class="form-group">
        <label for="item-select">Item</label>
        <select name="item_code[]" id="item-select" onchange="toggleItemFields()" required>
            <option value="">Select Item</option>
            <?php foreach ($items as $item): ?>
                <option value="<?= htmlspecialchars($item['item_code']) ?>"><?= htmlspecialchars($item['description']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="trans_type">Transaction Type</label>
        <select name="trans_type" id="trans_type" required>
            <option value="in">Stock In (Delivery)</option>
            <option value="out">Stock Out</option>
            <option value="adjust">Adjustment</option>
        </select>
    </div>
</div>

<!-- Quantity Field -->
<div class="form-row">
    <div class="form-group">
        <label for="quantity">Quantity</label>
        <input type="number" id="quantity" name="quantity" min="1" required>
    </div>
</div>

<!-- Reel Number Field (only for Drop Cable) -->
<div class="form-row" id="reel-field" style="display: none;">
    <div class="form-group">
        <label for="reel_number">Reel Number</label>
        <input type="text" id="reel_number" name="reel_number" placeholder="Enter Reel Number">
    </div>
</div>

<!-- Serial Number Input for Scanning Multiple Numbers -->
<div class="form-row" id="serial-field" style="display: block;">
    <div class="form-group">
        <label for="serial_number">Serial Numbers (Scan Multiple)</label>
        <textarea id="serial_number" name="serial_number" placeholder="Scan or Enter Serial Numbers, one per line" rows="4" required></textarea>
    </div>
</div>

<!-- Add Item Button -->
<button type="button" class="btn btn-primary" onclick="addItemField()">Add Item</button>

<!-- Selected Items List -->
<div class="selected-items">
    <ul id="selected-items-container"></ul>
</div>
<!-- Selected Items List -->
<div class="selected-items">
    <ul id="selected-items-container"></ul>
</div>

<!-- Processed By and Released By Fields -->
<div class="form-row">
    <div class="form-group">
        <label for="processed_by">Processed By</label>
        <select name="processed_by" id="processed_by" required>
            <option value="">Select Processor</option>
            <?php foreach ($processors as $processor): ?>
                <option value="<?= htmlspecialchars($processor['id']) ?>"><?= htmlspecialchars($processor['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="released_by">Released By</label>
        <select name="released_by" id="released_by" required>
            <option value="">Select Released By</option>
            <?php foreach ($releasedByUsers as $user): ?>
                <option value="<?= htmlspecialchars($user['id']) ?>"><?= htmlspecialchars($user['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- Notes Field -->
<div class="form-row">
    <div class="form-group">
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="3" placeholder="Additional information about this transaction"></textarea>
    </div>
</div>

<!-- Confirm Button -->
<button type="button" class="btn btn-success" id="submit-btn" style="display: none;" onclick="confirmSelection()">Confirm Selection</button>

<!-- Submit Button -->
<button type="submit" class="btn btn-danger" style="display: none;">Record Transaction</button>
        </form>
    </div>
</body>
</html>
