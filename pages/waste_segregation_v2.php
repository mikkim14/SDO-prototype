<?php
$page_title = 'Waste Segregation';
require_once 'tailwind-header.php';

$message = '';
$error_msg = '';

$module = 'waste_segregated';
$can_input = AccessControl::canInput($user['office'], $module);
$filter = AccessControl::getGHGFilterClause($user['office'], $user['campus'], $module);

// Fetch all waste segregation records
$records = AccessControl::fetchRecords($db, 'tblsolidwastesegregated', $filter, 'Year DESC, Month DESC', 100);

// Handle form submission
if (Helper::isPostRequest()) {
    if (!$can_input) {
        $error_msg = 'Your office is read-only for Waste Segregation. Contact Central to modify data.';
    }

    $year = Helper::getPost('year', date('Y'));
    $quarter = Helper::getPost('quarter', '');
    $month = Helper::getPost('month', '');
    $main_category = Helper::getPost('main_category', '');
    $sub_category = Helper::getPost('sub_category', '');
    $quantity_in_kg = Helper::getPost('quantity_in_kg', 0);
    $record_id = Helper::getPost('record_id', '');

    // Auto-calculations
    $ghg_emission_kg_co2e = $quantity_in_kg * 0.5; // Waste emission factor
    $ghg_emission_t_co2e = $ghg_emission_kg_co2e / 1000;

    // Validation
    $validator = new Validator();
    $validator->validate('year', $year, 'required|numeric');
    $validator->validate('month', $month, 'required');
    $validator->validate('main_category', $main_category, 'required');
    $validator->validate('quantity_in_kg', $quantity_in_kg, 'required|numeric');
    
    if ($validator->fails()) {
        $error_msg = array_values($validator->errors())[0];
    } elseif ($can_input) {
        try {
            if ($record_id) {
                // Update
                $query = "UPDATE tblsolidwastesegregated SET Year = ?, Quarter = ?, Month = ?, MainCategory = ?, SubCategory = ?, QuantityInKG = ?, GHGEmissionKGCO2e = ?, GHGEmissionTCO2e = ? WHERE id = ? AND Campus = ?";
                $stmt = $db->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("issssdddis", $year, $quarter, $month, $main_category, $sub_category, $quantity_in_kg, $ghg_emission_kg_co2e, $ghg_emission_t_co2e, $record_id, $user['campus']);
                    if ($stmt->execute()) {
                        $message = 'Record updated successfully';
                        Helper::logActivity($db, 'Updated Waste Segregation Record (ID: ' . $record_id . ')', 'Waste Segregation Report');
                    } else {
                        $error_msg = 'Failed to update record';
                    }
                    $stmt->close();
                }
            } else {
                // Insert
                $query = "INSERT INTO tblsolidwastesegregated (Campus, Year, Quarter, Month, MainCategory, SubCategory, QuantityInKG, GHGEmissionKGCO2e, GHGEmissionTCO2e) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("sissssddd", $user['campus'], $year, $quarter, $month, $main_category, $sub_category, $quantity_in_kg, $ghg_emission_kg_co2e, $ghg_emission_t_co2e);
                    if ($stmt->execute()) {
                        $message = 'Record added successfully';
                        Helper::logActivity($db, 'Added Waste Segregation Record for ' . $user['campus'] . ' - ' . $month . ' ' . $year, 'Waste Segregation Report');
                    } else {
                        $error_msg = 'Failed to add record';
                    }
                    $stmt->close();
                }
            }
            
            // Refresh records
            $records = AccessControl::fetchRecords($db, 'tblsolidwastesegregated', $filter, 'Year DESC, Month DESC', 100);
            
        } catch (Exception $e) {
            $error_msg = 'Error: ' . $e->getMessage();
        }
    }
}

