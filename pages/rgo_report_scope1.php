<?php
$page_title = 'Scope 1 Report - Campus Level';
require_once 'tailwind-header.php';

// RGO users see only their campus data
$selected_campus = $user['campus'];
$selected_year = $_GET['year'] ?? null;

// Get years
$years = [];
$year_query = "SELECT DISTINCT YearTransact as year FROM tbllpg WHERE YearTransact IS NOT NULL";
$result = $db->query($year_query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['year'])) {
            $years[$row['year']] = true;
        }
    }
}
$years = array_keys($years);
sort($years);
$years = array_reverse($years);

// Scope 1: LPG only for RGO
$scope_categories = ['lpg'];
$breakdown = AccessControl::getGHGBreakdownByCategory($db, $selected_campus, $selected_year);
$breakdown = array_filter($breakdown, function($key) use ($scope_categories) {
    return in_array($key, $scope_categories);
}, ARRAY_FILTER_USE_KEY);

$total_emissions = array_sum(array_column($breakdown, 'kg_co2'));
$total_records = array_sum(array_column($breakdown, 'records'));

// Get campus total (all scopes combined) if campus filter is active
$campus_total = null;
if ($selected_campus) {
    $campus_total = AccessControl::getRoleBasedGHGTotals($db, 'Research and Development', $selected_campus, $selected_year);
}

