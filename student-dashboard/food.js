// Food Tracking Page Script

// Initialize the food page when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initializeFoodPage();
    setupFoodEventListeners();
    updateFoodList();
});

// Initialize food page components
function initializeFoodPage() {
    // Set default date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('food-date').value = today;
}

// Setup all event listeners for food page
function setupFoodEventListeners() {
    // Food form submission
    document.getElementById('food-input-form').addEventListener('submit', handleFoodSubmission);

    // Filters
    document.getElementById('food-apply-filters').addEventListener('click', filterFoodData);

    // Export and clear buttons
    document.getElementById('export-food').addEventListener('click', exportFoodData);
    document.getElementById('clear-food-data').addEventListener('click', clearFoodData);
}

// Handle food form submission
function handleFoodSubmission(e) {
    e.preventDefault();

    const date = document.getElementById('food-date').value;
    const mealType = document.getElementById('meal-type').value;
    const item = document.getElementById('food-item').value.trim();
    const quantity = parseInt(document.getElementById('food-quantity').value);
    const calories = parseInt(document.getElementById('food-calories').value);

    // Validate
    const errors = validateFoodEntry(date, mealType, item, quantity, calories);
    if (errors.length > 0) {
        showMessage('food-validation-message', 'Validation errors: ' + errors.join('; '), 'error');
        return;
    }

    // Add entry
    foodData.push({ date, mealType, item, quantity, calories });
    saveDataToStorage();

    showMessage('food-validation-message', 'Food entry added successfully!', 'success');

    // Reset form and update display
    e.target.reset();
    document.getElementById('food-date').value = date; // Keep same date
    updateFoodList();
}

// Update food data list
function updateFoodList(filteredData = null) {
    const list = document.getElementById('food-data-list');
    const count = document.getElementById('food-entry-count');
    const dataToShow = filteredData || foodData;

    count.textContent = dataToShow.length;

    list.innerHTML = dataToShow.map((entry, index) =>
        `<li>
            <strong>${entry.date}</strong> | ${entry.mealType} | ${entry.item} (${entry.quantity}g, ${entry.calories} cal)
            <button class="delete-btn" onclick="deleteFoodEntry(${filteredData ? foodData.indexOf(entry) : index})" style="float:right; background:#dc2626; padding:5px 10px; color:white; border:none; border-radius:4px; cursor:pointer;">✕</button>
        </li>`
    ).join('');
}

// Filter food data
function filterFoodData() {
    const filterDate = document.getElementById('food-filter-date').value;
    const filterMeal = document.getElementById('food-filter-meal').value;

    let filtered = foodData;

    if (filterDate) {
        filtered = filtered.filter(entry => entry.date === filterDate);
    }

    if (filterMeal) {
        filtered = filtered.filter(entry => entry.mealType === filterMeal);
    }

    updateFoodList(filtered);
}

// Delete food entry
function deleteFoodEntry(index) {
    if (confirm('Are you sure you want to delete this food entry?')) {
        foodData.splice(index, 1);
        saveDataToStorage();
        updateFoodList();
    }
}

// Export food data
function exportFoodData() {
    const csv = exportFoodDataCSV();
    if (!csv) {
        showMessage('food-validation-message', 'No food data to export!', 'error');
        return;
    }

    downloadFile(csv, 'food-data.csv', 'text/csv');
    showMessage('food-validation-message', 'Food data exported successfully!', 'success');
}

// Clear food data
function clearFoodData() {
    if (confirm('Are you sure you want to clear ALL food data? This cannot be undone.')) {
        foodData = [];
        saveDataToStorage();
        updateFoodList();
        showMessage('food-validation-message', 'All food data cleared!', 'info');
    }
}

// Utility functions
function showMessage(elementId, message, type) {
    const element = document.getElementById(elementId);
    element.textContent = message;
    element.className = `validation-msg ${type} show`;

    setTimeout(() => {
        element.classList.remove('show');
    }, 5000);
}

function downloadFile(content, filename, type) {
    const blob = new Blob([content], { type });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

// Load data from localStorage on page load
loadDataFromStorage();
