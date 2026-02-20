<?php
$page_title = 'Treated Water Consumption';
require_once 'tailwind-header.php';

$message = '';
$error_msg = '';

$module = 'treated_water';
$can_input = AccessControl::canInput($user['office'], $module);
$filter = AccessControl::getGHGFilterClause($user['office'], $user['campus'], $module);

// Fetch all treated water records
$records = AccessControl::fetchRecords($db, 'tbltreatedwater', $filter, 'Month DESC', 100);

// Handle form submission
if (Helper::isPostRequest()) {
    if (!$can_input) {
        $error_msg = 'Your office is read-only for Treated Water. Contact Central to modify data.';
    }

    $year = Helper::getPost('year', '');
    $month = Helper::getPost('month', '');
    $quarter = Helper::getPost('quarter', '');
    $treated_water_volume = Helper::getPost('treated_water_volume', 0);
    $reused_treated_water_volume = Helper::getPost('reused_treated_water_volume', 0);
    $record_id = Helper::getPost('record_id', '');

    // Auto-calculations
    $factor_kg_co2e = $treated_water_volume * 1.062; // Treated water emission factor
    $factor_t_co2e = $factor_kg_co2e / 1000;
    $effluent_volume = 0; // Default value
    $price_per_liter = 0; // Default value

    // Validation
    $validator = new Validator();
    $validator->validate('year', $year, 'required|numeric');
    $validator->validate('month', $month, 'required');
    $validator->validate('treated_water_volume', $treated_water_volume, 'required|numeric');
    
    if ($validator->fails()) {
        $error_msg = array_values($validator->errors())[0];
    } elseif ($can_input) {
        try {
            if ($record_id) {
                // Update
                $query = "UPDATE tbltreatedwater SET Year = ?, Month = ?, Quarter = ?, TreatedWaterVolume = ?, ReusedTreatedWaterVolume = ?, EffluentVolume = ?, PricePerLiter = ?, FactorKGCO2e = ?, FactorTCO2e = ? WHERE id = ? AND Campus = ?";
                $stmt = $db->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("sssddddddis", $year, $month, $quarter, $treated_water_volume, $reused_treated_water_volume, $effluent_volume, $price_per_liter, $factor_kg_co2e, $factor_t_co2e, $record_id, $user['campus']);
                    if ($stmt->execute()) {
                        $message = 'Record updated successfully';
                        Helper::logActivity($db, 'Updated Treated Water Record (ID: ' . $record_id . ')', 'Treated Water Report');
                    } else {
                        $error_msg = 'Failed to update record';
                    }
                    $stmt->close();
                }
            } else {
                // Insert
                $query = "INSERT INTO tbltreatedwater (Campus, Year, Month, Quarter, TreatedWaterVolume, ReusedTreatedWaterVolume, EffluentVolume, PricePerLiter, FactorKGCO2e, FactorTCO2e) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("ssssdddddd", $user['campus'], $year, $month, $quarter, $treated_water_volume, $reused_treated_water_volume, $effluent_volume, $price_per_liter, $factor_kg_co2e, $factor_t_co2e);
                    if ($stmt->execute()) {
                        $message = 'Record added successfully';
                        Helper::logActivity($db, 'Added Treated Water Record for ' . $user['campus'] . ' - ' . $month, 'Treated Water Report');
                    } else {
                        $error_msg = 'Failed to add record';
                    }
                    $stmt->close();
                }
            }
            
            // Refresh records
            $records = AccessControl::fetchRecords($db, 'tbltreatedwater', $filter, 'Month DESC', 100);
            
        } catch (Exception $e) {
            $error_msg = 'Error: ' . $e->getMessage();
        }
    }
}

