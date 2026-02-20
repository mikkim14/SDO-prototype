<?php
$page_title = 'Scope 3 Report - EMU';
require_once 'tailwind-header.php';
require_once '../includes/GHGCalculator.php';

// EMU can only see their campus data
$selected_campus = $user['campus'];
$selected_year = $_GET['year'] ?? '';
$selected_category = $_GET['category'] ?? '';

// Get available years from all Scope 3 sources
$years = [];
$year_queries = [
    "SELECT DISTINCT YEAR(date) as year FROM tblwater WHERE date IS NOT NULL AND campus = ?",
    "SELECT DISTINCT Year as year FROM tbltreatedwater WHERE Year IS NOT NULL AND Campus = ?",
    "SELECT DISTINCT Year as year FROM tblsolidwastesegregated WHERE Year IS NOT NULL AND campus = ?",
    "SELECT DISTINCT Year as year FROM tblsolidwasteunsegregated WHERE Year IS NOT NULL AND campus = ?",
];

foreach ($year_queries as $query) {
    $stmt = $db->prepare($query);
    if ($stmt) {
        $stmt->bind_param("s", $selected_campus);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            if ($row['year'] && !in_array($row['year'], $years)) {
                $years[] = $row['year'];
            }
        }
        $stmt->close();
    }
}
sort($years);

// Build where clause
$where = "WHERE campus = ?";
$params = [$selected_campus];
$types = "s";

$where_year = $where;
if ($selected_year) {
    $where_year .= " AND YEAR(date) = ?";
}

$where_treated = "WHERE Campus = ?";
if ($selected_year) {
    $where_treated .= " AND Year = ?";
}

// Calculate totals for Scope 3
$scope_total = 0;
$categories_data = [];

// Water
$query = "SELECT * FROM tblwater $where_year ORDER BY date DESC LIMIT 1000";
$stmt = $db->prepare($query);
if ($selected_year) {
    $stmt->bind_param("ss", $selected_campus, $selected_year);
} else {
    $stmt->bind_param("s", $selected_campus);
}
$stmt->execute();
$result = $stmt->get_result();
$water_records = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$water_total = 0;
foreach ($water_records as $record) {
    $kg_co2 = ($record['Consumption'] ?? 0) * 0.344;
    $water_total += $kg_co2;
}
$categories_data['water'] = ['records' => $water_records, 'total_kg_co2' => $water_total];
$scope_total += $water_total;

// Treated Water
$query = "SELECT * FROM tbltreatedwater $where_treated ORDER BY Year DESC, Month LIMIT 1000";
$stmt = $db->prepare($query);
if ($selected_year) {
    $stmt->bind_param("ss", $selected_campus, $selected_year);
} else {
    $stmt->bind_param("s", $selected_campus);
}
$stmt->execute();
$result = $stmt->get_result();
$treated_records = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$treated_total = 0;
foreach ($treated_records as $record) {
    $kg_co2 = ($record['TreatedWaterVolume'] ?? 0) * 1.062;
    $treated_total += $kg_co2;
}
$categories_data['treated_water'] = ['records' => $treated_records, 'total_kg_co2' => $treated_total];
$scope_total += $treated_total;

// Waste Segregated
$where_waste = $where;
if ($selected_year) {
    $where_waste .= " AND Year = ?";
}
$query = "SELECT * FROM tblsolidwastesegregated $where_waste ORDER BY Year DESC, Month LIMIT 1000";
$stmt = $db->prepare($query);
if ($selected_year) {
    $stmt->bind_param("ss", $selected_campus, $selected_year);
} else {
    $stmt->bind_param("s", $selected_campus);
}
$stmt->execute();
$result = $stmt->get_result();
$waste_seg_records = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$waste_seg_total = 0;
foreach ($waste_seg_records as $record) {
    $kg_co2 = ($record['GHGEmissionKGCO2e'] ?? 0);
    $waste_seg_total += $kg_co2;
}
$categories_data['waste_seg'] = ['records' => $waste_seg_records, 'total_kg_co2' => $waste_seg_total];
$scope_total += $waste_seg_total;

