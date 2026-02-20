let data = [];
let chartInstances = {};
let forecastModel = null;
let modelMetrics = {};

// Number formatting helper
function formatNumber(value, decimals = 2) {
    if (value === null || value === undefined || isNaN(value)) return '--';
    return Number(value).toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
}

// ============= DATA MANAGEMENT =============
// Wait for DOM to be ready before adding event listeners
document.addEventListener('DOMContentLoaded', () => {
    // Only setup data management if using this system (not dashboard.js emissions data)
    const loadSampleBtn = document.getElementById('load-sample');
    const manualInput = document.getElementById('manual-input');
    const applyFiltersBtn = document.getElementById('apply-filters');
    const exportCsvBtn = document.getElementById('export-csv');
    const exportJsonBtn = document.getElementById('export-json');
    const clearDataBtn = document.getElementById('clear-data');
    
    if (loadSampleBtn) {
        loadSampleBtn.addEventListener('click', () => {
            data = JSON.parse(JSON.stringify(sampleData || []));
            showMessage('Sample data loaded successfully!', 'success');
            updateDataList();
        });
    }

    if (manualInput) {
        manualInput.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const year = parseInt(document.getElementById('year').value);
            const month = document.getElementById('month').value ? parseInt(document.getElementById('month').value) : null;
            const emission = parseFloat(document.getElementById('emission').value);
            const category = document.getElementById('category').value;
            const source = document.getElementById('source').value || 'Unknown';
            
            // Validate
            const errors = validateDataEntry(year, emission, category);
            if (errors.length > 0) {
                showMessage('Validation errors: ' + errors.join('; '), 'error');
                return;
            }
            
            data.push({ year, month: month || undefined, emission, category, source });
            showMessage('Data entry added successfully!', 'success');
            updateDataList();
            e.target.reset();
        });
    }

    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', () => {
            const filterCategory = document.getElementById('filter-category').value.toLowerCase();
            const filterYear = document.getElementById('filter-year').value;
            
            let filtered = data;
            if (filterCategory) filtered = filtered.filter(d => d.category.toLowerCase().includes(filterCategory));
            if (filterYear) filtered = filtered.filter(d => d.year === parseInt(filterYear));
            
            displayFilteredData(filtered);
        });
    }

    // Export buttons
    if (exportCsvBtn) {
        exportCsvBtn.addEventListener('click', () => {
            if (data.length === 0) {
                showMessage('No data to export!', 'warning');
                return;
            }
            
            let csv = 'Year,Month,Consumption (kg),Category,Source\n';
            data.forEach(d => {
                csv += `${d.year},${d.month || ''},${d.emission},${d.category},${d.source}\n`;
            });
            
            downloadFile(csv, 'consumption-data.csv', 'text/csv');
        });
    }

    if (exportJsonBtn) {
        exportJsonBtn.addEventListener('click', () => {
            if (data.length === 0) {
                showMessage('No data to export!', 'warning');
                return;
            }
            
            const enrichedData = data.map(d => ({
                ...d,
                emission_kg: d.emission
            }));
            
            const json = JSON.stringify(enrichedData, null, 2);
            downloadFile(json, 'consumption-data.json', 'application/json');
        });
    }

    if (clearDataBtn) {
        clearDataBtn.addEventListener('click', () => {
            if (confirm('Are you sure you want to clear all data? This cannot be undone.')) {
                data = [];
                updateDataList();
                showMessage('All data cleared!', 'info');
            }
        });
    }
}); // Close DOMContentLoaded event

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
    showMessage(`${filename} exported successfully!`, 'success');
}

function displayFilteredData(filteredData) {
    const list = document.getElementById('data-list');
    if (!list) return;
    list.innerHTML = filteredData.map((d, idx) => 
        `<li>
            <strong>${d.year}${d.month ? '-' + d.month : ''}</strong> | 
            ${d.emission.toFixed(2)} kg | 
            ${d.category} from ${d.source}
            <button class="delete-btn" onclick="deleteDataPoint(${data.indexOf(d)})" style="float:right; background:#dc2626; padding:5px 10px; color:white; border:none; border-radius:4px; cursor:pointer;">✕</button>
        </li>`
    ).join('');
}

function updateDataList() {
    const entryCount = document.getElementById('entry-count');
    if (!entryCount) return;
    entryCount.textContent = data.length;
    displayFilteredData(data);
}

function deleteDataPoint(index) {
    data.splice(index, 1);
    updateDataList();
}
