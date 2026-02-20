<?php
$page_title = 'Scope 2 Report - EMU';
require_once 'tailwind-header.php';
require_once '../includes/GHGCalculator.php';

// Handle download requests
$download_format = $_GET['download'] ?? '';
if (in_array($download_format, ['csv', 'pdf'])) {
    // Set flag to skip HTML output
    $is_download = true;
} else {
    $is_download = false;
}

// EMU can only see their own office data for their campus
$selected_campus = $user['campus'];
$selected_year = $_GET['year'] ?? '';
$selected_month = $_GET['month'] ?? '';
$selected_quarter = $_GET['quarter'] ?? '';
$selected_category = $_GET['category'] ?? '';

// Get available years
$years = [];
$year_query = "SELECT DISTINCT year FROM electricity_consumption WHERE year IS NOT NULL AND campus = ? ORDER BY year DESC";
$stmt = $db->prepare($year_query);
$stmt->bind_param("s", $selected_campus);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if ($row['year']) {
        $years[] = $row['year'];
    }
}
$stmt->close();

// Get available months
$months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

// Get available quarters
$quarters = ['Q1', 'Q2', 'Q3', 'Q4'];

// Get available categories
$categories = [];
$cat_query = "SELECT DISTINCT category FROM electricity_consumption WHERE campus = ? AND category IS NOT NULL ORDER BY category";
$stmt = $db->prepare($cat_query);
$stmt->bind_param("s", $selected_campus);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if ($row['category']) {
        $categories[] = $row['category'];
    }
}
$stmt->close();

// Build where clause for campus only
$where = "WHERE campus = ?";
$params = [$selected_campus];
$types = "s";

if ($selected_year) {
    $where .= " AND year = ?";
    $params[] = $selected_year;
    $types .= "s";
}

if ($selected_month) {
    $where .= " AND month = ?";
    $params[] = $selected_month;
    $types .= "s";
}

if ($selected_quarter) {
    $where .= " AND quarter = ?";
    $params[] = $selected_quarter;
    $types .= "s";
}

if ($selected_category) {
    $where .= " AND category = ?";
    $params[] = $selected_category;
    $types .= "s";
}

// Calculate totals for Scope 2 (Electricity only)
$scope_total = 0;
$categories_data = [];

// Electricity
$query = "SELECT * FROM electricity_consumption $where ORDER BY year DESC, month DESC LIMIT 1000";
$stmt = $db->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$electricity_records = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$electricity_total = 0;
foreach ($electricity_records as $record) {
    $kg_co2 = ($record['consumption'] ?? 0) * 0.7264;
    $electricity_total += $kg_co2;
}
$categories_data['electricity'] = ['records' => $electricity_records, 'total_kg_co2' => $electricity_total];
$scope_total = $electricity_total;