// Waste Unsegregated
$query = "SELECT * FROM tblsolidwasteunsegregated $where_waste ORDER BY Year DESC, Month LIMIT 1000";
$stmt = $db->prepare($query);
if ($selected_year) {
    $stmt->bind_param("ss", $selected_campus, $selected_year);
} else {
    $stmt->bind_param("s", $selected_campus);
}
$stmt->execute();
$result = $stmt->get_result();
$waste_unseg_records = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$waste_unseg_total = 0;
foreach ($waste_unseg_records as $record) {
    $kg_co2 = ($record['GHGEmissionKGCO2e'] ?? 0);
    $waste_unseg_total += $kg_co2;
}
$categories_data['waste_unseg'] = ['records' => $waste_unseg_records, 'total_kg_co2' => $waste_unseg_total];
$scope_total += $waste_unseg_total;

$total_records = count($water_records) + count($treated_records) + count($waste_seg_records) + count($waste_unseg_records);

// Apply category filter if selected
if ($selected_category && isset($categories_data[$selected_category])) {
    $categories_data = [$selected_category => $categories_data[$selected_category]];
    $scope_total = $categories_data[$selected_category]['total_kg_co2'];
    $total_records = count($categories_data[$selected_category]['records']);
}

// Build breakdown for analytics
$breakdown = [];
$category_names = [
    'water' => 'Water Consumption',
    'treated_water' => 'Treated Water',
    'waste_seg' => 'Waste (Segregated)',
    'waste_unseg' => 'Waste (Unsegregated)'
];
foreach ($categories_data as $cat_key => $cat_data) {
    $consumption = 0;
    $unit = '';
    if ($cat_key === 'water') {
        foreach ($cat_data['records'] as $rec) {
            $consumption += ($rec['Consumption'] ?? 0);
        }
        $unit = 'm³';
    } elseif ($cat_key === 'treated_water') {
        foreach ($cat_data['records'] as $rec) {
            $consumption += ($rec['TreatedWaterVolume'] ?? 0);
        }
        $unit = 'm³';
    } elseif ($cat_key === 'waste_seg') {
        foreach ($cat_data['records'] as $rec) {
            $consumption += ($rec['QuantityInKG'] ?? 0);
        }
        $unit = 'kg';
    } elseif ($cat_key === 'waste_unseg') {
        foreach ($cat_data['records'] as $rec) {
            $consumption += ($rec['QuantityInKG'] ?? 0);
        }
        $unit = 'kg';
    }
    $breakdown[$cat_key] = [
        'name' => $category_names[$cat_key],
        'kg_co2' => $cat_data['total_kg_co2'],
        'records' => count($cat_data['records']),
        'consumption' => $consumption,
        'unit' => $unit
    ];
}
?>

