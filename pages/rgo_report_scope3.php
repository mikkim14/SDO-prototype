<?php
$page_title = 'Scope 3 Report - RGO';
require_once 'tailwind-header.php';
require_once '../includes/GHGCalculator.php';
require_once '../includes/AccessControl.php';

$selected_campus = $user['campus'];
$selected_year = $_GET['year'] ?? '';

// Get available years
$years = [];
$year_query = "SELECT DISTINCT YearTransaction as year FROM tblfoodwaste WHERE YearTransaction IS NOT NULL AND Campus = ? ORDER BY year DESC";
$stmt = $db->prepare($year_query);
if ($stmt) {
    $stmt->bind_param("s", $selected_campus);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if ($row['year']) $years[] = $row['year'];
    }
    $stmt->close();
}

// Build where clause
$where = "WHERE Campus = ?";
$params = [$selected_campus];
$types = "s";

if ($selected_year) {
    $where .= " AND YearTransaction = ?";
    $params[] = $selected_year;
    $types .= "s";
}

// Fetch Food records (Scope 3)
$query = "SELECT * FROM tblfoodwaste $where ORDER BY YearTransaction DESC, Month DESC LIMIT 1000";
$stmt = $db->prepare($query);
$detailed_records = [];
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $detailed_records = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Calculate totals
$scope_total = 0;
$total_servings = 0;
foreach ($detailed_records as $record) {
    $scope_total += ($record['GHGEmissionKGCO2e'] ?? 0);
    $total_servings += ($record['QuantityOfServing'] ?? 0);
}

$scope_t_co2 = $scope_total / 1000;
$tree_offset = ceil($scope_total / 21);
?>

                    <div>
                        <!-- Page Header -->
                        <div class="mb-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                                        <i class="fas fa-layer-group text-amber-600 mr-3"></i>
                                        Scope 3 Report - Food Consumption
                                    </h1>
                                    <p class="text-gray-600 mt-1">
                                        <?php echo htmlspecialchars($selected_campus); ?> Campus • RGO
                                    </p>
                                </div>
                                
                                <!-- Download Buttons -->
                                <div class="flex gap-2">
                                    <button onclick="downloadPDF()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold transition-colors flex items-center">
                                        <i class="fas fa-file-pdf mr-2"></i>PDF
                                    </button>
                                    <button onclick="downloadCSV()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-semibold transition-colors flex items-center">
                                        <i class="fas fa-file-csv mr-2"></i>CSV
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm">Total Records</p>
                                        <p class="text-3xl font-bold text-gray-800 dark:text-gray-200 mt-1" id="record-count"><?php echo count($detailed_records); ?></p>
                                    </div>
                                    <i class="fas fa-database text-4xl text-gray-300 dark:text-gray-700"></i>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm">Total Servings</p>
                                        <p class="text-3xl font-bold text-gray-800 dark:text-gray-200 mt-1"><?php echo number_format($total_servings, 0); ?></p>
                                        <p class="text-gray-500 dark:text-gray-500 text-xs">servings</p>
                                    </div>
                                    <i class="fas fa-utensils text-4xl text-amber-300 dark:text-amber-900"></i>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm">Scope 3 Emissions</p>
                                        <p class="text-3xl font-bold text-gray-800 dark:text-gray-200 mt-1"><?php echo number_format($scope_total, 2); ?></p>
                                        <p class="text-gray-500 dark:text-gray-500 text-xs">kg CO₂e (<?php echo number_format($scope_t_co2, 3); ?> t CO₂e)</p>
                                    </div>
                                    <i class="fas fa-smog text-4xl text-amber-300 dark:text-amber-900"></i>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm">Tree Offset</p>
                                        <p class="text-3xl font-bold text-gray-800 dark:text-gray-200 mt-1"><?php echo number_format($tree_offset); ?></p>
                                        <p class="text-gray-500 dark:text-gray-500 text-xs">trees needed</p>
                                    </div>
                                    <i class="fas fa-tree text-4xl text-green-300 dark:text-green-900"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                                    <select name="year" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                                        <option value="">All Years</option>
                                        <?php foreach ($years as $year): ?>
                                            <option value="<?php echo htmlspecialchars($year); ?>" <?php echo ($selected_year == $year) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($year); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="flex items-end">
                                    <a href="?" class="w-full px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg text-sm font-semibold transition-colors text-center">
                                        <i class="fas fa-redo mr-2"></i>Clear Filters
                                    </a>
                                </div>
                            </form>
                        </div>

                        <!-- Records Table -->
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                                        <i class="fas fa-table text-amber-600 mr-2"></i>
                                        Food Consumption Records (Scope 3)
                                    </h2>
                                    <div class="flex items-center gap-2">
                                        <label class="text-sm text-gray-600">Show</label>
                                        <select id="scope3-per-page" class="px-2 py-1 border border-gray-300 rounded text-sm">
                                            <option value="10">10</option>
                                            <option value="25" selected>25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                        <span class="text-sm text-gray-600">entries</span>
                                    </div>
                                </div>

                                <div class="overflow-x-auto">
                                    <?php if (count($detailed_records) > 0): ?>
                                        <table class="w-full" id="scope3-table">
                                            <thead class="bg-gray-100 border-b-2 border-gray-200">
                                                <tr>
                                                    <?php foreach (array_keys($detailed_records[0]) as $idx => $column): ?>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                            <?php echo htmlspecialchars($column); ?>
                                                        </th>
                                                    <?php endforeach; ?>
                                                </tr>
                                                <tr>
                                                    <?php foreach (array_keys($detailed_records[0]) as $idx => $column): ?>
                                                        <th class="px-4 py-2">
                                                            <input type="text" placeholder="Filter..." data-table="scope3-table" data-col="<?php echo $idx; ?>" class="filter-input w-full px-2 py-1 text-xs border border-gray-300 rounded">
                                                        </th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                <?php foreach ($detailed_records as $record): ?>
                                                    <tr class="hover:bg-gray-50">
                                                        <?php foreach ($record as $value): ?>
                                                            <td class="px-4 py-3 text-sm text-gray-800 whitespace-nowrap">
                                                                <?php echo htmlspecialchars($value ?? '-'); ?>
                                                            </td>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>

                                        <div class="mt-4 flex items-center justify-between">
                                            <div class="text-sm text-gray-600">
                                                Showing <span id="scope3-start">1</span> to <span id="scope3-end">25</span> of <span id="scope3-total"><?php echo count($detailed_records); ?></span> records
                                            </div>
                                            <div class="flex gap-1" id="scope3-pagination"></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-12 text-gray-500">
                                            <i class="fas fa-inbox text-5xl mb-4"></i>
                                            <p class="text-lg">No records found</p>
                                            <p class="text-sm">Try adjusting your filters or add new data</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.text('Scope 3 Report - Food Consumption', 14, 20);
    doc.text('Records: <?php echo count($detailed_records); ?>', 14, 30);
    doc.text('Servings: <?php echo number_format($total_servings, 0); ?>', 14, 40);
    doc.text('CO2: <?php echo number_format($scope_total, 2); ?> kg', 14, 50);
    doc.save('scope3_food_report.pdf');
}

