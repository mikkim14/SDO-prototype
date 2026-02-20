// Dashboard with Descriptive Analytics
let filteredData = [];
let originalData = [];

// Initialize the dashboard on page load
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOMContentLoaded event fired');
    loadEmissionsData();
    setupEventListeners();
    updateDashboard();
    console.log('Dashboard initialization complete');
});

if (document.readyState === 'loading') {
} else {
    setTimeout(() => {
        if (originalData.length === 0) {
            loadEmissionsData();
            setupEventListeners();
            updateDashboard();
        }
    }, 100);
}

function loadEmissionsData() {
    try {
        if (typeof emissionsData !== 'undefined' && emissionsData && emissionsData.length > 0) {
            originalData = JSON.parse(JSON.stringify(emissionsData));
            filteredData = JSON.parse(JSON.stringify(originalData));
            console.log('✓ Emissions data loaded successfully. Total records:', originalData.length);
        } else {
            console.error('✗ emissionsData is not defined or empty');
            originalData = [];
            filteredData = [];
        }
    } catch (error) {
        console.error('Error loading emissions data:', error);
        originalData = [];
        filteredData = [];
    }
}

// Setup event listeners for filters
function setupEventListeners() {
    document.getElementById('apply-filters-btn').addEventListener('click', applyFilters);
    document.getElementById('reset-filters-btn').addEventListener('click', resetFilters);
}

function getCategoryTotal(category) {
    return filteredData
        .filter(item => item.category === category)
        .reduce((sum, item) => sum + item.value, 0);
}

// Apply filters
function applyFilters() {
    const scopeFilter = document.getElementById('scope-filter').value;
    const yearFilter = document.getElementById('year-filter').value;
    const campusFilter = document.getElementById('campus-filter').value;

    filteredData = originalData.filter(item => {
        let scopeMatch = !scopeFilter || item.scope === scopeFilter;
        let yearMatch = !yearFilter || item.year === parseInt(yearFilter);
        let campusMatch = !campusFilter || item.campus === campusFilter;
        return scopeMatch && yearMatch && campusMatch;
    });

    updateDashboard();
    console.log('Applied filters. Filtered data:', filteredData.length);
}

// Reset filters
function resetFilters() {
    document.getElementById('scope-filter').value = '';
    document.getElementById('year-filter').value = '';
    document.getElementById('campus-filter').value = '';
    filteredData = JSON.parse(JSON.stringify(originalData));
    updateDashboard();
}

function updateDashboard() {
    console.log('updateDashboard called. filteredData length:', filteredData.length, 'originalData length:', originalData.length);
    
    if (filteredData.length === 0 && originalData.length > 0) {
        filteredData = JSON.parse(JSON.stringify(originalData));
        console.log('Populated filteredData from originalData');
    }
    
    if (filteredData.length === 0) {
        console.warn('No data available to display');
        displayEmptyState();
        return;
    }

    // Extract values for calculations
    const values = filteredData.map(item => item.value);
    console.log('Processing', values.length, 'values. Total:', values.reduce((a, b) => a + b, 0));

    // Calculate descriptive statistics
    const stats = {
        total: values.reduce((a, b) => a + b, 0),
        average: values.reduce((a, b) => a + b, 0) / values.length,
        min: Math.min(...values),
        max: Math.max(...values),
        count: values.length,
        stdDev: calculateStdDev(values)
    };

    // Calculate category totals
    const categoryTotals = {
        electricity: getCategoryTotal('Electricity'),
        fuel: getCategoryTotal('Fuel'),
        lpg: getCategoryTotal('LPG'),
        water: getCategoryTotal('Water'),
        waste: getCategoryTotal('Waste'),
        travel: getCategoryTotal('Travel'),
        food: getCategoryTotal('Food')
    };

    // Total Emissions with category breakdown
    document.getElementById('total-emissions').textContent = formatNumber(stats.total);
    document.getElementById('electricity-value').textContent = formatNumber(categoryTotals.electricity) + ' kg CO₂e';
    document.getElementById('fuel-value').textContent = formatNumber(categoryTotals.fuel) + ' kg CO₂e';
    document.getElementById('lpg-value').textContent = formatNumber(categoryTotals.lpg) + ' kg CO₂e';
    document.getElementById('water-value').textContent = formatNumber(categoryTotals.water) + ' kg CO₂e';
    document.getElementById('treated-water-value').textContent = formatNumber(categoryTotals.water * 0.15) + ' kg CO₂e';
    document.getElementById('waste-value').textContent = formatNumber(categoryTotals.waste) + ' kg CO₂e';
    document.getElementById('flight-value').textContent = formatNumber(categoryTotals.travel) + ' kg CO₂e';
    document.getElementById('accommodation-value').textContent = formatNumber(categoryTotals.travel * 0.25) + ' kg CO₂e';
    document.getElementById('food-value').textContent = formatNumber(categoryTotals.food) + ' kg CO₂e';

    // Metric Tons
    const metricTons = stats.total / 1000;
    document.getElementById('metric-tons').textContent = formatNumber(metricTons, 3);
    document.getElementById('data-points').textContent = stats.count;
    document.getElementById('avg-per-record').textContent = formatNumber(stats.average, 2) + ' kg';

    // Tree Offset
    const treesNeeded = Math.ceil(stats.total / 21);
    document.getElementById('trees-needed').textContent = treesNeeded.toLocaleString();

    // Generate charts
    generateTrendChart();
    generateCategoryChart();
    generateCompositionChart();
    generatePeriodComparisonTable();
}

