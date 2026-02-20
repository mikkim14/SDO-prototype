<?php
$page_title = 'CSD Dashboard';
require_once 'tailwind-header.php';
require_once '../includes/GHGCalculator.php';

// Get statistics - System-wide (no campus filter for CSD)
$stats = [];

// Electricity records count and total consumption - ALL campuses
$stmt = $db->prepare("SELECT COUNT(*) as count, SUM(IFNULL(consumption, 0)) as total FROM electricity_consumption");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['electricity'] = $row['count'];
$stats['electricity_total'] = $row['total'];
$stmt->close();

// Fuel records count and total consumption - ALL campuses
$stmt = $db->prepare("SELECT COUNT(*) as count, SUM(IFNULL(quantity_liters, 0)) as total FROM fuel_emissions");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['fuel'] = $row['count'];
    $stats['fuel_total'] = $row['total'];
    $stmt->close();
} else {
    $stats['fuel'] = 0;
    $stats['fuel_total'] = 0;
}

// LPG records count and total quantity - ALL campuses
<<<<<<< HEAD
$stmt = $db->prepare("SELECT COUNT(*) as count, SUM(IFNULL(TankQuantity, 0)) as total FROM tbllpg"); // Changed Quantity to TankQuantity based on table structure 
=======
$stmt = $db->prepare("SELECT COUNT(*) as count, SUM(IFNULL(Quantity, 0)) as total FROM tbllpg");
>>>>>>> 3c4d3d619b6602e11c434c946c294534539ab9d3
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['lpg'] = $row['count'];
    $stats['lpg_total'] = $row['total'];
    $stmt->close();
} else {
    $stats['lpg'] = 0;
    $stats['lpg_total'] = 0;
}

// Water records count and total consumption - ALL campuses
$stmt = $db->prepare("SELECT COUNT(*) as count, SUM(IFNULL(Consumption, 0)) as total FROM tblwater");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['water'] = $row['count'];
$stats['water_total'] = $row['total'];
$stmt->close();

// Treated Water records count and total volume - ALL campuses
$stmt = $db->prepare("SELECT COUNT(*) as count, SUM(IFNULL(TreatedWaterVolume, 0)) as total FROM tbltreatedwater");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['treated_water'] = $row['count'];
$stats['treated_water_total'] = $row['total'];
$stmt->close();

// Waste Segregated records count and total quantity - ALL campuses
$stmt = $db->prepare("SELECT COUNT(*) as count, SUM(IFNULL(QuantityInKG, 0)) as total FROM tblsolidwastesegregated");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['waste_seg'] = $row['count'];
$stats['waste_seg_total'] = $row['total'];
$stmt->close();

// Waste Unsegregated records count and total quantity - ALL campuses
$stmt = $db->prepare("SELECT COUNT(*) as count, SUM(IFNULL(QuantityInKG, 0)) as total FROM tblsolidwasteunsegregated");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['waste_unseg'] = $row['count'];
$stats['waste_unseg_total'] = $row['total'];
$stmt->close();

// Flight records count - ALL campuses
$stmt = $db->prepare("SELECT COUNT(*) as count FROM tblflight");
$stmt->execute();
$result = $stmt->get_result();
$stats['flight'] = $result->fetch_assoc()['count'];
$stmt->close();

// Accommodation records count - ALL campuses
$stmt = $db->prepare("SELECT COUNT(*) as count FROM tblaccommodation");
$stmt->execute();
$result = $stmt->get_result();
$stats['accommodation'] = $result->fetch_assoc()['count'];
$stmt->close();

// Food records count and total quantity - ALL campuses
<<<<<<< HEAD
$stmt = $db->prepare("SELECT COUNT(*) as count, SUM(IFNULL(QuantityOfServing, 0)) as total FROM tblfoodwaste"); // Change QuantityInKG to QuantityOfServing if that's the correct field for food waste quantity
=======
$stmt = $db->prepare("SELECT COUNT(*) as count, SUM(IFNULL(QuantityInKG, 0)) as total FROM tblfoodwaste");
>>>>>>> 3c4d3d619b6602e11c434c946c294534539ab9d3
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['food'] = $row['count'];
    $stats['food_total'] = $row['total'];
    $stmt->close();
} else {
    $stats['food'] = 0;
    $stats['food_total'] = 0;
}

// Calculate total waste for summary
$stats['waste'] = $stats['waste_seg'] + $stats['waste_unseg'];

