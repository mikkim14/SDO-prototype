<?php
$page_title = 'Scope 2 Report - Campus Level';
require_once 'tailwind-header.php';

// SDO users see only their campus data
$selected_campus = $user['campus'];
$selected_year = $_GET['year'] ?? null;

$years = [];
$result = $db->query("SELECT DISTINCT year FROM electricity_consumption WHERE year IS NOT NULL AND year != '' ORDER BY year DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $years[] = $row['year'];
    }
}

// Scope 2: Electricity only
$scope_categories = ['electricity'];
$breakdown = AccessControl::getGHGBreakdownByCategory($db, $selected_campus, $selected_year);
$breakdown = array_filter($breakdown, function($key) use ($scope_categories) {
    return in_array($key, $scope_categories);
}, ARRAY_FILTER_USE_KEY);

$total_emissions = array_sum(array_column($breakdown, 'kg_co2'));
$total_records = array_sum(array_column($breakdown, 'records'));
$total_consumption = array_sum(array_column($breakdown, 'consumption'));

// Get campus total (all scopes combined) if campus filter is active
$campus_total = null;
if ($selected_campus) {
    $campus_total = AccessControl::getRoleBasedGHGTotals($db, 'Sustainable Development Office', $selected_campus, $selected_year);
}

// Fetch detailed electricity records
$electricity_records = [];
$where = [];
$params = [];
$types = '';
if ($selected_campus) {
    $where[] = "campus = ?";
    $params[] = $selected_campus;
    $types .= 's';
}
if ($selected_year) {
    $where[] = "year = ?";
    $params[] = $selected_year;
    $types .= 's';
}
$where_clause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$query = "SELECT * FROM electricity_consumption $where_clause ORDER BY year DESC, month";
$stmt = $db->prepare($query);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $electricity_records[] = $row;
}
$stmt->close();
?>

<div>
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-bolt text-yellow-600 mr-2"></i>
                Scope 2 Report: Indirect Emissions
            </h1>
            <p class="text-gray-600 mt-1">Electricity Consumption</p>
        </div>
        <div class="flex gap-2">
            <button onclick="downloadPDF()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                <i class="fas fa-file-pdf mr-2"></i>PDF
            </button>
            <button onclick="downloadCSV()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                <i class="fas fa-file-excel mr-2"></i>CSV
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <form method="GET" class="flex gap-4 items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                <select name="year" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">All Years</option>
                    <?php foreach ($years as $year): ?>
                        <option value="<?php echo htmlspecialchars($year); ?>" <?php echo ($selected_year === $year) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($year); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-filter mr-2"></i>Apply
            </button>
            <?php if ($selected_year): ?>
                <a href="?" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600">Total Consumption</p>
            <p class="text-2xl font-bold text-yellow-600"><?php echo number_format($total_consumption); ?> kWh</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600">Total Emissions</p>
            <p class="text-2xl font-bold text-red-600"><?php echo number_format($total_emissions, 2); ?> kg</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600">Total Records</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($total_records); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600">Trees Needed</p>
            <p class="text-2xl font-bold text-green-600"><?php echo number_format(ceil($total_emissions / 21)); ?></p>
        </div>
    </div>

    <!-- Detailed Records Table -->
    <?php if (!empty($electricity_records)): ?>
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b bg-yellow-50 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800"><i class="fas fa-bolt text-yellow-600 mr-2"></i>Electricity Consumption Records (<span id="elec-count"><?php echo count($electricity_records); ?></span>)</h2>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Rows per page:</label>
                <select id="elec-per-page" class="px-2 py-1 border rounded text-sm">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="elec-table">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Campus</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Year</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Prev Reading</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Curr Reading</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Multiplier</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Consumption (kWh)</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">kg CO2</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Trees</th>
                    </tr>
                    <tr class="bg-white">
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="elec-table" data-col="0" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="elec-table" data-col="1" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="elec-table" data-col="2" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="elec-table" data-col="3" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="elec-table" data-col="4" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="elec-table" data-col="5" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="elec-table" data-col="6" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="elec-table" data-col="7" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="elec-table" data-col="8" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="elec-table" data-col="9" placeholder="Search..."></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($electricity_records as $rec): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2"><?php echo htmlspecialchars($rec['campus'] ?? ''); ?></td>
                            <td class="px-4 py-2"><span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs"><?php echo htmlspecialchars($rec['category'] ?? ''); ?></span></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($rec['month'] ?? ''); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($rec['year'] ?? ''); ?></td>
                            <td class="px-4 py-2 text-right"><?php echo number_format($rec['prev_reading'] ?? 0, 2); ?></td>
                            <td class="px-4 py-2 text-right"><?php echo number_format($rec['current_reading'] ?? 0, 2); ?></td>
                            <td class="px-4 py-2 text-right"><?php echo number_format($rec['multiplier'] ?? 0, 2); ?></td>
                            <td class="px-4 py-2 text-right font-medium"><?php echo number_format($rec['consumption'] ?? 0, 2); ?></td>
                            <td class="px-4 py-2 text-right font-semibold text-red-600"><?php echo number_format($rec['kg_co2_per_kwh'] ?? 0, 2); ?></td>
                            <td class="px-4 py-2 text-right text-green-600"><?php echo number_format($rec['tree_offset'] ?? 0); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gray-50 font-semibold">
                    <tr>
                        <td colspan="7" class="px-4 py-3 text-sm">TOTAL</td>
                        <td class="px-4 py-3 text-sm text-right"><?php echo number_format($total_consumption, 2); ?></td>
                        <td class="px-4 py-3 text-sm text-right text-red-600"><?php echo number_format($total_emissions, 2); ?></td>
                        <td class="px-4 py-3 text-sm text-right text-green-600"><?php echo number_format(ceil($total_emissions / 21)); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="px-6 py-4 border-t bg-gray-50 flex justify-between items-center">
            <div class="text-sm text-gray-600">
                Showing <span id="elec-start">1</span> to <span id="elec-end">25</span> of <span id="elec-total"><?php echo count($electricity_records); ?></span> entries
            </div>
            <div class="flex gap-2" id="elec-pagination"></div>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-lg shadow-md p-6 text-center text-gray-500">
        <i class="fas fa-inbox text-4xl mb-2"></i>
        <p>No records found for the selected filters</p>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<script>
