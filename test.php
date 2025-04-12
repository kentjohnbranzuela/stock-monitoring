<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reel Number Toggle</title>
    <style>
        .form-group {
            margin-bottom: 1rem;
        }
        #reel-field {
            display: none;
        }
    </style>
</head>
<body>

<form>
    <div class="form-row">
        <div class="form-group">
            <label>Item</label>
            <select name="item_code" id="item-select" required>
                <option value="">Select Item</option>
                <option value="item1" data-description="FOC; DROP CABLE; 2 CORE; FIG8; ORANGE ST">FOC; DROP CABLE; 2 CORE; FIG8; ORANGE ST</option>
                <option value="item2" data-description="CAT5e Ethernet Cable">CAT5e Ethernet Cable</option>
                <option value="item3" data-description="DROP CABLE; BLACK">DROP CABLE; BLACK</option>
            </select>
        </div>

        <div class="form-group" id="reel-field">
            <label>Reel Number</label>
            <input type="text" name="reel_number" id="reel_number_input" placeholder="Enter Reel Number">
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const itemSelect = document.getElementById('item-select');
        const reelField = document.getElementById('reel-field');
        const reelInput = document.getElementById('reel_number_input');

        itemSelect.addEventListener('change', function () {
            const selectedOption = itemSelect.options[itemSelect.selectedIndex];
            const description = selectedOption.getAttribute('data-description')?.toUpperCase() || '';

            if (description.includes('DROP CABLE')) {
                reelField.style.display = 'block';
            } else {
                reelField.style.display = 'none';
                reelInput.value = '';
            }
        });
    });
</script>

</body>
</html>