// Fetch detailed LPG records
$lpg_records = [];

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
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-fire text-orange-600 mr-2"></i>
                Scope 1 Report: Direct Emissions
            </h1>
            <p class="text-gray-600 mt-1">LPG • <?php echo htmlspecialchars($selected_campus); ?> Campus</p>
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
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-filter mr-2"></i>Apply Filters
            </button>
            <?php if ($selected_year): ?>
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

    

    <!-- LPG Records Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 bg-red-50 border-b border-red-200">
            <h2 class="text-xl font-bold text-gray-800">
                <i class="fas fa-fire text-red-600 mr-2"></i>
                LPG Consumption Records
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full" id="lpg-table">
                <thead class="bg-red-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Campus</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Office</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Year</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Month</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Quarter</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Concessionaries Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Tank Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Tank Weight (kg)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Tank Volume (L)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Total Tank Volume (L)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">GHG Emission (kg CO2e)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">GHG Emission (tCO2e)</th>
                    </tr>
                    <tr class="bg-white border-t border-red-200">
                        <th class="px-2 py-2"><input type="text" placeholder="Filter ID" class="w-full px-2 py-1 text-xs border border-gray-300 rounded lpg-filter" data-column="0"></th>
                        <th class="px-2 py-2"><input type="text" placeholder="Filter Campus" class="w-full px-2 py-1 text-xs border border-gray-300 rounded lpg-filter" data-column="1"></th>
                        <th class="px-2 py-2"><input type="text" placeholder="Filter Office" class="w-full px-2 py-1 text-xs border border-gray-300 rounded lpg-filter" data-column="2"></th>
                        <th class="px-2 py-2"><input type="text" placeholder="Filter Year" class="w-full px-2 py-1 text-xs border border-gray-300 rounded lpg-filter" data-column="3"></th>
                        <th class="px-2 py-2"><input type="text" placeholder="Filter Month" class="w-full px-2 py-1 text-xs border border-gray-300 rounded lpg-filter" data-column="4"></th>
                        <th class="px-2 py-2"><input type="text" placeholder="Filter Quarter" class="w-full px-2 py-1 text-xs border border-gray-300 rounded lpg-filter" data-column="5"></th>
                        <th class="px-2 py-2"><input type="text" placeholder="Filter Type" class="w-full px-2 py-1 text-xs border border-gray-300 rounded lpg-filter" data-column="6"></th>
                        <th class="px-2 py-2"><input type="text" placeholder="Filter Qty" class="w-full px-2 py-1 text-xs border border-gray-300 rounded lpg-filter" data-column="7"></th>
                        <th class="px-2 py-2"><input type="text" placeholder="Filter Weight" class="w-full px-2 py-1 text-xs border border-gray-300 rounded lpg-filter" data-column="8"></th>
                        <th class="px-2 py-2"><input type="text" placeholder="Filter Volume" class="w-full px-2 py-1 text-xs border border-gray-300 rounded lpg-filter" data-column="9"></th>
                        <th class="px-2 py-2"><input type="text" placeholder="Filter Total Vol" class="w-full px-2 py-1 text-xs border border-gray-300 rounded lpg-filter" data-column="10"></th>
                        <th class="px-2 py-2"><input type="text" placeholder="Filter GHG (kg)" class="w-full px-2 py-1 text-xs border border-gray-300 rounded lpg-filter" data-column="11"></th>
                        <th class="px-2 py-2"><input type="text" placeholder="Filter GHG (t)" class="w-full px-2 py-1 text-xs border border-gray-300 rounded lpg-filter" data-column="12"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($lpg_records)): ?>
                        <?php foreach ($lpg_records as $record): ?>
                            <tr class="hover:bg-gray-50 lpg-row">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($record['id']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($record['Campus']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($record['Office']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($record['YearTransact']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($record['Month']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($record['Quarter'] ?? '-'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($record['ConcessionariesType'] ?? '-'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($record['TankQuantity'] ?? '-'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo isset($record['TankWeight']) ? number_format($record['TankWeight'], 2) : '-'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo isset($record['TankVolume']) ? number_format($record['TankVolume'], 2) : '-'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo isset($record['TotalTankVolume']) ? number_format($record['TotalTankVolume'], 2) : '-'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-red-600"><?php echo isset($record['GHGEmissionKGCO2e']) ? number_format($record['GHGEmissionKGCO2e'], 2) : '-'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo isset($record['GHGEmissionTCO2e']) ? number_format($record['GHGEmissionTCO2e'], 4) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="13" class="px-6 py-4 text-center text-gray-500">No LPG records found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
            <div class="text-sm text-gray-700">
                Showing <span class="font-medium" id="lpg-start">1</span> to <span class="font-medium" id="lpg-end">10</span> of <span class="font-medium" id="lpg-total"><?php echo count($lpg_records); ?></span> records
                <span id="lpg-count" class="ml-2 text-gray-500">(<span id="lpg-filtered"><?php echo count($lpg_records); ?></span> filtered)</span>
            </div>
            <div class="flex items-center gap-2">
                <label for="lpg-per-page" class="text-sm text-gray-700">Per page:</label>
                <select id="lpg-per-page" class="px-3 py-1 border border-gray-300 rounded-md text-sm">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div id="lpg-pagination" class="flex gap-1"></div>
        </div>
    </div>
</div>

<script>
// Pagination system for LPG table
const lpgPagination = {
    currentPage: 1,
    perPage: 10,
    filteredRows: []
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Get all LPG rows
    lpgPagination.filteredRows = Array.from(document.querySelectorAll('.lpg-row'));
    
    // Set up filter inputs
    document.querySelectorAll('.lpg-filter').forEach(input => {
        input.addEventListener('input', applyLpgFilters);
    });
    
    // Set up per-page selector
    document.getElementById('lpg-per-page').addEventListener('change', function() {
        lpgPagination.perPage = parseInt(this.value);
        lpgPagination.currentPage = 1;
        displayLpgPage();
    });
    
    // Initial display
    displayLpgPage();
});

function applyLpgFilters() {
    const rows = Array.from(document.querySelectorAll('.lpg-row'));
    const filters = Array.from(document.querySelectorAll('.lpg-filter'));
    
    lpgPagination.filteredRows = rows.filter(row => {
        return filters.every(filter => {
            const columnIndex = parseInt(filter.dataset.column);
            const cellText = row.cells[columnIndex].textContent.toLowerCase();
            const filterText = filter.value.toLowerCase();
            return cellText.includes(filterText);
        });
    });
    
    lpgPagination.currentPage = 1;
    displayLpgPage();
}

function displayLpgPage() {
    const start = (lpgPagination.currentPage - 1) * lpgPagination.perPage;
    const end = start + lpgPagination.perPage;
    
    // Hide all rows
    document.querySelectorAll('.lpg-row').forEach(row => row.style.display = 'none');
    
    // Show rows for current page
    lpgPagination.filteredRows.slice(start, end).forEach(row => row.style.display = '');
    
    // Update pagination info
    const totalRecords = lpgPagination.filteredRows.length;
    document.getElementById('lpg-start').textContent = totalRecords > 0 ? start + 1 : 0;
    document.getElementById('lpg-end').textContent = Math.min(end, totalRecords);
    document.getElementById('lpg-filtered').textContent = totalRecords;
    
    renderLpgPagination();
}

function renderLpgPagination() {
    const totalPages = Math.ceil(lpgPagination.filteredRows.length / lpgPagination.perPage);
    const paginationContainer = document.getElementById('lpg-pagination');
    paginationContainer.innerHTML = '';
    
    if (totalPages <= 1) return;
    
    // Previous button
    const prevBtn = document.createElement('button');
    prevBtn.textContent = 'Previous';
    prevBtn.className = 'px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed';
    prevBtn.disabled = lpgPagination.currentPage === 1;
    prevBtn.onclick = () => changeLpgPage(lpgPagination.currentPage - 1);
    paginationContainer.appendChild(prevBtn);
    
    // Page numbers
    const startPage = Math.max(1, lpgPagination.currentPage - 2);
    const endPage = Math.min(totalPages, lpgPagination.currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.textContent = i;
        pageBtn.className = `px-3 py-1 text-sm border rounded-md ${i === lpgPagination.currentPage ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-100'}`;
        pageBtn.onclick = () => changeLpgPage(i);
        paginationContainer.appendChild(pageBtn);
    }
    
    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.textContent = 'Next';
    nextBtn.className = 'px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed';
    nextBtn.disabled = lpgPagination.currentPage === totalPages;
    nextBtn.onclick = () => changeLpgPage(lpgPagination.currentPage + 1);
    paginationContainer.appendChild(nextBtn);
}

function changeLpgPage(page) {
    lpgPagination.currentPage = page;
    displayLpgPage();
}

// Download functions
function downloadPDF() {
    const campus = '<?php echo htmlspecialchars($selected_campus); ?>';
    const year = '<?php echo htmlspecialchars($selected_year); ?>';
    window.location.href = `../api/download_report.php?type=pdf&scope=1&campus=${encodeURIComponent(campus)}&year=${encodeURIComponent(year)}&role=rgo`;
}

function downloadCSV() {
    const campus = '<?php echo htmlspecialchars($selected_campus); ?>';
    const year = '<?php echo htmlspecialchars($selected_year); ?>';
    window.location.href = `../api/download_report.php?type=csv&scope=1&campus=${encodeURIComponent(campus)}&year=${encodeURIComponent(year)}&role=rgo`;
}
</script>

<?php
// Include footer
$helper->includeTemplate('footer');
?>
