<?php
$page_title = 'GSO Dashboard';
require_once 'tailwind-header.php';
require_once '../includes/GHGCalculator.php';

// Get statistics
$stats = [];

// Fuel records count and total consumption
$stmt = $db->prepare("SELECT COUNT(*) as count, SUM(IFNULL(quantity_liters, 0)) as total FROM fuel_emissions WHERE campus = ?");
if ($stmt) {
    $stmt->bind_param("s", $user['campus']);
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

// Get year and scope filters
$selected_year = isset($_GET['year']) ? $_GET['year'] : null;
$selected_scope = isset($_GET['scope']) ? $_GET['scope'] : null;

// Get available years from fuel table
$years = [];
$stmt = $db->prepare("SELECT DISTINCT YEAR(date) as year FROM fuel_emissions WHERE campus = ? AND date IS NOT NULL ORDER BY year DESC");
if ($stmt) {
    $stmt->bind_param("s", $user['campus']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if ($row['year']) {
            $years[] = $row['year'];
        }
    }
    $stmt->close();
}

// Get breakdown by category
$breakdown = AccessControl::getGHGBreakdownByCategory($db, $user['campus'], $selected_year);

// Filter to show only GSO categories
$gso_categories = ['fuel'];
$breakdown = array_filter($breakdown, function($key) use ($gso_categories) {
    return in_array($key, $gso_categories);
}, ARRAY_FILTER_USE_KEY);

// Apply scope filter if selected
if ($selected_scope) {
    $breakdown = array_filter($breakdown, function($data) use ($selected_scope) {
        $scope_map = ['scope1' => 'Scope 1', 'scope2' => 'Scope 2', 'scope3' => 'Scope 3'];
        return isset($scope_map[$selected_scope]) && $data['scope'] === $scope_map[$selected_scope];
    });
}

// Calculate GHG totals based on GSO categories only
$ghg_stats = [];
$ghg_stats['total_kg_co2'] = array_sum(array_column($breakdown, 'kg_co2'));
$ghg_stats['t_co2'] = $ghg_stats['total_kg_co2'] / 1000;
$ghg_stats['tree_offset'] = ceil($ghg_stats['total_kg_co2'] / 21);
?>

                    <div class="max-w-6xl">
                        <!-- Page Header -->
                        <div class="mb-8">
                            <h1 class="text-4xl font-bold text-gray-800 flex items-center">
                                <i class="fas fa-gas-pump text-orange-600 mr-3"></i>
                                General Services Office Dashboard
                            </h1>
                            <p class="text-gray-600 mt-2 flex items-center">
                                <i class="fas fa-map-marker-alt mr-2"></i>
                                <?php echo htmlspecialchars($user['campus']); ?> Campus • GSO
                            </p>
                        </div>

                        <!-- GHG Statistics Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-md p-6 text-white">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <p class="text-orange-100 text-sm">Total Emissions</p>
                                        <p class="text-3xl font-bold mt-1"><?php echo number_format($ghg_stats['total_kg_co2'] ?? 0, 2); ?></p>
                                        <p class="text-orange-100 text-xs">kg CO₂e</p>
                                    </div>
                                    <i class="fas fa-smog text-5xl opacity-20"></i>
                                </div>
                                <div class="pt-3 border-t border-orange-400">
                                    <div class="text-xs">
                                        <span class="text-orange-200">Fuel:</span> <span class="font-semibold"><?php echo number_format($stats['fuel_total'] ?? 0, 2); ?> L</span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-md p-6 text-white">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <p class="text-blue-100 text-sm">Carbon Footprint</p>
                                        <p class="text-3xl font-bold mt-1"><?php echo number_format($ghg_stats['t_co2'] ?? 0, 2); ?></p>
                                        <p class="text-blue-100 text-xs">tonnes CO₂e</p>
                                    </div>
                                    <i class="fas fa-cloud text-5xl opacity-20"></i>
                                </div>
                                <div class="pt-3 border-t border-blue-400 text-xs text-blue-100">
                                    Environmental Impact Metric
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-md p-6 text-white">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <p class="text-green-100 text-sm">Trees to Offset</p>
                                        <p class="text-3xl font-bold mt-1"><?php echo number_format($ghg_stats['tree_offset'] ?? 0); ?></p>
                                        <p class="text-green-100 text-xs">trees needed</p>
                                    </div>
                                    <i class="fas fa-tree text-5xl opacity-20"></i>
                                </div>
                                <div class="pt-3 border-t border-green-400 text-xs text-green-100">
                                    Annual planting target
                                </div>
                            </div>
                        </div>

                        <!-- Quick Access -->
                        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-th-large text-orange-600 mr-2"></i>
                                Quick Access
                            </h2>
                            
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                <a href="gso_report_category.php?cat=fuel" class="p-4 bg-orange-50 hover:bg-orange-100 rounded-lg transition-colors border border-orange-200 text-center">
                                    <i class="fas fa-gas-pump text-orange-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">Fuel</p>
                                </a>
                            </div>
                        </div>

                        <!-- GHG Breakdown by Category -->
                        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                                    <i class="fas fa-chart-pie text-orange-600 mr-2"></i>
                                    GHG Emissions by Category
                                </h2>
                                
                                <!-- Filters -->
                                <form method="GET" class="flex items-center gap-3">
                                    <div class="flex items-center gap-2">
                                        <label class="text-sm text-gray-600">Scope:</label>
                                        <select name="scope" onchange="this.form.submit()" class="px-3 py-1 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                                            <option value="">All Scopes</option>
                                            <option value="scope1" <?php echo ($selected_scope === 'scope1') ? 'selected' : ''; ?>>Scope 1 (Fuel)</option>
                                        </select>
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        <label class="text-sm text-gray-600">Year:</label>
                                        <select name="year" onchange="this.form.submit()" class="px-3 py-1 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
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
                                <div class="mb-3 px-3 py-2 bg-orange-50 border border-orange-200 rounded text-sm text-orange-800">
                                    <i class="fas fa-filter mr-2"></i>
                                    Showing data for: 
                                    <?php 
                                    $filters = [];
                                    if ($selected_scope) {
                                        $scope_names = ['scope1' => 'Scope 1'];
                                        $filters[] = '<strong>' . $scope_names[$selected_scope] . '</strong>';
                                    }
                                    if ($selected_year) {
                                        $filters[] = '<strong>Year ' . htmlspecialchars($selected_year) . '</strong>';
                                    }
                                    echo implode(' <span class="mx-1">|</span> ', $filters);
                                    ?>
                                    <a href="?" class="ml-3 text-orange-600 hover:text-orange-800 underline">Clear filters</a>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($breakdown)): ?>
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
                                                        <span class="px-2 py-1 bg-orange-100 text-orange-800 rounded text-xs font-medium">
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
                                                                <div class="bg-orange-600 h-2 rounded-full" style="width: <?php echo min($percentage, 100); ?>%"></div>
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
                            <?php else: ?>
                                <div class="text-center py-8 text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-3"></i>
                                    <p>No data available<?php echo $selected_year ? ' for ' . htmlspecialchars($selected_year) : ''; ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

<?php require_once 'tailwind-footer.php'; ?>
