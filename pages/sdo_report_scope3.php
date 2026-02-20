<?php
$page_title = 'Scope 3 Report - Campus Level';
require_once 'tailwind-header.php';

// SDO users see only their campus data
$selected_campus = $user['campus'];
$selected_year = $_GET['year'] ?? null;
$selected_category = $_GET['category'] ?? null;

// Get years from multiple tables
$years = [];
$year_queries = [
    "SELECT DISTINCT year FROM electricity_consumption WHERE year IS NOT NULL",
    "SELECT DISTINCT Year as year FROM tblsolidwastesegregated WHERE Year IS NOT NULL",
    "SELECT DISTINCT Year as year FROM tblflight WHERE Year IS NOT NULL",
    "SELECT DISTINCT YearTransact as year FROM tblaccommodation WHERE YearTransact IS NOT NULL",
    "SELECT DISTINCT YearTransaction as year FROM tblfoodwaste WHERE YearTransaction IS NOT NULL"
];
foreach ($year_queries as $query) {
    $result = $db->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['year'])) $years[$row['year']] = true;
        }
    }
}
$years = array_keys($years);
rsort($years);

// Scope 3: All other indirect emissions
$scope_categories = ['water', 'treated_water', 'waste_segregated', 'waste_unsegregated', 'flight', 'accommodation', 'food'];
$breakdown = AccessControl::getGHGBreakdownByCategory($db, $selected_campus, $selected_year);
$breakdown = array_filter($breakdown, function($key) use ($scope_categories) {
    return in_array($key, $scope_categories);
}, ARRAY_FILTER_USE_KEY);

$total_emissions = array_sum(array_column($breakdown, 'kg_co2'));
$total_records = array_sum(array_column($breakdown, 'records'));

// Get campus total (all scopes combined) if campus filter is active
$campus_total = null;
if ($selected_campus) {
    $campus_total = AccessControl::getRoleBasedGHGTotals($db, 'Sustainable Development Office', $selected_campus, $selected_year);
}

// Fetch detailed records for each Scope 3 category
$water_records = [];
$treated_water_records = [];
$waste_seg_records = [];
$waste_unseg_records = [];
$flight_records = [];
$accommodation_records = [];
$food_records = [];

// Helper to build WHERE clause
$buildWhere = function($campus_col, $year_col) use ($selected_campus, $selected_year) {
    $where = [];
    $params = [];
    $types = '';
    if ($selected_campus) {
        $where[] = "$campus_col = ?";
        $params[] = $selected_campus;
        $types .= 's';
    }
    if ($selected_year && $year_col) {
        $where[] = "$year_col = ?";
        $params[] = $selected_year;
        $types .= 's';
    }
    return [count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '', $types, $params];
};