// Handle delete
if (Helper::isGetRequest() && isset($_GET['delete_id'])) {
    if (!$can_input) {
        $error_msg = 'Your office is read-only for Treated Water. Contact Central to modify data.';
    } else {
        $delete_id = (int)$_GET['delete_id'];
        $query = "DELETE FROM tbltreatedwater WHERE id = ? AND Campus = ?";
        $stmt = $db->prepare($query);
        if ($stmt) {
            $stmt->bind_param("is", $delete_id, $user['campus']);
            if ($stmt->execute()) {
                $message = 'Record deleted successfully';
                Helper::logActivity($db, 'Deleted Treated Water Record (ID: ' . $delete_id . ')', 'Treated Water Report');
            }
            $stmt->close();
            
            // Refresh records
            $records = AccessControl::fetchRecords($db, 'tbltreatedwater', $filter, 'Month DESC', 100);
        }
    }
}
?>

                    <div class="max-w-6xl">
                        <!-- Page Header -->
                        <div class="mb-6">
                            <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                                <i class="fas fa-water text-blue-500 mr-3"></i>
                                Treated Water Consumption Records
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
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
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
                                        <label for="treated_water_volume" class="block text-sm font-medium text-gray-700 mb-2">
                                            Treated Water Volume (m³) <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="number" 
                                            id="treated_water_volume" 
                                            name="treated_water_volume" 
                                            step="0.01"
                                            required
                                            data-rules="required|numeric"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="0.00"
                                        >
                                    </div>

                                    <div>
                                        <label for="reused_treated_water_volume" class="block text-sm font-medium text-gray-700 mb-2">
                                            Reused Treated Water Volume (m³)
                                        </label>
                                        <input 
                                            type="number" 
                                            id="reused_treated_water_volume" 
                                            name="reused_treated_water_volume" 
                                            step="0.01"
                                            value="0"
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
                                    <select id="treated-water-per-page" class="px-2 py-1 border border-gray-300 rounded text-sm">
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
                                    <table class="w-full" id="treated-water-table">
                                        <thead class="bg-gray-100 border-b border-gray-200">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Campus</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Year</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Month</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Quarter</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Treated Water Vol</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Reused Vol</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Effluent Vol</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Price/Liter</th>
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
                                                <th class="px-4 py-2"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" placeholder="Filter..." data-column="3"></th>
                                                <th class="px-4 py-2"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" placeholder="Filter..." data-column="4"></th>
                                                <th class="px-4 py-2"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" placeholder="Filter..." data-column="5"></th>
                                                <th class="px-4 py-2"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" placeholder="Filter..." data-column="6"></th>
                                                <th class="px-4 py-2"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" placeholder="Filter..." data-column="7"></th>
                                                <th class="px-4 py-2"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <?php foreach ($records as $record): ?>
                                                <tr class="hover:bg-gray-50 transition-colors">
                                                    <td class="px-4 py-3 text-xs text-gray-800"><?php echo htmlspecialchars($record['Campus'] ?? ''); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800"><?php echo htmlspecialchars($record['Year'] ?? '-'); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800"><?php echo htmlspecialchars($record['Month'] ?? 'N/A'); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800"><?php echo htmlspecialchars($record['Quarter'] ?? '-'); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800 text-right font-semibold"><?php echo Helper::formatNumber($record['TreatedWaterVolume'] ?? 0); ?> m³</td>
                                                    <td class="px-4 py-3 text-xs text-gray-800 text-right"><?php echo Helper::formatNumber($record['ReusedTreatedWaterVolume'] ?? 0); ?> m³</td>
                                                    <td class="px-4 py-3 text-xs text-gray-800 text-right"><?php echo Helper::formatNumber($record['EffluentVolume'] ?? 0); ?> m³</td>
                                                    <td class="px-4 py-3 text-xs text-gray-800 text-right">₱<?php echo Helper::formatNumber($record['PricePerLiter'] ?? 0); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800 text-right font-semibold text-green-700"><?php echo Helper::formatNumber($record['FactorKGCO2e'] ?? 0, 5); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800 text-right font-semibold text-blue-700"><?php echo Helper::formatNumber($record['FactorTCO2e'] ?? 0, 5); ?></td>
                                                    <td class="px-4 py-3 text-center">
                                                        <button 
                                                           onclick="editTreatedWaterRecord(<?php echo htmlspecialchars(json_encode($record)); ?>)"
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
                                        Showing <span id="treated-water-start">1</span> to <span id="treated-water-end">25</span> of <span id="treated-water-total"><?php echo count($records); ?></span> entries
                                    </div>
                                    <div class="flex gap-1" id="treated-water-pagination">
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
                                    <div class="text-yellow-600 text-sm font-semibold uppercase mb-2">Total Volume</div>
                                    <div class="text-3xl font-bold text-yellow-900">
                                        <?php echo number_format(array_sum(array_column($records, 'TreatedWaterVolume')), 2); ?> m³
                                    </div>
                                </div>
                                
                                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                                    <div class="text-green-600 text-sm font-semibold uppercase mb-2">Total Emissions</div>
                                    <div class="text-3xl font-bold text-green-900">
                                        <?php echo number_format(array_sum(array_column($records, 'FactorKGCO2e')), 2); ?> kg CO₂e
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

<script>
// Edit Treated Water Record Function
function editTreatedWaterRecord(record) {
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    document.querySelector('input[name="record_id"]').value = record.id;
    document.querySelector('input[name="year"]').value = record.Year || '';
    document.querySelector('select[name="month"]').value = record.Month || '';
    document.querySelector('select[name="quarter"]').value = record.Quarter || '';
    document.querySelector('input[name="treated_water_volume"]').value = record.TreatedWaterVolume || 0;
    document.querySelector('input[name="reused_treated_water_volume"]').value = record.ReusedTreatedWaterVolume || 0;
    
    document.querySelector('.bg-white.rounded-lg.shadow-md.p-6.mb-8 h2').innerHTML = '<i class="fas fa-edit text-blue-600 mr-2"></i>Edit Record';
    document.querySelector('button[type="submit"]').innerHTML = '<i class="fas fa-save mr-2"></i>Update Record';
}

