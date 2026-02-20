<?php
$page_title = 'Category Report - Campus Level';
require_once 'tailwind-header.php';

$category = $_GET['cat'] ?? 'electricity';
// SDO users see only their campus data
$selected_campus = $user['campus'];
$selected_year = $_GET['year'] ?? null;

// Category configuration
$category_config = [
    'electricity' => ['name' => 'Electricity', 'icon' => 'fa-bolt', 'color' => 'yellow', 'table' => 'electricity_consumption', 'year_col' => 'year'],
    'water' => ['name' => 'Water', 'icon' => 'fa-droplet', 'color' => 'blue', 'table' => 'tblwater', 'year_col' => 'YEAR(date)'],
    'treated_water' => ['name' => 'Treated Water', 'icon' => 'fa-faucet', 'color' => 'cyan', 'table' => 'tbltreatedwater', 'year_col' => 'Year'],
    'waste_segregated' => ['name' => 'Waste Segregated', 'icon' => 'fa-recycle', 'color' => 'green', 'table' => 'tblsolidwastesegregated', 'year_col' => 'Year'],
    'waste_unsegregated' => ['name' => 'Waste Unsegregated', 'icon' => 'fa-dumpster', 'color' => 'orange', 'table' => 'tblsolidwasteunsegregated', 'year_col' => 'Year'],
    'lpg' => ['name' => 'LPG', 'icon' => 'fa-fire', 'color' => 'orange', 'table' => 'tbllpg', 'year_col' => 'YearTransact'],
    'fuel' => ['name' => 'Fuel', 'icon' => 'fa-gas-pump', 'color' => 'red', 'table' => 'fuel_emissions', 'year_col' => 'YEAR(date)'],
    'food' => ['name' => 'Food', 'icon' => 'fa-utensils', 'color' => 'green', 'table' => 'tblfoodwaste', 'year_col' => 'YearTransaction'],
    'flight' => ['name' => 'Flight', 'icon' => 'fa-plane', 'color' => 'indigo', 'table' => 'tblflight', 'year_col' => 'Year'],
    'accommodation' => ['name' => 'Accommodation', 'icon' => 'fa-hotel', 'color' => 'purple', 'table' => 'tblaccommodation', 'year_col' => 'YearTransact'],
];

$config = $category_config[$category] ?? $category_config['electricity'];

// Get campuses
$campuses = [];
$stmt = $db->prepare("SELECT DISTINCT campus FROM electricity_consumption ORDER BY campus");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $campuses[] = $row['campus'];
}
$stmt->close();

// Get years
$years = [];
if ($config['year_col']) {
    $year_col = $config['year_col'];
    $campus_col = ($config['table'] === 'fuel_emissions') ? 'campus' : 'Campus';
    
    if (strpos($year_col, '(') !== false) {
        $query = "SELECT DISTINCT $year_col as year FROM {$config['table']} WHERE $campus_col = ? ORDER BY year DESC";
    } else {
        $query = "SELECT DISTINCT $year_col as year FROM {$config['table']} WHERE $campus_col = ? AND $year_col IS NOT NULL ORDER BY year DESC";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bind_param('s', $selected_campus);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['year'])) $years[] = $row['year'];
    }
    $stmt->close();
}

// Get data for selected category
$breakdown = AccessControl::getGHGBreakdownByCategory($db, $selected_campus, $selected_year);
$data = $breakdown[$category] ?? null;

if (!$data) {
    $data = ['name' => $config['name'], 'records' => 0, 'consumption' => 0, 'unit' => '', 'kg_co2' => 0, 'office' => '-'];
}

// Fetch detailed records from database
$detailed_records = [];
$where_parts = [];
$params = [];
$types = '';

if ($selected_campus) {
    if ($config['table'] === 'fuel_emissions') {
        $where_parts[] = "campus = ?";
    } else {
        $where_parts[] = "Campus = ?";
    }
    $params[] = $selected_campus;
    $types .= 's';
}

if ($selected_year && $config['year_col']) {
    $year_col = $config['year_col'];
    if (strpos($year_col, '(') !== false) {
        // For YEAR(date) type columns
        $where_parts[] = "$year_col = ?";
    } else {
        $where_parts[] = "$year_col = ?";
    }
    $params[] = $selected_year;
    $types .= 's';
}

$where_clause = count($where_parts) > 0 ? 'WHERE ' . implode(' AND ', $where_parts) : '';
$query = "SELECT * FROM {$config['table']} $where_clause ORDER BY id DESC LIMIT 1000";
$stmt = $db->prepare($query);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $detailed_records[] = $row;
}
$stmt->close();
?>