// Total campuses count
$stmt = $db->prepare("SELECT COUNT(DISTINCT campus) as count FROM electricity_consumption");
$stmt->execute();
$result = $stmt->get_result();
$stats['campuses'] = $result->fetch_assoc()['count'];
$stmt->close();

// Total offices count (active data entry offices)
$stmt = $db->prepare("SELECT COUNT(DISTINCT office) as count FROM tblsignin WHERE office != 'Central Sustainable Office'");
$stmt->execute();
$result = $stmt->get_result();
$stats['offices'] = $result->fetch_assoc()['count'];
$stmt->close();

// Recent activity - only this user's activity
$activity_records = [];
$stmt = $db->prepare("SELECT * FROM activity_log WHERE username = ? ORDER BY timestamp DESC LIMIT 5");
$stmt->bind_param("s", $user['username']);
$stmt->execute();
$result = $stmt->get_result();
$activity_records = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get filters from query string
$selected_campus = isset($_GET['campus']) ? $_GET['campus'] : null;
$selected_year = isset($_GET['year']) ? $_GET['year'] : null;
$selected_scope = isset($_GET['scope']) ? $_GET['scope'] : null;

// Define scope categories
$scope_categories = [
    '1' => ['fuel', 'lpg'],
    '2' => ['electricity'],
    '3' => ['water', 'treated_water', 'waste_segregated', 'waste_unsegregated', 'flight', 'accommodation', 'food']
];

// Get list of available years from all tables
$years = [];
$year_queries = [
    "SELECT DISTINCT year FROM electricity_consumption WHERE year IS NOT NULL AND year != ''",
    "SELECT DISTINCT YearTransact as year FROM tbllpg WHERE YearTransact IS NOT NULL",
    "SELECT DISTINCT YearTransaction as year FROM tblfoodwaste WHERE YearTransaction IS NOT NULL",
    "SELECT DISTINCT Year as year FROM tblsolidwastesegregated WHERE Year IS NOT NULL",
    "SELECT DISTINCT Year as year FROM tblflight WHERE Year IS NOT NULL",
    "SELECT DISTINCT YearTransact as year FROM tblaccommodation WHERE YearTransact IS NOT NULL"
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

// Get list of all campuses
$campuses = [];
$stmt = $db->prepare("SELECT DISTINCT campus FROM electricity_consumption ORDER BY campus");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $campuses[] = $row['campus'];
}
$stmt->close();

// Calculate GHG totals based on CSD role (system-wide) with year filter
$ghg_stats = AccessControl::getRoleBasedGHGTotals($db, $user['office'], $user['campus'], $selected_year);

// Get GHG breakdown by category with filters
$ghg_breakdown = AccessControl::getGHGBreakdownByCategory($db, $selected_campus, $selected_year);

