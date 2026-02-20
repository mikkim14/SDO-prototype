<?php
$page_title = 'Scope 1 Report - Campus Level';
require_once 'tailwind-header.php';

// SDO users see only their campus data
$selected_campus = $user['campus'];
$selected_year = $_GET['year'] ?? null;

// Get years
$years = [];
$year_queries = [
    "SELECT DISTINCT YEAR(date) as year FROM fuel_emissions WHERE YEAR(date) IS NOT NULL",
    "SELECT DISTINCT YearTransact as year FROM tbllpg WHERE YearTransact IS NOT NULL"
];
foreach ($year_queries as $query) {
    $result = $db->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['year'])) {
                $years[$row['year']] = true;
            }
        }
    }
}
$years = array_keys($years);
sort($years);
$years = array_reverse($years);

// Category filter for scope breakdown
$selected_category = $_GET['category'] ?? null;

// Scope 1: Fuel and LPG
$scope_categories = ['fuel', 'lpg'];
$breakdown = AccessControl::getGHGBreakdownByCategory($db, $selected_campus, $selected_year);
$breakdown = array_filter($breakdown, function($key) use ($scope_categories) {
    return in_array($key, $scope_categories);
}, ARRAY_FILTER_USE_KEY);

// Apply category filter if selected
if ($selected_category && isset($breakdown[$selected_category])) {
    $breakdown = [$selected_category => $breakdown[$selected_category]];
}

$total_emissions = array_sum(array_column($breakdown, 'kg_co2'));
$total_records = array_sum(array_column($breakdown, 'records'));

// Get campus total (all scopes combined) if campus filter is active
$campus_total = null;
if ($selected_campus) {
    $campus_total = AccessControl::getRoleBasedGHGTotals($db, 'Sustainable Development Office', $selected_campus, $selected_year);
}

// Fetch detailed records for Excel export
$fuel_records = [];
$lpg_records = [];

// Fuel records
$where = [];
$params = [];
$types = '';
if ($selected_campus) {
    $where[] = "campus = ?";
    $params[] = $selected_campus;
    $types .= 's';
}
if ($selected_year) {
    $where[] = "YEAR(date) = ?";
    $params[] = $selected_year;
    $types .= 's';
}
$where_clause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$query = "SELECT * FROM fuel_emissions $where_clause ORDER BY date DESC";
$stmt = $db->prepare($query);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $fuel_records[] = $row;
}
$stmt->close();

// LPG records
$where = [];
$params = [];
$types = '';
if ($selected_campus) {
    $where[] = "Campus = ?";
    $params[] = $selected_campus;
    $types .= 's';
}
if ($selected_year) {
    $where[] = "YearTransact = ?";
    $params[] = $selected_year;
    $types .= 's';
}
$where_clause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$query = "SELECT * FROM tbllpg $where_clause ORDER BY YearTransact DESC, Month DESC";
$stmt = $db->prepare($query);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $lpg_records[] = $row;
}
$stmt->close();
?>

