<?php
$page_title = 'Water Consumption';
require_once 'tailwind-header.php';

$message = '';
$error_msg = '';

$module = 'water';
$can_input = AccessControl::canInput($user['office'], $module);
$filter = AccessControl::getGHGFilterClause($user['office'], $user['campus'], $module);

// Fetch all water records (role-aware)
$records = AccessControl::fetchRecords($db, 'tblwater', $filter, 'Date DESC', 100);

// Handle form submission
if (Helper::isPostRequest()) {
    if (!$can_input) {
        $error_msg = 'Your office is read-only for Water. Contact Central to modify data.';
    }

    $year = Helper::getPost('year', '');
    $month = Helper::getPost('month', '');
    $quarter = Helper::getPost('quarter', '');
    $date = Helper::getPost('date', '');
    $category = Helper::getPost('category', 'Mains');
    $prev_reading = Helper::getPost('prev_reading', 0);
    $current_reading = Helper::getPost('current_reading', '');
    $total_amount = Helper::getPost('total_amount', 0);
    $record_id = Helper::getPost('record_id', '');

    // Calculate consumption and price per liter
    $consumption = $current_reading - $prev_reading;
    $price_per_liter = ($consumption > 0 && $total_amount > 0) ? $total_amount / ($consumption * 1000) : 0; // Convert m³ to liters

    // Validation
    $validator = new Validator();
    $validator->validate('date', $date, 'required|date');
    $validator->validate('current_reading', $current_reading, 'required|numeric');
    
    if ($validator->fails()) {
        $error_msg = array_values($validator->errors())[0];
    } elseif ($can_input) {
        try {
            if ($record_id) {
                // Update
                $query = "UPDATE tblwater SET Year = ?, Month = ?, Quarter = ?, Date = ?, Category = ?, PreviousReading = ?, CurrentReading = ?, Consumption = ?, PricePerLiter = ?, TotalAmount = ? WHERE id = ? AND Campus = ?";
                $stmt = $db->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("isssssddddis", $year, $month, $quarter, $date, $category, $prev_reading, $current_reading, $consumption, $price_per_liter, $total_amount, $record_id, $user['campus']);
                    if ($stmt->execute()) {
                        $message = 'Record updated successfully';
                        Helper::logActivity($db, 'Updated Water Consumption Record (ID: ' . $record_id . ')', 'Water Report');
                    } else {
                        $error_msg = 'Failed to update record';
                    }
                    $stmt->close();
                }
            } else {
                // Insert
                $query = "INSERT INTO tblwater (Campus, Year, Month, Quarter, Date, Category, PreviousReading, CurrentReading, Consumption, PricePerLiter, TotalAmount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("sissssddddd", $user['campus'], $year, $month, $quarter, $date, $category, $prev_reading, $current_reading, $consumption, $price_per_liter, $total_amount);
                    if ($stmt->execute()) {
                        $message = 'Record added successfully';
                        Helper::logActivity($db, 'Added Water Consumption Record for ' . $user['campus'] . ' - ' . $date, 'Water Report');
                    } else {
                        $error_msg = 'Failed to add record';
                    }
                    $stmt->close();
                }
            }
            
            // Refresh records
            $records = AccessControl::fetchRecords($db, 'tblwater', $filter, 'date DESC', 100);
            
        } catch (Exception $e) {
            $error_msg = 'Error: ' . $e->getMessage();
        }
    }
}