// empty state
function displayEmptyState() {
    document.getElementById('total-emissions').textContent = '0.00';
    document.getElementById('electricity-value').textContent = '0.00 kg CO₂e';
    document.getElementById('fuel-value').textContent = '0.00 kg CO₂e';
    document.getElementById('lpg-value').textContent = '0.00 kg CO₂e';
    document.getElementById('water-value').textContent = '0.00 kg CO₂e';
    document.getElementById('treated-water-value').textContent = '0.00 kg CO₂e';
    document.getElementById('waste-value').textContent = '0.00 kg CO₂e';
    document.getElementById('flight-value').textContent = '0.00 kg CO₂e';
    document.getElementById('accommodation-value').textContent = '0.00 kg CO₂e';
    document.getElementById('food-value').textContent = '0.00 kg CO₂e';
    
    document.getElementById('metric-tons').textContent = '0.000';
    document.getElementById('data-points').textContent = '0';
    document.getElementById('avg-per-record').textContent = '0.00 kg';
    
    document.getElementById('trees-needed').textContent = '0';
    
    // Clear tables
    const tbody = document.querySelector('#period-comparison-table tbody');
    if (tbody) tbody.innerHTML = '';
}

// Calculate standard deviation
function calculateStdDev(values) {
    if (values.length === 0) return 0;
    const mean = values.reduce((a, b) => a + b) / values.length;
    const squaredDifferences = values.map(val => Math.pow(val - mean, 2));
    const avgSquaredDifference = squaredDifferences.reduce((a, b) => a + b) / values.length;
    return Math.sqrt(avgSquaredDifference);
}

// trend chart
function generateTrendChart() {
    const groupedByYear = {};
    
    filteredData.forEach(item => {
        if (!groupedByYear[item.year]) {
            groupedByYear[item.year] = [];
        }
        groupedByYear[item.year].push(item.value);
    });

    const years = Object.keys(groupedByYear).sort();
    const totals = years.map(year => 
        groupedByYear[year].reduce((a, b) => a + b, 0)
    );

    const ctx = document.getElementById('dashboard-trend');
    if (!ctx) return;

    if (window.trendChart) {
        window.trendChart.destroy();
    }

    window.trendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: years,
            datasets: [{
                label: 'Total Emissions (kg CO2e)',
                data: totals,
                borderColor: '#FF6B6B',
                backgroundColor: 'rgba(255, 107, 107, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointBackgroundColor: '#FF6B6B'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: true }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

// category breakdown chart
function generateCategoryChart() {
    const groupedByCategory = {};
    
    filteredData.forEach(item => {
        if (!groupedByCategory[item.category]) {
            groupedByCategory[item.category] = 0;
        }
        groupedByCategory[item.category] += item.value;
    });

    const categories = Object.keys(groupedByCategory);
    const values = categories.map(cat => groupedByCategory[cat]);

    const ctx = document.getElementById('dashboard-industry');
    if (!ctx) return;

    if (window.categoryChart) {
        window.categoryChart.destroy();
    }

    window.categoryChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: categories,
            datasets: [{
                label: 'Emissions by Category (kg CO2e)',
                data: values,
                backgroundColor: [
                    '#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F', '#BB8FCE'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

//pie chart
function generateCompositionChart() {
    const groupedByCategory = {};
    
    filteredData.forEach(item => {
        if (!groupedByCategory[item.category]) {
            groupedByCategory[item.category] = 0;
        }
        groupedByCategory[item.category] += item.value;
    });

    const categories = Object.keys(groupedByCategory);
    const values = categories.map(cat => groupedByCategory[cat]);

    const ctx = document.getElementById('dashboard-gas-pie');
    if (!ctx) return;

    if (window.compositionChart) {
        window.compositionChart.destroy();
    }

    window.compositionChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: categories,
            datasets: [{
                data: values,
                backgroundColor: [
                    '#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F', '#BB8FCE'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

// number utility
function formatNumber(value, decimals = 2) {
    if (value === null || value === undefined || isNaN(value)) return '--';
    return Number(value).toLocaleString(undefined, { 
        minimumFractionDigits: decimals, 
        maximumFractionDigits: decimals 
    });
}

// period comparison table
function generatePeriodComparisonTable() {
    const groupedByYear = {};
    
    originalData.forEach(item => {
        if (!groupedByYear[item.year]) {
            groupedByYear[item.year] = 0;
        }
        groupedByYear[item.year] += item.value;
    });

    const years = Object.keys(groupedByYear).sort((a, b) => a - b);
    const tbody = document.querySelector('#period-comparison-table tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    years.forEach((year, idx) => {
        const value = groupedByYear[year];
        const prevValue = idx > 0 ? groupedByYear[years[idx - 1]] : null;
        const yoyChange = prevValue ? ((value - prevValue) / prevValue * 100) : 0;
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${year}</td>
            <td>${formatNumber(value, 0)}</td>
            <td>${yoyChange > 0 ? '+' : ''}${formatNumber(yoyChange, 2)}%</td>
            <td>--</td>
        `;
        tbody.appendChild(row);
    });
}

