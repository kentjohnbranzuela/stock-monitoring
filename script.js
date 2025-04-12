 function switchTab(tabId) {
            window.location.href = `index.php?tab=${tabId}`;
        }
        
        function showAddItemModal() {
            document.getElementById('addItemModal').style.display = 'flex';
        }
        
        function hideAddItemModal() {
            document.getElementById('addItemModal').style.display = 'none';
        }
        
        function showEditItemModal(itemCode) {
            // In a real implementation, you would fetch the item details via AJAX
            // For this example, we'll just show the modal
            document.getElementById('editItemCode').value = itemCode;
            document.getElementById('editItemModal').style.display = 'flex';
            
            // You would populate the form fields here with the item's current data
            // For example:
            // fetch(`get_item.php?item_code=${itemCode}`)
            //     .then(response => response.json())
            //     .then(data => {
            //         document.getElementById('editItemDesc').value = data.description;
            //         document.getElementById('editItemCategory').value = data.category;
            //         document.getElementById('editItemStock').value = data.current_stock;
            //         document.getElementById('editItemMinStock').value = data.min_stock;
            //     });
        }
        
        function hideEditItemModal() {
            document.getElementById('editItemModal').style.display = 'none';
        }
        
        function confirmDeleteItem(itemCode) {
            if (confirm(`Are you sure you want to delete item ${itemCode}?`)) {
                window.location.href = `delete_item.php?item_code=${itemCode}`;
            }
        }
        
        function filterTransactions() {
            const fromDate = document.getElementById('fromDate').value;
            const toDate = document.getElementById('toDate').value;
            const itemFilter = document.getElementById('itemFilter').value;
            const typeFilter = document.getElementById('typeFilter').value;
            
            // In a real implementation, you would make an AJAX request to filter transactions
            // For this example, we'll just reload the page with filter parameters
            window.location.href = `index.php?tab=dashboard&from=${fromDate}&to=${toDate}&item=${itemFilter}&type=${typeFilter}`;
        }
        
        function generateReport() {
    const form = document.createElement('form');
    form.method = 'GET';
    form.action = 'index.php';
    
    // Add current tab
    const tabInput = document.createElement('input');
    tabInput.type = 'hidden';
    tabInput.name = 'tab';
    tabInput.value = 'reports';
    form.appendChild(tabInput);
    
    // Add report parameters
    const params = {
        'generate_report': '1',
        'reportType': document.getElementById('reportType').value,
        'reportFrom': document.getElementById('reportFrom').value,
        'reportTo': document.getElementById('reportTo').value,
        'reportCategory': document.getElementById('reportCategory').value
    };
    
    for (const key in params) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = params[key];
        form.appendChild(input);
    }
    
    document.body.appendChild(form);
    form.submit();
}
        
        function exportToExcel() {
    // Collect all filter parameters
    const params = {
        'reportType': document.getElementById('reportType').value,
        'reportFrom': document.getElementById('reportFrom').value,
        'reportTo': document.getElementById('reportTo').value,
        'reportCategory': document.getElementById('reportCategory').value
    };
    
    // Convert to query string
    const queryString = Object.keys(params)
        .map(key => `${key}=${encodeURIComponent(params[key])}`)
        .join('&');
    
    // Open export URL
    window.open(`export_excel.php?${queryString}`, '_blank');
}
        function showAddProcessorModal() {
    document.getElementById('addProcessorModal').style.display = 'flex';
}

function hideAddProcessorModal() {
    document.getElementById('addProcessorModal').style.display = 'none';
}

function showEditProcessorModal(id) {
    // In a real implementation, fetch processor data via AJAX
    // For now, we'll just set the ID
    document.getElementById('editProcessorId').value = id;
    document.getElementById('editProcessorModal').style.display = 'flex';
    
    // You would fetch the processor details here and populate the form
    // Example:
    // fetch(`get_processor.php?id=${id}`)
    //     .then(response => response.json())
    //     .then(data => {
    //         document.getElementById('editProcessorName').value = data.name;
    //         document.getElementById('editProcessorStatus').value = data.is_active;
    //     });
}

function hideEditProcessorModal() {
    document.getElementById('editProcessorModal').style.display = 'none';
}