// Treated Water Table Pagination and Filtering
const treatedWaterTable = document.getElementById('treated-water-table');
const treatedWaterPerPage = document.getElementById('treated-water-per-page');
const treatedWaterPagination = document.getElementById('treated-water-pagination');
const treatedWaterStart = document.getElementById('treated-water-start');
const treatedWaterEnd = document.getElementById('treated-water-end');
const treatedWaterTotal = document.getElementById('treated-water-total');

let treatedWaterCurrentPage = 1;
let treatedWaterRowsPerPage = 25;
let treatedWaterFilteredRows = [];

function initTreatedWaterTable() {
    const tbody = treatedWaterTable.querySelector('tbody');
    treatedWaterFilteredRows = Array.from(tbody.querySelectorAll('tr'));
    treatedWaterTotal.textContent = treatedWaterFilteredRows.length;
    displayTreatedWaterPage(1);
}

function displayTreatedWaterPage(page) {
    treatedWaterCurrentPage = page;
    const tbody = treatedWaterTable.querySelector('tbody');
    const allRows = tbody.querySelectorAll('tr');
    
    allRows.forEach(row => row.style.display = 'none');
    
    const start = (page - 1) * treatedWaterRowsPerPage;
    const end = start + treatedWaterRowsPerPage;
    const visibleRows = treatedWaterFilteredRows.slice(start, end);
    
    visibleRows.forEach(row => row.style.display = '');
    
    treatedWaterStart.textContent = treatedWaterFilteredRows.length > 0 ? start + 1 : 0;
    treatedWaterEnd.textContent = Math.min(end, treatedWaterFilteredRows.length);
    treatedWaterTotal.textContent = treatedWaterFilteredRows.length;
    
    renderTreatedWaterPagination();
}

function renderTreatedWaterPagination() {
    const totalPages = Math.ceil(treatedWaterFilteredRows.length / treatedWaterRowsPerPage);
    treatedWaterPagination.innerHTML = '';
    
    if (totalPages <= 1) return;
    
    const prevBtn = document.createElement('button');
    prevBtn.innerHTML = '&laquo;';
    prevBtn.className = 'px-3 py-1 border rounded ' + (treatedWaterCurrentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-100');
    prevBtn.disabled = treatedWaterCurrentPage === 1;
    prevBtn.onclick = () => changeTreatedWaterPage(treatedWaterCurrentPage - 1);
    treatedWaterPagination.appendChild(prevBtn);
    
    for (let i = 1; i <= Math.min(totalPages, 5); i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        btn.className = 'px-3 py-1 border rounded ' + (i === treatedWaterCurrentPage ? 'bg-blue-600 text-white' : 'bg-white hover:bg-gray-100');
        btn.onclick = () => changeTreatedWaterPage(i);
        treatedWaterPagination.appendChild(btn);
    }
    
    const nextBtn = document.createElement('button');
    nextBtn.innerHTML = '&raquo;';
    nextBtn.className = 'px-3 py-1 border rounded ' + (treatedWaterCurrentPage === totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-100');
    nextBtn.disabled = treatedWaterCurrentPage === totalPages;
    nextBtn.onclick = () => changeTreatedWaterPage(treatedWaterCurrentPage + 1);
    treatedWaterPagination.appendChild(nextBtn);
}

function changeTreatedWaterPage(page) {
    displayTreatedWaterPage(page);
}

function applyTreatedWaterFilters() {
    const tbody = treatedWaterTable.querySelector('tbody');
    const allRows = Array.from(tbody.querySelectorAll('tr'));
    const filterInputs = document.querySelectorAll('.filter-input');
    
    treatedWaterFilteredRows = allRows.filter(row => {
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
    
    displayTreatedWaterPage(1);
}

if (treatedWaterPerPage) {
    treatedWaterPerPage.addEventListener('change', function() {
        treatedWaterRowsPerPage = parseInt(this.value);
        displayTreatedWaterPage(1);
    });
}

const filterInputs = document.querySelectorAll('.filter-input');
filterInputs.forEach(input => {
    input.addEventListener('keyup', applyTreatedWaterFilters);
});

if (treatedWaterTable) {
    initTreatedWaterTable();
}
</script>

<?php require_once 'tailwind-footer.php'; ?>

