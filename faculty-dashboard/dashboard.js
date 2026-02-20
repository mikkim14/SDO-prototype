// Dashboard Page Script for Faculty

let foodChart = null;
let transportPieChart = null;

// Initialize the dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initializeDashboard();
    setupDashboardEventListeners();
    updateDashboard();
});

// Initialize dashboard components
function initializeDashboard() {
    const today = new Date().toISOString().split('T')[0];
    const dashboardDateEl = document.getElementById('dashboard-date');
    if (dashboardDateEl) dashboardDateEl.value = today;

    initializeCharts();
}

// Setup all event listeners for dashboard
function setupDashboardEventListeners() {
    const dashboardDateEl = document.getElementById('dashboard-date');
    if (dashboardDateEl) dashboardDateEl.addEventListener('change', updateDashboard);

    const exportBtn = document.getElementById('export-personal');
    if (exportBtn) exportBtn.addEventListener('click', exportPersonalData);
    const clearBtn = document.getElementById('clear-personal-data');
    if (clearBtn) clearBtn.addEventListener('click', clearPersonalData);
}

// Update dashboard with current data
function updateDashboard() {
    const selectedDate = document.getElementById('dashboard-date') ? document.getElementById('dashboard-date').value : null;

    updateKPICards(selectedDate);
    updateCharts();
}

// Update KPI cards
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

// Initialize charts
function initializeCharts() {
    const foodCtxEl = document.getElementById('food-trend-chart');
    const transportCtxEl = document.getElementById('transport-pie-chart');
    if (!foodCtxEl || !transportCtxEl) return;

    const foodCtx = foodCtxEl.getContext('2d');
    const transportCtx = transportCtxEl.getContext('2d');

    foodChart = new Chart(foodCtx, {
        type: 'line',
        data: { labels: [], datasets: [{ label: 'Daily Calories', data: [], borderColor: '#667eea', backgroundColor: 'rgba(102, 126, 234, 0.1)', tension: 0.4, fill: true }] },
        options: { responsive: true, maintainAspectRatio: false }
    });

    transportPieChart = new Chart(transportCtx, {
        type: 'pie',
        data: { labels: [], datasets: [{ data: [], backgroundColor: ['#667eea','#764ba2','#f093fb','#f5576c','#4ecdc4','#44a08d','#96ceb4'] }] },
        options: { responsive: true, maintainAspectRatio: false }
    });
}

// Update charts with current data
function updateCharts() {
    updateFoodTrendChart();
    updateTransportPieChart();
}

function updateFoodTrendChart() {
    if (!foodChart) return;
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

function updateTransportPieChart() {
    if (!transportPieChart) return;
    const modeStats = getTransportModeStats();
    const labels = Object.keys(modeStats);
    const data = labels.map(mode => modeStats[mode].count);
    transportPieChart.data.labels = labels;
    transportPieChart.data.datasets[0].data = data;
    transportPieChart.update();
}

// Export and clear functions
function exportPersonalData() {
    const foodCsv = exportFoodDataCSV();
    const transportCsv = exportTransportDataCSV();

    if (!foodCsv && !transportCsv) {
        showMessage('dashboard-validation-message', 'No data to export!', 'error');
        return;
    }

    let combinedCsv = 'FOOD DATA\n';
    if (foodCsv) combinedCsv += foodCsv + '\n\n';
    combinedCsv += 'TRANSPORT DATA\n';
    if (transportCsv) combinedCsv += transportCsv;

    downloadFile(combinedCsv, 'faculty-personal-data.csv', 'text/csv');
    showMessage('dashboard-validation-message', 'Personal data exported successfully!', 'success');
}

function clearPersonalData() {
    if (confirm('Are you sure you want to clear ALL personal data? This cannot be undone.')) {
        foodData = [];
        transportData = [];
        saveDataToStorage();
        updateDashboard();
        showMessage('dashboard-validation-message', 'All personal data cleared!', 'info');
    }
}

// Utility functions
function showMessage(elementId, message, type) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = message;
        element.className = `validation-msg ${type} show`;
        setTimeout(() => { element.classList.remove('show'); }, 5000);
    }
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