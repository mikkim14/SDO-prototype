// Student Personal Dashboard Script
let foodChart = null;
let transportPieChart = null;

// Initialize the dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initializeDashboard();
    setupEventListeners();
    updateDashboard();
});

// Initialize dashboard components
function initializeDashboard() {
    // Set default date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('dashboard-date').value = today;
    document.getElementById('food-date').value = today;
    document.getElementById('transport-date').value = today;

    // Initialize charts
    initializeCharts();
}

// Setup all event listeners
function setupEventListeners() {
    // Food form submission
    document.getElementById('food-input-form').addEventListener('submit', handleFoodSubmission);

    // Transport form submission
    document.getElementById('transport-input-form').addEventListener('submit', handleTransportSubmission);

    // Dashboard date change
    document.getElementById('dashboard-date').addEventListener('change', updateDashboard);

    // Filters
    document.getElementById('food-apply-filters').addEventListener('click', filterFoodData);
    document.getElementById('transport-apply-filters').addEventListener('click', filterTransportData);

    // Export buttons
    document.getElementById('export-personal').addEventListener('click', exportPersonalData);
    document.getElementById('clear-personal-data').addEventListener('click', clearPersonalData);
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
    showMessage('food-validation-message', 'Food entry added successfully!', 'success');

    // Reset form and update display
    e.target.reset();
    document.getElementById('food-date').value = date; // Keep same date
    updateFoodList();
    updateDashboard();
}

// Handle transport form submission
function handleTransportSubmission(e) {
    e.preventDefault();

    const date = document.getElementById('transport-date').value;
    const mode = document.getElementById('transport-mode').value;
    const distance = parseFloat(document.getElementById('transport-distance').value);
    const duration = parseInt(document.getElementById('transport-duration').value);
    const purpose = document.getElementById('transport-purpose').value.trim();

    // Validate
    const errors = validateTransportEntry(date, mode, distance, duration, purpose);
    if (errors.length > 0) {
        showMessage('transport-validation-message', 'Validation errors: ' + errors.join('; '), 'error');
        return;
    }

    // Calculate emissions and add entry
    const emissions = calculateEmissions(mode, distance);
    transportData.push({ date, mode, distance, duration, purpose, emissions });

    showMessage('transport-validation-message', 'Transport entry added successfully!', 'success');

    // Reset form and update display
    e.target.reset();
    document.getElementById('transport-date').value = date; // Keep same date
    updateTransportList();
    updateDashboard();
}

// Update dashboard with current data
function updateDashboard() {
    const selectedDate = document.getElementById('dashboard-date').value;

    // Update KPI cards
    updateKPICards(selectedDate);

    // Update charts
    updateCharts();
}

// Update KPI cards
function updateKPICards(date) {
    const foodSummary = getDailyFoodSummary(date);
    const transportSummary = getDailyTransportSummary(date);
    const weeklySummary = getWeeklySummary();

    // Food card
    document.getElementById('daily-calories').textContent = foodSummary.totalCalories.toLocaleString();
    document.getElementById('daily-weight').textContent = foodSummary.totalWeight + ' g';
    document.getElementById('daily-items').textContent = foodSummary.itemCount;

    // Transport card
    document.getElementById('daily-distance').textContent = transportSummary.totalDistance.toFixed(1);
    document.getElementById('daily-time').textContent = transportSummary.totalTime + ' min';
    document.getElementById('daily-emissions').textContent = transportSummary.totalEmissions.toFixed(3);

    // Weekly card
    document.getElementById('weekly-avg').textContent = weeklySummary.avgCaloriesPerDay;
    document.getElementById('weekly-days').textContent = weeklySummary.daysLogged + '/7';
}

