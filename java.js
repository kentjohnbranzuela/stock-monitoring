document.addEventListener('DOMContentLoaded', function() {
    const itemSelect = document.getElementById('item-select');
    const reelField = document.getElementById('reel-field');
    const reelInput = document.getElementById('reel_number_input');

    // Debugging log
    console.log('Script loaded successfully.');

    itemSelect.addEventListener('change', function() {
        const selectedOption = itemSelect.options[itemSelect.selectedIndex];
        const description = selectedOption.getAttribute('data-description')?.toUpperCase() || '';

        // Debug: Log the description of the selected item
        console.log('Selected Item Description:', description);

        // Show reel number field if "DROP CABLE" is selected
        if (description.includes('DROP CABLE')) {
            reelField.style.display = 'block';
            console.log('Reel Number Field Displayed.');
        } else {
            reelField.style.display = 'none';
            reelInput.value = ''; // Clear the value if hidden
            console.log('Reel Number Field Hidden.');
        }
    });
});