// Handle delete
if (Helper::isGetRequest() && isset($_GET['delete_id'])) {
    if (!$can_input) {
        $error_msg = 'Your office is read-only for Water. Contact Central to modify data.';
    } else {
        $delete_id = (int)$_GET['delete_id'];
        $query = "DELETE FROM tblwater WHERE id = ? AND Campus = ?";
        $stmt = $db->prepare($query);
        if ($stmt) {
            $stmt->bind_param("is", $delete_id, $user['campus']);
            if ($stmt->execute()) {
                $message = 'Record deleted successfully';
                Helper::logActivity($db, 'Deleted Water Consumption Record (ID: ' . $delete_id . ')', 'Water Report');
            }
            $stmt->close();
            
            // Refresh records
            $records = AccessControl::fetchRecords($db, 'tblwater', $filter, 'date DESC', 100);
        }
    }
}
?>

                    <div class="max-w-6xl">
                        <!-- Page Header -->
                        <div class="mb-6">
                            <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                                <i class="fas fa-tint text-blue-500 mr-3"></i>
                                Water Consumption Records
                            </h1>
                            <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($user['campus']); ?></p>
                        </div>

                        <!-- Messages -->
                        <?php if (!empty($message)): ?>
                            <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded flex items-center animate-pulse">
                                <i class="fas fa-check-circle mr-3"></i>
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error_msg)): ?>
                            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded flex items-center">
                                <i class="fas fa-exclamation-circle mr-3"></i>
                                <?php echo htmlspecialchars($error_msg); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Form Card -->
                        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-plus-circle text-blue-600 mr-2"></i>
                                Add New Record
                            </h2>

                            <form method="POST" action="" class="auto-validate">
                                <input type="hidden" name="record_id" value="">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div>
                                        <label for="campus" class="block text-sm font-medium text-gray-700 mb-2">
                                            Campus <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            id="campus" 
                                            name="campus"
                                            value="<?php echo htmlspecialchars($user['campus']); ?>"
                                            readonly
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed focus:outline-none"
                                        >
                                    </div>

                                    <div>
                                        <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                                            Category <span class="text-red-500">*</span>
                                        </label>
                                        <select 
                                            id="category" 
                                            name="category"
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        >
                                            <option value="Mains">Mains</option>
                                            <option value="Deep Well">Deep Well</option>
                                            <option value="Drinking Water">Drinking Water</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="year" class="block text-sm font-medium text-gray-700 mb-2">
                                            Year <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="number" 
                                            id="year" 
                                            name="year"
                                            min="2020"
                                            max="2100"
                                            value="<?php echo date('Y'); ?>"
                                            required
                                            data-rules="required|numeric"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        >
                                    </div>

                                    <div>
                                        <label for="month" class="block text-sm font-medium text-gray-700 mb-2">
                                            Month <span class="text-red-500">*</span>
                                        </label>
                                        <select 
                                            id="month" 
                                            name="month"
                                            required
                                            data-rules="required"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        >
                                            <option value="">Select Month</option>
                                            <option value="January">January</option>
                                            <option value="February">February</option>
                                            <option value="March">March</option>
                                            <option value="April">April</option>
                                            <option value="May">May</option>
                                            <option value="June">June</option>
                                            <option value="July">July</option>
                                            <option value="August">August</option>
                                            <option value="September">September</option>
                                            <option value="October">October</option>
                                            <option value="November">November</option>
                                            <option value="December">December</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="quarter" class="block text-sm font-medium text-gray-700 mb-2">
                                            Quarter
                                        </label>
                                        <select 
                                            id="quarter" 
                                            name="quarter"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        >
                                            <option value="">Select Quarter</option>
                                            <option value="Q1">Q1 (Jan-Mar)</option>
                                            <option value="Q2">Q2 (Apr-Jun)</option>
                                            <option value="Q3">Q3 (Jul-Sep)</option>
                                            <option value="Q4">Q4 (Oct-Dec)</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="date" class="block text-sm font-medium text-gray-700 mb-2">
                                            Date <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="date" 
                                            id="date" 
                                            name="date" 
                                            required
                                            data-rules="required|date"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        >
                                    </div>

                                    <div>
                                        <label for="total_amount" class="block text-sm font-medium text-gray-700 mb-2">
                                            Total Amount (₱)
                                        </label>
                                        <input 
                                            type="number" 
                                            id="total_amount" 
                                            name="total_amount" 
                                            step="0.01"
                                            value="0"
                                            data-rules="numeric"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="0.00"
                                        >
                                    </div>

                                    <div>
                                        <label for="prev_reading" class="block text-sm font-medium text-gray-700 mb-2">
                                            Previous Reading (m³)
                                        </label>
                                        <input 
                                            type="number" 
                                            id="prev_reading" 
                                            name="prev_reading" 
                                            step="0.01"
                                            value="0"
                                            data-rules="numeric"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="0.00"
                                        >
                                    </div>

                                    <div>
                                        <label for="current_reading" class="block text-sm font-medium text-gray-700 mb-2">
                                            Current Reading (m³) <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="number" 
                                            id="current_reading" 
                                            name="current_reading" 
                                            step="0.01"
                                            required
                                            data-rules="required|numeric"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="0.00"
                                        >
                                    </div>
                                </div>

                                <div class="mt-4 flex gap-3">
                                    <button 
                                        type="submit"
                                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors flex items-center"
                                    >
                                        <i class="fas fa-save mr-2"></i>Save Record
                                    </button>
                                    <button 
                                        type="reset"
                                        class="px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition-colors flex items-center"
                                    >
                                        <i class="fas fa-redo mr-2"></i>Reset
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Records Table -->
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                                <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                                    <i class="fas fa-list mr-2"></i>
                                    Existing Records
                                </h2>
                                <div class="flex items-center gap-2">
                                    <label class="text-sm text-gray-600">Show:</label>
                                    <select id="water-per-page" class="px-2 py-1 border border-gray-300 rounded text-sm">
                                        <option value="10">10</option>
                                        <option value="25" selected>25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                    <span class="text-sm text-gray-600">entries</span>
                                </div>
                            </div>

                            <?php if (count($records) > 0): ?>
                                <div class="overflow-x-auto">
                                    <table class="w-full" id="water-table">
                                        <thead class="bg-gray-100 border-b border-gray-200">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Campus</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Category</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Year</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Month</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Quarter</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Date</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Prev Reading</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Current Reading</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Consumption (m³)</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Price/L</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Total Amount</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">kg CO₂e</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">t CO₂e</th>
                                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700">Actions</th>
                                            </tr>
                                            <tr class="bg-gray-50">
                                                <th class="px-4 py-2"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" placeholder="Filter..." data-column="0"></th>
                                                <th class="px-4 py-2"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" placeholder="Filter..." data-column="1"></th>
                                                <th class="px-4 py-2"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" placeholder="Filter..." data-column="2"></th>
                                                <th class="px-4 py-2"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" placeholder="Filter..." data-column="3"></th>
                                                <th class="px-4 py-2"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" placeholder="Filter..." data-column="4"></th>
                                                <th class="px-4 py-2"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" placeholder="Filter..." data-column="5"></th>
                                                <th class="px-4 py-2"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" placeholder="Filter..." data-column="6"></th>
                                                <th class="px-4 py-2"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" placeholder="Filter..." data-column="7"></th>
                                                <th class="px-4 py-2"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" placeholder="Filter..." data-column="8"></th>
                                                <th class="px-4 py-2"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" placeholder="Filter..." data-column="9"></th>
                                                <th class="px-4 py-2"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" placeholder="Filter..." data-column="10"></th>
                                                <th class="px-4 py-2"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" placeholder="Filter..." data-column="11"></th>
                                                <th class="px-4 py-2"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" placeholder="Filter..." data-column="12"></th>
                                                <th class="px-4 py-2"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <?php foreach ($records as $record): ?>
                                                <tr class="hover:bg-gray-50 transition-colors">
                                                    <td class="px-4 py-3 text-xs text-gray-800"><?php echo htmlspecialchars($record['Campus'] ?? ''); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800"><?php echo htmlspecialchars($record['Category'] ?? 'N/A'); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800"><?php echo htmlspecialchars($record['Year'] ?? ''); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800"><?php echo htmlspecialchars($record['Month'] ?? ''); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800"><?php echo htmlspecialchars($record['Quarter'] ?? '-'); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800"><?php echo Helper::formatDate($record['Date']); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800 text-right"><?php echo Helper::formatNumber($record['PreviousReading'] ?? 0); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800 text-right"><?php echo Helper::formatNumber($record['CurrentReading'] ?? 0); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800 text-right font-semibold"><?php echo Helper::formatNumber($record['Consumption'] ?? 0); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800 text-right">₱<?php echo Helper::formatNumber($record['PricePerLiter'] ?? 0); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800 text-right">₱<?php echo Helper::formatNumber($record['TotalAmount'] ?? 0); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800 text-right font-semibold text-green-700"><?php echo Helper::formatNumber($record['FactorKGCO2e'] ?? 0, 5); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800 text-right font-semibold text-blue-700"><?php echo Helper::formatNumber($record['FactorTCO2e'] ?? 0, 5); ?></td>
                                                    <td class="px-4 py-3 text-center">
                                                        <button 
                                                           onclick="editWaterRecord(<?php echo htmlspecialchars(json_encode($record)); ?>)"
                                                           class="inline-block px-2 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-xs transition-colors mr-1">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="?delete_id=<?php echo $record['id']; ?>" 
                                                           class="inline-block px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded text-xs transition-colors"
                                                           onclick="return confirm('Are you sure you want to delete this record?');">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-between items-center">
                                    <div class="text-sm text-gray-600">
                                        Showing <span id="water-start">1</span> to <span id="water-end">25</span> of <span id="water-total"><?php echo count($records); ?></span> entries
                                    </div>
                                    <div class="flex gap-1" id="water-pagination">
                                        <!-- Pagination buttons will be inserted here -->
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-3"></i>
                                    <p class="text-lg">No records found</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Statistics Card -->
                        <?php if (count($records) > 0): ?>
                            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                                    <div class="text-blue-600 text-sm font-semibold uppercase mb-2">Total Records</div>
                                    <div class="text-3xl font-bold text-blue-900"><?php echo count($records); ?></div>
                                </div>
                                
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                                    <div class="text-yellow-600 text-sm font-semibold uppercase mb-2">Total Consumption</div>
                                    <div class="text-3xl font-bold text-yellow-900">
                                        <?php echo Helper::formatNumber(array_sum(array_column($records, 'Consumption'))); ?> m³
                                    </div>
                                </div>
                                
                                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                                    <div class="text-green-600 text-sm font-semibold uppercase mb-2">Average/Record</div>
                                    <div class="text-3xl font-bold text-green-900">
                                        <?php echo Helper::formatNumber(array_sum(array_column($records, 'Consumption')) / max(1, count($records))); ?> m³
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