<div>
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-leaf text-green-600 mr-2"></i>
                Scope 3 Report: Other Indirect Emissions
            </h1>
            <p class="text-gray-600 mt-1">Water, Treated Water & Waste • <?php echo htmlspecialchars($selected_campus); ?> Campus</p>
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
                        <option value="<?php echo htmlspecialchars($year); ?>" <?php echo ($selected_year == $year) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($year); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">All Categories</option>
                    <option value="water" <?php echo $selected_category === 'water' ? 'selected' : ''; ?>>Water</option>
                    <option value="treated_water" <?php echo $selected_category === 'treated_water' ? 'selected' : ''; ?>>Treated Water</option>
                    <option value="waste_seg" <?php echo $selected_category === 'waste_seg' ? 'selected' : ''; ?>>Waste (Segregated)</option>
                    <option value="waste_unseg" <?php echo $selected_category === 'waste_unseg' ? 'selected' : ''; ?>>Waste (Unsegregated)</option>
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

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Emissions</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400"><?php echo number_format($scope_total, 2); ?> <span class="text-sm">kg CO₂</span></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Records</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-200"><?php echo number_format($total_records); ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Trees Needed</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400"><?php echo number_format(ceil($scope_total / 21)); ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <?php 
            $consumption_label = 'Total Consumption';
            $consumption_value = 0;
            $consumption_unit = '';
            
            if ($selected_category === 'water') {
                $consumption_label = 'Water Consumption';
                foreach ($water_records as $rec) {
                    $consumption_value += ($rec['Consumption'] ?? 0);
                }
                $consumption_unit = 'm³';
            } elseif ($selected_category === 'treated_water') {
                $consumption_label = 'Treated Water Volume';
                foreach ($treated_records as $rec) {
                    $consumption_value += ($rec['TreatedWaterVolume'] ?? 0);
                }
                $consumption_unit = 'm³';
            } elseif ($selected_category === 'waste_seg') {
                $consumption_label = 'Waste Quantity';
                foreach ($waste_seg_records as $rec) {
                    $consumption_value += ($rec['QuantityInKG'] ?? 0);
                }
                $consumption_unit = 'kg';
            } elseif ($selected_category === 'waste_unseg') {
                $consumption_label = 'Waste Amount';
                foreach ($waste_unseg_records as $rec) {
                    $consumption_value += ($rec['QuantityInKG'] ?? 0);
                }
                $consumption_unit = 'kg';
            } else {
                // All categories combined
                $consumption_label = 'Combined Total';
                foreach ($water_records as $rec) {
                    $consumption_value += ($rec['Consumption'] ?? 0);
                }
                foreach ($treated_records as $rec) {
                    $consumption_value += ($rec['TreatedWaterVolume'] ?? 0);
                }
                foreach ($waste_seg_records as $rec) {
                    $consumption_value += ($rec['QuantityInKG'] ?? 0);
                }
                foreach ($waste_unseg_records as $rec) {
                    $consumption_value += ($rec['QuantityInKG'] ?? 0);
                }
                $consumption_unit = 'mixed';
            }
            ?>
            <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($consumption_label); ?></p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?php echo number_format($consumption_value, 2); ?> <span class="text-sm"><?php echo htmlspecialchars($consumption_unit); ?></span></p>
        </div>
    </div>

    <!-- Per-Category Analytics (shown when filtering by category) 
    <?php if ($selected_category && count($breakdown) > 0): ?>
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
            <i class="fas fa-chart-bar mr-2"></i>
            Category Breakdown
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php foreach ($breakdown as $cat => $data): ?>
                <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900 dark:to-green-800 rounded-lg shadow p-4 border-l-4 border-green-500">
                    <h4 class="text-sm font-semibold text-green-900 dark:text-green-200 mb-3"><?php echo htmlspecialchars($data['name']); ?></h4>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-green-700 dark:text-green-300">Emissions:</span>
                            <span class="text-sm font-bold text-green-900 dark:text-green-100"><?php echo number_format($data['kg_co2'], 2); ?> kg</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-green-700 dark:text-green-300">Records:</span>
                            <span class="text-sm font-bold text-green-900 dark:text-green-100"><?php echo number_format($data['records']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-green-700 dark:text-green-300">Trees Needed:</span>
                            <span class="text-sm font-bold text-green-700 dark:text-green-300"><?php echo number_format(ceil($data['kg_co2'] / 21)); ?></span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-green-200 dark:border-green-700">
                            <span class="text-xs text-green-700 dark:text-green-300">Consumption:</span>
                            <span class="text-sm font-medium text-green-800 dark:text-green-200"><?php echo number_format($data['consumption'], 2); ?> <?php echo htmlspecialchars($data['unit']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div> -->
    <?php endif; ?>

    <!-- Water Records -->
    <?php if ((!$selected_category || $selected_category === 'water') && !empty($water_records)): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 border-b dark:border-gray-700 bg-blue-50 dark:bg-blue-900 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200"><i class="fas fa-droplet text-blue-600 dark:text-blue-400 mr-2"></i>Water Consumption Records (<span id="water-count"><?php echo count($water_records); ?></span>)</h2>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600 dark:text-gray-400">Rows per page:</label>
                <select id="water-per-page" class="px-2 py-1 border rounded text-sm dark:bg-gray-700 dark:border-gray-600">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="water-table">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">ID</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Campus</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Year</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Month</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Quarter</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Date</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Category</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Prev Reading</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Curr Reading</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Consumption</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Total Amount</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Price/Liter</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">kg CO2</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Trees</th>
                    </tr>
                    <tr class="bg-white dark:bg-gray-800">
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input dark:bg-gray-700 dark:border-gray-600" data-table="water-table" data-col="0" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input dark:bg-gray-700 dark:border-gray-600" data-table="water-table" data-col="1" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input dark:bg-gray-700 dark:border-gray-600" data-table="water-table" data-col="2" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input dark:bg-gray-700 dark:border-gray-600" data-table="water-table" data-col="3" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input dark:bg-gray-700 dark:border-gray-600" data-table="water-table" data-col="4" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input dark:bg-gray-700 dark:border-gray-600" data-table="water-table" data-col="5" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input dark:bg-gray-700 dark:border-gray-600" data-table="water-table" data-col="6" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input dark:bg-gray-700 dark:border-gray-600" data-table="water-table" data-col="7" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input dark:bg-gray-700 dark:border-gray-600" data-table="water-table" data-col="8" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input dark:bg-gray-700 dark:border-gray-600" data-table="water-table" data-col="9" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input dark:bg-gray-700 dark:border-gray-600" data-table="water-table" data-col="10" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input dark:bg-gray-700 dark:border-gray-600" data-table="water-table" data-col="11" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input dark:bg-gray-700 dark:border-gray-600" data-table="water-table" data-col="12" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input dark:bg-gray-700 dark:border-gray-600" data-table="water-table" data-col="13" placeholder="Search..."></th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700">
                    <?php foreach ($water_records as $rec): 
                        $kg_co2 = ($rec['Consumption'] ?? 0) * 0.344;
                        $trees = ceil($kg_co2 / 21);
                    ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['id'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['Campus'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['Year'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['Month'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['Quarter'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['Date'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['Category'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-right text-gray-700 dark:text-gray-300"><?php echo number_format($rec['PreviousReading'] ?? 0, 2); ?></td>
                            <td class="px-2 py-2 text-xs text-right text-gray-700 dark:text-gray-300"><?php echo number_format($rec['CurrentReading'] ?? 0, 2); ?></td>
                            <td class="px-2 py-2 text-xs text-right text-gray-700 dark:text-gray-300"><?php echo number_format($rec['Consumption'] ?? 0, 2); ?></td>
                            <td class="px-2 py-2 text-xs text-right text-gray-700 dark:text-gray-300"><?php echo number_format($rec['TotalAmount'] ?? 0, 2); ?></td>
                            <td class="px-2 py-2 text-xs text-right text-gray-700 dark:text-gray-300"><?php echo number_format($rec['PricePerLiter'] ?? 0, 2); ?></td>
                            <td class="px-2 py-2 text-xs text-right font-semibold text-gray-800 dark:text-gray-200"><?php echo number_format($kg_co2, 2); ?></td>
                            <td class="px-2 py-2 text-xs text-right text-green-600 dark:text-green-400"><?php echo number_format($trees); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex justify-between items-center">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Showing <span id="water-start">1</span> to <span id="water-end">25</span> of <span id="water-total"><?php echo count($water_records); ?></span> entries
            </div>
            <div class="flex gap-2" id="water-pagination"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Treated Water Records -->
    <?php if ((!$selected_category || $selected_category === 'treated_water') && !empty($treated_records)): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 border-b dark:border-gray-700 bg-cyan-50 dark:bg-cyan-900 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200"><i class="fas fa-recycle text-cyan-600 dark:text-cyan-400 mr-2"></i>Treated Water Records (<span id="treated-count"><?php echo count($treated_records); ?></span>)</h2>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600 dark:text-gray-400">Rows per page:</label>
                <select id="treated-per-page" class="px-2 py-1 border rounded text-sm dark:bg-gray-700 dark:border-gray-600">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="treated-table">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">ID</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Campus</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Month</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Treated Volume</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Reused Volume</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Effluent Volume</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Price/Liter</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Year</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Quarter</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">kg CO2</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Trees</th>
                    </tr>
                    <tr class="bg-white dark:bg-gray-800">
                        <?php for($i = 0; $i < 11; $i++): ?>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input dark:bg-gray-700 dark:border-gray-600" data-table="treated-table" data-col="<?php echo $i; ?>" placeholder="Search..."></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700">
                    <?php foreach ($treated_records as $rec): 
                        $kg_co2 = ($rec['TreatedWaterVolume'] ?? 0) * 1.062;
                        $trees = ceil($kg_co2 / 21);
                    ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['id'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['Campus'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['Month'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-right text-gray-700 dark:text-gray-300"><?php echo number_format($rec['TreatedWaterVolume'] ?? 0, 2); ?></td>
                            <td class="px-2 py-2 text-xs text-right text-gray-700 dark:text-gray-300"><?php echo number_format($rec['ReusedTreatedWaterVolume'] ?? 0, 2); ?></td>
                            <td class="px-2 py-2 text-xs text-right text-gray-700 dark:text-gray-300"><?php echo number_format($rec['EffluentVolume'] ?? 0, 2); ?></td>
                            <td class="px-2 py-2 text-xs text-right text-gray-700 dark:text-gray-300"><?php echo number_format($rec['PricePerLiter'] ?? 0, 2); ?></td>
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['Year'] ?? 'N/A'); ?></td>
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['Quarter'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-right font-semibold text-gray-800 dark:text-gray-200"><?php echo number_format($kg_co2, 2); ?></td>
                            <td class="px-2 py-2 text-xs text-right text-green-600 dark:text-green-400"><?php echo number_format($trees); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex justify-between items-center">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Showing <span id="treated-start">1</span> to <span id="treated-end">25</span> of <span id="treated-total"><?php echo count($treated_records); ?></span> entries
            </div>
            <div class="flex gap-2" id="treated-pagination"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Waste Segregated Records -->
    <?php if ((!$selected_category || $selected_category === 'waste_seg') && !empty($waste_seg_records)): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 border-b dark:border-gray-700 bg-green-50 dark:bg-green-900 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200"><i class="fas fa-trash text-green-600 dark:text-green-400 mr-2"></i>Waste Segregated Records (<span id="wasteseg-count"><?php echo count($waste_seg_records); ?></span>)</h2>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600 dark:text-gray-400">Rows per page:</label>
                <select id="wasteseg-per-page" class="px-2 py-1 border rounded text-sm dark:bg-gray-700 dark:border-gray-600">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="wasteseg-table">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">ID</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Campus</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Year</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Quarter</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Month</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Main Category</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Sub Category</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Quantity (kg)</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">GHG kg CO2e</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">GHG t CO2e</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Trees</th>
                    </tr>
                    <tr class="bg-white dark:bg-gray-800">
                        <?php for($i = 0; $i < 11; $i++): ?>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input dark:bg-gray-700 dark:border-gray-600" data-table="wasteseg-table" data-col="<?php echo $i; ?>" placeholder="Search..."></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700">
                    <?php foreach ($waste_seg_records as $rec): 
                        $kg_co2 = ($rec['GHGEmissionKGCO2e'] ?? 0);
                        $trees = ceil($kg_co2 / 21);
                    ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['id'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['Campus'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['Year'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['Quarter'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['Month'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['MainCategory'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['SubCategory'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-right text-gray-700 dark:text-gray-300"><?php echo number_format($rec['QuantityInKG'] ?? 0, 2); ?></td>
                            <td class="px-2 py-2 text-xs text-right font-semibold text-gray-800 dark:text-gray-200"><?php echo number_format($rec['GHGEmissionKGCO2e'] ?? 0, 2); ?></td>
                            <td class="px-2 py-2 text-xs text-right text-gray-700 dark:text-gray-300"><?php echo number_format($rec['GHGEmissionTCO2e'] ?? 0, 6); ?></td>
                            <td class="px-2 py-2 text-xs text-right text-green-600 dark:text-green-400"><?php echo number_format($trees); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex justify-between items-center">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Showing <span id="wasteseg-start">1</span> to <span id="wasteseg-end">25</span> of <span id="wasteseg-total"><?php echo count($waste_seg_records); ?></span> entries
            </div>
            <div class="flex gap-2" id="wasteseg-pagination"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Waste Unsegregated Records -->
    <?php if ((!$selected_category || $selected_category === 'waste_unseg') && !empty($waste_unseg_records)): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 border-b dark:border-gray-700 bg-orange-50 dark:bg-orange-900 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200"><i class="fas fa-recycle text-orange-600 dark:text-orange-400 mr-2"></i>Waste Unsegregated Records (<span id="wasteunseg-count"><?php echo count($waste_unseg_records); ?></span>)</h2>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600 dark:text-gray-400">Rows per page:</label>
                <select id="wasteunseg-per-page" class="px-2 py-1 border rounded text-sm dark:bg-gray-700 dark:border-gray-600">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="wasteunseg-table">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">ID</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Campus</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Year</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Quarter</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Month</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Waste Type</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Quantity (kg)</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Sent Landfill (kg)</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">GHG kg CO2e</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">GHG t CO2e</th>
                        <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase whitespace-nowrap">Trees</th>
                    </tr>
                    <tr class="bg-white dark:bg-gray-800">
                        <?php for($i = 0; $i < 11; $i++): ?>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input dark:bg-gray-700 dark:border-gray-600" data-table="wasteunseg-table" data-col="<?php echo $i; ?>" placeholder="Search..."></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700">
                    <?php foreach ($waste_unseg_records as $rec): 
                        $kg_co2 = ($rec['GHGEmissionKGCO2e'] ?? 0);
                        $trees = ceil($kg_co2 / 21);
                    ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['id'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['Campus'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['Year'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['Quarter'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['Month'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($rec['WasteType'] ?? ''); ?></td>
                            <td class="px-2 py-2 text-xs text-right text-gray-700 dark:text-gray-300"><?php echo number_format($rec['QuantityInKG'] ?? 0, 2); ?></td>
                            <td class="px-2 py-2 text-xs text-right text-gray-700 dark:text-gray-300"><?php echo number_format($rec['SentToLandfillKG'] ?? 0, 2); ?></td>
                            <td class="px-2 py-2 text-xs text-right font-semibold text-gray-800 dark:text-gray-200"><?php echo number_format($rec['GHGEmissionKGCO2e'] ?? 0, 2); ?></td>
                            <td class="px-2 py-2 text-xs text-right text-gray-700 dark:text-gray-300"><?php echo number_format($rec['GHGEmissionTCO2e'] ?? 0, 6); ?></td>
                            <td class="px-2 py-2 text-xs text-right text-green-600 dark:text-green-400"><?php echo number_format($trees); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex justify-between items-center">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Showing <span id="wasteunseg-start">1</span> to <span id="wasteunseg-end">25</span> of <span id="wasteunseg-total"><?php echo count($waste_unseg_records); ?></span> entries
            </div>
            <div class="flex gap-2" id="wasteunseg-pagination"></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<script>
// Multi-table pagination and filtering
const tables = {
    'water-table': {
        currentPage: 1,
        perPage: 25,
        filteredRows: []
    },
    'treated-table': {
        currentPage: 1,
        perPage: 25,
        filteredRows: []
    },
    'wasteseg-table': {
        currentPage: 1,
        perPage: 25,
        filteredRows: []
    },
    'wasteunseg-table': {
        currentPage: 1,
        perPage: 25,
        filteredRows: []
    }
};

function displayPage(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const config = tables[tableId];
    const tbody = table.querySelector('tbody');
    const allRows = Array.from(tbody.querySelectorAll('tr'));
    
    // Apply filters first
    config.filteredRows = allRows.filter(row => {
        if (!hasActiveFilters(tableId)) return true;
        
        const cells = row.querySelectorAll('td');
        const filters = document.querySelectorAll(`.filter-input[data-table="${tableId}"]`);
        
        return Array.from(filters).every((filter, index) => {
            if (!filter.value.trim()) return true;
            const cellText = cells[index]?.textContent.toLowerCase() || '';
            return cellText.includes(filter.value.toLowerCase());
        });
    });
    
    const totalFiltered = config.filteredRows.length;
    const start = (config.currentPage - 1) * config.perPage;
    const end = Math.min(start + config.perPage, totalFiltered);
    
    // Hide all rows
    allRows.forEach(row => row.style.display = 'none');
    
    // Show only current page rows
    config.filteredRows.slice(start, end).forEach(row => row.style.display = '');
    
    // Update pagination info
    const prefix = tableId.replace('-table', '');
    document.getElementById(`${prefix}-start`).textContent = totalFiltered > 0 ? start + 1 : 0;
    document.getElementById(`${prefix}-end`).textContent = end;
    document.getElementById(`${prefix}-total`).textContent = totalFiltered;
    document.getElementById(`${prefix}-count`).textContent = totalFiltered;
    
    renderPagination(tableId);
}

function renderPagination(tableId) {
    const config = tables[tableId];
    const totalFiltered = config.filteredRows.length;
    const totalPages = Math.ceil(totalFiltered / config.perPage);
    const prefix = tableId.replace('-table', '');
    const paginationDiv = document.getElementById(`${prefix}-pagination`);
    
    if (!paginationDiv) return;
    
    let html = '';
    
    // Previous button
    html += `<button onclick="changePage('${tableId}', ${config.currentPage - 1})" 
        class="px-3 py-1 rounded border ${config.currentPage === 1 ? 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-500'} text-sm"
        ${config.currentPage === 1 ? 'disabled' : ''}>Prev</button>`;
    
    // Page numbers
    const startPage = Math.max(1, config.currentPage - 2);
    const endPage = Math.min(totalPages, config.currentPage + 2);
    
    if (startPage > 1) {
        html += `<button onclick="changePage('${tableId}', 1)" class="px-3 py-1 rounded border bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-500 text-sm">1</button>`;
        if (startPage > 2) html += `<span class="px-2 text-gray-500">...</span>`;
    }
    
    for (let i = startPage; i <= endPage; i++) {
        html += `<button onclick="changePage('${tableId}', ${i})" 
            class="px-3 py-1 rounded border ${i === config.currentPage ? 'bg-blue-500 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-500'} text-sm">${i}</button>`;
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += `<span class="px-2 text-gray-500">...</span>`;
        html += `<button onclick="changePage('${tableId}', ${totalPages})" class="px-3 py-1 rounded border bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-500 text-sm">${totalPages}</button>`;
    }
    
    // Next button
    html += `<button onclick="changePage('${tableId}', ${config.currentPage + 1})" 
        class="px-3 py-1 rounded border ${config.currentPage === totalPages || totalPages === 0 ? 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-500'} text-sm"
        ${config.currentPage === totalPages || totalPages === 0 ? 'disabled' : ''}>Next</button>`;
    
    paginationDiv.innerHTML = html;
}

function changePage(tableId, page) {
    const config = tables[tableId];
    const totalPages = Math.ceil(config.filteredRows.length / config.perPage);
    
    if (page < 1 || page > totalPages) return;
    
    config.currentPage = page;
    displayPage(tableId);
}

function applyFilters(tableId) {
    tables[tableId].currentPage = 1;
    displayPage(tableId);
}

function hasActiveFilters(tableId) {
    const filters = document.querySelectorAll(`.filter-input[data-table="${tableId}"]`);
    return Array.from(filters).some(filter => filter.value.trim() !== '');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Per-page selectors
    ['water', 'treated', 'wasteseg', 'wasteunseg'].forEach(prefix => {
        const selector = document.getElementById(`${prefix}-per-page`);
        if (selector) {
            selector.addEventListener('change', function() {
                const tableId = `${prefix}-table`;
                tables[tableId].perPage = parseInt(this.value);
                tables[tableId].currentPage = 1;
                displayPage(tableId);
            });
        }
    });
    
    // Filter inputs
    document.querySelectorAll('.filter-input').forEach(input => {
        input.addEventListener('input', function() {
            const tableId = this.dataset.table;
            applyFilters(tableId);
        });
    });
    
    // Initialize all tables
    Object.keys(tables).forEach(tableId => {
        if (document.getElementById(tableId)) {
            displayPage(tableId);
        }
    });
});

function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.text('Scope 3 Report', 14, 20);
    doc.text('Campus: <?php echo addslashes($selected_campus); ?>', 14, 28);
    doc.autoTable({
        startY: 35,
        head: [['Category', 'Records', 'CO2 (kg)']],
        body: [
            ['Water', '<?php echo count($water_records); ?>', '<?php echo number_format($water_total, 2); ?>'],
            ['Treated Water', '<?php echo count($treated_records); ?>', '<?php echo number_format($treated_total, 2); ?>'],
            ['Waste Segregated', '<?php echo count($waste_seg_records); ?>', '<?php echo number_format($waste_seg_total, 2); ?>'],
            ['Waste Unsegregated', '<?php echo count($waste_unseg_records); ?>', '<?php echo number_format($waste_unseg_total, 2); ?>']
        ],
        foot: [['TOTAL', '<?php echo $total_records; ?>', '<?php echo number_format($scope_total, 2); ?>']],
    });
    doc.save('scope3_report.pdf');
}

function downloadCSV() {
    let csv = '=== SCOPE 3 REPORT ===\n';
    csv += 'Campus: <?php echo addslashes($selected_campus); ?>\n';
    csv += 'Generated: <?php echo date('Y-m-d H:i:s'); ?>\n\n';
    csv += 'Category,Records,CO2 (kg)\n';
    csv += 'Water,<?php echo count($water_records); ?>,<?php echo $water_total; ?>\n';
    csv += 'Treated Water,<?php echo count($treated_records); ?>,<?php echo $treated_total; ?>\n';
    csv += 'Waste Segregated,<?php echo count($waste_seg_records); ?>,<?php echo $waste_seg_total; ?>\n';
    csv += 'Waste Unsegregated,<?php echo count($waste_unseg_records); ?>,<?php echo $waste_unseg_total; ?>\n';
    csv += 'TOTAL,<?php echo $total_records; ?>,<?php echo $scope_total; ?>\n';
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'scope3_report_<?php echo date('Y-m-d'); ?>.csv';
    a.click();
}
</script>

<?php require_once 'tailwind-footer.php'; ?>