<div>
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas <?php echo $config['icon']; ?> text-<?php echo $config['color']; ?>-600 mr-2"></i>
                <?php echo $config['name']; ?> Report
            </h1>
            <p class="text-gray-600 mt-1">Detailed consumption and emissions data</p>
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
            <input type="hidden" name="cat" value="<?php echo htmlspecialchars($category); ?>">
            <?php if (!empty($years)): ?>
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
            <?php endif; ?>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-filter mr-2"></i>Apply
            </button>
            <?php if ($selected_year): ?>
                <a href="?cat=<?php echo htmlspecialchars($category); ?>" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600">Total Records</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($data['records']); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600">Total Consumption</p>
            <p class="text-2xl font-bold text-<?php echo $config['color']; ?>-600"><?php echo number_format($data['consumption'], 2); ?></p>
            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($data['unit']); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600">CO2 Emissions</p>
            <p class="text-2xl font-bold text-red-600"><?php echo number_format($data['kg_co2'], 2); ?> kg</p>
            <p class="text-xs text-gray-500"><?php echo number_format($data['kg_co2'] / 1000, 4); ?> tCO2</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600">Trees Needed</p>
            <p class="text-2xl font-bold text-green-600"><?php echo number_format(ceil($data['kg_co2'] / 21)); ?></p>
            <p class="text-xs text-gray-500">@ 21 kg CO2/tree/year</p>
        </div>
    </div>

    <!-- Detailed Records Table -->
    <?php if (!empty($detailed_records)): ?>
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 border-b bg-<?php echo $config['color']; ?>-50 flex justify-between items-center">
            <h2 class="text-xl font-semibold"><i class="fas <?php echo $config['icon']; ?> text-<?php echo $config['color']; ?>-600 mr-2"></i><?php echo $config['name']; ?> Records (<span id="record-count"><?php echo count($detailed_records); ?></span>)</h2>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Rows per page:</label>
                <select id="category-per-page" class="px-2 py-1 border rounded text-sm">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="category-table">
                <thead class="bg-gray-50">
                    <tr>
                        <?php foreach (array_keys($detailed_records[0]) as $col): ?>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"><?php echo htmlspecialchars($col); ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <tr class="bg-white">
                        <?php $colIdx = 0; foreach (array_keys($detailed_records[0]) as $col): ?>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="category-table" data-col="<?php echo $colIdx++; ?>" placeholder="Search..."></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($detailed_records as $rec): ?>
                    <tr class="hover:bg-gray-50">
                        <?php foreach ($rec as $val): ?>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($val ?? ''); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t bg-gray-50 flex justify-between items-center">
            <div class="text-sm text-gray-600">
                Showing <span id="category-start">1</span> to <span id="category-end">25</span> of <span id="category-total"><?php echo count($detailed_records); ?></span> entries
            </div>
            <div class="flex gap-2" id="category-pagination"></div>
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
<script>
function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.text('<?php echo $config['name']; ?> Report', 14, 20);
    doc.text('Records: <?php echo $data['records']; ?>', 14, 30);
    doc.text('Consumption: <?php echo number_format($data['consumption'], 2); ?> <?php echo $data['unit']; ?>', 14, 40);
    doc.text('CO2 Emissions: <?php echo number_format($data['kg_co2'], 2); ?> kg', 14, 50);
    doc.save('<?php echo $category; ?>_report.pdf');
}

function downloadCSV() {
    let csv = '=== <?php echo strtoupper($config['name']); ?> RECORDS ===\n';
    
    const table = document.getElementById('category-table');
    if (table) {
        // Get headers
        const headers = [];
        table.querySelectorAll('thead tr:first-child th').forEach(th => {
            headers.push(th.textContent.trim());
        });
        csv += headers.map(h => '"' + h + '"').join(',') + '\n';
        
        // Get visible rows only
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
    csv += 'Category,Office,Records,Consumption,Unit,CO2 (kg)\n';
    csv += '"<?php echo addslashes($data['name']); ?>","<?php echo addslashes($data['office']); ?>",<?php echo $data['records']; ?>,<?php echo $data['consumption']; ?>,"<?php echo addslashes($data['unit']); ?>",<?php echo $data['kg_co2']; ?>\n';
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = '<?php echo $category; ?>_detailed_report_<?php echo date('Y-m-d'); ?>.csv';
    a.click();
}

// Multi-column filtering and pagination
document.addEventListener('DOMContentLoaded', function() {
    const tables = { 'category-table': { currentPage: 1, perPage: 25, filteredRows: [] } };
    
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
        
        document.getElementById('category-start').textContent = filteredRows.length > 0 ? start + 1 : 0;
        document.getElementById('category-end').textContent = end;
        document.getElementById('category-total').textContent = filteredRows.length;
        
        renderPagination(tableId, filteredRows.length);
    }
    
    function renderPagination(tableId, totalRows) {
        const config = tables[tableId];
        const totalPages = Math.ceil(totalRows / config.perPage);
        const paginationDiv = document.getElementById('category-pagination');
        
        let html = '';
        html += `<button onclick="changePageCategory('${tableId}', ${config.currentPage - 1})" ${config.currentPage === 1 ? 'disabled' : ''} class="px-3 py-1 border rounded text-sm ${config.currentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-100'}">&laquo; Prev</button>`;
        
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= config.currentPage - 2 && i <= config.currentPage + 2)) {
                html += `<button onclick="changePageCategory('${tableId}', ${i})" class="px-3 py-1 border rounded text-sm ${i === config.currentPage ? 'bg-blue-600 text-white font-semibold' : 'bg-white hover:bg-gray-100'}">${i}</button>`;
            } else if (i === config.currentPage - 3 || i === config.currentPage + 3) {
                html += `<span class="px-2 text-gray-500">...</span>`;
            }
        }
        
        html += `<button onclick="changePageCategory('${tableId}', ${config.currentPage + 1})" ${config.currentPage === totalPages || totalPages === 0 ? 'disabled' : ''} class="px-3 py-1 border rounded text-sm ${config.currentPage === totalPages || totalPages === 0 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-100'}">Next &raquo;</button>`;
        
        paginationDiv.innerHTML = html;
    }
    
    window.changePageCategory = function(tableId, page) {
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
    
    const select = document.getElementById('category-per-page');
    if (select) {
        select.addEventListener('change', function() {
            tables['category-table'].perPage = parseInt(this.value);
            tables['category-table'].currentPage = 1;
            displayPage('category-table');
        });
    }
    
    const filterInputs = document.querySelectorAll('.filter-input');
    filterInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const tableId = this.getAttribute('data-table');
            applyFilters(tableId);
        });
    });
    
    if (document.getElementById('category-table')) {
        displayPage('category-table');
    }
});
</script>

<?php require_once 'tailwind-footer.php'; ?>
