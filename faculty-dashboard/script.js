// Faculty dashboard combined script (copied from student version)
let foodChart = null;
let transportPieChart = null;

document.addEventListener('DOMContentLoaded', () => {
    initializeDashboard();
    setupEventListeners();
    updateDashboard();
});

function initializeDashboard() {
    const today = new Date().toISOString().split('T')[0];
    const dashboardDate = document.getElementById('dashboard-date');
    if (dashboardDate) dashboardDate.value = today;
    const foodDate = document.getElementById('food-date'); if (foodDate) foodDate.value = today;
    const transportDate = document.getElementById('transport-date'); if (transportDate) transportDate.value = today;
    initializeCharts();
}

function setupEventListeners() {
    const foodForm = document.getElementById('food-input-form'); if (foodForm) foodForm.addEventListener('submit', handleFoodSubmission);
    const transportForm = document.getElementById('transport-input-form'); if (transportForm) transportForm.addEventListener('submit', handleTransportSubmission);
    const dashboardDate = document.getElementById('dashboard-date'); if (dashboardDate) dashboardDate.addEventListener('change', updateDashboard);
    const foodFilter = document.getElementById('food-apply-filters'); if (foodFilter) foodFilter.addEventListener('click', filterFoodData);
    const transportFilter = document.getElementById('transport-apply-filters'); if (transportFilter) transportFilter.addEventListener('click', filterTransportData);
    const exportBtn = document.getElementById('export-personal'); if (exportBtn) exportBtn.addEventListener('click', exportPersonalData);
    const clearBtn = document.getElementById('clear-personal-data'); if (clearBtn) clearBtn.addEventListener('click', clearPersonalData);
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
    showMessage('food-validation-message', 'Food entry added successfully!', 'success');
    e.target.reset(); if (document.getElementById('food-date')) document.getElementById('food-date').value = date;
    updateFoodList(); updateDashboard();
}

function handleTransportSubmission(e) {
    e.preventDefault();
    const date = document.getElementById('transport-date').value;
    const mode = document.getElementById('transport-mode').value;
    const distance = parseFloat(document.getElementById('transport-distance').value);
    const duration = parseInt(document.getElementById('transport-duration').value);
    const purpose = document.getElementById('transport-purpose').value.trim();
    const errors = validateTransportEntry(date, mode, distance, duration, purpose);
    if (errors.length > 0) { showMessage('transport-validation-message', 'Validation errors: ' + errors.join('; '), 'error'); return; }
    const emissions = calculateEmissions(mode, distance);
    transportData.push({ date, mode, distance, duration, purpose, emissions });
    showMessage('transport-validation-message', 'Transport entry added successfully!', 'success');
    e.target.reset(); if (document.getElementById('transport-date')) document.getElementById('transport-date').value = date;
    updateTransportList(); updateDashboard();
}

function updateDashboard() {
    const selectedDate = document.getElementById('dashboard-date') ? document.getElementById('dashboard-date').value : null;
    updateKPICards(selectedDate);
    updateCharts();
}

function updateKPICards(date) {
    const foodSummary = getDailyFoodSummary(date);
    const transportSummary = getDailyTransportSummary(date);
    const weeklySummary = getWeeklySummary();
    if (document.getElementById('daily-calories')) document.getElementById('daily-calories').textContent = foodSummary.totalCalories.toLocaleString();
    if (document.getElementById('daily-weight')) document.getElementById('daily-weight').textContent = foodSummary.totalWeight + ' g';
    if (document.getElementById('daily-items')) document.getElementById('daily-items').textContent = foodSummary.itemCount;
    if (document.getElementById('daily-distance')) document.getElementById('daily-distance').textContent = transportSummary.totalDistance.toFixed(1);
    if (document.getElementById('daily-time')) document.getElementById('daily-time').textContent = transportSummary.totalTime + ' min';
    if (document.getElementById('daily-emissions')) document.getElementById('daily-emissions').textContent = transportSummary.totalEmissions.toFixed(3);
    if (document.getElementById('weekly-avg')) document.getElementById('weekly-avg').textContent = weeklySummary.avgCaloriesPerDay;
    if (document.getElementById('weekly-days')) document.getElementById('weekly-days').textContent = weeklySummary.daysLogged + '/7';
}

function initializeCharts() {
    const foodCtxEl = document.getElementById('food-trend-chart');
    const transportCtxEl = document.getElementById('transport-pie-chart');
    if (!foodCtxEl || !transportCtxEl) return;
    const foodCtx = foodCtxEl.getContext('2d');
    const transportCtx = transportCtxEl.getContext('2d');
    foodChart = new Chart(foodCtx, { type: 'line', data: { labels: [], datasets: [{ label: 'Daily Calories', data: [], borderColor: '#667eea', backgroundColor: 'rgba(102, 126, 234, 0.1)', tension: 0.4, fill: true }] }, options: { responsive: true, maintainAspectRatio: false } });
    transportPieChart = new Chart(transportCtx, { type: 'pie', data: { labels: [], datasets: [{ data: [], backgroundColor: ['#667eea','#764ba2','#f093fb','#f5576c','#4ecdc4','#44a08d','#96ceb4'] }] }, options: { responsive: true, maintainAspectRatio: false } });
}