// Initialize charts
function initializeCharts() {
    const foodCtx = document.getElementById('food-trend-chart').getContext('2d');
    const transportCtx = document.getElementById('transport-pie-chart').getContext('2d');

    foodChart = new Chart(foodCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Daily Calories',
                data: [],
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#e2e8f0'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#e2e8f0'
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#e2e8f0'
                    }
                }
            }
        }
    });

    transportPieChart = new Chart(transportCtx, {
        type: 'pie',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#667eea',
                    '#764ba2',
                    '#f093fb',
                    '#f5576c',
                    '#4ecdc4',
                    '#44a08d',
                    '#96ceb4'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: '#e2e8f0'
                    }
                }
            }
        }
    });
}

// Update charts with current data
function updateCharts() {
    updateFoodTrendChart();
    updateTransportPieChart();
}

// Update food trend chart (last 7 days)
function updateFoodTrendChart() {
    const dates = [];
    const calories = [];

    for (let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        const dateStr = date.toISOString().split('T')[0];
        dates.push(dateStr);

        const summary = getDailyFoodSummary(dateStr);
        calories.push(summary.totalCalories);
    }

    foodChart.data.labels = dates;
    foodChart.data.datasets[0].data = calories;
    foodChart.update();
}

// Update transport pie chart
function updateTransportPieChart() {
    const modeStats = getTransportModeStats();
    const labels = Object.keys(modeStats);
    const data = labels.map(mode => modeStats[mode].count);

    transportPieChart.data.labels = labels;
    transportPieChart.data.datasets[0].data = data;
    transportPieChart.update();
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

// Update transport data list
function updateTransportList(filteredData = null) {
    const list = document.getElementById('transport-data-list');
    const count = document.getElementById('transport-entry-count');
    const dataToShow = filteredData || transportData;

    count.textContent = dataToShow.length;

    list.innerHTML = dataToShow.map((entry, index) =>
        `<li>
            <strong>${entry.date}</strong> | ${entry.mode} | ${entry.distance}km (${entry.duration}min) | ${entry.purpose} | ${entry.emissions}kg CO₂
            <button class="delete-btn" onclick="deleteTransportEntry(${filteredData ? transportData.indexOf(entry) : index})" style="float:right; background:#dc2626; padding:5px 10px; color:white; border:none; border-radius:4px; cursor:pointer;">✕</button>
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

// Filter transport data
function filterTransportData() {
    const filterDate = document.getElementById('transport-filter-date').value;
    const filterMode = document.getElementById('transport-filter-mode').value;

    let filtered = transportData;

    if (filterDate) {
        filtered = filtered.filter(entry => entry.date === filterDate);
    }

    if (filterMode) {
        filtered = filtered.filter(entry => entry.mode === filterMode);
    }

    updateTransportList(filtered);
}

// Delete functions
function deleteFoodEntry(index) {
    if (confirm('Are you sure you want to delete this food entry?')) {
        foodData.splice(index, 1);
        updateFoodList();
        updateDashboard();
    }
}

function deleteTransportEntry(index) {
    if (confirm('Are you sure you want to delete this transport entry?')) {
        transportData.splice(index, 1);
        updateTransportList();
        updateDashboard();
    }
}

// Export personal data
function exportPersonalData() {
    const foodCsv = exportFoodData();
    const transportCsv = exportTransportData();

    if (!foodCsv && !transportCsv) {
        showMessage('food-validation-message', 'No data to export!', 'error');
        return;
    }

    let combinedCsv = 'FOOD DATA\n';
    if (foodCsv) {
        combinedCsv += foodCsv + '\n\n';
    }

    combinedCsv += 'TRANSPORT DATA\n';
    if (transportCsv) {
        combinedCsv += transportCsv;
    }

    downloadFile(combinedCsv, 'student-personal-data.csv', 'text/csv');
    showMessage('food-validation-message', 'Personal data exported successfully!', 'success');
}

// Clear all personal data
function clearPersonalData() {
    if (confirm('Are you sure you want to clear ALL personal data? This cannot be undone.')) {
        foodData = [];
        transportData = [];
        updateFoodList();
        updateTransportList();
        updateDashboard();
        showMessage('food-validation-message', 'All personal data cleared!', 'info');
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

// Load sample data on page load (optional)
loadSampleData();
updateFoodList();
updateTransportList();
