// Food Tracking Page Script (faculty copy)

// Initialize the food page when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initializeFoodPage();
    setupFoodEventListeners();
    updateFoodList();
});

function initializeFoodPage() {
    const today = new Date().toISOString().split('T')[0];
    const el = document.getElementById('food-date');
    if (el) el.value = today;
}

function setupFoodEventListeners() {
    const form = document.getElementById('food-input-form');
    if (form) form.addEventListener('submit', handleFoodSubmission);
    const filterBtn = document.getElementById('food-apply-filters');
    if (filterBtn) filterBtn.addEventListener('click', filterFoodData);
    const exportBtn = document.getElementById('export-food');
    if (exportBtn) exportBtn.addEventListener('click', exportFoodData);
    const clearBtn = document.getElementById('clear-food-data');
    if (clearBtn) clearBtn.addEventListener('click', clearFoodData);
}

function handleFoodSubmission(e) {
    e.preventDefault();
    const date = document.getElementById('food-date').value;
    const mealType = document.getElementById('meal-type').value;
    const item = document.getElementById('food-item').value.trim();
    const quantity = parseInt(document.getElementById('food-quantity').value);
    const calories = parseInt(document.getElementById('food-calories').value);

    const errors = validateFoodEntry(date, mealType, item, quantity, calories);
    if (errors.length > 0) { showMessage('food-validation-message', 'Validation errors: ' + errors.join('; '), 'error'); return; }

    foodData.push({ date, mealType, item, quantity, calories });
    saveDataToStorage();
    showMessage('food-validation-message', 'Food entry added successfully!', 'success');
    e.target.reset();
    document.getElementById('food-date').value = date;
    updateFoodList();
}

function updateFoodList(filteredData = null) {
    const list = document.getElementById('food-data-list');
    const count = document.getElementById('food-entry-count');
    const dataToShow = filteredData || foodData;
    if (count) count.textContent = dataToShow.length;
    if (!list) return;
    list.innerHTML = dataToShow.map((entry, index) =>
        `<li>
            <strong>${entry.date}</strong> | ${entry.mealType} | ${entry.item} (${entry.quantity}g, ${entry.calories} cal)
            <button class="delete-btn" onclick="deleteFoodEntry(${filteredData ? foodData.indexOf(entry) : index})" style="float:right; background:#dc2626; padding:5px 10px; color:white; border:none; border-radius:4px; cursor:pointer;">✕</button>
        </li>`
    ).join('');
}

function filterFoodData() {
    const filterDate = document.getElementById('food-filter-date').value;
    const filterMeal = document.getElementById('food-filter-meal').value;
    let filtered = foodData;
    if (filterDate) filtered = filtered.filter(entry => entry.date === filterDate);
    if (filterMeal) filtered = filtered.filter(entry => entry.mealType === filterMeal);
    updateFoodList(filtered);
}

function deleteFoodEntry(index) {
    if (confirm('Are you sure you want to delete this food entry?')) {
        foodData.splice(index, 1);
        saveDataToStorage();
        updateFoodList();
    }
}

function exportFoodData() {
    const csv = exportFoodDataCSV();
    if (!csv) { showMessage('food-validation-message', 'No food data to export!', 'error'); return; }
    downloadFile(csv, 'faculty-food-data.csv', 'text/csv');
    showMessage('food-validation-message', 'Food data exported successfully!', 'success');
}

function clearFoodData() {
    if (confirm('Are you sure you want to clear ALL food data? This cannot be undone.')) {
        foodData = [];
        saveDataToStorage();
        updateFoodList();
        showMessage('food-validation-message', 'All food data cleared!', 'info');
    }
}

function showMessage(elementId, message, type) {
    const element = document.getElementById(elementId);
    if (!element) return;
    element.textContent = message;
    element.className = `validation-msg ${type} show`;
    setTimeout(() => { element.classList.remove('show'); }, 5000);
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

loadDataFromStorage();