// Water
list($where, $types, $params) = $buildWhere('Campus', null);
$query = "SELECT * FROM tblwater $where ORDER BY Date DESC LIMIT 1000";
$stmt = $db->prepare($query);
if (count($params) > 0) $stmt->bind_param($types, ...$params);
$stmt->execute();
$water_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Treated Water
list($where, $types, $params) = $buildWhere('Campus', null);
$query = "SELECT * FROM tbltreatedwater $where ORDER BY Month DESC LIMIT 1000";
$stmt = $db->prepare($query);
if (count($params) > 0) $stmt->bind_param($types, ...$params);
$stmt->execute();
$treated_water_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Waste Segregated
list($where, $types, $params) = $buildWhere('Campus', 'Year');
$query = "SELECT * FROM tblsolidwastesegregated $where ORDER BY Year DESC, Month LIMIT 1000";
$stmt = $db->prepare($query);
if (count($params) > 0) $stmt->bind_param($types, ...$params);
$stmt->execute();
$waste_seg_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Waste Unsegregated
list($where, $types, $params) = $buildWhere('Campus', 'Year');
$query = "SELECT * FROM tblsolidwasteunsegregated $where ORDER BY Year DESC, Month LIMIT 1000";
$stmt = $db->prepare($query);
if (count($params) > 0) $stmt->bind_param($types, ...$params);
$stmt->execute();
$waste_unseg_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Flight
list($where, $types, $params) = $buildWhere('Campus', 'Year');
$query = "SELECT * FROM tblflight $where ORDER BY Year DESC, TravelDate LIMIT 1000";
$stmt = $db->prepare($query);
if (count($params) > 0) $stmt->bind_param($types, ...$params);
$stmt->execute();
$flight_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Accommodation
list($where, $types, $params) = $buildWhere('Campus', 'YearTransact');
$query = "SELECT * FROM tblaccommodation $where ORDER BY YearTransact DESC, TravelDateFrom LIMIT 1000";
$stmt = $db->prepare($query);
if (count($params) > 0) $stmt->bind_param($types, ...$params);
$stmt->execute();
$accommodation_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Food
list($where, $types, $params) = $buildWhere('Campus', 'YearTransaction');
$query = "SELECT * FROM tblfoodwaste $where ORDER BY YearTransaction DESC, Month LIMIT 1000";
$stmt = $db->prepare($query);
if (count($params) > 0) $stmt->bind_param($types, ...$params);
$stmt->execute();
$food_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div>
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-globe text-blue-600 mr-2"></i>
                Scope 3 Report: Other Indirect Emissions
            </h1>
            <p class="text-gray-600 mt-1">Water, Waste, Travel, and Food Consumption</p>
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">All Categories</option>
                    <option value="water" <?php echo ($selected_category === 'water') ? 'selected' : ''; ?>>Water Only</option>
                    <option value="treated_water" <?php echo ($selected_category === 'treated_water') ? 'selected' : ''; ?>>Treated Water Only</option>
                    <option value="waste_segregated" <?php echo ($selected_category === 'waste_segregated') ? 'selected' : ''; ?>>Waste Segregated Only</option>
                    <option value="waste_unsegregated" <?php echo ($selected_category === 'waste_unsegregated') ? 'selected' : ''; ?>>Waste Unsegregated Only</option>
                    <option value="flight" <?php echo ($selected_category === 'flight') ? 'selected' : ''; ?>>Flight Only</option>
                    <option value="accommodation" <?php echo ($selected_category === 'accommodation') ? 'selected' : ''; ?>>Accommodation Only</option>
                    <option value="food" <?php echo ($selected_category === 'food') ? 'selected' : ''; ?>>Food Only</option>
                </select>
            </div>
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
            <?php if ($selected_year || $selected_category): ?>
                <a href="?" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Emissions</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400"><?php echo number_format($total_emissions, 2); ?> kg</p>
            <p class="text-xs text-gray-500 dark:text-gray-500"><?php echo number_format($total_emissions / 1000, 4); ?> tCO2</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Records</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-200"><?php echo number_format($total_records); ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Trees Needed</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400"><?php echo number_format(ceil($total_emissions / 21)); ?></p>
        </div>
    </div>

    <!-- Summary Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 border-b">
            <h2 class="text-xl font-semibold">Summary by Category</h2>
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

    <!-- Water Records -->
    <?php if ((!$selected_category || $selected_category === 'water') && !empty($water_records)): ?>
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 border-b bg-blue-50 flex justify-between items-center">
            <h2 class="text-xl font-semibold"><i class="fas fa-droplet text-blue-600 mr-2"></i>Water Consumption (<span id="water-count"><?php echo count($water_records); ?></span>)</h2>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Rows per page:</label>
                <select id="water-per-page" class="px-2 py-1 border rounded text-sm">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="water-table">
                <thead class="bg-gray-50">
                    <tr>
                        <?php if (!empty($water_records)): foreach (array_keys($water_records[0]) as $col): ?>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"><?php echo htmlspecialchars($col); ?></th>
                        <?php endforeach; endif; ?>
                    </tr>
                    <tr class="bg-white">
                        <?php if (!empty($water_records)): $colIdx = 0; foreach (array_keys($water_records[0]) as $col): ?>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="water-table" data-col="<?php echo $colIdx++; ?>" placeholder="Search..."></th>
                        <?php endforeach; endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($water_records as $rec): ?>
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
                Showing <span id="water-start">1</span> to <span id="water-end">25</span> of <span id="water-total"><?php echo count($water_records); ?></span> entries
            </div>
            <div class="flex gap-2" id="water-pagination"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Treated Water Records -->
    <?php if ((!$selected_category || $selected_category === 'treated_water') && !empty($treated_water_records)): ?>
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 border-b bg-cyan-50 flex justify-between items-center">
            <h2 class="text-xl font-semibold"><i class="fas fa-faucet text-cyan-600 mr-2"></i>Treated Water (<span id="treated-count"><?php echo count($treated_water_records); ?></span>)</h2>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Rows per page:</label>
                <select id="treated-per-page" class="px-2 py-1 border rounded text-sm">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="treated-table">
                <thead class="bg-gray-50">
                    <tr>
                        <?php if (!empty($treated_water_records)): foreach (array_keys($treated_water_records[0]) as $col): ?>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"><?php echo htmlspecialchars($col); ?></th>
                        <?php endforeach; endif; ?>
                    </tr>
                    <tr class="bg-white">
                        <?php if (!empty($treated_water_records)): $colIdx = 0; foreach (array_keys($treated_water_records[0]) as $col): ?>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="treated-table" data-col="<?php echo $colIdx++; ?>" placeholder="Search..."></th>
                        <?php endforeach; endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($treated_water_records as $rec): ?>
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
                Showing <span id="treated-start">1</span> to <span id="treated-end">25</span> of <span id="treated-total"><?php echo count($treated_water_records); ?></span> entries
            </div>
            <div class="flex gap-2" id="treated-pagination"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Waste Segregated Records -->
    <?php if ((!$selected_category || $selected_category === 'waste_segregated') && !empty($waste_seg_records)): ?>
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 border-b bg-green-50 flex justify-between items-center">
            <h2 class="text-xl font-semibold"><i class="fas fa-recycle text-green-600 mr-2"></i>Waste Segregated (<span id="waste-seg-count"><?php echo count($waste_seg_records); ?></span>)</h2>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Rows per page:</label>
                <select id="waste-seg-per-page" class="px-2 py-1 border rounded text-sm">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="waste-seg-table">
                <thead class="bg-gray-50">
                    <tr>
                        <?php if (!empty($waste_seg_records)): foreach (array_keys($waste_seg_records[0]) as $col): ?>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"><?php echo htmlspecialchars($col); ?></th>
                        <?php endforeach; endif; ?>
                    </tr>
                    <tr class="bg-white">
                        <?php if (!empty($waste_seg_records)): $colIdx = 0; foreach (array_keys($waste_seg_records[0]) as $col): ?>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="waste-seg-table" data-col="<?php echo $colIdx++; ?>" placeholder="Search..."></th>
                        <?php endforeach; endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($waste_seg_records as $rec): ?>
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
                Showing <span id="waste-seg-start">1</span> to <span id="waste-seg-end">25</span> of <span id="waste-seg-total"><?php echo count($waste_seg_records); ?></span> entries
            </div>
            <div class="flex gap-2" id="waste-seg-pagination"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Waste Unsegregated Records -->
    <?php if ((!$selected_category || $selected_category === 'waste_unsegregated') && !empty($waste_unseg_records)): ?>
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
            <h2 class="text-xl font-semibold"><i class="fas fa-trash text-gray-600 mr-2"></i>Waste Unsegregated (<span id="waste-unseg-count"><?php echo count($waste_unseg_records); ?></span>)</h2>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Rows per page:</label>
                <select id="waste-unseg-per-page" class="px-2 py-1 border rounded text-sm">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="waste-unseg-table">
                <thead class="bg-gray-50">
                    <tr>
                        <?php if (!empty($waste_unseg_records)): foreach (array_keys($waste_unseg_records[0]) as $col): ?>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"><?php echo htmlspecialchars($col); ?></th>
                        <?php endforeach; endif; ?>
                    </tr>
                    <tr class="bg-white">
                        <?php if (!empty($waste_unseg_records)): $colIdx = 0; foreach (array_keys($waste_unseg_records[0]) as $col): ?>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="waste-unseg-table" data-col="<?php echo $colIdx++; ?>" placeholder="Search..."></th>
                        <?php endforeach; endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($waste_unseg_records as $rec): ?>
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
                Showing <span id="waste-unseg-start">1</span> to <span id="waste-unseg-end">25</span> of <span id="waste-unseg-total"><?php echo count($waste_unseg_records); ?></span> entries
            </div>
            <div class="flex gap-2" id="waste-unseg-pagination"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Flight Records -->
    <?php if ((!$selected_category || $selected_category === 'flight') && !empty($flight_records)): ?>
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 border-b bg-indigo-50 flex justify-between items-center">
            <h2 class="text-xl font-semibold"><i class="fas fa-plane text-indigo-600 mr-2"></i>Flight Travel (<span id="flight-count"><?php echo count($flight_records); ?></span>)</h2>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Rows per page:</label>
                <select id="flight-per-page" class="px-2 py-1 border rounded text-sm">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="flight-table">
                <thead class="bg-gray-50">
                    <tr>
                        <?php if (!empty($flight_records)): foreach (array_keys($flight_records[0]) as $col): ?>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"><?php echo htmlspecialchars($col); ?></th>
                        <?php endforeach; endif; ?>
                    </tr>
                    <tr class="bg-white">
                        <?php if (!empty($flight_records)): $colIdx = 0; foreach (array_keys($flight_records[0]) as $col): ?>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="flight-table" data-col="<?php echo $colIdx++; ?>" placeholder="Search..."></th>
                        <?php endforeach; endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($flight_records as $rec): ?>
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
                Showing <span id="flight-start">1</span> to <span id="flight-end">25</span> of <span id="flight-total"><?php echo count($flight_records); ?></span> entries
            </div>
            <div class="flex gap-2" id="flight-pagination"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Accommodation Records -->
    <?php if ((!$selected_category || $selected_category === 'accommodation') && !empty($accommodation_records)): ?>
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 border-b bg-purple-50 flex justify-between items-center">
            <h2 class="text-xl font-semibold"><i class="fas fa-hotel text-purple-600 mr-2"></i>Accommodation (<span id="accommodation-count"><?php echo count($accommodation_records); ?></span>)</h2>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Rows per page:</label>
                <select id="accommodation-per-page" class="px-2 py-1 border rounded text-sm">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="accommodation-table">
                <thead class="bg-gray-50">
                    <tr>
                        <?php if (!empty($accommodation_records)): foreach (array_keys($accommodation_records[0]) as $col): ?>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"><?php echo htmlspecialchars($col); ?></th>
                        <?php endforeach; endif; ?>
                    </tr>
                    <tr class="bg-white">
                        <?php if (!empty($accommodation_records)): $colIdx = 0; foreach (array_keys($accommodation_records[0]) as $col): ?>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="accommodation-table" data-col="<?php echo $colIdx++; ?>" placeholder="Search..."></th>
                        <?php endforeach; endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($accommodation_records as $rec): ?>
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
                Showing <span id="accommodation-start">1</span> to <span id="accommodation-end">25</span> of <span id="accommodation-total"><?php echo count($accommodation_records); ?></span> entries
            </div>
            <div class="flex gap-2" id="accommodation-pagination"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Food Records -->
    <?php if ((!$selected_category || $selected_category === 'food') && !empty($food_records)): ?>
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 border-b bg-green-50 flex justify-between items-center">
            <h2 class="text-xl font-semibold"><i class="fas fa-utensils text-green-600 mr-2"></i>Food Waste (<span id="food-count"><?php echo count($food_records); ?></span>)</h2>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Rows per page:</label>
                <select id="food-per-page" class="px-2 py-1 border rounded text-sm">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="food-table">
                <thead class="bg-gray-50">
                    <tr>
                        <?php if (!empty($food_records)): foreach (array_keys($food_records[0]) as $col): ?>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"><?php echo htmlspecialchars($col); ?></th>
                        <?php endforeach; endif; ?>
                    </tr>
                    <tr class="bg-white">
                        <?php if (!empty($food_records)): $colIdx = 0; foreach (array_keys($food_records[0]) as $col): ?>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="food-table" data-col="<?php echo $colIdx++; ?>" placeholder="Search..."></th>
                        <?php endforeach; endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($food_records as $rec): ?>
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
                Showing <span id="food-start">1</span> to <span id="food-end">25</span> of <span id="food-total"><?php echo count($food_records); ?></span> entries
            </div>
            <div class="flex gap-2" id="food-pagination"></div>
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
    doc.text('Scope 3 Report', 14, 20);
    doc.autoTable({
        startY: 30,
        head: [['Category', 'Office', 'Records', 'Consumption', 'CO2 (kg)']],
        body: [<?php foreach ($breakdown as $data): ?>['<?php echo addslashes($data['name']); ?>', '<?php echo addslashes($data['office']); ?>', '<?php echo $data['records']; ?>', '<?php echo number_format($data['consumption'], 2) . " " . $data['unit']; ?>', '<?php echo number_format($data['kg_co2'], 2); ?>'],<?php endforeach; ?>],
        foot: [['TOTAL', '', '<?php echo $total_records; ?>', '-', '<?php echo number_format($total_emissions, 2); ?>']],
    });
    doc.save('scope3_report.pdf');
}

