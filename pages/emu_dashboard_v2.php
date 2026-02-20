<?php
$page_title = 'EMU Dashboard';
require_once 'tailwind-header.php';
require_once '../includes/GHGCalculator.php';

// Get statistics
$stats = [];

// Electricity records count and total consumption
$stmt = $db->prepare("SELECT COUNT(*) as count, SUM(IFNULL(consumption, 0)) as total FROM electricity_consumption WHERE campus = ?");
$stmt->bind_param("s", $user['campus']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['electricity'] = $row['count'];
$stats['electricity_total'] = $row['total'];
$stmt->close();

// Water records count and total consumption
$stmt = $db->prepare("SELECT COUNT(*) as count, SUM(IFNULL(Consumption, 0)) as total FROM tblwater WHERE Campus = ?");
$stmt->bind_param("s", $user['campus']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['water'] = $row['count'];
$stats['water_total'] = $row['total'];
$stmt->close();

// Treated Water records count and total volume
$stmt = $db->prepare("SELECT COUNT(*) as count, SUM(IFNULL(TreatedWaterVolume, 0)) as total FROM tbltreatedwater WHERE Campus = ?");
$stmt->bind_param("s", $user['campus']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['treated_water'] = $row['count'];
$stats['treated_water_total'] = $row['total'];
$stmt->close();

// Waste Segregated records count and total quantity
$stmt = $db->prepare("SELECT COUNT(*) as count, SUM(IFNULL(QuantityInKG, 0)) as total FROM tblsolidwastesegregated WHERE Campus = ?");
$stmt->bind_param("s", $user['campus']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['waste_seg'] = $row['count'];
$stats['waste_seg_total'] = $row['total'];
$stmt->close();

// Waste Unsegregated records count and total quantity
$stmt = $db->prepare("SELECT COUNT(*) as count, SUM(IFNULL(QuantityInKG, 0)) as total FROM tblsolidwasteunsegregated WHERE Campus = ?");
$stmt->bind_param("s", $user['campus']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['waste_unseg'] = $row['count'];
$stats['waste_unseg_total'] = $row['total'];
$stmt->close();

// Get year filter
$selected_year = isset($_GET['year']) ? $_GET['year'] : null;
$selected_scope = isset($_GET['scope']) ? $_GET['scope'] : null;

// Get available years
$years = [];
$stmt = $db->prepare("SELECT DISTINCT year FROM electricity_consumption WHERE campus = ? ORDER BY year DESC");
$stmt->bind_param("s", $user['campus']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $years[] = $row['year'];
}
$stmt->close();

// Get breakdown by category for scope filtering
$breakdown = AccessControl::getGHGBreakdownByCategory($db, $user['campus'], $selected_year);

// Filter to show only EMU categories
$emu_categories = ['electricity', 'water', 'treated_water', 'waste_segregated', 'waste_unsegregated'];
$breakdown = array_filter($breakdown, function($key) use ($emu_categories) {
    return in_array($key, $emu_categories);
}, ARRAY_FILTER_USE_KEY);

// Calculate GHG totals based on EMU categories only
$ghg_stats = [];
$ghg_stats['total_kg_co2'] = array_sum(array_column($breakdown, 'kg_co2'));
$ghg_stats['t_co2'] = $ghg_stats['total_kg_co2'] / 1000;
$ghg_stats['tree_offset'] = ceil($ghg_stats['total_kg_co2'] / 21);

// Filter by scope if selected
if ($selected_scope) {
    $scope_categories = [];
    if ($selected_scope === 'scope2') {
        $scope_categories = ['electricity'];
    } elseif ($selected_scope === 'scope3') {
        $scope_categories = ['water', 'treated_water', 'waste_segregated', 'waste_unsegregated'];
    }
    
    if (!empty($scope_categories)) {
        $breakdown = array_filter($breakdown, function($key) use ($scope_categories) {
            return in_array($key, $scope_categories);
        }, ARRAY_FILTER_USE_KEY);
        
        $ghg_stats['total_kg_co2'] = array_sum(array_column($breakdown, 'kg_co2'));
        $ghg_stats['t_co2'] = $ghg_stats['total_kg_co2'] / 1000;
        $ghg_stats['tree_offset'] = ceil($ghg_stats['total_kg_co2'] / 21);
    }
}
?>

                    <div class="max-w-6xl">
                        <!-- Page Header -->
                        <div class="mb-8">
                            <h1 class="text-4xl font-bold text-gray-800 flex items-center">
                                <i class="fas fa-seedling text-green-600 mr-3"></i>
                                Environmental Management Unit Dashboard
                            </h1>
                            <p class="text-gray-600 mt-2 flex items-center">
                                <i class="fas fa-map-marker-alt mr-2"></i>
                                <?php echo htmlspecialchars($user['campus']); ?> Campus • EMU
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
                                        <div><span class="text-green-200">Water:</span> <span class="font-semibold"><?php echo number_format($stats['water_total'] ?? 0, 0); ?> m³</span></div>
                                        <div><span class="text-green-200">Treated Water:</span> <span class="font-semibold"><?php echo number_format($stats['treated_water_total'] ?? 0, 0); ?> m³</span></div>
                                        <div><span class="text-green-200">Waste:</span> <span class="font-semibold"><?php echo number_format(($stats['waste_seg_total'] ?? 0) + ($stats['waste_unseg_total'] ?? 0), 0); ?> kg</span></div>
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
                                        <!--<div><span class="text-blue-200">Total Records:</span> <span class="font-semibold"><?php echo $stats['electricity'] + $stats['water'] + $stats['treated_water'] + $stats['waste_seg'] + $stats['waste_unseg']; ?></span></div>-->
                                        <div><span class="text-blue-200">Avg/Record:</span> <span class="font-semibold"><?php $total_records = ($stats['electricity'] ?? 0) + ($stats['water'] ?? 0) + ($stats['treated_water'] ?? 0) + ($stats['waste_seg'] ?? 0) + ($stats['waste_unseg'] ?? 0); echo number_format($total_records > 0 ? (($ghg_stats['total_kg_co2'] ?? 0) / $total_records) : 0, 2); ?> kg</span></div>
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
                                        <!--<div><span class="text-emerald-200">Campus Footprint:</span> <span class="font-semibold"><?php echo number_format($ghg_stats['t_co2'], 2); ?> t CO₂e</span></div>-->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Entry Records Summary -->
                        <!--<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <p class="text-gray-600 text-sm">Electricity</p>
                                        <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo $stats['electricity']; ?></p>
                                        <p class="text-xs text-gray-500">records</p>
                                    </div>
                                    <i class="fas fa-bolt text-3xl text-yellow-200"></i>
                                </div>
                                <div class="pt-3 border-t border-gray-100">
                                    <p class="text-xs text-gray-500">Total Consumption</p>
                                    <p class="text-lg font-semibold text-yellow-600"><?php echo number_format($stats['electricity_total'], 2); ?> kWh</p>
                                </div>
                            </div>

                            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <p class="text-gray-600 text-sm">Water</p>
                                        <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo $stats['water']; ?></p>
                                        <p class="text-xs text-gray-500">records</p>
                                    </div>
                                    <i class="fas fa-droplet text-3xl text-blue-200"></i>
                                </div>
                                <div class="pt-3 border-t border-gray-100">
                                    <p class="text-xs text-gray-500">Total Consumption</p>
                                    <p class="text-lg font-semibold text-blue-600"><?php echo number_format($stats['water_total'], 2); ?> m³</p>
                                </div>
                            </div>

                            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-cyan-500">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <p class="text-gray-600 text-sm">Treated Water</p>
                                        <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo $stats['treated_water']; ?></p>
                                        <p class="text-xs text-gray-500">records</p>
                                    </div>
                                    <i class="fas fa-faucet text-3xl text-cyan-200"></i>
                                </div>
                                <div class="pt-3 border-t border-gray-100">
                                    <p class="text-xs text-gray-500">Total Volume</p>
                                    <p class="text-lg font-semibold text-cyan-600"><?php echo number_format($stats['treated_water_total'], 2); ?> m³</p>
                                </div>
                            </div>

                            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-gray-500">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <p class="text-gray-600 text-sm">Waste Seg.</p>
                                        <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo $stats['waste_seg']; ?></p>
                                        <p class="text-xs text-gray-500">records</p>
                                    </div>
                                    <i class="fas fa-trash text-3xl text-gray-200"></i>
                                </div>
                                <div class="pt-3 border-t border-gray-100">
                                    <p class="text-xs text-gray-500">Total Quantity</p>
                                    <p class="text-lg font-semibold text-gray-600"><?php echo number_format($stats['waste_seg_total'], 2); ?> kg</p>
                                </div>
                            </div>

                            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <p class="text-gray-600 text-sm">Waste Unseg.</p>
                                        <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo $stats['waste_unseg']; ?></p>
                                        <p class="text-xs text-gray-500">records</p>
                                    </div>
                                    <i class="fas fa-recycle text-3xl text-red-200"></i>
                                </div>
                                <div class="pt-3 border-t border-gray-100">
                                    <p class="text-xs text-gray-500">Total Quantity</p>
                                    <p class="text-lg font-semibold text-red-600"><?php echo number_format($stats['waste_unseg_total'], 2); ?> kg</p>
                                </div>
                            </div>
                        </div>-->

                        <!-- Quick Access -->
                        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-th-large text-blue-600 mr-2"></i>
                                Quick Access - Reports by Category
                            </h2>
                            
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                <a href="emu_report_category.php?cat=electricity" class="p-4 bg-yellow-50 hover:bg-yellow-100 rounded-lg transition-colors border border-yellow-200 text-center">
                                    <i class="fas fa-bolt text-yellow-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">Electricity</p>
                                </a>
                                
                                <a href="emu_report_category.php?cat=water" class="p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors border border-blue-200 text-center">
                                    <i class="fas fa-droplet text-blue-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">Water</p>
                                </a>
                                
                                <a href="emu_report_category.php?cat=treated_water" class="p-4 bg-cyan-50 hover:bg-cyan-100 rounded-lg transition-colors border border-cyan-200 text-center">
                                    <i class="fas fa-faucet text-cyan-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">Treated Water</p>
                                </a>
                                
                                <a href="emu_report_category.php?cat=waste_segregated" class="p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors border border-gray-200 text-center">
                                    <i class="fas fa-trash text-gray-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">Waste Seg.</p>
                                </a>
                                
                                <a href="emu_report_category.php?cat=waste_unsegregated" class="p-4 bg-red-50 hover:bg-red-100 rounded-lg transition-colors border border-red-200 text-center">
                                    <i class="fas fa-recycle text-red-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">Waste Unseg.</p>
                                </a>
                            </div>
                        </div>

                        <!-- GHG Breakdown by Category -->
                        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                                    <i class="fas fa-chart-pie text-green-600 mr-2"></i>
                                    GHG Emissions by Category
                                </h2>
                                
                                <!-- Filters -->
                                <form method="GET" class="flex items-center gap-3">
                                    <div class="flex items-center gap-2">
                                        <label class="text-sm text-gray-600">Scope:</label>
                                        <select name="scope" onchange="this.form.submit()" class="px-3 py-1 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">All Scopes</option>
                                            <option value="scope2" <?php echo ($selected_scope === 'scope2') ? 'selected' : ''; ?>>Scope 2 (Electricity)</option>
                                            <option value="scope3" <?php echo ($selected_scope === 'scope3') ? 'selected' : ''; ?>>Scope 3 (Water, Waste)</option>
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
                                </form>
                            </div>

                            <?php if ($selected_year || $selected_scope): ?>
                                <div class="mb-3 px-3 py-2 bg-blue-50 border border-blue-200 rounded text-sm text-blue-800">
                                    <i class="fas fa-filter mr-2"></i>
                                    Showing data for: 
                                    <?php 
                                    $filters = [];
                                    if ($selected_scope) {
                                        $scope_names = ['scope2' => 'Scope 2', 'scope3' => 'Scope 3'];
                                        $filters[] = '<strong>' . $scope_names[$selected_scope] . '</strong>';
                                    }
                                    if ($selected_year) {
                                        $filters[] = '<strong>Year ' . htmlspecialchars($selected_year) . '</strong>';
                                    }
                                    echo implode(' <span class="mx-1">|</span> ', $filters);
                                    ?>
                                    <a href="?" class="ml-3 text-blue-600 hover:text-blue-800 underline">Clear filters</a>
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
                                        $total_emissions = array_sum(array_column($breakdown, 'kg_co2'));
                                        foreach ($breakdown as $category => $data): 
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
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        

                        
                    </div>

<?php require_once 'tailwind-footer.php'; ?>



