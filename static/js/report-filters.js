// Multi-column filtering and pagination for report tables
document.addEventListener('DOMContentLoaded', function() {
    // Table configuration
    const tables = {};
    
    // Initialize tables
    document.querySelectorAll('[data-report-table]').forEach(table => {
        const tableId = table.id;
        tables[tableId] = {
            currentPage: 1,
            perPage: 25,
            totalRows: 0,
            filteredRows: []
        };
    });
    
    // Multi-column filter function
    function applyFilters(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const tbody = table.getElementsByTagName('tbody')[0];
        const rows = Array.from(tbody.getElementsByTagName('tr'));
        const filterInputs = table.querySelectorAll('.filter-input');
        const filters = {};
        
        // Collect all filter values
        filterInputs.forEach(input => {
            const colIndex = parseInt(input.getAttribute('data-col'));
            const filterValue = input.value.toLowerCase().trim();
            if (filterValue) {
                filters[colIndex] = filterValue;
            }
        });
        
        // Filter rows - check ALL columns simultaneously
        tables[tableId].filteredRows = [];
        rows.forEach(row => {
            const cells = row.getElementsByTagName('td');
            let showRow = true;
            
            // Check all filters (must match ALL filters)
            for (let colIndex in filters) {
                if (cells[colIndex]) {
                    const cellText = (cells[colIndex].textContent || cells[colIndex].innerText).toLowerCase();
                    if (cellText.indexOf(filters[colIndex]) === -1) {
                        showRow = false;
                        break;
                    }
                }
            }
            
            if (showRow) {
                tables[tableId].filteredRows.push(row);
            }
        });
        
        // Update count
        const countSpan = table.closest('[data-report-table]').querySelector('[data-count]');
        if (countSpan) {
            countSpan.textContent = tables[tableId].filteredRows.length;
        }
        
        // Reset to page 1 and display
        tables[tableId].currentPage = 1;
        displayPage(tableId);
    }
    
    // Display specific page
    function displayPage(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const tbody = table.getElementsByTagName('tbody')[0];
        const allRows = Array.from(tbody.getElementsByTagName('tr'));
        const config = tables[tableId];
        const filteredRows = config.filteredRows.length > 0 || hasActiveFilters(tableId) ? config.filteredRows : allRows;
        
        // Hide all rows first
        allRows.forEach(row => row.style.display = 'none');
        
        // Calculate pagination
        const start = (config.currentPage - 1) * config.perPage;
        const end = Math.min(start + config.perPage, filteredRows.length);
        
        // Show rows for current page
        for (let i = start; i < end; i++) {
            if (filteredRows[i]) {
                filteredRows[i].style.display = '';
            }
        }
        
        // Update pagination info
        const container = table.closest('[data-report-table]');
        const startSpan = container.querySelector('[data-start]');
        const endSpan = container.querySelector('[data-end]');
        const totalSpan = container.querySelector('[data-total]');
        
        if (startSpan) startSpan.textContent = filteredRows.length > 0 ? start + 1 : 0;
        if (endSpan) endSpan.textContent = end;
        if (totalSpan) totalSpan.textContent = filteredRows.length;
        
        // Render pagination buttons
        renderPagination(tableId, filteredRows.length);
    }
    
    // Check if table has active filters
    function hasActiveFilters(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return false;
        
        const filterInputs = table.querySelectorAll('.filter-input');
        for (let input of filterInputs) {
            if (input.value.trim()) return true;
        }
        return false;
    }
    
    // Render pagination buttons
    function renderPagination(tableId, totalRows) {
        const config = tables[tableId];
        const totalPages = Math.ceil(totalRows / config.perPage);
        const container = document.getElementById(tableId).closest('[data-report-table]');
        const paginationDiv = container.querySelector('[data-pagination]');
        
        if (!paginationDiv) return;
        
        let html = '';
        
        // Previous button
        html += `<button onclick="window.reportChangePage('${tableId}', ${config.currentPage - 1})" ${config.currentPage === 1 ? 'disabled' : ''} class="px-3 py-1 border rounded text-sm ${config.currentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-100'}">&laquo; Prev</button>`;
        
        // Page numbers (show first, last, and pages around current)
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= config.currentPage - 2 && i <= config.currentPage + 2)) {
                html += `<button onclick="window.reportChangePage('${tableId}', ${i})" class="px-3 py-1 border rounded text-sm ${i === config.currentPage ? 'bg-blue-600 text-white font-semibold' : 'bg-white hover:bg-gray-100'}">${i}</button>`;
            } else if (i === config.currentPage - 3 || i === config.currentPage + 3) {
                html += `<span class="px-2 text-gray-500">...</span>`;
            }
        }
        
        // Next button
        html += `<button onclick="window.reportChangePage('${tableId}', ${config.currentPage + 1})" ${config.currentPage === totalPages || totalPages === 0 ? 'disabled' : ''} class="px-3 py-1 border rounded text-sm ${config.currentPage === totalPages || totalPages === 0 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-100'}">Next &raquo;</button>`;
        
        paginationDiv.innerHTML = html;
    }
    
    // Global change page function
    window.reportChangePage = function(tableId, page) {
        const config = tables[tableId];
        if (!config) return;
        
        const table = document.getElementById(tableId);
        const tbody = table.getElementsByTagName('tbody')[0];
        const allRows = Array.from(tbody.getElementsByTagName('tr'));
        const totalRows = config.filteredRows.length > 0 || hasActiveFilters(tableId) ? config.filteredRows.length : allRows.length;
        const totalPages = Math.ceil(totalRows / config.perPage);
        
        if (page >= 1 && page <= totalPages) {
            config.currentPage = page;
            displayPage(tableId);
            
            // Scroll to table
            table.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };
    
    // Per page change handlers
    document.querySelectorAll('[data-per-page]').forEach(select => {
        select.addEventListener('change', function() {
            const tableId = this.getAttribute('data-table-id');
            if (tables[tableId]) {
                tables[tableId].perPage = parseInt(this.value);
                tables[tableId].currentPage = 1;
                displayPage(tableId);
            }
        });
    });
    
    // Filter input handlers
    document.querySelectorAll('.filter-input').forEach(input => {
        input.addEventListener('keyup', function() {
            const tableId = this.getAttribute('data-table');
            if (tables[tableId]) {
                applyFilters(tableId);
            }
        });
    });
    
    // Initialize display for all tables
    Object.keys(tables).forEach(tableId => {
        const table = document.getElementById(tableId);
        if (table) {
            const tbody = table.getElementsByTagName('tbody')[0];
            const allRows = Array.from(tbody.getElementsByTagName('tr'));
            tables[tableId].filteredRows = allRows;
            displayPage(tableId);
        }
    });
});