function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.text('Scope 2 Report: Electricity', 14, 20);
    doc.autoTable({
        startY: 30,
        head: [['Category', 'Records', 'Consumption (kWh)', 'CO2 (kg)']],
        body: [<?php foreach ($breakdown as $data): ?>['<?php echo addslashes($data['name']); ?>', '<?php echo $data['records']; ?>', '<?php echo number_format($data['consumption'], 2); ?>', '<?php echo number_format($data['kg_co2'], 2); ?>'],<?php endforeach; ?>],
        foot: [['TOTAL', '<?php echo $total_records; ?>', '<?php echo number_format($total_consumption, 2); ?>', '<?php echo number_format($total_emissions, 2); ?>']],
    });
    doc.save('scope2_report.pdf');
}

function downloadCSV() {
    let csv = '=== ELECTRICITY CONSUMPTION RECORDS ===\n';
    
    const table = document.getElementById('elec-table');
    if (table) {
        // Get headers
        const headers = [];
        table.querySelectorAll('thead tr:first-child th').forEach(th => {
            headers.push(th.textContent.trim());
        });
        csv += headers.map(h => '"' + h + '"').join(',') + '\n';
        
        // Get visible rows only
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            if (row.style.display !== 'none') {
                const cells = row.querySelectorAll('td');
                const values = [];
                cells.forEach(cell => {
                    const text = cell.textContent.trim();
                    values.push(isNaN(text) || text === '' ? '"' + text.replace(/"/g, '""') + '"' : text);
                });
                csv += values.join(',') + '\n';
            }
        });
    }
    
    csv += '\n\n=== SUMMARY ===\n';
    csv += 'Category,Records,Consumption (kWh),CO2 (kg)\n';
    <?php foreach ($breakdown as $data): ?>
    csv += '"<?php echo addslashes($data['name']); ?>",<?php echo $data['records']; ?>,<?php echo $data['consumption']; ?>,<?php echo $data['kg_co2']; ?>\n';
    <?php endforeach; ?>
    csv += 'TOTAL,<?php echo $total_records; ?>,<?php echo $total_consumption; ?>,<?php echo $total_emissions; ?>\n';
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'scope2_detailed_report_<?php echo date('Y-m-d'); ?>.csv';
    a.click();
}

// Multi-column filtering and pagination
document.addEventListener('DOMContentLoaded', function() {
    const tables = { 'elec-table': { currentPage: 1, perPage: 25, totalRows: 0, filteredRows: [] } };
    
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
        
        document.getElementById('elec-count').textContent = tables[tableId].filteredRows.length;
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
        
        document.getElementById('elec-start').textContent = filteredRows.length > 0 ? start + 1 : 0;
        document.getElementById('elec-end').textContent = end;
        document.getElementById('elec-total').textContent = filteredRows.length;
        
        renderPagination(tableId, filteredRows.length);
    }
    
    function renderPagination(tableId, totalRows) {
        const config = tables[tableId];
        const totalPages = Math.ceil(totalRows / config.perPage);
        const paginationDiv = document.getElementById('elec-pagination');
        
        let html = '';
        html += `<button onclick="changePage('${tableId}', ${config.currentPage - 1})" ${config.currentPage === 1 ? 'disabled' : ''} class="px-3 py-1 border rounded text-sm ${config.currentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-100'}">&laquo; Prev</button>`;
        
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= config.currentPage - 2 && i <= config.currentPage + 2)) {
                html += `<button onclick="changePage('${tableId}', ${i})" class="px-3 py-1 border rounded text-sm ${i === config.currentPage ? 'bg-blue-600 text-white font-semibold' : 'bg-white hover:bg-gray-100'}">${i}</button>`;
            } else if (i === config.currentPage - 3 || i === config.currentPage + 3) {
                html += `<span class="px-2 text-gray-500">...</span>`;
            }
        }
        
        html += `<button onclick="changePage('${tableId}', ${config.currentPage + 1})" ${config.currentPage === totalPages || totalPages === 0 ? 'disabled' : ''} class="px-3 py-1 border rounded text-sm ${config.currentPage === totalPages || totalPages === 0 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-100'}">Next &raquo;</button>`;
        
        paginationDiv.innerHTML = html;
    }
    
    window.changePage = function(tableId, page) {
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
    
    const select = document.getElementById('elec-per-page');
    if (select) {
        select.addEventListener('change', function() {
            tables['elec-table'].perPage = parseInt(this.value);
            tables['elec-table'].currentPage = 1;
            displayPage('elec-table');
        });
    }
    
    const filterInputs = document.querySelectorAll('.filter-input');
    filterInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const tableId = this.getAttribute('data-table');
            applyFilters(tableId);
        });
    });
    
    if (document.getElementById('elec-table')) {
        displayPage('elec-table');
    }
});
</script>

<?php require_once 'tailwind-footer.php'; ?>