<div>
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-layer-group text-red-600 mr-2"></i>
                Scope 1 Report: Direct Emissions
            </h1>
            <p class="text-gray-600 mt-1">Fuel and LPG Consumption</p>
        </div>
        <div class="flex gap-2">
            <button onclick="downloadPDF()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                <i class="fas fa-file-pdf mr-2"></i>Download PDF
            </button>
            <button onclick="downloadCSV()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-file-excel mr-2"></i>Download CSV
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <form method="GET" class="flex gap-4 items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All (Fuel & LPG)</option>
                    <option value="fuel" <?php echo ($selected_category === 'fuel') ? 'selected' : ''; ?>>Fuel Only</option>
                    <option value="lpg" <?php echo ($selected_category === 'lpg') ? 'selected' : ''; ?>>LPG Only</option>
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                <select name="year" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Years</option>
                    <?php foreach ($years as $year): ?>
                        <option value="<?php echo htmlspecialchars($year); ?>" <?php echo ($selected_year === $year) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($year); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-filter mr-2"></i>Apply Filters
            </button>
            <?php if ($selected_year || $selected_category): ?>
                <a href="?" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Emissions</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400"><?php echo number_format($total_emissions, 2); ?> kg</p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1"><?php echo number_format($total_emissions / 1000, 4); ?> tCO2</p>
                </div>
                <i class="fas fa-smog text-4xl text-red-200 dark:text-red-900"></i>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Records</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-200"><?php echo number_format($total_records); ?></p>
                </div>
                <i class="fas fa-database text-4xl text-gray-200 dark:text-gray-700"></i>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Trees Needed</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400"><?php echo number_format(ceil($total_emissions / 21)); ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">21 kg CO2/tree/year</p>
                </div>
                <i class="fas fa-tree text-4xl text-green-200 dark:text-green-900"></i>
            </div>
        </div>
    </div>

    

    <!-- Summary Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Summary by Category</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Office</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Records</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Consumption</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">CO2 (kg)</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($breakdown as $data): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($data['name']); ?></td>
                            <td class="px-6 py-4 text-sm"><span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs"><?php echo htmlspecialchars($data['office']); ?></span></td>
                            <td class="px-6 py-4 text-sm text-right"><?php echo number_format($data['records']); ?></td>
                            <td class="px-6 py-4 text-sm text-right"><?php echo number_format($data['consumption'], 2); ?> <?php echo htmlspecialchars($data['unit']); ?></td>
                            <td class="px-6 py-4 text-sm font-semibold text-right"><?php echo number_format($data['kg_co2'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Fuel Records Table -->
    <?php if ((!$selected_category || $selected_category === 'fuel') && !empty($fuel_records)): ?>
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 border-b bg-red-50 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800"><i class="fas fa-gas-pump text-red-600 mr-2"></i>Fuel Consumption Records (<span id="fuel-count"><?php echo count($fuel_records); ?></span>)</h2>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Rows per page:</label>
                <select id="fuel-per-page" class="px-2 py-1 border rounded text-sm">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="fuel-table">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Campus</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Driver</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Vehicle</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fuel Type</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Quantity (L)</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total Emission (kg)</th>
                    </tr>
                    <tr class="bg-white">
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="fuel-table" data-col="0" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="fuel-table" data-col="1" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="fuel-table" data-col="2" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="fuel-table" data-col="3" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="fuel-table" data-col="4" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="fuel-table" data-col="5" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="fuel-table" data-col="6" placeholder="Search..."></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($fuel_records as $rec): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2"><?php echo htmlspecialchars($rec['campus'] ?? ''); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($rec['date'] ?? ''); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($rec['driver'] ?? ''); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($rec['vehicle_equipment'] ?? ''); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($rec['fuel_type'] ?? ''); ?></td>
                            <td class="px-4 py-2 text-right font-medium"><?php echo number_format($rec['quantity_liters'] ?? 0, 2); ?></td>
                            <td class="px-4 py-2 text-right font-semibold"><?php echo number_format($rec['total_emission'] ?? 0, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t bg-gray-50 flex justify-between items-center">
            <div class="text-sm text-gray-600">
                Showing <span id="fuel-start">1</span> to <span id="fuel-end">25</span> of <span id="fuel-total"><?php echo count($fuel_records); ?></span> entries
            </div>
            <div class="flex gap-2" id="fuel-pagination"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- LPG Records Table -->
    <?php if ((!$selected_category || $selected_category === 'lpg') && !empty($lpg_records)): ?>
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 border-b bg-orange-50 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800"><i class="fas fa-fire text-orange-600 mr-2"></i>LPG Consumption Records (<span id="lpg-count"><?php echo count($lpg_records); ?></span>)</h2>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Rows per page:</label>
                <select id="lpg-per-page" class="px-2 py-1 border rounded text-sm">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="lpg-table">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Campus</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Office</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Year</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Tank Qty</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total Volume (L)</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">GHG (kg CO2e)</th>
                    </tr>
                    <tr class="bg-white">
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="lpg-table" data-col="0" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="lpg-table" data-col="1" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="lpg-table" data-col="2" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="lpg-table" data-col="3" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="lpg-table" data-col="4" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="lpg-table" data-col="5" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="lpg-table" data-col="6" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="lpg-table" data-col="7" placeholder="Search..."></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($lpg_records as $rec): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2"><?php echo htmlspecialchars($rec['Campus'] ?? ''); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($rec['Office'] ?? ''); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($rec['YearTransact'] ?? ''); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($rec['Month'] ?? ''); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($rec['ConcessionariesType'] ?? ''); ?></td>
                            <td class="px-4 py-2 text-right"><?php echo number_format($rec['TankQuantity'] ?? 0); ?></td>
                            <td class="px-4 py-2 text-right font-medium"><?php echo number_format($rec['TotalTankVolume'] ?? 0, 2); ?></td>
                            <td class="px-4 py-2 text-right font-semibold"><?php echo number_format($rec['GHGEmissionKGCO2e'] ?? 0, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t bg-gray-50 flex justify-between items-center">
            <div class="text-sm text-gray-600">
                Showing <span id="lpg-start">1</span> to <span id="lpg-end">25</span> of <span id="lpg-total"><?php echo count($lpg_records); ?></span> entries
            </div>
            <div class="flex gap-2" id="lpg-pagination"></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<script>
function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    doc.setFontSize(18);
    doc.text('Scope 1 Report: Direct Emissions', 14, 20);
    doc.setFontSize(11);
    doc.text('Fuel and LPG Consumption', 14, 28);
    
    <?php if ($selected_campus || $selected_year): ?>
    doc.setFontSize(10);
    doc.text('Filters: <?php echo $selected_campus ? "Campus: " . $selected_campus . " " : ""; ?><?php echo $selected_year ? "Year: " . $selected_year : ""; ?>', 14, 36);
    <?php endif; ?>
    
    const tableData = [
        <?php foreach ($breakdown as $data): ?>
        ['<?php echo addslashes($data['name']); ?>', '<?php echo addslashes($data['office']); ?>', '<?php echo $data['records']; ?>', '<?php echo number_format($data['consumption'], 2) . " " . $data['unit']; ?>', '<?php echo number_format($data['kg_co2'], 2); ?>'],
        <?php endforeach; ?>
    ];
    
    doc.autoTable({
        startY: <?php echo ($selected_campus || $selected_year) ? 42 : 35; ?>,
        head: [['Category', 'Office', 'Records', 'Consumption', 'CO2 (kg)']],
        body: tableData,
        foot: [['TOTAL', '', '<?php echo number_format($total_records); ?>', '-', '<?php echo number_format($total_emissions, 2); ?>']],
        theme: 'grid'
    });
    
    doc.save('scope1_report_<?php echo date('Y-m-d'); ?>.pdf');
}

function downloadCSV() {
    let csv = '';
    
    // Export Fuel records if visible
    const fuelTable = document.getElementById('fuel-table');
    if (fuelTable) {
        csv += '=== FUEL CONSUMPTION RECORDS ===\n';
        const fuelHeaders = [];
        fuelTable.querySelectorAll('thead tr:first-child th').forEach(th => {
            fuelHeaders.push(th.textContent.trim());
        });
        csv += fuelHeaders.map(h => '"' + h + '"').join(',') + '\n';
        
        const fuelRows = fuelTable.querySelectorAll('tbody tr');
        fuelRows.forEach(row => {
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
        csv += '\n\n';
    }
    
    // Export LPG records if visible
    const lpgTable = document.getElementById('lpg-table');
    if (lpgTable) {
        csv += '=== LPG CONSUMPTION RECORDS ===\n';
        const lpgHeaders = [];
        lpgTable.querySelectorAll('thead tr:first-child th').forEach(th => {
            lpgHeaders.push(th.textContent.trim());
        });
        csv += lpgHeaders.map(h => '"' + h + '"').join(',') + '\n';
        
        const lpgRows = lpgTable.querySelectorAll('tbody tr');
        lpgRows.forEach(row => {
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
    csv += 'Category,Office,Records,Consumption,Unit,CO2 (kg)\n';
    <?php foreach ($breakdown as $data): ?>
    csv += '"<?php echo addslashes($data['name']); ?>","<?php echo addslashes($data['office']); ?>",<?php echo $data['records']; ?>,<?php echo $data['consumption']; ?>,"<?php echo $data['unit']; ?>",<?php echo $data['kg_co2']; ?>\n';
    <?php endforeach; ?>
    csv += 'TOTAL,,-,-,-,<?php echo $total_emissions; ?>\n';
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'scope1_detailed_report_<?php echo date('Y-m-d'); ?>.csv';
    a.click();
}

// Multi-column filtering and pagination
document.addEventListener('DOMContentLoaded', function() {
    // Initialize pagination for each table
    const tables = {
        'fuel-table': { currentPage: 1, perPage: 25, totalRows: 0, filteredRows: [] },
        'lpg-table': { currentPage: 1, perPage: 25, totalRows: 0, filteredRows: [] }
    };
    
    // Multi-column filter function
    function applyFilters(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const tbody = table.getElementsByTagName('tbody')[0];
        const rows = tbody.getElementsByTagName('tr');
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
        
        // Filter rows
        tables[tableId].filteredRows = [];
        for (let i = 0; i < rows.length; i++) {
            const cells = rows[i].getElementsByTagName('td');
            let showRow = true;
            
            // Check all filters
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
                tables[tableId].filteredRows.push(rows[i]);
            }
        }
        
        // Update count
        const countId = tableId === 'fuel-table' ? 'fuel-count' : 'lpg-count';
        document.getElementById(countId).textContent = tables[tableId].filteredRows.length;
        
        // Reset to page 1 and display
        tables[tableId].currentPage = 1;
        displayPage(tableId);
    }
    
    // Check if any filters are active
    function hasActiveFilters(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return false;
        const filterInputs = table.querySelectorAll('.filter-input');
        for (let input of filterInputs) {
            if (input.value.trim()) return true;
        }
        return false;
    }
    
    // Display specific page
    function displayPage(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const tbody = table.getElementsByTagName('tbody')[0];
        const allRows = Array.from(tbody.getElementsByTagName('tr'));
        const config = tables[tableId];
        // Use filtered rows if filters are active, otherwise use all rows
        const filteredRows = hasActiveFilters(tableId) ? config.filteredRows : allRows;
        
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
        const prefix = tableId === 'fuel-table' ? 'fuel' : 'lpg';
        document.getElementById(prefix + '-start').textContent = filteredRows.length > 0 ? start + 1 : 0;
        document.getElementById(prefix + '-end').textContent = end;
        document.getElementById(prefix + '-total').textContent = filteredRows.length;
        
        // Render pagination buttons
        renderPagination(tableId, filteredRows.length);
    }
    
    // Render pagination buttons
    function renderPagination(tableId, totalRows) {
        const config = tables[tableId];
        const totalPages = Math.ceil(totalRows / config.perPage);
        const prefix = tableId === 'fuel-table' ? 'fuel' : 'lpg';
        const paginationDiv = document.getElementById(prefix + '-pagination');
        
        let html = '';
        
        // Previous button
        html += `<button onclick="changePage('${tableId}', ${config.currentPage - 1})" ${config.currentPage === 1 ? 'disabled' : ''} class="px-3 py-1 border rounded ${config.currentPage === 1 ? 'bg-gray-100 text-gray-400' : 'bg-white hover:bg-gray-100'}">Previous</button>`;
        
        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= config.currentPage - 2 && i <= config.currentPage + 2)) {
                html += `<button onclick="changePage('${tableId}', ${i})" class="px-3 py-1 border rounded ${i === config.currentPage ? 'bg-blue-600 text-white' : 'bg-white hover:bg-gray-100'}">${i}</button>`;
            } else if (i === config.currentPage - 3 || i === config.currentPage + 3) {
                html += `<span class="px-2">...</span>`;
            }
        }
        
        // Next button
        html += `<button onclick="changePage('${tableId}', ${config.currentPage + 1})" ${config.currentPage === totalPages || totalPages === 0 ? 'disabled' : ''} class="px-3 py-1 border rounded ${config.currentPage === totalPages || totalPages === 0 ? 'bg-gray-100 text-gray-400' : 'bg-white hover:bg-gray-100'}">Next</button>`;
        
        paginationDiv.innerHTML = html;
    }
    
    // Change page function (global)
    window.changePage = function(tableId, page) {
        const config = tables[tableId];
        const totalRows = config.filteredRows.length > 0 ? config.filteredRows.length : document.getElementById(tableId).getElementsByTagName('tbody')[0].getElementsByTagName('tr').length;
        const totalPages = Math.ceil(totalRows / config.perPage);
        
        if (page >= 1 && page <= totalPages) {
            config.currentPage = page;
            displayPage(tableId);
        }
    };
    
    // Per page change handlers
    ['fuel', 'lpg'].forEach(prefix => {
        const select = document.getElementById(prefix + '-per-page');
        if (select) {
            select.addEventListener('change', function() {
                const tableId = prefix + '-table';
                tables[tableId].perPage = parseInt(this.value);
                tables[tableId].currentPage = 1;
                displayPage(tableId);
            });
        }
    });
    
    // Filter input handlers
    const filterInputs = document.querySelectorAll('.filter-input');
    filterInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const tableId = this.getAttribute('data-table');
            applyFilters(tableId);
        });
    });
    
    // Initialize display
    Object.keys(tables).forEach(tableId => {
        const table = document.getElementById(tableId);
        if (table) {
            displayPage(tableId);
        }
    });
});
</script>

<?php require_once 'tailwind-footer.php'; ?>