// Handle downloads
if ($is_download && $download_format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="scope2_electricity_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Scope 2 Report - Electricity', $selected_campus . ' Campus']);
    fputcsv($output, ['Generated on', date('F d, Y')]);
    if ($selected_year) fputcsv($output, ['Year Filter', $selected_year]);
    if ($selected_month) fputcsv($output, ['Month Filter', $selected_month]);
    if ($selected_quarter) fputcsv($output, ['Quarter Filter', $selected_quarter]);
    if ($selected_category) fputcsv($output, ['Category Filter', $selected_category]);
    fputcsv($output, ['Total Emissions', number_format($scope_total, 2) . ' kg CO₂e']);
    fputcsv($output, []);
    
    fputcsv($output, ['Year', 'Month', 'Quarter', 'Category', 'Prev Reading', 'Current Reading', 'Multiplier', 'Consumption (kWh)', 'Total Amount (₱)', 'kg CO₂e']);
    
    foreach ($electricity_records as $record) {
        $kg_co2 = ($record['consumption'] ?? 0) * 0.7264;
        fputcsv($output, [
            $record['year'],
            $record['month'],
            $record['quarter'] ?? '',
            $record['category'] ?? '',
            number_format($record['prev_reading'] ?? 0, 2),
            number_format($record['current_reading'] ?? 0, 2),
            number_format($record['multiplier'] ?? 1, 2),
            number_format($record['consumption'] ?? 0, 2),
            number_format($record['total_amount'] ?? 0, 2),
            number_format($kg_co2, 2)
        ]);
    }
    
    fclose($output);
    exit;
} elseif ($is_download && $download_format === 'pdf') {
    // Generate printable HTML for PDF
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Scope 2 Report - Electricity</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { text-align: center; color: #333; }
            .info { text-align: center; margin-bottom: 20px; font-size: 12px; color: #666; }
            .filters { background: #f5f5f5; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
            .total { background: #ffc107; padding: 15px; text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; font-size: 10px; }
            th { background: #333; color: white; padding: 8px; text-align: center; }
            td { border: 1px solid #ddd; padding: 6px; text-align: center; }
            tr:nth-child(even) { background: #f9f9f9; }
            .number { text-align: right; }
            @media print {
                body { margin: 0; }
                .no-print { display: none; }
            }
        </style>
        <script>
            window.onload = function() { window.print(); }
        </script>
    </head>
    <body>
        <h1>Scope 2 Report - Electricity</h1>
        <div class="info">
            <strong><?php echo htmlspecialchars($selected_campus); ?> Campus - EMU</strong><br>
            Generated on <?php echo date('F d, Y'); ?>
        </div>
        
        <?php if ($selected_year || $selected_month || $selected_quarter || $selected_category): ?>
        <div class="filters">
            <strong>Filters Applied:</strong>
            <?php if ($selected_year): ?> Year: <?php echo $selected_year; ?> |<?php endif; ?>
            <?php if ($selected_month): ?> Month: <?php echo $selected_month; ?> |<?php endif; ?>
            <?php if ($selected_quarter): ?> Quarter: <?php echo $selected_quarter; ?> |<?php endif; ?>
            <?php if ($selected_category): ?> Category: <?php echo $selected_category; ?><?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="total">
            Total Emissions: <?php echo number_format($scope_total, 2); ?> kg CO₂e
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Year</th>
                    <th>Month</th>
                    <th>Quarter</th>
                    <th>Category</th>
                    <th>Prev Reading</th>
                    <th>Current Reading</th>
                    <th>Multiplier</th>
                    <th>Consumption (kWh)</th>
                    <th>Amount (₱)</th>
                    <th>kg CO₂e</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($electricity_records as $record): 
                    $kg_co2 = ($record['consumption'] ?? 0) * 0.7264;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($record['year']); ?></td>
                    <td><?php echo htmlspecialchars($record['month']); ?></td>
                    <td><?php echo htmlspecialchars($record['quarter'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($record['category'] ?? ''); ?></td>
                    <td class="number"><?php echo number_format($record['prev_reading'] ?? 0, 2); ?></td>
                    <td class="number"><?php echo number_format($record['current_reading'] ?? 0, 2); ?></td>
                    <td class="number"><?php echo number_format($record['multiplier'] ?? 1, 2); ?></td>
                    <td class="number"><?php echo number_format($record['consumption'] ?? 0, 2); ?></td>
                    <td class="number"><?php echo number_format($record['total_amount'] ?? 0, 2); ?></td>
                    <td class="number"><strong><?php echo number_format($kg_co2, 2); ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="no-print" style="margin-top: 20px; text-align: center;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">Print to PDF</button>
            <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">Close</button>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Calculate totals
$total_consumption = 0;
$total_records = count($electricity_records);
foreach ($electricity_records as $record) {
    $total_consumption += ($record['consumption'] ?? 0);
}

// Build breakdown for per-category analytics
$breakdown = [];
if ($selected_category) {
    $breakdown[$selected_category] = [
        'name' => ucfirst($selected_category),
        'kg_co2' => $scope_total,
        'records' => $total_records,
        'consumption' => $total_consumption,
        'unit' => 'kWh'
    ];
}
?>

<div>
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-bolt text-yellow-600 mr-2"></i>
                Scope 2 Report: Indirect Emissions
            </h1>
            <p class="text-gray-600 mt-1">Electricity Consumption • <?php echo htmlspecialchars($selected_campus); ?> Campus</p>
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
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-filter mr-2"></i>Apply
            </button>
            <?php if ($selected_year): ?>
                <a href="?" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Consumption</p>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400"><?php echo number_format($total_consumption); ?> kWh</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Emissions</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400"><?php echo number_format($scope_total, 2); ?> kg</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Records</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-200"><?php echo number_format($total_records); ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Trees Needed</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400"><?php echo number_format(ceil($scope_total / 21)); ?></p>
        </div>
    </div>

    <!-- Per-Category Analytics (shown when filtering by category) -->
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
    </div>
    <?php endif; ?>

    <!-- Detailed Records Table -->
    <?php if (!empty($electricity_records)): ?>
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b bg-yellow-50 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800"><i class="fas fa-bolt text-yellow-600 mr-2"></i>Electricity Consumption Records (<span id="elec-count"><?php echo count($electricity_records); ?></span>)</h2>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Rows per page:</label>
                <select id="elec-per-page" class="px-2 py-1 border rounded text-sm">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="elec-table">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Campus</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Year</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Prev Reading</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Curr Reading</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Multiplier</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Consumption (kWh)</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">kg CO2</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Trees</th>
                    </tr>
                    <tr class="bg-white">
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="elec-table" data-col="0" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="elec-table" data-col="1" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="elec-table" data-col="2" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="elec-table" data-col="3" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="elec-table" data-col="4" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="elec-table" data-col="5" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="elec-table" data-col="6" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="elec-table" data-col="7" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="elec-table" data-col="8" placeholder="Search..."></th>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="elec-table" data-col="9" placeholder="Search..."></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($electricity_records as $rec): 
                        $kg_co2 = ($rec['consumption'] ?? 0) * 0.7264;
                        $trees = ceil($kg_co2 / 21);
                    ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2"><?php echo htmlspecialchars($rec['campus'] ?? ''); ?></td>
                            <td class="px-4 py-2"><span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs"><?php echo htmlspecialchars($rec['category'] ?? ''); ?></span></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($rec['month'] ?? ''); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($rec['year'] ?? ''); ?></td>
                            <td class="px-4 py-2 text-right"><?php echo number_format($rec['prev_reading'] ?? 0, 2); ?></td>
                            <td class="px-4 py-2 text-right"><?php echo number_format($rec['current_reading'] ?? 0, 2); ?></td>
                            <td class="px-4 py-2 text-right"><?php echo number_format($rec['multiplier'] ?? 0, 2); ?></td>
                            <td class="px-4 py-2 text-right font-medium"><?php echo number_format($rec['consumption'] ?? 0, 2); ?></td>
                            <td class="px-4 py-2 text-right font-semibold text-red-600"><?php echo number_format($kg_co2, 2); ?></td>
                            <td class="px-4 py-2 text-right text-green-600"><?php echo number_format($trees); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gray-50 font-semibold">
                    <tr>
                        <td colspan="7" class="px-4 py-3 text-sm">TOTAL</td>
                        <td class="px-4 py-3 text-sm text-right"><?php echo number_format($total_consumption, 2); ?></td>
                        <td class="px-4 py-3 text-sm text-right text-red-600"><?php echo number_format($scope_total, 2); ?></td>
                        <td class="px-4 py-3 text-sm text-right text-green-600"><?php echo number_format(ceil($scope_total / 21)); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="px-6 py-4 border-t bg-gray-50 flex justify-between items-center">
            <div class="text-sm text-gray-600">
                Showing <span id="elec-start">1</span> to <span id="elec-end">25</span> of <span id="elec-total"><?php echo count($electricity_records); ?></span> entries
            </div>
            <div class="flex gap-2" id="elec-pagination"></div>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<script>
function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.text('Scope 2 Report: Electricity', 14, 20);
    doc.text('Campus: <?php echo addslashes($selected_campus); ?>', 14, 28);
    doc.autoTable({
        startY: 35,
        head: [['Category', 'Records', 'Consumption (kWh)', 'CO2 (kg)']],
        body: [['Electricity', '<?php echo $total_records; ?>', '<?php echo number_format($total_consumption, 2); ?>', '<?php echo number_format($scope_total, 2); ?>']],
        foot: [['TOTAL', '<?php echo $total_records; ?>', '<?php echo number_format($total_consumption, 2); ?>', '<?php echo number_format($scope_total, 2); ?>']],
    });
    doc.save('scope2_report.pdf');
}

function downloadCSV() {
    let csv = '=== ELECTRICITY CONSUMPTION RECORDS ===\n';
    csv += 'Campus: <?php echo addslashes($selected_campus); ?>\n';
    csv += 'Generated: <?php echo date('Y-m-d H:i:s'); ?>\n\n';
    
    const table = document.getElementById('elec-table');
    if (table) {
        const headers = [];
        table.querySelectorAll('thead tr:first-child th').forEach(th => {
            headers.push(th.textContent.trim());
        });
        csv += headers.map(h => '"' + h + '"').join(',') + '\n';
        
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
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
        
        csv += '\n\n=== SUMMARY ===\n';
        csv += 'Total Records,<?php echo $total_records; ?>\n';
        csv += 'Total Consumption (kWh),<?php echo $total_consumption; ?>\n';
        csv += 'Total CO2 (kg),<?php echo $scope_total; ?>\n';
    }
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'scope2_detailed_report_<?php echo date('Y-m-d'); ?>.csv';
    a.click();
}

// Multi-column filtering and pagination
document.addEventListener('DOMContentLoaded', function() {
    const tables = { 'elec-table': { currentPage: 1, perPage: 25, totalRows: 0, filteredRows: [] } };
    
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
        
        document.getElementById('elec-count').textContent = tables[tableId].filteredRows.length;
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
        
        document.getElementById('elec-start').textContent = filteredRows.length > 0 ? start + 1 : 0;
        document.getElementById('elec-end').textContent = end;
        document.getElementById('elec-total').textContent = filteredRows.length;
        
        renderPagination(tableId, filteredRows.length);
    }
    
    function renderPagination(tableId, totalRows) {
        const config = tables[tableId];
        const totalPages = Math.ceil(totalRows / config.perPage);
        const paginationDiv = document.getElementById('elec-pagination');
        
        let html = '';
        html += `<button onclick="changePage('${tableId}', ${config.currentPage - 1})" ${config.currentPage === 1 ? 'disabled' : ''} class="px-3 py-1 border rounded text-sm ${config.currentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-100'}">&laquo; Prev</button>`;
        
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= config.currentPage - 2 && i <= config.currentPage + 2)) {
                html += `<button onclick="changePage('${tableId}', ${i})" class="px-3 py-1 border rounded text-sm ${i === config.currentPage ? 'bg-blue-600 text-white font-semibold' : 'bg-white hover:bg-gray-100'}">${i}</button>`;
            } else if (i === config.currentPage - 3 || i === config.currentPage + 3) {
                html += `<span class="px-2 text-gray-500">...</span>`;
            }
        }
        
        html += `<button onclick="changePage('${tableId}', ${config.currentPage + 1})" ${config.currentPage === totalPages || totalPages === 0 ? 'disabled' : ''} class="px-3 py-1 border rounded text-sm ${config.currentPage === totalPages || totalPages === 0 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-100'}">Next &raquo;</button>`;
        
        paginationDiv.innerHTML = html;
    }
    
    window.changePage = function(tableId, page) {
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
    
    const select = document.getElementById('elec-per-page');
    if (select) {
        select.addEventListener('change', function() {
            tables['elec-table'].perPage = parseInt(this.value);
            tables['elec-table'].currentPage = 1;
            displayPage('elec-table');
        });
    }
    
    const filterInputs = document.querySelectorAll('.filter-input');
    filterInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const tableId = this.getAttribute('data-table');
            applyFilters(tableId);
        });
    });
    
    if (document.getElementById('elec-table')) {
        displayPage('elec-table');
    }
});
</script>

<?php require_once 'tailwind-footer.php'; ?>
