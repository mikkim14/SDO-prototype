// Dashboard Page Script

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
    // Set default date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('dashboard-date').value = today;

    // Initialize charts
    initializeCharts();
}

// Setup all event listeners for dashboard
function setupDashboardEventListeners() {
    // Dashboard date change
    document.getElementById('dashboard-date').addEventListener('change', updateDashboard);

    // Export and clear buttons
    document.getElementById('export-personal').addEventListener('click', exportPersonalData);
    document.getElementById('clear-personal-data').addEventListener('click', clearPersonalData);
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

// Export personal data
function exportPersonalData() {
    const foodCsv = exportFoodDataCSV();
    const transportCsv = exportTransportDataCSV();

    if (!foodCsv && !transportCsv) {
        showMessage('dashboard-validation-message', 'No data to export!', 'error');
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
    showMessage('dashboard-validation-message', 'Personal data exported successfully!', 'success');
}

// Clear all personal data
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

        setTimeout(() => {
            element.classList.remove('show');
        }, 5000);
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