function confirmDeleteProcessor(id) {
    if (confirm('Are you sure you want to delete this processor?')) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'manage_processor.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;
        form.appendChild(idInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
document.addEventListener('DOMContentLoaded', function() {
    const reportType = document.getElementById('reportType');
    const dateFields = document.querySelectorAll('.date-field-group');
    
    function toggleDateFields() {
        const showDates = reportType.value === 'movement';
        dateFields.forEach(field => {
            field.style.display = showDates ? 'block' : 'none';
        });
    }
    
    // Initial toggle
    toggleDateFields();
    
    // Toggle when report type changes
    reportType.addEventListener('change', toggleDateFields);
});
// Scanner function (using camera)
function startSerialScanner() {
    // Auto-focus sa field para sa USB scanner
    document.getElementById('serialNumberInput').focus();
    
    // Tanggalin ang alert() kung ayaw mo ng popup
    // alert("Please focus on the Serial Number field...");
    
    // Optional: Maglagay ng visual cue imbes na alert
    const input = document.getElementById('serialNumberInput');
    input.placeholder = "Ready to scan... (Focus here)";
    input.style.borderColor = "#3498db";
}
function showModemDetails(serial) {
    // Simple alert for now - replace with modal or redirect
    alert("MODEM Details\nSerial: " + serial + "\nThis will show full details");
}

function copySerial(serial) {
    navigator.clipboard.writeText(serial);
    alert("Copied to clipboard: " + serial);
}
// Transaction Filtering
document.addEventListener('DOMContentLoaded', function() {
    // Load initial filtered data
    loadFilteredTransactions();
    
    // Setup filter button
    document.getElementById('applyFilter').addEventListener('click', function(e) {
        e.preventDefault();
        loadFilteredTransactions();
    });
});

async function loadFilteredTransactions() {
    try {
        const filters = {
            fromDate: document.getElementById('fromDate').value,
            toDate: document.getElementById('toDate').value,
            itemFilter: document.getElementById('itemFilter').value,
            typeFilter: document.getElementById('typeFilter').value
        };

        const response = await fetch('filter_transactions.php', {
            method: 'POST',
            body: JSON.stringify(filters),
            headers: {
                'Content-Type': 'application/json'
            }
        });

        const result = await response.json();
        
        if (!response.ok || !result.success) {
            throw new Error(result.error || 'Unknown error occurred');
        }

        updateTransactionsTable(result.data);
        
    } catch (error) {
        console.error('Error:', error);
        alert('Error loading transactions: ' + error.message);
    }
}

function updateTransactionsTable(transactions) {
    const tbody = document.getElementById('transactions-body');
    tbody.innerHTML = '';
    
    if (transactions.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center">No transactions found</td></tr>`;
        return;
    }

    transactions.forEach(trans => {
        const dateTime = new Date(trans.transaction_date);
        const formattedDateTime = dateTime.toLocaleString('en-PH', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        }).replace(',', '');

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${formattedDateTime}</td>
            <td>${escapeHtml(trans.item_desc)}</td>
            <td><span class="badge ${trans.transaction_type === 'in' ? 'badge-in' : 'badge-out'}">
                ${trans.transaction_type.toUpperCase()}
            </span></td>
            <td>${trans.quantity}</td>
            <td>${escapeHtml(trans.processed_by)}</td>
            <td>${escapeHtml(trans.notes)}</td>
        `;
        tbody.appendChild(row);
    });
}
function escapeHtml(unsafe) {
    if (unsafe == null) return '';
    return unsafe.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
// Add this to your script.js
let allTechnicians = [];

function fetchAllTechnicians() {
    fetch('get_all_technicians.php')
        .then(response => response.json())
        .then(data => {
            allTechnicians = data;
            populateTechnicianDropdown();
        });
}

function populateTechnicianDropdown() {
    const dropdown = document.getElementById('specificTechFilter');
    dropdown.innerHTML = '<option value="">All Technicians</option>';
    
    allTechnicians.forEach(tech => {
        const option = document.createElement('option');
        option.value = tech.processed_by;
        option.textContent = tech.processed_by;
        dropdown.appendChild(option);
    });
}

function updateTechnicianDropdown() {
    const typeFilter = document.getElementById('techTypeFilter').value;
    const dropdown = document.getElementById('specificTechFilter');
    
    dropdown.innerHTML = '<option value="">All Technicians</option>';
    
    if (typeFilter === '') {
        // Show all technicians
        allTechnicians.forEach(tech => {
            const option = document.createElement('option');
            option.value = tech.processed_by;
            option.textContent = tech.processed_by;
            dropdown.appendChild(option);
        });
    } else {
        // Filter by SLR/SLI
        const filtered = allTechnicians.filter(tech => 
            tech.processed_by.startsWith(typeFilter)
        );
        
        filtered.forEach(tech => {
            const option = document.createElement('option');
            option.value = tech.processed_by;
            option.textContent = tech.processed_by;
            dropdown.appendChild(option);
        });
    }
}

// Call this when page loads
document.addEventListener('DOMContentLoaded', function() {
    fetchAllTechnicians();
});
async function loadTechnicianReport(event) {
    if (event) event.preventDefault();
    
    console.log("Generate Report button clicked");
    
    try {
        const filters = {
            fromDate: document.getElementById('techFromDate').value,
            toDate: document.getElementById('techToDate').value,
            typeFilter: document.getElementById('techTypeFilter').value,
            specificTech: document.getElementById('specificTechFilter').value
        };

        console.log("Using filters:", filters);

        // Show loading state
        const btn = event.target;
        btn.disabled = true;
        btn.textContent = "Generating...";

        const response = await fetch('get_technician_report.php', {
            method: 'POST',
            body: JSON.stringify(filters),
            headers: {
                'Content-Type': 'application/json'
            }
        });

        const result = await response.json();
        
        console.log("Full response:", result); // Debug the complete response

        if (!result.success) {
            throw new Error(result.error || 'Unknown server error');
        }

        if (!result.data || result.data.length === 0) {
            console.log("No data found for filters:", filters);
            alert("No transactions found for the selected date range/filters");
            return;
        }

        updateTechReport(result.data);
        
    } catch (error) {
        console.error("Error details:", error);
        alert("Failed to load report: " + error.message);
    } finally {
        const btn = document.getElementById('generateTechReportBtn');
        if (btn) {
            btn.disabled = false;
            btn.textContent = "GENERATE REPORT";
        }
    }
}
document.addEventListener('DOMContentLoaded', function() {
    // Make sure this runs after page loads
    
    // Handle Generate Report button
    const generateBtn = document.getElementById('generateTechReportBtn');
    if (generateBtn) {
        generateBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log("Generate button clicked"); // Debug
            loadTechnicianReport();
        });
    } else {
        console.error("Generate button not found!");
    }
});
function updateTechReport(data) {
    console.log("Full API response data:", data); // Debug entire response
    const reportBody = document.getElementById('techReportBody');
    reportBody.innerHTML = '';

    if (data.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = '<td colspan="8" class="text-center">No data available for the given filters.</td>';
        reportBody.appendChild(row);
        return;
    }

    // Loop through data and create rows
    data.forEach(rowData => {
        // Debug each row's realesBy data
        console.log(`Processing row ${rowData.transaction_id}`, {
            last_realesBy: rowData.last_realesBy,
            realesBy: rowData.realesBy,
            last_realesby: rowData.last_realesby
        });

        const row = document.createElement('tr');
        row.setAttribute('data-id', rowData.transaction_id);

        // Determine the correct released by value
        const releasedByValue = rowData.last_realesBy || 
                              rowData.realesBy || 
                              rowData.last_realesby || 
                              'N/A';

        const statusColor = getStatusColor(rowData.status);  // Get the color based on status
        const realesByColor = getRealesByColor(releasedByValue); // Get the color based on realesBy

        row.innerHTML = `
            <td>${rowData.transaction_id || 'N/A'}</td>
            <td>${rowData.transaction_date || 'N/A'}</td>
            <td>${rowData.processed_by}</td>
            <td><span class="accountNumber">${rowData.account_number || ''}</span><input type="text" class="accountNumberInput" value="${rowData.account_number || ''}" style="display: none;" /></td>
            <td><span class="serialNumber">${rowData.serial_number || ''}</span><input type="text" class="serialNumberInput" value="${rowData.serial_number || ''}" style="display: none;" /></td>
            <td>
    <span class="status" style="background-color: ${statusColor}; color: white; padding: 5px 10px; border-radius: 5px;">${rowData.status || 'N/A'}</span>
    <select class="statusSelect" style="display: none;">
        <option value="ACTIVATED" ${rowData.status === 'ACTIVATED' ? 'selected' : ''}>ACTIVATED</option>
        <option value="INSTALLED / ICS" ${rowData.status === 'INSTALLED / ICS' ? 'selected' : ''}>INSTALLED / ICS</option>
        <option value="PENDING FOR ACTIVATION" ${rowData.status === 'PENDING FOR ACTIVATION' ? 'selected' : ''}>PENDING FOR ACTIVATION</option>
        <option value="ON HAND" ${rowData.status === 'ON HAND' ? 'selected' : ''}>ON HAND</option>
        <option value="ASSIGN TECH" ${rowData.status === 'ASSIGN TECH' ? 'selected' : ''}>ASSIGN TECH</option>
        <option value="DEFECTIVE" ${rowData.status === 'DEFECTIVE' ? 'selected' : ''}>DEFECTIVE</option>
        <option value="RETURN" ${rowData.status === 'RETURN' ? 'selected' : ''}>RETURN</option>
                <option value="N/A" ${rowData.status === 'N/A' ? 'selected' : ''}>N/A</option>

    </select>
</td>
            <td>
                <span class="realesBy" style="color: ${realesByColor};">${releasedByValue}</span>
            </td>
            <td>
                <button class="editButton" style="background-color: #007bff; color: white; border: none; padding: 5px 10px; cursor: pointer;">Edit</button>
            </td>
        `;

        reportBody.appendChild(row);
    });

    // Event delegation for edit buttons
   reportBody.addEventListener('click', function(e) {
    if (e.target.classList.contains('editButton')) {
        const row = e.target.closest('tr');

        if (e.target.textContent === 'Edit') {
            toggleEditMode(row);
            e.target.textContent = 'Save';
            e.target.style.backgroundColor = 'green';
        } else {
            saveEditedRow(row); // Save to DB
            e.target.textContent = 'Edit';
            e.target.style.backgroundColor = '#007bff';
        }
    }
});
function toggleEditMode(row) {
    row.querySelector('.accountNumber').style.display = 'none';
    row.querySelector('.accountNumberInput').style.display = 'inline-block';

    row.querySelector('.serialNumber').style.display = 'none';
    row.querySelector('.serialNumberInput').style.display = 'inline-block';

    row.querySelector('.status').style.display = 'none';
    row.querySelector('.statusSelect').style.display = 'inline-block';
}

}
function saveEditedRow(row) {
    const transactionId = row.getAttribute('data-id');
    const accountNumber = row.querySelector('.accountNumberInput').value;
    const serialNumber = row.querySelector('.serialNumberInput').value;
    const status = row.querySelector('.statusSelect').value;

    fetch('save_update_report.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            transaction_id: transactionId,
            account_number: accountNumber,
            serial_number: serialNumber,
            status: status
        })
    })
    .then(res => res.json())
    .then(data => {
        console.log('Update response:', data);
        if (data.success) {
            // Update displayed values
            row.querySelector('.accountNumber').textContent = accountNumber;
            row.querySelector('.serialNumber').textContent = serialNumber;
            row.querySelector('.status').textContent = status;
            row.querySelector('.status').style.backgroundColor = getStatusColor(status);

            // Hide inputs again
            row.querySelector('.accountNumber').style.display = 'inline-block';
            row.querySelector('.accountNumberInput').style.display = 'none';
            row.querySelector('.serialNumber').style.display = 'inline-block';
            row.querySelector('.serialNumberInput').style.display = 'none';
            row.querySelector('.status').style.display = 'inline-block';
            row.querySelector('.statusSelect').style.display = 'none';
        } else {
            alert('Failed to update!');
        }
    })
    .catch(err => {
        console.error('Error updating:', err);
        alert('Error occurred.');
    });
}

// Function to get the color based on status
// Function to get the background color based on status
function getStatusColor(status) {
    switch (status) {
        case 'ACTIVATED':
            return '#28a745';  // Green background for ACTIVATED
        case 'INSTALLED / ICS':
            return '#007bff';  // Blue background for INSTALLED / ICS
        case 'PENDING FOR ACTIVATION':
            return '#fd7e14';  // Orange background for PENDING FOR ACTIVATION
        case 'ON HAND':
            return '#6c757d';  // Gray background for ON HAND
        case 'ASSIGN TECH':
            return '#6f42c1';  // Purple background for ASSIGN TECH
        case 'DEFECTIVE':
            return '#dc3545';  // Red background for DEFECTIVE
        case 'RETURN':
            return '#795548';  // Brown background for RETURN
        default:
            return '#343a40';  // Dark default background color
    }
}


// Function to get the color based on realesBy value
function getRealesByColor(realesBy) {
    // Default to black kung wala
    if (!realesBy) return 'black';

    // Generate a simple color from the string
    let hash = 0;
    for (let i = 0; i < realesBy.length; i++) {
        hash = realesBy.charCodeAt(i) + ((hash << 5) - hash);
    }

    // Convert hash to hex color
    let color = '#';
    for (let i = 0; i < 3; i++) {
        let value = (hash >> (i * 8)) & 0xFF;
        color += ('00' + value.toString(16)).substr(-2);
    }

    return color;
}


// Function to save updated data
async function saveTechEdits() {
    const rows = document.querySelectorAll("#techReportBody tr[data-id]");
    const dataToSave = [];

  rows.forEach(row => {
    const transactionId = row.getAttribute('data-id');  // Get the transaction_id from the row
    
    if (!transactionId) {
        console.error('Transaction ID is missing for row:', row); // Log if missing
        return;
    }

    console.log("Transaction ID:", transactionId);  // Should log the correct transaction_id

    const accountNumber = row.querySelector('.accountNumberInput').value;
    const serialNumber = row.querySelector('.serialNumberInput').value;
    const status = row.querySelector('.statusSelect').value;
    const realesBy = row.querySelector('.realesBySelect').value;

    dataToSave.push({
        transaction_id: transactionId,  // Ensure this is correctly passed
        account_number: accountNumber,
        serial_number: serialNumber,
        status: status,
        last_realesby: realesBy
    });
});

    try {
                const response = await fetch('save_update_report.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(dataToSave)
            });

        const result = await response.json();
        
        console.log("Response from server:", result);  // Check the server response

        if (result.success) {
            alert(`Successfully updated ${result.updated_records} records!`);
            // Refresh the data from server
            fetchDataAndUpdateTable();
        } else {
            alert('Error: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to save changes! Check console for details.');
    }
}


// Add this new function to fetch fresh data
async function fetchDataAndUpdateTable() {
    try {
        const response = await fetch('get_technician_report.php');
        const data = await response.json();
        updateTechReport(data);
    } catch (error) {
        console.error("Error fetching data:", error);
    }
}

window.onload = function() {
    // Example: Fetch updated data when the page loads
    fetch('get_technician_report.php', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        updateTechReport(data); // Update your table with fresh data
    })
    .catch(error => {
        console.error("Error fetching data:", error);
    });
};
fetch('save_update_report.php', {
    method: 'POST', // Ensure the method is POST
    body: JSON.stringify(dataToSave), // Send the correct data
    headers: {
        'Content-Type': 'application/json' // Set content-type as JSON
    }
})
.then(result => {
    if (result.success) {
        alert(`Success! ${result.updated_records} records updated. Details:\n${result.debug_summary}`);
        fetchDataAndUpdateTable();
    } else {
        alert('Error: ' + result.error + '\nDebug info: ' + (result.debug_log || 'None'));
    }
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));

async function loadTechnicianReport(event) {
    if (event) event.preventDefault();
    
    const btn = event.target;
    const originalText = btn.textContent;
    
    try {
        // Set loading state
        btn.disabled = true;
        btn.textContent = "Generating...";
        
        const filters = {
            fromDate: document.getElementById('techFromDate').value,
            toDate: document.getElementById('techToDate').value,
            typeFilter: document.getElementById('techTypeFilter').value,
            specificTech: document.getElementById('specificTechFilter').value
        };

        const response = await fetch('get_technician_report.php', {
            method: 'POST',
            body: JSON.stringify(filters),
            headers: { 'Content-Type': 'application/json' }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Unknown server error');
        }

        updateTechReport(result.data);
        
    } catch (error) {
        console.error("Error:", error);
        alert("Error generating report: " + error.message);
    } finally {
        // This will always run, regardless of success/failure
        btn.disabled = false;
        btn.textContent = originalText;
    }
}
function toggleEditMode(row) {
    const isEditing = row.classList.contains('editing');
    
    if (isEditing) {
        // Save changes ONLY for this row
        saveSingleEdit(row);
        row.classList.remove('editing');
    } else {
        // Enter edit mode
        row.classList.add('editing');
    }
    
    // Toggle visibility of elements (inputs and selects for editing)
    const inputs = row.querySelectorAll('.accountNumberInput, .serialNumberInput, .statusSelect, .realesBySelect');
    const displayElements = row.querySelectorAll('.accountNumber, .serialNumber, .status, .realesBy');
    
    inputs.forEach(input => {
        input.style.display = isEditing ? 'none' : 'block'; // Hide inputs if editing
    });
    displayElements.forEach(element => {
        element.style.display = isEditing ? 'block' : 'none'; // Hide text if editing
    });
    
    // Update button text
    const editButton = row.querySelector('.editButton');
    if (editButton) {
        editButton.textContent = isEditing ? 'Save' : 'Edit';
    }
}

async function saveSingleEdit(row) {
    const transactionId = row.getAttribute('data-id');
    
    if (!transactionId) {
        console.error('Transaction ID is missing for row:', row);
        alert('Error: Missing transaction ID');
        return;
    }

    const dataToSave = {
        transaction_id: transactionId,
        account_number: row.querySelector('.accountNumberInput').value,
        serial_number: row.querySelector('.serialNumberInput').value,
        status: row.querySelector('.statusSelect').value,
        last_realesby: row.querySelector('.realesBySelect').value
    };

    // Validate inputs before saving
    if (!validateSerialNumber(dataToSave.serial_number)) {
        alert('Invalid serial number format');
        return;
    }

    try {
        const response = await fetch('save_update_report.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(dataToSave)
        });

        const result = await response.json();
        
        if (result.success) {
            // Update just this row's display (no need to refresh whole table)
            row.querySelector('.accountNumber').textContent = dataToSave.account_number;
            row.querySelector('.serialNumber').textContent = dataToSave.serial_number;
            row.querySelector('.status').textContent = dataToSave.status;
            row.querySelector('.realesBy').textContent = dataToSave.last_realesby;
            
            // Show success message for this row only
            const successMsg = document.createElement('span');
            successMsg.textContent = '✓ Saved';
            successMsg.style.color = 'green';
            successMsg.style.marginLeft = '10px';
            
            const buttonCell = row.querySelector('td:last-child');
            buttonCell.appendChild(successMsg);

            const editButton = row.querySelector('.editButton');
            editButton.disabled = true;
            editButton.textContent = 'Saving...';

            // Re-enable after save completes
            setTimeout(() => {
                successMsg.remove();
                editButton.disabled = false;
                editButton.textContent = 'Edit'; // Reset text after saving
            }, 2000);

        } else {
            alert('Error saving: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to save changes! Check console for details.');
    }
}

function validateSerialNumber(serialNumber) {
    // Implement your validation logic here (e.g., regex or length check)
    return /^[A-Za-z0-9]+$/.test(serialNumber);
}
function toggleEditMode(row) {
    const isEditing = row.classList.contains('editing');
    
    if (isEditing) {
        // Save changes ONLY for this row
        saveSingleEdit(row);
        row.classList.remove('editing');
    } else {
        // Enter edit mode
        row.classList.add('editing');
    }

    // Toggle visibility of elements (inputs and selects for editing)
    const inputs = row.querySelectorAll('.accountNumberInput, .serialNumberInput, .statusSelect, .realesBySelect');
    const displayElements = row.querySelectorAll('.accountNumber, .serialNumber, .status, .realesBy');
    
    inputs.forEach(input => {
        input.style.display = isEditing ? 'none' : 'block'; // Show inputs if editing
    });
    displayElements.forEach(element => {
        element.style.display = isEditing ? 'block' : 'none'; // Show text if not editing
    });

    // Update button text
    const editButton = row.querySelector('.editButton');
    if (editButton) {
        editButton.textContent = isEditing ? 'Save' : 'Edit';
    }
}
//create
document.getElementById("open-create-form").addEventListener("click", () => {
    document.getElementById("create-modal").style.display = "flex";
});

document.querySelector(".close-btn").addEventListener("click", () => {
    document.getElementById("create-modal").style.display = "none";
});

document.getElementById("account-number").addEventListener("change", function () {
    const accountNumber = this.value;
    if (!accountNumber) return;

    fetch('get_transaction_info.php?account_number=' + encodeURIComponent(accountNumber))
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById("serial-number").value = data.serial_number;
                document.getElementById("processed-by").value = data.processed_by;
                document.getElementById("transaction-date").value = data.transaction_date;
            } else {
                alert('Transaction data not found.');
            }
        })
        .catch(() => alert('Error fetching data.'));
});
document.getElementById('serial-number').addEventListener('change', function () {
    const serial = this.value;

    if (serial) {
        fetch(`get_transaction_details.php?serial_number=${encodeURIComponent(serial)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('account-number').value = data.account_number || '';
                    document.getElementById('processed-by').value = data.processed_by || '';
                    document.getElementById('transaction-date').value = data.transaction_date || '';
                } else {
                    alert('No data found for this serial number.');
                }
            })
            .catch(err => console.error('Fetch error:', err));
    }
});
