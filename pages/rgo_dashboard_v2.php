<?php
$page_title = 'RGO Dashboard';
require_once 'tailwind-header.php';
require_once '../includes/GHGCalculator.php';

// Get statistics
$stats = [];

// LPG records count and total volume
$stmt = $db->prepare("SELECT COUNT(*) as count, SUM(IFNULL(TotalTankVolume, 0)) as total FROM tbllpg WHERE Campus = ?");
$stmt->bind_param("s", $user['campus']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['lpg'] = $row['count'];
$stats['lpg_total'] = $row['total'];
$stmt->close();

// Food records count and total quantity
$stmt = $db->prepare("SELECT COUNT(*) as count, SUM(IFNULL(QuantityOfServing, 0)) as total FROM tblfoodwaste WHERE Campus = ?");
if ($stmt) {
    $stmt->bind_param("s", $user['campus']);
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

// Get year filter
$selected_year = isset($_GET['year']) ? $_GET['year'] : null;

// Get available years from LPG table
$years = [];
$stmt = $db->prepare("SELECT DISTINCT YearTransact as year FROM tbllpg WHERE Campus = ? ORDER BY year DESC");
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

// Filter to show only RGO categories
$rgo_categories = ['lpg', 'food'];
$breakdown = array_filter($breakdown, function($key) use ($rgo_categories) {
    return in_array($key, $rgo_categories);
}, ARRAY_FILTER_USE_KEY);

// Calculate GHG totals based on RGO categories only
$ghg_stats = [];
$ghg_stats['total_kg_co2'] = array_sum(array_column($breakdown, 'kg_co2'));
$ghg_stats['t_co2'] = $ghg_stats['total_kg_co2'] / 1000;
$ghg_stats['tree_offset'] = ceil($ghg_stats['total_kg_co2'] / 21);
?>

                    <div class="max-w-6xl">
                        <!-- Page Header -->
                        <div class="mb-8">
                            <h1 class="text-4xl font-bold text-gray-800 flex items-center">
                                <i class="fas fa-recycle text-orange-600 mr-3"></i>
                                Resource Generation Office Dashboard
                            </h1>
                            <p class="text-gray-600 mt-2 flex items-center">
                                <i class="fas fa-map-marker-alt mr-2"></i>
                                <?php echo htmlspecialchars($user['campus']); ?> Campus • RGO
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
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div><span class="text-orange-200">LPG:</span> <span class="font-semibold"><?php echo number_format($stats['lpg_total'] ?? 0, 2); ?> L</span></div>
                                        <div><span class="text-orange-200">Food:</span> <span class="font-semibold"><?php echo number_format($stats['food_total'] ?? 0, 0); ?> servings</span></div>
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
                                        <div><span class="text-blue-200">Total Records:</span> <span class="font-semibold"><?php echo ($stats['lpg'] ?? 0) + ($stats['food'] ?? 0); ?></span></div>
                                        <div><span class="text-blue-200">Avg/Record:</span> <span class="font-semibold"><?php $total_records = ($stats['lpg'] ?? 0) + ($stats['food'] ?? 0); echo number_format($total_records > 0 ? (($ghg_stats['total_kg_co2'] ?? 0) / $total_records) : 0, 2); ?> kg</span></div>
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
                                    </div>
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
                                <a href="rgo_report_category.php?cat=lpg" class="p-4 bg-red-50 hover:bg-red-100 rounded-lg transition-colors border border-red-200 text-center">
                                    <i class="fas fa-fire text-red-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">LPG</p>
                                </a>
                                
                                <a href="rgo_report_category.php?cat=food" class="p-4 bg-green-50 hover:bg-green-100 rounded-lg transition-colors border border-green-200 text-center">
                                    <i class="fas fa-utensils text-green-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">Food</p>
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
                                
                                <!-- Year Filter -->
                                <form method="GET" class="flex items-center gap-3">
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

                            <?php if ($selected_year): ?>
                                <div class="mb-3 px-3 py-2 bg-orange-50 border border-orange-200 rounded text-sm text-orange-800">
                                    <i class="fas fa-filter mr-2"></i>
                                    Showing data for: <strong>Year <?php echo htmlspecialchars($selected_year); ?></strong>
                                    <a href="?" class="ml-3 text-orange-600 hover:text-orange-800 underline">Clear filters</a>
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
                                        if (count($breakdown) > 0):
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
                                                            <div class="bg-orange-600 h-2 rounded-full" style="width: <?php echo min($percentage, 100); ?>%"></div>
                                                        </div>
                                                        <?php echo number_format($percentage, 1); ?>%
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php 
                                            endforeach;
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                                    <i class="fas fa-inbox text-3xl mb-2"></i>
                                                    <p>No data available. Start by adding LPG or Food Consumption records.</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <?php if (count($breakdown) > 0): ?>
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
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>

<?php require_once 'tailwind-footer.php'; ?>