function downloadCSV() {
    let csv = '=== SCOPE 3 REPORT - FOOD CONSUMPTION ===\n';
    
    const table = document.getElementById('scope3-table');
    if (table) {
        const headers = [];
        table.querySelectorAll('thead tr:first-child th').forEach(th => {
            headers.push(th.textContent.trim());
        });
        csv += headers.map(h => '"' + h + '"').join(',') + '\n';
        
        const rows = table.querySelectorAll('tbody tr');
        let hasData = false;
        rows.forEach(row => {
            if (row.style.display !== 'none') {
                hasData = true;
                const cells = row.querySelectorAll('td');
                const values = [];
                cells.forEach(cell => {
                    const text = cell.textContent.trim();
                    values.push(isNaN(text) || text === '' ? '"' + text.replace(/"/g, '""') + '"' : text);
                });
                csv += values.join(',') + '\n';
            }
        });
        
        if (!hasData) {
            csv += 'No records found\n';
        }
    } else {
        csv += 'No records found\n';
    }
    
    csv += '\n\n=== SUMMARY ===\n';
    csv += 'Records,Servings,CO2 (kg)\n';
    csv += '<?php echo count($detailed_records); ?>,<?php echo $total_servings; ?>,<?php echo $scope_total; ?>\n';
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'scope3_food_report_<?php echo date('Y-m-d'); ?>.csv';
    a.click();
}

// Pagination and filtering
document.addEventListener('DOMContentLoaded', function() {
    const tables = { 'scope3-table': { currentPage: 1, perPage: 25, filteredRows: [] } };
    
    function hasActiveFilters(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return false;
        const filterInputs = table.querySelectorAll('.filter-input');
        for (let input of filterInputs) {
            if (input.value.trim()) return true;
        }
        return false;
    }
    
    function applyFilters(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const tbody = table.getElementsByTagName('tbody')[0];
        const rows = tbody.getElementsByTagName('tr');
        const filterInputs = table.querySelectorAll('.filter-input');
        const filters = {};
        
        filterInputs.forEach(input => {
            const colIndex = parseInt(input.getAttribute('data-col'));
            const filterValue = input.value.toLowerCase().trim();
            if (filterValue) filters[colIndex] = filterValue;
        });
        
        tables[tableId].filteredRows = [];
        for (let i = 0; i < rows.length; i++) {
            const cells = rows[i].getElementsByTagName('td');
            let showRow = true;
            
            for (let colIndex in filters) {
                if (cells[colIndex]) {
                    const cellText = (cells[colIndex].textContent || cells[colIndex].innerText).toLowerCase();
                    if (cellText.indexOf(filters[colIndex]) === -1) {
                        showRow = false;
                        break;
                    }
                }
            }
            
            if (showRow) tables[tableId].filteredRows.push(rows[i]);
        }
        
        document.getElementById('record-count').textContent = tables[tableId].filteredRows.length;
        tables[tableId].currentPage = 1;
        displayPage(tableId);
    }
    
    function displayPage(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const tbody = table.getElementsByTagName('tbody')[0];
        const allRows = Array.from(tbody.getElementsByTagName('tr'));
        const config = tables[tableId];
        const filteredRows = hasActiveFilters(tableId) ? config.filteredRows : allRows;
        
        allRows.forEach(row => row.style.display = 'none');
        
        const start = (config.currentPage - 1) * config.perPage;
        const end = Math.min(start + config.perPage, filteredRows.length);
        
        for (let i = start; i < end; i++) {
            if (filteredRows[i]) filteredRows[i].style.display = '';
        }
        
        document.getElementById('scope3-start').textContent = filteredRows.length > 0 ? start + 1 : 0;
        document.getElementById('scope3-end').textContent = end;
        document.getElementById('scope3-total').textContent = filteredRows.length;
        
        renderPagination(tableId, filteredRows.length);
    }
    
    function renderPagination(tableId, totalRows) {
        const config = tables[tableId];
        const totalPages = Math.ceil(totalRows / config.perPage);
        const paginationDiv = document.getElementById('scope3-pagination');
        
        let html = '';
        html += `<button onclick="changePageScope3('${tableId}', ${config.currentPage - 1})" ${config.currentPage === 1 ? 'disabled' : ''} class="px-3 py-1 border rounded text-sm ${config.currentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-100'}">&laquo; Prev</button>`;
        
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= config.currentPage - 2 && i <= config.currentPage + 2)) {
                html += `<button onclick="changePageScope3('${tableId}', ${i})" class="px-3 py-1 border rounded text-sm ${i === config.currentPage ? 'bg-blue-600 text-white font-semibold' : 'bg-white hover:bg-gray-100'}">${i}</button>`;
            } else if (i === config.currentPage - 3 || i === config.currentPage + 3) {
                html += `<span class="px-2 text-gray-500">...</span>`;
            }
        }
        
        html += `<button onclick="changePageScope3('${tableId}', ${config.currentPage + 1})" ${config.currentPage === totalPages || totalPages === 0 ? 'disabled' : ''} class="px-3 py-1 border rounded text-sm ${config.currentPage === totalPages || totalPages === 0 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-100'}">Next &raquo;</button>`;
        
        paginationDiv.innerHTML = html;
    }
    
    window.changePageScope3 = function(tableId, page) {
        const config = tables[tableId];
        const table = document.getElementById(tableId);
        const tbody = table.getElementsByTagName('tbody')[0];
        const allRows = Array.from(tbody.getElementsByTagName('tr'));
        const totalRows = hasActiveFilters(tableId) ? config.filteredRows.length : allRows.length;
        const totalPages = Math.ceil(totalRows / config.perPage);
        
        if (page >= 1 && page <= totalPages) {
            config.currentPage = page;
            displayPage(tableId);
        }
    };
    
    const select = document.getElementById('scope3-per-page');
    if (select) {
        select.addEventListener('change', function() {
            tables['scope3-table'].perPage = parseInt(this.value);
            tables['scope3-table'].currentPage = 1;
            displayPage('scope3-table');
        });
    }
    
    const filterInputs = document.querySelectorAll('.filter-input');
    filterInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const tableId = this.getAttribute('data-table');
            applyFilters(tableId);
        });
    });
    
    if (document.getElementById('scope3-table')) {
        displayPage('scope3-table');
    }
});
</script>

<?php require_once 'tailwind-footer.php'; ?>
