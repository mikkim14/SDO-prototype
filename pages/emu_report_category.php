<?php
$page_title = 'Category Report - EMU';
require_once 'tailwind-header.php';
require_once '../includes/GHGCalculator.php';

$category = $_GET['cat'] ?? 'electricity';

// Handle download requests
$download_format = $_GET['download'] ?? '';
if (in_array($download_format, ['csv', 'pdf'])) {
    $is_download = true;
} else {
    $is_download = false;
}

// Category configuration
$categories = [
    'electricity' => ['name' => 'Electricity', 'icon' => 'fa-bolt', 'color' => 'yellow', 'table' => 'electricity_consumption', 'year_col' => 'year', 'campus_col' => 'campus'],
    'water' => ['name' => 'Water', 'icon' => 'fa-droplet', 'color' => 'blue', 'table' => 'tblwater', 'year_col' => null, 'campus_col' => 'campus'],
    'treated_water' => ['name' => 'Treated Water', 'icon' => 'fa-faucet', 'color' => 'cyan', 'table' => 'tbltreatedwater', 'year_col' => 'Year', 'campus_col' => 'Campus'],
    'waste_segregated' => ['name' => 'Waste (Segregated)', 'icon' => 'fa-trash', 'color' => 'gray', 'table' => 'tblsolidwastesegregated', 'year_col' => 'Year', 'campus_col' => 'Campus'],
    'waste_unsegregated' => ['name' => 'Waste (Unsegregated)', 'icon' => 'fa-recycle', 'color' => 'red', 'table' => 'tblsolidwasteunsegregated', 'year_col' => 'Year', 'campus_col' => 'Campus'],
];

if (!isset($categories[$category])) {
    $category = 'electricity';
}

$cat_config = $categories[$category];
$selected_campus = $user['campus'];
$selected_year = $_GET['year'] ?? '';
$selected_month = $_GET['month'] ?? '';
$selected_quarter = $_GET['quarter'] ?? '';

// Get available years
$years = [];
$campus_col = $cat_config['campus_col'];
if ($cat_config['year_col']) {
    $query = "SELECT DISTINCT {$cat_config['year_col']} as year FROM {$cat_config['table']} WHERE {$cat_config['year_col']} IS NOT NULL AND {$campus_col} = ? ORDER BY year DESC";
} else {
    $query = "SELECT DISTINCT YEAR(date) as year FROM {$cat_config['table']} WHERE date IS NOT NULL AND {$campus_col} = ? ORDER BY year DESC";
}

$stmt = $db->prepare($query);
$stmt->bind_param("s", $selected_campus);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if ($row['year']) $years[] = $row['year'];
}
$stmt->close();

// Get available months
$months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

// Get available quarters
$quarters = ['Q1', 'Q2', 'Q3', 'Q4'];

// Build where clause
$where = "WHERE {$campus_col} = ?";
$params = [$selected_campus];
$types = "s";

if ($selected_year) {
    if ($cat_config['year_col']) {
        $where .= " AND {$cat_config['year_col']} = ?";
    } else {
        $where .= " AND YEAR(date) = ?";
    }
    $params[] = $selected_year;
    $types .= "s";
}

if ($selected_month) {
    if ($category === 'electricity' || in_array($category, ['waste_segregated', 'waste_unsegregated', 'treated_water'])) {
        $where .= " AND Month = ?";
        $params[] = $selected_month;
        $types .= "s";
    } elseif ($category === 'water') {
        $where .= " AND MONTHNAME(Date) = ?";
        $params[] = $selected_month;
        $types .= "s";
    }
}

if ($selected_quarter && in_array($category, ['electricity', 'waste_segregated'])) {
    $where .= " AND Quarter = ?";
    $params[] = $selected_quarter;
    $types .= "s";
}