// Handle delete
if (Helper::isGetRequest() && isset($_GET['delete_id'])) {
    if (!$can_input) {
        $error_msg = 'Your office is read-only for Waste Segregation. Contact Central to modify data.';
    } else {
        $delete_id = (int)$_GET['delete_id'];
        $query = "DELETE FROM tblsolidwastesegregated WHERE id = ? AND Campus = ?";
        $stmt = $db->prepare($query);
        if ($stmt) {
            $stmt->bind_param("is", $delete_id, $user['campus']);
            if ($stmt->execute()) {
                $message = 'Record deleted successfully';
                Helper::logActivity($db, 'Deleted Waste Segregation Record (ID: ' . $delete_id . ')', 'Waste Segregation Report');
            }
            $stmt->close();
            
            // Refresh records
            $records = AccessControl::fetchRecords($db, 'tblsolidwastesegregated', $filter, 'Year DESC, Month DESC', 100);
        }
    }
}
?>

                    <div class="max-w-6xl">
                        <!-- Page Header -->
                        <div class="mb-6">
                            <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                                <i class="fas fa-trash-alt text-green-600 mr-3"></i>
                                Waste Segregation Records
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
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                                            value="<?php echo date('Y'); ?>"
                                            min="2020"
                                            max="2100"
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        >
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
                                            <option value="Q1">Q1</option>
                                            <option value="Q2">Q2</option>
                                            <option value="Q3">Q3</option>
                                            <option value="Q4">Q4</option>
                                        </select>
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
                                        <label for="main_category" class="block text-sm font-medium text-gray-700 mb-2">
                                            Main Category <span class="text-red-500">*</span>
                                        </label>
                                        <select 
                                            id="main_category" 
                                            name="main_category" 
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        >
                                            <option value="">Select Category</option>
                                            <option value="biodegradable">Biodegradable</option>
                                            <option value="residual">Residual</option>
                                            <option value="recyclable">Recyclable</option>
                                            <option value="special">Special</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="sub_category" class="block text-sm font-medium text-gray-700 mb-2">
                                            Sub Category
                                        </label>
                                        <select 
                                            id="sub_category" 
                                            name="sub_category" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        >
                                            <option value="">Select Sub Category</option>
                                            <option value="Garden Waste">Garden Waste</option>
                                            <option value="Food Waste">Food Waste</option>
                                            <option value="Mixed Food and Garden Waste">Mixed Food and Garden Waste</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="quantity_in_kg" class="block text-sm font-medium text-gray-700 mb-2">
                                            Quantity (kg) <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="number" 
                                            id="quantity_in_kg" 
                                            name="quantity_in_kg" 
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
                                    <select id="waste-seg-per-page" class="px-2 py-1 border border-gray-300 rounded text-sm">
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
                                    <table class="w-full" id="waste-seg-table">
                                        <thead class="bg-gray-100 border-b border-gray-200">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">ID</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Campus</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Year</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Quarter</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Month</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Main Category</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Sub Category</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Quantity (kg)</th>
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
                                                <th class="px-4 py-2"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <?php foreach ($records as $record): ?>
                                                <tr class="hover:bg-gray-50 transition-colors">
                                                    <td class="px-4 py-3 text-xs text-gray-800"><?php echo htmlspecialchars($record['id'] ?? ''); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800"><?php echo htmlspecialchars($record['Campus'] ?? ''); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800"><?php echo htmlspecialchars($record['Year'] ?? '-'); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800"><?php echo htmlspecialchars($record['Quarter'] ?? '-'); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800"><?php echo htmlspecialchars($record['Month'] ?? '-'); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800"><?php echo htmlspecialchars($record['MainCategory'] ?? '-'); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800"><?php echo htmlspecialchars($record['SubCategory'] ?? '-'); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800 text-right"><?php echo Helper::formatNumber($record['QuantityInKG'] ?? 0); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800 text-right font-semibold text-green-700"><?php echo Helper::formatNumber($record['GHGEmissionKGCO2e'] ?? 0); ?></td>
                                                    <td class="px-4 py-3 text-xs text-gray-800 text-right font-semibold text-blue-700"><?php echo Helper::formatNumber($record['GHGEmissionTCO2e'] ?? 0); ?></td>
                                                    <td class="px-4 py-3 text-center">
                                                        <button 
                                                           onclick="editWasteSegRecord(<?php echo htmlspecialchars(json_encode($record)); ?>)"
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
                                        Showing <span id="waste-seg-start">1</span> to <span id="waste-seg-end">25</span> of <span id="waste-seg-total"><?php echo count($records); ?></span> entries
                                    </div>
                                    <div class="flex gap-1" id="waste-seg-pagination">
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
                                    <div class="text-yellow-600 text-sm font-semibold uppercase mb-2">Total Quantity</div>
                                    <div class="text-3xl font-bold text-yellow-900">
                                        <?php echo number_format(array_sum(array_column($records, 'QuantityInKG')), 2); ?> kg
                                    </div>
                                </div>
                                
                                <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                                    <div class="text-red-600 text-sm font-semibold uppercase mb-2">Total Emissions</div>
                                    <div class="text-3xl font-bold text-red-900">
                                        <?php echo number_format(array_sum(array_column($records, 'GHGEmissionKGCO2e')), 2); ?> kg CO₂e
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

