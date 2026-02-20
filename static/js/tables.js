/**
 * Data Table JavaScript
 */

class DataTable {
    constructor(selector, options = {}) {
        this.table = document.querySelector(selector);
        this.options = {
            sortable: true,
            searchable: true,
            paginated: true,
            itemsPerPage: 10,
            ...options
        };
        
        this.data = [];
        this.filteredData = [];
        this.currentPage = 1;
        this.init();
    }
    
    init() {
        if (!this.table) return;
        
        this.extractData();
        this.setupControls();
        this.render();
    }
    
    extractData() {
        const tbody = this.table.querySelector('tbody');
        if (!tbody) return;
        
        this.data = Array.from(tbody.querySelectorAll('tr')).map(row => ({
            element: row,
            text: row.textContent.toLowerCase()
        }));
        
        this.filteredData = [...this.data];
    }
    
    setupControls() {
        const container = this.table.parentElement;
        
        if (this.options.searchable) {
            const searchBox = document.createElement('input');
            searchBox.type = 'text';
            searchBox.placeholder = 'Search table...';
            searchBox.className = 'mb-4 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500';
            
            searchBox.addEventListener('keyup', Utils.debounce(() => {
                this.search(searchBox.value);
            }, 300));
            
            container.insertBefore(searchBox, this.table);
        }
        
        if (this.options.sortable) {
            const headers = this.table.querySelectorAll('thead th');
            headers.forEach((header, index) => {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => this.sort(index));
            });
        }
    }
    
    search(query) {
        query = query.toLowerCase();
        this.filteredData = this.data.filter(row => row.text.includes(query));
        this.currentPage = 1;
        this.render();
    }
    
    sort(columnIndex) {
        const tbody = this.table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort((a, b) => {
            const aValue = a.cells[columnIndex].textContent.trim();
            const bValue = b.cells[columnIndex].textContent.trim();
            
            if (!isNaN(aValue) && !isNaN(bValue)) {
                return parseFloat(aValue) - parseFloat(bValue);
            }
            
            return aValue.localeCompare(bValue);
        });
        
        rows.forEach(row => tbody.appendChild(row));
    }
    
    render() {
        const tbody = this.table.querySelector('tbody');
        if (!tbody) return;
        
        // Clear existing rows
        tbody.innerHTML = '';
        
        if (this.options.paginated) {
            const totalPages = Math.ceil(this.filteredData.length / this.options.itemsPerPage);
            const start = (this.currentPage - 1) * this.options.itemsPerPage;
            const end = start + this.options.itemsPerPage;
            
            const paginatedData = this.filteredData.slice(start, end);
            paginatedData.forEach(row => tbody.appendChild(row.element));
            
            this.renderPagination(totalPages);
        } else {
            this.filteredData.forEach(row => tbody.appendChild(row.element));
        }
    }
    
    renderPagination(totalPages) {
        let pagination = document.querySelector('.table-pagination');
        if (pagination) pagination.remove();
        
        if (totalPages <= 1) return;
        
        const container = this.table.parentElement;
        pagination = document.createElement('div');
        pagination.className = 'table-pagination mt-4 flex justify-center gap-2';
        
        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.textContent = i;
            btn.className = `px-3 py-1 rounded ${
                i === this.currentPage 
                    ? 'bg-blue-600 text-white' 
                    : 'bg-gray-200 text-gray-800 hover:bg-gray-300'
            }`;
            btn.addEventListener('click', () => {
                this.currentPage = i;
                this.render();
            });
            pagination.appendChild(btn);
        }
        
        container.appendChild(pagination);
    }
}

// Initialize tables
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('table.data-table').forEach(table => {
        new DataTable(table);
    });
});