// Fetch records - handle different date column names
if ($cat_config['year_col']) {
    $query = "SELECT * FROM {$cat_config['table']} $where ORDER BY {$cat_config['year_col']} DESC, Month DESC LIMIT 1000";
} else {
    $query = "SELECT * FROM {$cat_config['table']} $where ORDER BY date DESC LIMIT 1000";
}
$stmt = $db->prepare($query);
if (!$stmt) {
    die("Query preparation failed: " . $db->error . " Query: " . $query);
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$records = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate total emissions
$total_kg_co2 = 0;
foreach ($records as $record) {
    switch ($category) {
        case 'electricity':
            $kwh = $record['consumption'] ?? 0;
            $total_kg_co2 += $kwh * 0.7264;
            break;
        case 'water':
            $total_kg_co2 += ($record['Consumption'] ?? 0) * 0.344;
            break;
        case 'treated_water':
            $total_kg_co2 += ($record['TreatedWaterVolume'] ?? 0) * 1.062;
            break;
        case 'waste_segregated':
            $total_kg_co2 += ($record['QuantityInKG'] ?? 0) * 0.5;
            break;
        case 'waste_unsegregated':
            $total_kg_co2 += ($record['QuantityInKG'] ?? 0) * 0.5;
            break;
    }
}

// Handle CSV download
if ($is_download && $download_format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $category . '_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, [$cat_config['name'] . ' Report', $selected_campus . ' Campus']);
    fputcsv($output, ['Generated on', date('F d, Y')]);
    if ($selected_year) fputcsv($output, ['Year Filter', $selected_year]);
    if ($selected_month) fputcsv($output, ['Month Filter', $selected_month]);
    if ($selected_quarter) fputcsv($output, ['Quarter Filter', $selected_quarter]);
    fputcsv($output, ['Total Emissions', number_format($total_kg_co2, 2) . ' kg CO₂e']);
    fputcsv($output, []);
    
    // Dynamic headers based on category
    if ($category === 'electricity') {
        fputcsv($output, ['Year', 'Month', 'Category', 'Prev Reading', 'Current Reading', 'Multiplier', 'Consumption (kWh)', 'Total Amount (₱)', 'kg CO₂e']);
        foreach ($records as $record) {
            $kg_co2 = ($record['consumption'] ?? 0) * 0.7264;
            fputcsv($output, [
                $record['year'],
                $record['month'],
                $record['category'] ?? '',
                number_format($record['prev_reading'] ?? 0, 2),
                number_format($record['current_reading'] ?? 0, 2),
                number_format($record['multiplier'] ?? 1, 2),
                number_format($record['consumption'] ?? 0, 2),
                number_format($record['total_amount'] ?? 0, 2),
                number_format($kg_co2, 2)
            ]);
        }
    } elseif ($category === 'water') {
        fputcsv($output, ['Date', 'Category', 'Previous Reading', 'Current Reading', 'Consumption (m³)', 'kg CO₂e']);
        foreach ($records as $record) {
            $kg_co2 = ($record['Consumption'] ?? 0) * 0.344;
            fputcsv($output, [
                $record['Date'] ?? '',
                $record['Category'] ?? '',
                number_format($record['PreviousReading'] ?? 0, 2),
                number_format($record['CurrentReading'] ?? 0, 2),
                number_format($record['Consumption'] ?? 0, 2),
                number_format($kg_co2, 2)
            ]);
        }
    } elseif ($category === 'treated_water') {
        fputcsv($output, ['Month', 'Treated Water Volume (m³)', 'Reused Volume (m³)', 'Effluent Volume (m³)', 'kg CO₂e']);
        foreach ($records as $record) {
            $kg_co2 = ($record['TreatedWaterVolume'] ?? 0) * 1.062;
            fputcsv($output, [
                $record['Month'] ?? '',
                number_format($record['TreatedWaterVolume'] ?? 0, 2),
                number_format($record['ReusedTreatedWaterVolume'] ?? 0, 2),
                number_format($record['EffluentVolume'] ?? 0, 2),
                number_format($kg_co2, 2)
            ]);
        }
    } elseif ($category === 'waste_segregated') {
        fputcsv($output, ['Year', 'Month', 'Main Category', 'Sub Category', 'Quantity (kg)', 'kg CO₂e']);
        foreach ($records as $record) {
            $kg_co2 = ($record['QuantityInKG'] ?? 0) * 0.5;
            fputcsv($output, [
                $record['Year'] ?? '',
                $record['Month'] ?? '',
                $record['MainCategory'] ?? '',
                $record['SubCategory'] ?? '',
                number_format($record['QuantityInKG'] ?? 0, 2),
                number_format($kg_co2, 2)
            ]);
        }
    } elseif ($category === 'waste_unsegregated') {
        fputcsv($output, ['Year', 'Month', 'Waste Type', 'Quantity (kg)', 'To Landfill (kg)', 'Percentage', 'kg CO₂e']);
        foreach ($records as $record) {
            $kg_co2 = ($record['QuantityInKG'] ?? 0) * 0.5;
            fputcsv($output, [
                $record['Year'] ?? '',
                $record['Month'] ?? '',
                $record['WasteType'] ?? '',
                number_format($record['QuantityInKG'] ?? 0, 2),
                number_format($record['SentToLandfillKG'] ?? 0, 2),
                number_format($record['Percentage'] ?? 0, 2) . '%',
                number_format($kg_co2, 2)
            ]);
        }
    }
    
    fclose($output);
    exit;
}

// Handle PDF download (printable HTML)
if ($is_download && $download_format === 'pdf') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo $cat_config['name']; ?> Report</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { text-align: center; color: #333; }
            .info { text-align: center; margin-bottom: 20px; font-size: 12px; color: #666; }
            .filters { background: #f5f5f5; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
            .total { background: #4CAF50; color: white; padding: 15px; text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; font-size: 10px; }
            th { background: #333; color: white; padding: 8px; text-align: center; }
            td { border: 1px solid #ddd; padding: 6px; }
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
        <h1><?php echo $cat_config['name']; ?> Report</h1>
        <div class="info">
            <strong><?php echo htmlspecialchars($selected_campus); ?> Campus - EMU</strong><br>
            Generated on <?php echo date('F d, Y'); ?>
        </div>
        
        <?php if ($selected_year || $selected_month || $selected_quarter): ?>
        <div class="filters">
            <strong>Filters Applied:</strong>
            <?php if ($selected_year): ?> Year: <?php echo $selected_year; ?> |<?php endif; ?>
            <?php if ($selected_month): ?> Month: <?php echo $selected_month; ?> |<?php endif; ?>
            <?php if ($selected_quarter): ?> Quarter: <?php echo $selected_quarter; ?><?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="total">
            Total Emissions: <?php echo number_format($total_kg_co2, 2); ?> kg CO₂e
        </div>
        
        <table>
            <thead>
                <tr>
                    <?php if ($category === 'electricity'): ?>
                        <th>Year</th><th>Month</th><th>Category</th><th>Prev Reading</th><th>Current Reading</th>
                        <th>Multiplier</th><th>Consumption</th><th>Amount (₱)</th><th>kg CO₂e</th>
                    <?php elseif ($category === 'water'): ?>
                        <th>Date</th><th>Category</th><th>Prev Reading</th><th>Current Reading</th>
                        <th>Consumption</th><th>kg CO₂e</th>
                    <?php elseif ($category === 'treated_water'): ?>
                        <th>Month</th><th>Treated Water Vol</th><th>Reused Vol</th><th>Effluent Vol</th><th>kg CO₂e</th>
                    <?php elseif ($category === 'waste_segregated'): ?>
                        <th>Year</th><th>Month</th><th>Main Category</th><th>Sub Category</th><th>Quantity</th><th>kg CO₂e</th>
                    <?php elseif ($category === 'waste_unsegregated'): ?>
                        <th>Year</th><th>Month</th><th>Waste Type</th><th>Quantity</th><th>To Landfill</th><th>%</th><th>kg CO₂e</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $record): 
                    $kg_co2 = 0;
                    switch ($category) {
                        case 'electricity':
                            $kg_co2 = ($record['consumption'] ?? 0) * 0.7264;
                            break;
                        case 'water':
                            $kg_co2 = ($record['Consumption'] ?? 0) * 0.344;
                            break;
                        case 'treated_water':
                            $kg_co2 = ($record['TreatedWaterVolume'] ?? 0) * 1.062;
                            break;
                        case 'waste_segregated':
                        case 'waste_unsegregated':
                            $kg_co2 = ($record['QuantityInKG'] ?? 0) * 0.5;
                            break;
                    }
                ?>
                <tr>
                    <?php if ($category === 'electricity'): ?>
                        <td><?php echo $record['year']; ?></td>
                        <td><?php echo $record['month']; ?></td>
                        <td><?php echo $record['category'] ?? ''; ?></td>
                        <td class="number"><?php echo number_format($record['prev_reading'] ?? 0, 2); ?></td>
                        <td class="number"><?php echo number_format($record['current_reading'] ?? 0, 2); ?></td>
                        <td class="number"><?php echo number_format($record['multiplier'] ?? 1, 2); ?></td>
                        <td class="number"><?php echo number_format($record['consumption'] ?? 0, 2); ?> kWh</td>
                        <td class="number"><?php echo number_format($record['total_amount'] ?? 0, 2); ?></td>
                        <td class="number"><strong><?php echo number_format($kg_co2, 2); ?></strong></td>
                    <?php elseif ($category === 'water'): ?>
                        <td><?php echo $record['Date'] ?? ''; ?></td>
                        <td><?php echo $record['Category'] ?? ''; ?></td>
                        <td class="number"><?php echo number_format($record['PreviousReading'] ?? 0, 2); ?></td>
                        <td class="number"><?php echo number_format($record['CurrentReading'] ?? 0, 2); ?></td>
                        <td class="number"><?php echo number_format($record['Consumption'] ?? 0, 2); ?> m³</td>
                        <td class="number"><strong><?php echo number_format($kg_co2, 2); ?></strong></td>
                    <?php elseif ($category === 'treated_water'): ?>
                        <td><?php echo $record['Month'] ?? ''; ?></td>
                        <td class="number"><?php echo number_format($record['TreatedWaterVolume'] ?? 0, 2); ?> m³</td>
                        <td class="number"><?php echo number_format($record['ReusedTreatedWaterVolume'] ?? 0, 2); ?> m³</td>
                        <td class="number"><?php echo number_format($record['EffluentVolume'] ?? 0, 2); ?> m³</td>
                        <td class="number"><strong><?php echo number_format($kg_co2, 2); ?></strong></td>
                    <?php elseif ($category === 'waste_segregated'): ?>
                        <td><?php echo $record['Year'] ?? ''; ?></td>
                        <td><?php echo $record['Month'] ?? ''; ?></td>
                        <td><?php echo $record['MainCategory'] ?? ''; ?></td>
                        <td><?php echo $record['SubCategory'] ?? ''; ?></td>
                        <td class="number"><?php echo number_format($record['QuantityInKG'] ?? 0, 2); ?> kg</td>
                        <td class="number"><strong><?php echo number_format($kg_co2, 2); ?></strong></td>
                    <?php elseif ($category === 'waste_unsegregated'): ?>
                        <td><?php echo $record['Year'] ?? ''; ?></td>
                        <td><?php echo $record['Month'] ?? ''; ?></td>
                        <td><?php echo $record['WasteType'] ?? ''; ?></td>
                        <td class="number"><?php echo number_format($record['QuantityInKG'] ?? 0, 2); ?> kg</td>
                        <td class="number"><?php echo number_format($record['SentToLandfillKG'] ?? 0, 2); ?> kg</td>
                        <td class="number"><?php echo number_format($record['Percentage'] ?? 0, 2); ?>%</td>
                        <td class="number"><strong><?php echo number_format($kg_co2, 2); ?></strong></td>
                    <?php endif; ?>
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

// Calculate consumption totals
$total_consumption = 0;
foreach ($records as $record) {
    switch ($category) {
        case 'electricity':
            $total_consumption += ($record['consumption'] ?? 0);
            break;
        case 'water':
            $total_consumption += ($record['Consumption'] ?? 0);
            break;
        case 'treated_water':
            $total_consumption += ($record['TreatedWaterVolume'] ?? 0);
            break;
        case 'waste_segregated':
        case 'waste_unsegregated':
            $total_consumption += ($record['QuantityInKG'] ?? 0);
            break;
    }
}
?>

<div>
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas <?php echo $cat_config['icon']; ?> text-<?php echo $cat_config['color']; ?>-600 mr-2"></i>
                <?php echo $cat_config['name']; ?> Report
            </h1>
            <p class="text-gray-600 mt-1">Detailed consumption and emissions data • <?php echo htmlspecialchars($selected_campus); ?> Campus</p>
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
        <form method="GET" class="flex gap-4 items-end flex-wrap">
            <input type="hidden" name="cat" value="<?php echo htmlspecialchars($category); ?>">
            <?php if (!empty($years)): ?>
            <div class="flex-1 min-w-[150px]">
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
            <?php endif; ?>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-filter mr-2"></i>Apply
            </button>
            <?php if ($selected_year): ?>
                <a href="?cat=<?php echo htmlspecialchars($category); ?>" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600">Total Records</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo number_format(count($records)); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600">Total Consumption</p>
            <p class="text-2xl font-bold text-<?php echo $cat_config['color']; ?>-600"><?php echo number_format($total_consumption, 2); ?></p>
            <p class="text-xs text-gray-500"><?php 
                if ($category === 'electricity') echo 'kWh';
                elseif (in_array($category, ['water', 'treated_water'])) echo 'm³';
                else echo 'kg';
            ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600">CO2 Emissions</p>
            <p class="text-2xl font-bold text-red-600"><?php echo number_format($total_kg_co2, 2); ?> kg</p>
            <p class="text-xs text-gray-500"><?php echo number_format($total_kg_co2 / 1000, 4); ?> tCO2</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm text-gray-600">Trees Needed</p>
            <p class="text-2xl font-bold text-green-600"><?php echo number_format(ceil($total_kg_co2 / 21)); ?></p>
            <p class="text-xs text-gray-500">@ 21 kg CO2/tree/year</p>
        </div>
    </div>

    <!-- Detailed Records Table -->
    <?php if (!empty($records)): ?>
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 border-b bg-<?php echo $cat_config['color']; ?>-50 flex justify-between items-center">
            <h2 class="text-xl font-semibold"><i class="fas <?php echo $cat_config['icon']; ?> text-<?php echo $cat_config['color']; ?>-600 mr-2"></i><?php echo $cat_config['name']; ?> Records (<span id="record-count"><?php echo count($records); ?></span>)</h2>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Rows per page:</label>
                <select id="category-per-page" class="px-2 py-1 border rounded text-sm">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="category-table">
                <thead class="bg-gray-50">
                    <tr>
                        <?php foreach (array_keys($records[0]) as $col): ?>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"><?php echo htmlspecialchars($col); ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <tr class="bg-white">
                        <?php $colIdx = 0; foreach (array_keys($records[0]) as $col): ?>
                        <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-table="category-table" data-col="<?php echo $colIdx++; ?>" placeholder="Search..."></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($records as $rec): ?>
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
                Showing <span id="category-start">1</span> to <span id="category-end">25</span> of <span id="category-total"><?php echo count($records); ?></span> entries
            </div>
            <div class="flex gap-2" id="category-pagination"></div>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-lg shadow-md p-6 text-center text-gray-500">
        <i class="fas fa-inbox text-4xl mb-2"></i>
        <p>No records found for <?php echo $cat_config['name']; ?></p>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<script>
function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.text('<?php echo addslashes($cat_config['name']); ?> Report', 14, 20);
    doc.text('Campus: <?php echo addslashes($selected_campus); ?>', 14, 28);
    doc.autoTable({
        startY: 35,
        head: [['Total Records', 'Total Consumption', 'CO2 (kg)', 'Trees']],
        body: [['<?php echo count($records); ?>', '<?php echo number_format($total_consumption, 2); ?>', '<?php echo number_format($total_kg_co2, 2); ?>', '<?php echo number_format(ceil($total_kg_co2 / 21)); ?>']],
    });
    doc.save('<?php echo $category; ?>_report.pdf');
}

function downloadCSV() {
    let csv = '=== <?php echo strtoupper($cat_config['name']); ?> RECORDS ===\n';
    csv += 'Campus: <?php echo addslashes($selected_campus); ?>\n';
    csv += 'Generated: <?php echo date('Y-m-d H:i:s'); ?>\n\n';
    
    const table = document.getElementById('category-table');
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
        csv += 'Total Records,<?php echo count($records); ?>\n';
        csv += 'Total Consumption,<?php echo $total_consumption; ?>\n';
        csv += 'Total CO2 (kg),<?php echo $total_kg_co2; ?>\n';
        csv += 'Trees Needed,<?php echo ceil($total_kg_co2 / 21); ?>\n';
    }
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = '<?php echo $category; ?>_report_<?php echo date('Y-m-d'); ?>.csv';
    a.click();
}

// Multi-column filtering and pagination
document.addEventListener('DOMContentLoaded', function() {
    const tables = { 'category-table': { currentPage: 1, perPage: 25, totalRows: 0, filteredRows: [] } };
    
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
        
        document.getElementById('record-count').textContent = tables[tableId].filteredRows.length;
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
        
        document.getElementById('category-start').textContent = filteredRows.length > 0 ? start + 1 : 0;
        document.getElementById('category-end').textContent = end;
        document.getElementById('category-total').textContent = filteredRows.length;
        
        renderPagination(tableId, filteredRows.length);
    }
    
    function renderPagination(tableId, totalRows) {
        const config = tables[tableId];
        const totalPages = Math.ceil(totalRows / config.perPage);
        const paginationDiv = document.getElementById('category-pagination');
        
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
    
    const select = document.getElementById('category-per-page');
    if (select) {
        select.addEventListener('change', function() {
            tables['category-table'].perPage = parseInt(this.value);
            tables['category-table'].currentPage = 1;
            displayPage('category-table');
        });
    }
    
    const filterInputs = document.querySelectorAll('.filter-input');
    filterInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const tableId = this.getAttribute('data-table');
            applyFilters(tableId);
        });
    });
    
    if (document.getElementById('category-table')) {
        displayPage('category-table');
    }
});
</script>

<?php require_once 'tailwind-footer.php'; ?>