<script>
// Edit Water Record Function
function editWaterRecord(record) {
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    document.querySelector('input[name="record_id"]').value = record.id;
    document.querySelector('select[name="category"]').value = record.Category || 'Mains';
    document.querySelector('input[name="year"]').value = record.Year || new Date().getFullYear();
    document.querySelector('select[name="month"]').value = record.Month || '';
    document.querySelector('select[name="quarter"]').value = record.Quarter || '';
    document.querySelector('input[name="date"]').value = record.Date;
    document.querySelector('input[name="prev_reading"]').value = record.PreviousReading || 0;
    document.querySelector('input[name="current_reading"]').value = record.CurrentReading || 0;
    document.querySelector('input[name="total_amount"]').value = record.TotalAmount || 0;
    
    document.querySelector('.bg-white.rounded-lg.shadow-md.p-6.mb-8 h2').innerHTML = '<i class="fas fa-edit text-blue-600 mr-2"></i>Edit Record';
    document.querySelector('button[type="submit"]').innerHTML = '<i class="fas fa-save mr-2"></i>Update Record';
}

// Water Table Pagination and Filtering
const waterTable = document.getElementById('water-table');
const waterPerPage = document.getElementById('water-per-page');
const waterPagination = document.getElementById('water-pagination');
const waterStart = document.getElementById('water-start');
const waterEnd = document.getElementById('water-end');
const waterTotal = document.getElementById('water-total');