function updateCharts() { updateFoodTrendChart(); updateTransportPieChart(); }

function updateFoodTrendChart() {
    if (!foodChart) return;
    const dates = [];
    const calories = [];
    for (let i = 6; i >= 0; i--) {
        const date = new Date(); date.setDate(date.getDate() - i); const dateStr = date.toISOString().split('T')[0]; dates.push(dateStr); const summary = getDailyFoodSummary(dateStr); calories.push(summary.totalCalories);
    }
    foodChart.data.labels = dates; foodChart.data.datasets[0].data = calories; foodChart.update();
}

function updateTransportPieChart() {
    if (!transportPieChart) return;
    const modeStats = getTransportModeStats(); const labels = Object.keys(modeStats); const data = labels.map(mode => modeStats[mode].count);
    transportPieChart.data.labels = labels; transportPieChart.data.datasets[0].data = data; transportPieChart.update();
}

function updateFoodList(filteredData = null) {
    const list = document.getElementById('food-data-list'); const count = document.getElementById('food-entry-count'); const dataToShow = filteredData || foodData; if (count) count.textContent = dataToShow.length; if (!list) return; list.innerHTML = dataToShow.map((entry, index) => `<li><strong>${entry.date}</strong> | ${entry.mealType} | ${entry.item} (${entry.quantity}g, ${entry.calories} cal)<button class="delete-btn" onclick="deleteFoodEntry(${filteredData ? foodData.indexOf(entry) : index})" style="float:right; background:#dc2626; padding:5px 10px; color:white; border:none; border-radius:4px; cursor:pointer;">✕</button></li>`).join(''); }

function updateTransportList(filteredData = null) {
    const list = document.getElementById('transport-data-list'); const count = document.getElementById('transport-entry-count'); const dataToShow = filteredData || transportData; if (count) count.textContent = dataToShow.length; if (!list) return; list.innerHTML = dataToShow.map((entry, index) => `<li><strong>${entry.date}</strong> | ${entry.mode} | ${entry.distance}km (${entry.duration}min) | ${entry.purpose} | ${entry.emissions}kg CO₂<button class="delete-btn" onclick="deleteTransportEntry(${filteredData ? transportData.indexOf(entry) : index})" style="float:right; background:#dc2626; padding:5px 10px; color:white; border:none; border-radius:4px; cursor:pointer;">✕</button></li>`).join(''); }

function filterFoodData() { const filterDate = document.getElementById('food-filter-date') ? document.getElementById('food-filter-date').value : null; const filterMeal = document.getElementById('food-filter-meal') ? document.getElementById('food-filter-meal').value : null; let filtered = foodData; if (filterDate) filtered = filtered.filter(entry => entry.date === filterDate); if (filterMeal) filtered = filtered.filter(entry => entry.mealType === filterMeal); updateFoodList(filtered); }

function filterTransportData() { const filterDate = document.getElementById('transport-filter-date') ? document.getElementById('transport-filter-date').value : null; const filterMode = document.getElementById('transport-filter-mode') ? document.getElementById('transport-filter-mode').value : null; let filtered = transportData; if (filterDate) filtered = filtered.filter(entry => entry.date === filterDate); if (filterMode) filtered = filtered.filter(entry => entry.mode === filterMode); updateTransportList(filtered); }

function deleteFoodEntry(index) { if (confirm('Are you sure you want to delete this food entry?')) { foodData.splice(index, 1); updateFoodList(); updateDashboard(); saveDataToStorage(); } }

function deleteTransportEntry(index) { if (confirm('Are you sure you want to delete this transport entry?')) { transportData.splice(index, 1); updateTransportList(); updateDashboard(); saveDataToStorage(); } }

function exportPersonalData() { const foodCsv = exportFoodDataCSV(); const transportCsv = exportTransportDataCSV(); if (!foodCsv && !transportCsv) { showMessage('dashboard-validation-message', 'No data to export!', 'error'); return; } let combinedCsv = 'FOOD DATA\n'; if (foodCsv) combinedCsv += foodCsv + '\n\n'; combinedCsv += 'TRANSPORT DATA\n'; if (transportCsv) combinedCsv += transportCsv; downloadFile(combinedCsv, 'faculty-personal-data.csv', 'text/csv'); showMessage('dashboard-validation-message', 'Personal data exported successfully!', 'success'); }

function clearPersonalData() { if (confirm('Are you sure you want to clear ALL personal data? This cannot be undone.')) { foodData = []; transportData = []; updateFoodList(); updateTransportList(); updateDashboard(); saveDataToStorage(); showMessage('dashboard-validation-message', 'All personal data cleared!', 'info'); } }

function showMessage(elementId, message, type) { const element = document.getElementById(elementId); if (!element) return; element.textContent = message; element.className = `validation-msg ${type} show`; setTimeout(() => { element.classList.remove('show'); }, 5000); }

function downloadFile(content, filename, type) { const blob = new Blob([content], { type }); const url = URL.createObjectURL(blob); const link = document.createElement('a'); link.href = url; link.download = filename; document.body.appendChild(link); link.click(); document.body.removeChild(link); URL.revokeObjectURL(url); }

// Load sample data on page load (faculty uses local storage keys set in data.js)
loadDataFromStorage();
updateFoodList();
updateTransportList();