// Filter by scope if selected
if ($selected_scope && isset($scope_categories[$selected_scope])) {
    $allowed_categories = $scope_categories[$selected_scope];
    $ghg_breakdown = array_filter($ghg_breakdown, function($key) use ($allowed_categories) {
        return in_array($key, $allowed_categories);
    }, ARRAY_FILTER_USE_KEY);
    
    // Recalculate stats based on filtered breakdown
    $ghg_stats['total_kg_co2'] = array_sum(array_column($ghg_breakdown, 'kg_co2'));
    $ghg_stats['t_co2'] = $ghg_stats['total_kg_co2'] / 1000;
    $ghg_stats['tree_offset'] = ceil($ghg_stats['total_kg_co2'] / 21);
}
?>

                    <div class="max-w-6xl">
                        <!-- Page Header -->
                        <div class="mb-8">
                            <h1 class="text-4xl font-bold text-gray-800 flex items-center">
                                <i class="fas fa-leaf text-green-600 mr-3"></i>
                                Center for Sustainable Development Dashboard
                            </h1>
                            <p class="text-gray-600 mt-2 flex items-center">
                                <i class="fas fa-globe mr-2"></i>
                                University-wide • All Campuses
                            </p>
                        </div>

                       
                        
                        <!-- GHG Statistics Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-md p-6 text-white">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <p class="text-green-100 text-sm">Total Emissions</p>
                                        <p class="text-3xl font-bold mt-1"><?php echo number_format($ghg_stats['total_kg_co2'] ?? 0, 2); ?></p>
                                        <p class="text-green-100 text-xs">kg CO₂e</p>
                                    </div>
                                    <i class="fas fa-smog text-5xl opacity-20"></i>
                                </div>
                                <div class="pt-3 border-t border-green-400">
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div><span class="text-green-200">Electricity:</span> <span class="font-semibold"><?php echo number_format($stats['electricity_total'] ?? 0, 0); ?> kWh</span></div>
                                        <div><span class="text-green-200">Fuel:</span> <span class="font-semibold"><?php echo number_format($stats['fuel_total'] ?? 0, 0); ?> L</span></div>
                                        <div><span class="text-green-200">LPG:</span> <span class="font-semibold"><?php echo number_format($stats['lpg_total'] ?? 0, 0); ?> kg</span></div>
                                        <div><span class="text-green-200">Water:</span> <span class="font-semibold"><?php echo number_format($stats['water_total'] ?? 0, 0); ?> m³</span></div>
                                        <div><span class="text-green-200">Treated Water:</span> <span class="font-semibold"><?php echo number_format($stats['treated_water_total'] ?? 0, 0); ?> m³</span></div>
                                        <div><span class="text-green-200">Waste:</span> <span class="font-semibold"><?php echo number_format(($stats['waste_seg_total'] ?? 0) + ($stats['waste_unseg_total'] ?? 0), 0); ?> kg</span></div>
                                        <div><span class="text-green-200">Flight:</span> <span class="font-semibold"><?php echo number_format($stats['flight'] ?? 0); ?> trips</span></div>
                                        <div><span class="text-green-200">Accommodation:</span> <span class="font-semibold"><?php echo number_format($stats['accommodation'] ?? 0); ?> stays</span></div>
                                        <div><span class="text-green-200">Food:</span> <span class="font-semibold"><?php echo number_format($stats['food_total'] ?? 0, 0); ?> kg</span></div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-md p-6 text-white">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <p class="text-blue-100 text-sm">Metric Tons</p>
                                        <p class="text-3xl font-bold mt-1"><?php echo number_format($ghg_stats['t_co2'] ?? 0, 3); ?></p>
                                        <p class="text-blue-100 text-xs">t CO₂e</p>
                                    </div>
                                    <i class="fas fa-weight text-5xl opacity-20"></i>
                                </div>
                                <div class="pt-3 border-t border-blue-400">
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div><span class="text-blue-200">Total Records:</span> <span class="font-semibold"><?php echo number_format(($stats['electricity'] ?? 0) + ($stats['fuel'] ?? 0) + ($stats['lpg'] ?? 0) + ($stats['water'] ?? 0) + ($stats['treated_water'] ?? 0) + ($stats['waste_seg'] ?? 0) + ($stats['waste_unseg'] ?? 0) + ($stats['flight'] ?? 0) + ($stats['accommodation'] ?? 0) + ($stats['food'] ?? 0)); ?></span></div>
                                        <div><span class="text-blue-200">Avg/Record:</span> <span class="font-semibold"><?php $total_records = ($stats['electricity'] ?? 0) + ($stats['fuel'] ?? 0) + ($stats['lpg'] ?? 0) + ($stats['water'] ?? 0) + ($stats['treated_water'] ?? 0) + ($stats['waste_seg'] ?? 0) + ($stats['waste_unseg'] ?? 0) + ($stats['flight'] ?? 0) + ($stats['accommodation'] ?? 0) + ($stats['food'] ?? 0); echo number_format($total_records > 0 ? (($ghg_stats['total_kg_co2'] ?? 0) / $total_records) : 0, 2); ?> kg</span></div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-lg shadow-md p-6 text-white">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <p class="text-emerald-100 text-sm">Tree Offset</p>
                                        <p class="text-3xl font-bold mt-1"><?php echo number_format($ghg_stats['tree_offset'] ?? 0); ?></p>
                                        <p class="text-emerald-100 text-xs">trees needed</p>
                                    </div>
                                    <i class="fas fa-tree text-5xl opacity-20"></i>
                                </div>
                                <div class="pt-3 border-t border-emerald-400">
                                    <div class="text-xs space-y-1">
                                        <div><span class="text-emerald-200">CO₂ per tree/year:</span> <span class="font-semibold">21 kg</span></div>
                                        <!--<div><span class="text-emerald-200">System Footprint:</span> <span class="font-semibold"><?php echo number_format($ghg_stats['t_co2'], 2); ?> t CO₂e</span></div>-->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- GHG Breakdown by Category -->
                        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                                    <i class="fas fa-chart-bar text-blue-600 mr-2"></i>
                                    GHG Emissions by Category
                                </h2>
                                
                                <!-- Filters -->
                                <form method="GET" class="flex items-center gap-3">
                                    <div class="flex items-center gap-2">
                                        <label class="text-sm text-gray-600">Scope:</label>
                                        <select name="scope" onchange="this.form.submit()" class="px-3 py-1 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">All Scopes</option>
                                            <option value="1" <?php echo ($selected_scope === '1') ? 'selected' : ''; ?>>Scope 1 (Fuel, LPG)</option>
                                            <option value="2" <?php echo ($selected_scope === '2') ? 'selected' : ''; ?>>Scope 2 (Electricity)</option>
                                            <option value="3" <?php echo ($selected_scope === '3') ? 'selected' : ''; ?>>Scope 3 (Water, Waste, Travel, Food)</option>
                                        </select>
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        <label class="text-sm text-gray-600">Year:</label>
                                        <select name="year" onchange="this.form.submit()" class="px-3 py-1 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">All Years</option>
                                            <?php foreach ($years as $year): ?>
                                                <option value="<?php echo htmlspecialchars($year); ?>" <?php echo ($selected_year === $year) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($year); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        <label class="text-sm text-gray-600">Campus:</label>
                                        <select name="campus" onchange="this.form.submit()" class="px-3 py-1 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">All Campuses</option>
                                            <?php foreach ($campuses as $campus_name): ?>
                                                <option value="<?php echo htmlspecialchars($campus_name); ?>" <?php echo ($selected_campus === $campus_name) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($campus_name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <!-- Hidden fields to preserve filters -->
                                        <?php if ($selected_year): ?>
                                            <input type="hidden" name="year" value="<?php echo htmlspecialchars($selected_year); ?>">
                                        <?php endif; ?>
                                        <?php if ($selected_scope): ?>
                                            <input type="hidden" name="scope" value="<?php echo htmlspecialchars($selected_scope); ?>">
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>

                            <?php if ($selected_campus || $selected_year || $selected_scope): ?>
                                <div class="mb-3 px-3 py-2 bg-blue-50 border border-blue-200 rounded text-sm text-blue-800">
                                    <i class="fas fa-filter mr-2"></i>
                                    Showing data for: 
                                    <?php 
                                    $filters = [];
                                    if ($selected_scope) {
                                        $scope_names = ['1' => 'Scope 1', '2' => 'Scope 2', '3' => 'Scope 3'];
                                        $filters[] = '<strong>' . $scope_names[$selected_scope] . '</strong>';
                                    }
                                    if ($selected_year) {
                                        $filters[] = '<strong>Year ' . htmlspecialchars($selected_year) . '</strong>';
                                    }
                                    if ($selected_campus) {
                                        $filters[] = '<strong>' . htmlspecialchars($selected_campus) . ' Campus</strong>';
                                    }
                                    echo implode(' <span class="mx-1">|</span> ', $filters);
                                    ?>
                                    <a href="?" class="ml-2 text-blue-600 hover:underline">Clear Filters</a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-100 border-b-2 border-gray-200">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Category</th>
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Scope</th>
                                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Consumption</th>
                                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Emissions</th>
                                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">% of Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php 
                                        $total_emissions = array_sum(array_column($ghg_breakdown, 'kg_co2'));
                                        foreach ($ghg_breakdown as $category => $data): 
                                            $percentage = $total_emissions > 0 ? ($data['kg_co2'] / $total_emissions) * 100 : 0;
                                        ?>
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="px-4 py-3 text-sm font-medium text-gray-800">
                                                    <?php echo htmlspecialchars($data['name']); ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-600">
                                                    <span class="px-2 py-1 <?php 
                                                        echo $data['scope'] === 'Scope 1' ? 'bg-orange-100 text-orange-800' : 
                                                            ($data['scope'] === 'Scope 2' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800');
                                                    ?> rounded text-xs font-medium">
                                                        <?php echo htmlspecialchars($data['scope']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right text-gray-600">
                                                    <?php echo number_format($data['consumption'], 2); ?> <?php echo htmlspecialchars($data['unit']); ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right font-semibold text-gray-800">
                                                    <?php echo number_format($data['kg_co2'], 2); ?> kg
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right text-gray-600">
                                                    <div class="flex items-center justify-end gap-2">
                                                        <div class="w-16 bg-gray-200 rounded-full h-2">
                                                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo min($percentage, 100); ?>%"></div>
                                                        </div>
                                                        <?php echo number_format($percentage, 1); ?>%
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="bg-gray-100 border-t-2 border-gray-300">
                                        <tr>
                                            <td colspan="2" class="px-4 py-3 text-sm font-bold text-gray-800">TOTAL</td>
                                            <td class="px-4 py-3 text-sm text-right text-gray-600">-</td>
                                            <td class="px-4 py-3 text-sm text-right font-bold text-red-800">
                                                <?php echo number_format($total_emissions, 2); ?> kg
                                            </td>
                                            <td class="px-4 py-3 text-sm text-right font-bold text-gray-800">100%</td>
                                        </tr>
                                         <!--<tr>
                                            <td colspan="6" class="px-4 py-3 text-sm text-gray-600 bg-green-50">
                                               <div class="flex items-center justify-between">
                                                    <span><i class="fas fa-tree text-green-600 mr-2"></i> <strong>Trees Needed:</strong> <?php echo number_format(ceil($total_emissions / 21)); ?> trees</span>
                                                    <span><strong>Total CO2:</strong> <?php echo number_format($total_emissions / 1000, 4); ?> tCO2</span>
                                                </div>
                                            </td>
                                        </tr>-->
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Quick Access Menu -->
                        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-th-large text-blue-600 mr-2"></i>
                                Quick Access - View Reports by Category
                            </h2>
                            
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                <a href="report_category.php?cat=electricity" class="p-4 bg-yellow-50 hover:bg-yellow-100 rounded-lg transition-colors border border-yellow-200 text-center">
                                    <i class="fas fa-bolt text-yellow-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">Electricity</p>
                                </a>
                                
                                <a href="report_category.php?cat=water" class="p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors border border-blue-200 text-center">
                                    <i class="fas fa-droplet text-blue-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">Water</p>
                                </a>
                                
                                <a href="report_category.php?cat=treated_water" class="p-4 bg-cyan-50 hover:bg-cyan-100 rounded-lg transition-colors border border-cyan-200 text-center">
                                    <i class="fas fa-faucet text-cyan-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">Treated Water</p>
                                </a>
                                
                                <a href="report_category.php?cat=fuel" class="p-4 bg-orange-50 hover:bg-orange-100 rounded-lg transition-colors border border-orange-200 text-center">
                                    <i class="fas fa-gas-pump text-orange-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">Fuel</p>
                                </a>
                                
                                <a href="report_category.php?cat=lpg" class="p-4 bg-red-50 hover:bg-red-100 rounded-lg transition-colors border border-red-200 text-center">
                                    <i class="fas fa-fire text-red-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">LPG</p>
                                </a>
                                
                                <a href="report_waste_segregated.php" class="p-4 bg-green-50 hover:bg-green-100 rounded-lg transition-colors border border-green-200 text-center">
                                    <i class="fas fa-recycle text-green-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">Waste Segregated</p>
                                </a>
                                
                                <a href="report_waste_unsegregated.php" class="p-4 bg-orange-50 hover:bg-orange-100 rounded-lg transition-colors border border-orange-200 text-center">
                                    <i class="fas fa-dumpster text-orange-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">Waste Unsegregated</p>
                                </a>
                                
                                <a href="report_category.php?cat=food" class="p-4 bg-green-50 hover:bg-green-100 rounded-lg transition-colors border border-green-200 text-center">
                                    <i class="fas fa-utensils text-green-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">Food</p>
                                </a>
                                
                                <a href="report_category.php?cat=flight" class="p-4 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors border border-indigo-200 text-center">
                                    <i class="fas fa-plane text-indigo-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">Flight</p>
                                </a>
                                
                                <a href="report_category.php?cat=accommodation" class="p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors border border-purple-200 text-center">
                                    <i class="fas fa-hotel text-purple-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">Accommodation</p>
                                </a>
                            </div>
                        </div>

                        <!-- System Information -->
                        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                                Central Administration - Read-Only Access
                            </h2>
                            
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <p class="text-gray-700 mb-2">
                                    <i class="fas fa-eye text-blue-600 mr-2"></i>
                                    <strong>Viewing Mode:</strong> You have read-only access to all GHG data across all campuses.
                                </p>
                                <p class="text-gray-700 mb-2">
                                    <i class="fas fa-lock text-blue-600 mr-2"></i>
                                    <strong>No Input Permissions:</strong> Central office cannot create, edit, or delete GHG records.
                                </p>
                                <p class="text-gray-700">
                                    <i class="fas fa-chart-line text-blue-600 mr-2"></i>
                                    <strong>Purpose:</strong> Monitor system-wide emissions and generate comprehensive reports across all campuses and offices.
                                </p>
                            </div>
                        </div>

                    </div>

<?php require_once 'tailwind-footer.php'; ?>