function downloadCSV() {
    let csv = '';
    
    // Helper function to export table
    function exportTable(tableId, title) {
        const table = document.getElementById(tableId);
        if (!table) return '';
        
        let output = '=== ' + title + ' ===\n';
        
        // Get headers
        const headers = [];
        table.querySelectorAll('thead tr:first-child th').forEach(th => {
            headers.push(th.textContent.trim());
        });
        output += headers.map(h => '"' + h + '"').join(',') + '\n';
        
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
                output += values.join(',') + '\n';
            }
        });
        output += '\n\n';
        return output;
    }
    
    // Export each visible table
    csv += exportTable('water-table', 'WATER CONSUMPTION RECORDS');
    csv += exportTable('treated-table', 'TREATED WATER RECORDS');
    csv += exportTable('waste-seg-table', 'WASTE SEGREGATED RECORDS');
    csv += exportTable('waste-unseg-table', 'WASTE UNSEGREGATED RECORDS');
    csv += exportTable('flight-table', 'FLIGHT TRAVEL RECORDS');
    csv += exportTable('accommodation-table', 'ACCOMMODATION RECORDS');
    csv += exportTable('food-table', 'FOOD WASTE RECORDS');
    
    csv += '\n\n=== SUMMARY ===\n';
    csv += 'Category,Office,Records,Consumption,Unit,CO2 (kg)\n';
    <?php foreach ($breakdown as $data): ?>
    csv += '"<?php echo addslashes($data['name']); ?>","<?php echo addslashes($data['office']); ?>",<?php echo $data['records']; ?>,<?php echo $data['consumption']; ?>,"<?php echo $data['unit']; ?>",<?php echo $data['kg_co2']; ?>\n';
    <?php endforeach; ?>
    csv += 'TOTAL,,-,-,-,<?php echo $total_emissions; ?>\n';
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'scope3_detailed_report_<?php echo date('Y-m-d'); ?>.csv';
    a.click();
}

