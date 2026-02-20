// Transport calculation for non-faculty dashboard
// Calculates transportation based on minutes
// Transport Tracking Page Script (non-faculty)

document.addEventListener('DOMContentLoaded', () => {
    initializeTransportPage();
    setupTransportEventListeners();
    updateTransportList();
});

function initializeTransportPage() {
    const today = new Date().toISOString().split('T')[0];
    const el = document.getElementById('transport-date');
    if (el) el.value = today;
}

function setupTransportEventListeners() {
    const form = document.getElementById('transport-input-form');
    if (form) form.addEventListener('submit', handleTransportSubmission);
    const filterBtn = document.getElementById('transport-apply-filters');
    if (filterBtn) filterBtn.addEventListener('click', filterTransportData);
    const exportBtn = document.getElementById('export-transport');
    if (exportBtn) exportBtn.addEventListener('click', exportTransportData);
    const clearBtn = document.getElementById('clear-transport-data');
    if (clearBtn) clearBtn.addEventListener('click', clearTransportData);
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

    const emissions = calculateEmissions(mode, duration);
    transportData.push({ date, mode, distance, duration, purpose, emissions });
    saveDataToStorage();
    showMessage('transport-validation-message', 'Transport entry added successfully!', 'success');
    e.target.reset();
    document.getElementById('transport-date').value = date;
    updateTransportList();
}

function updateTransportList(filteredData = null) {
    const list = document.getElementById('transport-data-list');
    const count = document.getElementById('transport-entry-count');
    const dataToShow = filteredData || transportData;
    if (count) count.textContent = dataToShow.length;
    if (!list) return;
    list.innerHTML = dataToShow.map((entry, index) =>
        `<li>
            <strong>${entry.date}</strong> | ${entry.mode} | ${entry.distance}km (${entry.duration}min) | ${entry.purpose} | ${entry.emissions}kg CO₂
            <button class="delete-btn" onclick="deleteTransportEntry(${filteredData ? transportData.indexOf(entry) : index})" style="float:right; background:#dc2626; padding:5px 10px; color:white; border:none; border-radius:4px; cursor:pointer;">✕</button>
        </li>`
    ).join('');
}

function filterTransportData() {
    const filterDate = document.getElementById('transport-filter-date').value;
    const filterMode = document.getElementById('transport-filter-mode').value;
    let filtered = transportData;
    if (filterDate) filtered = filtered.filter(entry => entry.date === filterDate);
    if (filterMode) filtered = filtered.filter(entry => entry.mode === filterMode);
    updateTransportList(filtered);
}

function deleteTransportEntry(index) {
    if (confirm('Are you sure you want to delete this transport entry?')) {
        transportData.splice(index, 1);
        saveDataToStorage();
        updateTransportList();
    }
}

function exportTransportData() {
    const csv = exportTransportDataCSV();
    if (!csv) { showMessage('transport-validation-message', 'No transport data to export!', 'error'); return; }
    downloadFile(csv, 'nonfaculty-transport-data.csv', 'text/csv');
    showMessage('transport-validation-message', 'Transport data exported successfully!', 'success');
}

function clearTransportData() {
    if (confirm('Are you sure you want to clear ALL transport data? This cannot be undone.')) {
        transportData = [];
        saveDataToStorage();
        updateTransportList();
        showMessage('transport-validation-message', 'All transport data cleared!', 'info');
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

window.addEventListener('DOMContentLoaded', loadDataFromStorage);
