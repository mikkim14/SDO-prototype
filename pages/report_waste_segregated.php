<?php
$page_title = 'Waste Segregated Report';
require_once 'tailwind-header.php';

$selected_campus = $_GET['campus'] ?? null;
$selected_year = $_GET['year'] ?? null;

// Get campuses
$campuses = [];
$stmt = $db->prepare("SELECT DISTINCT Campus FROM tblsolidwastesegregated ORDER BY Campus");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $campuses[] = $row['Campus'];
}
$stmt->close();

// Get years
$years = [];
$stmt = $db->prepare("SELECT DISTINCT Year FROM tblsolidwastesegregated WHERE Year IS NOT NULL ORDER BY Year DESC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $years[] = $row['Year'];
}
$stmt->close();

// Build WHERE clause
$where_parts = [];
$params = [];
$types = '';

if ($selected_campus) {
    $where_parts[] = "Campus = ?";
    $params[] = $selected_campus;
    $types .= 's';
}
if ($selected_year) {
    $where_parts[] = "Year = ?";
    $params[] = $selected_year;
    $types .= 's';
}

$where = count($where_parts) > 0 ? 'WHERE ' . implode(' AND ', $where_parts) : '';

// Fetch records
$waste_seg_records = [];
$query = "SELECT * FROM tblsolidwastesegregated $where ORDER BY Year DESC, Month, Quarter LIMIT 1000";
$stmt = $db->prepare($query);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$waste_seg_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate totals
$total_quantity = 0;
$total_ghg_kg = 0;
$total_ghg_t = 0;
foreach ($waste_seg_records as $record) {
    $total_quantity += $record['QuantityInKG'] ?? 0;
    $total_ghg_kg += $record['GHGEmissionKGCO2e'] ?? 0;
    $total_ghg_t += $record['GHGEmissionTCO2e'] ?? 0;
}
?>

                    <div class="max-w-7xl">
                        <!-- Page Header -->
                        <div class="mb-6">
                            <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                                <i class="fas fa-recycle text-green-600 mr-3"></i>
                                Waste Segregated Report
                            </h1>
                            <p class="text-gray-600 mt-1">Biodegradable and Recyclable Waste Management</p>
                        </div>

                        <!-- Filter Section -->
                        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                            <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Campus</label>
                                    <select name="campus" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">All Campuses</option>
                                        <?php foreach ($campuses as $campus): ?>
                                            <option value="<?php echo htmlspecialchars($campus); ?>" <?php echo ($selected_campus === $campus) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($campus); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                                    <select name="year" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">All Years</option>
                                        <?php foreach ($years as $year): ?>
                                            <option value="<?php echo $year; ?>" <?php echo ($selected_year == $year) ? 'selected' : ''; ?>>
                                                <?php echo $year; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="flex items-end">
                                    <button type="submit" class="w-full px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                                        <i class="fas fa-filter mr-2"></i>Apply Filter
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Statistics Cards -->
                        <?php if (!empty($waste_seg_records)): ?>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                                    <div class="text-blue-600 text-sm font-semibold uppercase mb-2">Total Records</div>
                                    <div class="text-3xl font-bold text-blue-900"><?php echo count($waste_seg_records); ?></div>
                                </div>
                                
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                                    <div class="text-yellow-600 text-sm font-semibold uppercase mb-2">Total Quantity</div>
                                    <div class="text-3xl font-bold text-yellow-900"><?php echo number_format($total_quantity, 2); ?> kg</div>
                                </div>
                                
                                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                                    <div class="text-green-600 text-sm font-semibold uppercase mb-2">GHG Emissions</div>
                                    <div class="text-3xl font-bold text-green-900"><?php echo number_format($total_ghg_kg, 2); ?> kg CO₂e</div>
                                </div>
                                
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                                    <div class="text-blue-600 text-sm font-semibold uppercase mb-2">GHG Emissions</div>
                                    <div class="text-3xl font-bold text-blue-900"><?php echo number_format($total_ghg_t, 2); ?> t CO₂e</div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Records Table -->
                        <?php if (!empty($waste_seg_records)): ?>
                            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                                <div class="px-6 py-4 border-b bg-green-50 flex justify-between items-center">
                                    <h2 class="text-xl font-semibold text-gray-800">
                                        <i class="fas fa-recycle text-green-600 mr-2"></i>
                                        Waste Segregated Records (<span id="waste-seg-count"><?php echo count($waste_seg_records); ?></span>)
                                    </h2>
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
                                                <?php foreach (array_keys($waste_seg_records[0]) as $col): ?>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"><?php echo htmlspecialchars($col); ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                            <tr class="bg-white">
                                                <?php $colIdx = 0; foreach (array_keys($waste_seg_records[0]) as $col): ?>
                                                <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="waste-seg-table" data-col="<?php echo $colIdx++; ?>" placeholder="Search..."></th>
                                                <?php endforeach; ?>
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
                        <?php else: ?>
                            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                                <i class="fas fa-inbox text-gray-400 text-5xl mb-4"></i>
                                <p class="text-gray-600 text-lg">No waste segregated records found</p>
                                <p class="text-gray-500 text-sm mt-2">Try adjusting your filters</p>
                            </div>
                        <?php endif; ?>
                    </div>

<script src="../static/js/tables.js"></script>
<script>
// Initialize waste segregated table
if (document.getElementById('waste-seg-table')) {
    initializeTable('waste-seg');
}
</script>

<?php require_once 'tailwind-footer.php'; ?>