// Multi-column filtering and pagination for all tables
document.addEventListener('DOMContentLoaded', function() {
    const tables = {
        'water-table': { currentPage: 1, perPage: 25, filteredRows: [], countId: 'water-count', prefix: 'water' },
        'treated-table': { currentPage: 1, perPage: 25, filteredRows: [], countId: 'treated-count', prefix: 'treated' },
        'waste-seg-table': { currentPage: 1, perPage: 25, filteredRows: [], countId: 'waste-seg-count', prefix: 'waste-seg' },
        'waste-unseg-table': { currentPage: 1, perPage: 25, filteredRows: [], countId: 'waste-unseg-count', prefix: 'waste-unseg' },
        'flight-table': { currentPage: 1, perPage: 25, filteredRows: [], countId: 'flight-count', prefix: 'flight' },
        'accommodation-table': { currentPage: 1, perPage: 25, filteredRows: [], countId: 'accommodation-count', prefix: 'accommodation' },
        'food-table': { currentPage: 1, perPage: 25, filteredRows: [], countId: 'food-count', prefix: 'food' }
    };
    
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
        
        const countElem = document.getElementById(tables[tableId].countId);
        if (countElem) countElem.textContent = tables[tableId].filteredRows.length;
        
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
        
        const startElem = document.getElementById(config.prefix + '-start');
        const endElem = document.getElementById(config.prefix + '-end');
        const totalElem = document.getElementById(config.prefix + '-total');
        
        if (startElem) startElem.textContent = filteredRows.length > 0 ? start + 1 : 0;
        if (endElem) endElem.textContent = end;
        if (totalElem) totalElem.textContent = filteredRows.length;
        
        renderPagination(tableId, filteredRows.length);
    }
    
    function renderPagination(tableId, totalRows) {
        const config = tables[tableId];
        const totalPages = Math.ceil(totalRows / config.perPage);
        const paginationDiv = document.getElementById(config.prefix + '-pagination');
        if (!paginationDiv) return;
        
        let html = '';
        html += `<button onclick=\"changePageScope3('${tableId}', ${config.currentPage - 1})\" ${config.currentPage === 1 ? 'disabled' : ''} class=\"px-3 py-1 border rounded text-sm ${config.currentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-100'}\">&laquo; Prev</button>`;\n        \n        for (let i = 1; i <= totalPages; i++) {\n            if (i === 1 || i === totalPages || (i >= config.currentPage - 2 && i <= config.currentPage + 2)) {\n                html += `<button onclick=\"changePageScope3('${tableId}', ${i})\" class=\"px-3 py-1 border rounded text-sm ${i === config.currentPage ? 'bg-blue-600 text-white font-semibold' : 'bg-white hover:bg-gray-100'}\">${i}</button>`;\n            } else if (i === config.currentPage - 3 || i === config.currentPage + 3) {\n                html += `<span class=\"px-2 text-gray-500\">...</span>`;\n            }\n        }\n        \n        html += `<button onclick=\"changePageScope3('${tableId}', ${config.currentPage + 1})\" ${config.currentPage === totalPages || totalPages === 0 ? 'disabled' : ''} class=\"px-3 py-1 border rounded text-sm ${config.currentPage === totalPages || totalPages === 0 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-100'}\">Next &raquo;</button>`;\n        \n        paginationDiv.innerHTML = html;\n    }\n    \n    window.changePageScope3 = function(tableId, page) {\n        const config = tables[tableId];\n        if (!config) return;\n        \n        const table = document.getElementById(tableId);\n        const tbody = table.getElementsByTagName('tbody')[0];\n        const allRows = Array.from(tbody.getElementsByTagName('tr'));\n        const totalRows = hasActiveFilters(tableId) ? config.filteredRows.length : allRows.length;\n        const totalPages = Math.ceil(totalRows / config.perPage);\n        \n        if (page >= 1 && page <= totalPages) {\n            config.currentPage = page;\n            displayPage(tableId);\n        }\n    };\n    \n    // Per page handlers\n    Object.keys(tables).forEach(tableId => {\n        const config = tables[tableId];\n        const select = document.getElementById(config.prefix + '-per-page');\n        if (select) {\n            select.addEventListener('change', function() {\n                config.perPage = parseInt(this.value);\n                config.currentPage = 1;\n                displayPage(tableId);\n            });\n        }\n    });\n    \n    // Filter handlers\n    const filterInputs = document.querySelectorAll('.filter-input');\n    filterInputs.forEach(input => {\n        input.addEventListener('keyup', function() {\n            const tableId = this.getAttribute('data-table');\n            if (tables[tableId]) applyFilters(tableId);\n        });\n    });\n    \n    // Initialize all tables\n    Object.keys(tables).forEach(tableId => {\n        if (document.getElementById(tableId)) {\n            displayPage(tableId);\n        }\n    });\n});
</script>

<?php require_once 'tailwind-footer.php'; ?>