<script>
// Edit Waste Segregation Record Function
function editWasteSegRecord(record) {
    // Scroll to form
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    // Populate form fields
    document.querySelector('input[name="record_id"]').value = record.id;
    document.querySelector('input[name="year"]').value = record.Year || '';
    document.querySelector('select[name="quarter"]').value = record.Quarter || '';
    document.querySelector('select[name="month"]').value = record.Month || '';
    document.querySelector('select[name="main_category"]').value = record.MainCategory || '';
    document.querySelector('select[name="sub_category"]').value = record.SubCategory || '';
    document.querySelector('input[name="quantity_in_kg"]').value = record.QuantityInKG || 0;
    
    // Update form title
    document.querySelector('.bg-white.rounded-lg.shadow-md.p-6.mb-8 h2').innerHTML = '<i class="fas fa-edit text-blue-600 mr-2"></i>Edit Record';
    
    // Change button text
    document.querySelector('button[type="submit"]').innerHTML = '<i class="fas fa-save mr-2"></i>Update Record';
}

// Waste Segregation Table Pagination and Filtering
const wasteSegTable = document.getElementById('waste-seg-table');
const wasteSegPerPage = document.getElementById('waste-seg-per-page');
const wasteSegPagination = document.getElementById('waste-seg-pagination');
const wasteSegStart = document.getElementById('waste-seg-start');
const wasteSegEnd = document.getElementById('waste-seg-end');
const wasteSegTotal = document.getElementById('waste-seg-total');

let wasteSegCurrentPage = 1;
let wasteSegRowsPerPage = 25;
let wasteSegFilteredRows = [];

function initWasteSegTable() {
    const tbody = wasteSegTable.querySelector('tbody');
    wasteSegFilteredRows = Array.from(tbody.querySelectorAll('tr'));
    wasteSegTotal.textContent = wasteSegFilteredRows.length;
    displayWasteSegPage(1);
}

function displayWasteSegPage(page) {
    wasteSegCurrentPage = page;
    const tbody = wasteSegTable.querySelector('tbody');
    const allRows = tbody.querySelectorAll('tr');
    
    allRows.forEach(row => row.style.display = 'none');
    
    const start = (page - 1) * wasteSegRowsPerPage;
    const end = start + wasteSegRowsPerPage;
    const visibleRows = wasteSegFilteredRows.slice(start, end);
    
    visibleRows.forEach(row => row.style.display = '');
    
    wasteSegStart.textContent = wasteSegFilteredRows.length > 0 ? start + 1 : 0;
    wasteSegEnd.textContent = Math.min(end, wasteSegFilteredRows.length);
    wasteSegTotal.textContent = wasteSegFilteredRows.length;
    
    renderWasteSegPagination();
}

function renderWasteSegPagination() {
    const totalPages = Math.ceil(wasteSegFilteredRows.length / wasteSegRowsPerPage);
    wasteSegPagination.innerHTML = '';
    
    if (totalPages <= 1) return;
    
    // Previous button
    const prevBtn = document.createElement('button');
    prevBtn.innerHTML = '&laquo;';
    prevBtn.className = 'px-3 py-1 border rounded ' + (wasteSegCurrentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-100');
    prevBtn.disabled = wasteSegCurrentPage === 1;
    prevBtn.onclick = () => changeWasteSegPage(wasteSegCurrentPage - 1);
    wasteSegPagination.appendChild(prevBtn);
    
    // Page numbers
    for (let i = 1; i <= Math.min(totalPages, 5); i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        btn.className = 'px-3 py-1 border rounded ' + (i === wasteSegCurrentPage ? 'bg-blue-600 text-white' : 'bg-white hover:bg-gray-100');
        btn.onclick = () => changeWasteSegPage(i);
        wasteSegPagination.appendChild(btn);
    }
    
    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.innerHTML = '&raquo;';
    nextBtn.className = 'px-3 py-1 border rounded ' + (wasteSegCurrentPage === totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-100');
    nextBtn.disabled = wasteSegCurrentPage === totalPages;
    nextBtn.onclick = () => changeWasteSegPage(wasteSegCurrentPage + 1);
    wasteSegPagination.appendChild(nextBtn);
}

function changeWasteSegPage(page) {
    displayWasteSegPage(page);
}

function applyWasteSegFilters() {
    const tbody = wasteSegTable.querySelector('tbody');
    const allRows = Array.from(tbody.querySelectorAll('tr'));
    const filterInputs = document.querySelectorAll('.filter-input');
    
    wasteSegFilteredRows = allRows.filter(row => {
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
    
    displayWasteSegPage(1);
}

// Event listeners
if (wasteSegPerPage) {
    wasteSegPerPage.addEventListener('change', function() {
        wasteSegRowsPerPage = parseInt(this.value);
        displayWasteSegPage(1);
    });
}

const filterInputs = document.querySelectorAll('.filter-input');
filterInputs.forEach(input => {
    input.addEventListener('keyup', applyWasteSegFilters);
});

// Initialize on page load
if (wasteSegTable) {
    initWasteSegTable();
}
</script>

<?php require_once 'tailwind-footer.php'; ?>

