// Transport Tracking Page Script

// Initialize the transport page when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initializeTransportPage();
    setupTransportEventListeners();
    updateTransportList();
});

// Initialize transport page components
function initializeTransportPage() {
    // Set default date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('transport-date').value = today;
}

// Setup all event listeners for transport page
function setupTransportEventListeners() {
    // Transport form submission
    document.getElementById('transport-input-form').addEventListener('submit', handleTransportSubmission);

    // Filters
    document.getElementById('transport-apply-filters').addEventListener('click', filterTransportData);

    // Export and clear buttons
    document.getElementById('export-transport').addEventListener('click', exportTransportData);
    document.getElementById('clear-transport-data').addEventListener('click', clearTransportData);
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
    const emissions = calculateEmissions(mode, duration);
    transportData.push({ date, mode, distance, duration, purpose, emissions });
    saveDataToStorage();

    showMessage('transport-validation-message', 'Transport entry added successfully!', 'success');

    // Reset form and update display
    e.target.reset();
    document.getElementById('transport-date').value = date; // Keep same date
    updateTransportList();
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

// Delete transport entry
function deleteTransportEntry(index) {
    if (confirm('Are you sure you want to delete this transport entry?')) {
        transportData.splice(index, 1);
        saveDataToStorage();
        updateTransportList();
    }
}

// Export transport data
function exportTransportData() {
    const csv = exportTransportDataCSV();
    if (!csv) {
        showMessage('transport-validation-message', 'No transport data to export!', 'error');
        return;
    }

    downloadFile(csv, 'transport-data.csv', 'text/csv');
    showMessage('transport-validation-message', 'Transport data exported successfully!', 'success');
}

// Clear transport data
function clearTransportData() {
    if (confirm('Are you sure you want to clear ALL transport data? This cannot be undone.')) {
        transportData = [];
        saveDataToStorage();
        updateTransportList();
        showMessage('transport-validation-message', 'All transport data cleared!', 'info');
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