let waterCurrentPage = 1;
let waterRowsPerPage = 25;
let waterFilteredRows = [];

function initWaterTable() {
    const tbody = waterTable.querySelector('tbody');
    waterFilteredRows = Array.from(tbody.querySelectorAll('tr'));
    waterTotal.textContent = waterFilteredRows.length;
    displayWaterPage(1);
}

function displayWaterPage(page) {
    waterCurrentPage = page;
    const tbody = waterTable.querySelector('tbody');
    const allRows = tbody.querySelectorAll('tr');
    
    allRows.forEach(row => row.style.display = 'none');
    
    const start = (page - 1) * waterRowsPerPage;
    const end = start + waterRowsPerPage;
    const visibleRows = waterFilteredRows.slice(start, end);
    
    visibleRows.forEach(row => row.style.display = '');
    
    waterStart.textContent = waterFilteredRows.length > 0 ? start + 1 : 0;
    waterEnd.textContent = Math.min(end, waterFilteredRows.length);
    waterTotal.textContent = waterFilteredRows.length;
    
    renderWaterPagination();
}

function renderWaterPagination() {
    const totalPages = Math.ceil(waterFilteredRows.length / waterRowsPerPage);
    waterPagination.innerHTML = '';
    
    if (totalPages <= 1) return;
    
    const prevBtn = document.createElement('button');
    prevBtn.innerHTML = '&laquo;';
    prevBtn.className = 'px-3 py-1 border rounded ' + (waterCurrentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-100');
    prevBtn.disabled = waterCurrentPage === 1;
    prevBtn.onclick = () => changeWaterPage(waterCurrentPage - 1);
    waterPagination.appendChild(prevBtn);
    
    for (let i = 1; i <= Math.min(totalPages, 5); i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        btn.className = 'px-3 py-1 border rounded ' + (i === waterCurrentPage ? 'bg-blue-600 text-white' : 'bg-white hover:bg-gray-100');
        btn.onclick = () => changeWaterPage(i);
        waterPagination.appendChild(btn);
    }
    
    const nextBtn = document.createElement('button');
    nextBtn.innerHTML = '&raquo;';
    nextBtn.className = 'px-3 py-1 border rounded ' + (waterCurrentPage === totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-100');
    nextBtn.disabled = waterCurrentPage === totalPages;
    nextBtn.onclick = () => changeWaterPage(waterCurrentPage + 1);
    waterPagination.appendChild(nextBtn);
}

function changeWaterPage(page) {
    displayWaterPage(page);
}

function applyWaterFilters() {
    const tbody = waterTable.querySelector('tbody');
    const allRows = Array.from(tbody.querySelectorAll('tr'));
    const filterInputs = document.querySelectorAll('.filter-input');
    
    waterFilteredRows = allRows.filter(row => {
        let match = true;
        filterInputs.forEach(input => {
            const columnIndex = parseInt(input.dataset.column);
            const filterValue = input.value.toLowerCase();
            if (filterValue) {
                const cell = row.cells[columnIndex];
                const cellText = cell ? cell.textContent.toLowerCase() : '';
                if (!cellText.includes(filterValue)) {
                    match = false;
                }
            }
        });
        return match;
    });
    
    displayWaterPage(1);
}

if (waterPerPage) {
    waterPerPage.addEventListener('change', function() {
        waterRowsPerPage = parseInt(this.value);
        displayWaterPage(1);
    });
}

const filterInputs = document.querySelectorAll('.filter-input');
filterInputs.forEach(input => {
    input.addEventListener('keyup', applyWaterFilters);
});

if (waterTable) {
    initWaterTable();
}
</script>

<?php require_once 'tailwind-footer.php'; ?>

