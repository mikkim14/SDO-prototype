<?php
$page_title = 'Food Consumption';
require_once 'tailwind-header.php';

$message = '';
$error_msg = '';

$module = 'food';
$can_input = AccessControl::canInput($user['office'], $module);

// Fetch all food consumption records
$query = "SELECT * FROM tblfoodwaste WHERE Campus = ? ORDER BY YearTransaction DESC, Month DESC, id DESC";
$stmt = $db->prepare($query);
if ($stmt) {
    $stmt->bind_param("s", $user['campus']);
    $stmt->execute();
    $result = $stmt->get_result();
    $records = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $records = [];
}

// Load record for editing
$edit_record = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $query = "SELECT * FROM tblfoodwaste WHERE id = ? AND Campus = ?";
    $stmt = $db->prepare($query);
    if ($stmt) {
        $stmt->bind_param("is", $edit_id, $user['campus']);
        $stmt->execute();
        $result = $stmt->get_result();
        $edit_record = $result->fetch_assoc();
        $stmt->close();
    }
}

// Handle form submission
if (Helper::isPostRequest()) {
    if (!$can_input) {
        $error_msg = 'Your office is read-only for Food. Contact Central to modify data.';
    }

    $year = Helper::getPost('year', '');
    $month = Helper::getPost('month', '');
    $quarter = Helper::getPost('quarter', '');
    $office = Helper::getPost('office', '');
    $food_type = Helper::getPost('food_type', '');
    $servings = Helper::getPost('servings', 0);
    $record_id = Helper::getPost('record_id', '');

    // Calculate GHG Emissions based on food type
    $emission_factors = [
        'Vegetarian' => 1.5,
        'Non-Vegetarian' => 3.0,
        'Vegan' => 1.0,
        'Other' => 2.0
    ];
    $factor = $emission_factors[$food_type] ?? 2.0;
    $ghg_kg = $servings * $factor;
    $ghg_t = $ghg_kg / 1000;

    // Validation
    $validator = new Validator();
    $validator->validate('year', $year, 'required');
    $validator->validate('month', $month, 'required');
    $validator->validate('quarter', $quarter, 'required');
    $validator->validate('office', $office, 'required');
    $validator->validate('food_type', $food_type, 'required');
    $validator->validate('servings', $servings, 'required|numeric');
    
    if ($validator->fails()) {
        $error_msg = array_values($validator->errors())[0];
    } elseif ($can_input) {
        try {
            if ($record_id) {
                // Update
                $query = "UPDATE tblfoodwaste SET Office = ?, YearTransaction = ?, Month = ?, Quarter = ?, TypeOfFoodServed = ?, QuantityOfServing = ?, GHGEmissionKGCO2e = ?, GHGEmissionTCO2e = ? WHERE id = ? AND Campus = ?";
                $stmt = $db->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("sssssddds", $office, $year, $month, $quarter, $food_type, $servings, $ghg_kg, $ghg_t, $record_id, $user['campus']);
                    if ($stmt->execute()) {
                        $message = 'Record updated successfully';
                        Helper::logActivity($db, 'Updated Food Consumption Record (ID: ' . $record_id . ')', 'Food Report');
                        header('Location: food_consumption_v2.php');
                        exit;
                    } else {
                        $error_msg = 'Failed to update record: ' . $stmt->error;
                    }
                    $stmt->close();
                }
            } else {
                // Insert
                $query = "INSERT INTO tblfoodwaste (Campus, Office, YearTransaction, Month, Quarter, TypeOfFoodServed, QuantityOfServing, GHGEmissionKGCO2e, GHGEmissionTCO2e) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("ssssssddd", $user['campus'], $office, $year, $month, $quarter, $food_type, $servings, $ghg_kg, $ghg_t);
                    if ($stmt->execute()) {
                        $message = 'Record added successfully';
                        Helper::logActivity($db, 'Added Food Consumption Record for ' . $user['campus'] . ' - ' . $year . '/' . $month, 'Food Report');
                    } else {
                        $error_msg = 'Failed to add record: ' . $stmt->error;
                    }
                    $stmt->close();
                }
            }
            
            // Refresh records
            $query = "SELECT * FROM tblfoodwaste WHERE Campus = ? ORDER BY YearTransaction DESC, Month DESC, id DESC LIMIT 100";
            $stmt = $db->prepare($query);
            if ($stmt) {
                $stmt->bind_param("s", $user['campus']);
                $stmt->execute();
                $result = $stmt->get_result();
                $records = $result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            }
            
        } catch (Exception $e) {
            $error_msg = 'Error: ' . $e->getMessage();
        }
    }
}

// Handle delete
if (Helper::isGetRequest() && isset($_GET['delete_id'])) {
    if (!$can_input) {
        $error_msg = 'Your office is read-only for Food. Contact Central to modify data.';
    } else {
        $delete_id = (int)$_GET['delete_id'];
        $query = "DELETE FROM tblfoodwaste WHERE id = ? AND campus = ?";
        $stmt = $db->prepare($query);
        if ($stmt) {
            $stmt->bind_param("is", $delete_id, $user['campus']);
            if ($stmt->execute()) {
                $message = 'Record deleted successfully';
                Helper::logActivity($db, 'Deleted Food Consumption Record (ID: ' . $delete_id . ')', 'Food Report');
            }
            $stmt->close();
            
            // Refresh records
            $records = AccessControl::fetchRecords($db, 'tblfoodwaste', $filter, 'date DESC', 100);
        }
    }
}
?>

                    <div class="max-w-6xl">
                        <!-- Page Header -->
                        <div class="mb-6">
                            <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                                <i class="fas fa-utensils text-green-500 mr-3"></i>
                                Food Consumption Records
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
                                <i class="fas fa-<?php echo $edit_record ? 'edit' : 'plus-circle'; ?> text-blue-600 mr-2"></i>
                                <?php echo $edit_record ? 'Edit Record' : 'Add New Record'; ?>
                            </h2>

                            <form method="POST" action="" class="auto-validate">
                                <input type="hidden" name="record_id" value="<?php echo $edit_record['id'] ?? ''; ?>">
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <label for="campus" class="block text-sm font-medium text-gray-700 mb-2">
                                            Campus <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            id="campus" 
                                            name="campus" 
                                            disabled
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 cursor-not-allowed"
                                            value="<?php echo htmlspecialchars($user['campus']); ?>"
                                        >
                                    </div>

                                    <div>
                                        <label for="office" class="block text-sm font-medium text-gray-700 mb-2">
                                            Office <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            id="office" 
                                            name="office" 
                                            required
                                            data-rules="required"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="Office Name"
                                            value="<?php echo htmlspecialchars($edit_record['Office'] ?? $user['office']); ?>"
                                        >
                                    </div>

                                    <div>
                                        <label for="quarter" class="block text-sm font-medium text-gray-700 mb-2">
                                            Quarter <span class="text-red-500">*</span>
                                        </label>
                                        <select 
                                            id="quarter" 
                                            name="quarter" 
                                            required
                                            data-rules="required"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        >
                                            <option value="">-- Select Quarter --</option>
                                            <option value="Q1" <?php echo ($edit_record['Quarter'] ?? '') == 'Q1' ? 'selected' : ''; ?>>Q1 (Jan-Mar)</option>
                                            <option value="Q2" <?php echo ($edit_record['Quarter'] ?? '') == 'Q2' ? 'selected' : ''; ?>>Q2 (Apr-Jun)</option>
                                            <option value="Q3" <?php echo ($edit_record['Quarter'] ?? '') == 'Q3' ? 'selected' : ''; ?>>Q3 (Jul-Sep)</option>
                                            <option value="Q4" <?php echo ($edit_record['Quarter'] ?? '') == 'Q4' ? 'selected' : ''; ?>>Q4 (Oct-Dec)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="year" class="block text-sm font-medium text-gray-700 mb-2">
                                            Year <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            id="year" 
                                            name="year" 
                                            required
                                            data-rules="required"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="2024"
                                            value="<?php echo htmlspecialchars($edit_record['YearTransaction'] ?? date('Y')); ?>"
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
                                            <option value="">-- Select Month --</option>
                                            <?php 
                                            $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                                            foreach ($months as $m): 
                                                $selected = ($edit_record['Month'] ?? '') == $m ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo $m; ?>" <?php echo $selected; ?>><?php echo $m; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="food_type" class="block text-sm font-medium text-gray-700 mb-2">
                                            Type of Food Served <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            id="food_type" 
                                            name="food_type" 
                                            required
                                            data-rules="required"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="e.g., Vegetarian, Non-Vegetarian, Vegan"
                                            value="<?php echo htmlspecialchars($edit_record['TypeOfFoodServed'] ?? ''); ?>"
                                        >
                                    </div>

                                    <div>
                                        <label for="servings" class="block text-sm font-medium text-gray-700 mb-2">
                                            Quantity of Serving <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="number" 
                                            id="servings" 
                                            name="servings" 
                                            min="1"
                                            step="1"
                                            required
                                            data-rules="required|numeric"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="0"
                                            value="<?php echo htmlspecialchars($edit_record['QuantityOfServing'] ?? ''); ?>"
                                        >
                                        <p class="text-xs text-gray-500 mt-1">GHG emissions will be calculated automatically</p>
                                    </div>
                                </div>

                                <div class="mt-4 flex gap-3">
                                    <button 
                                        type="submit"
                                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors flex items-center"
                                    >
                                        <i class="fas fa-save mr-2"></i><?php echo $edit_record ? 'Update Record' : 'Save Record'; ?>
                                    </button>
                                    <?php if ($edit_record): ?>
                                        <a href="food_consumption_v2.php" class="px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition-colors flex items-center">
                                            <i class="fas fa-times mr-2"></i>Cancel
                                        </a>
                                    <?php else: ?>
                                        <button 
                                            type="reset"
                                            class="px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition-colors flex items-center"
                                        >
                                            <i class="fas fa-redo mr-2"></i>Reset
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>

                        <!-- Records Table -->
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                                <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                                    <i class="fas fa-list mr-2"></i>
                                    Existing Records (<span id="record-count"><?php echo count($records); ?></span>)
                                </h2>
                                <div class="flex items-center gap-2">
                                    <label class="text-sm text-gray-600">Show</label>
                                    <select id="food-per-page" class="px-2 py-1 border border-gray-300 rounded text-sm">
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
                                    <table class="w-full" id="food-table">
                                        <thead class="bg-gray-100 border-b-2 border-gray-200">
                                            <tr>
                                                <?php foreach (array_keys($records[0]) as $idx => $column): ?>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase whitespace-nowrap"><?php echo htmlspecialchars($column); ?></th>
                                                <?php endforeach; ?>
                                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase whitespace-nowrap">Actions</th>
                                            </tr>
                                            <tr>
                                                <?php foreach (array_keys($records[0]) as $idx => $column): ?>
                                                    <th class="px-4 py-2">
                                                        <input type="text" placeholder="Filter..." data-table="food-table" data-col="<?php echo $idx; ?>" class="filter-input w-full px-2 py-1 text-xs border border-gray-300 rounded">
                                                    </th>
                                                <?php endforeach; ?>
                                                <th class="px-4 py-2"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <?php foreach ($records as $record): ?>
                                                <tr class="hover:bg-gray-50 transition-colors">
                                                    <?php foreach ($record as $value): ?>
                                                        <td class="px-4 py-3 text-sm text-gray-800 whitespace-nowrap"><?php echo htmlspecialchars($value ?? '-'); ?></td>
                                                    <?php endforeach; ?>
                                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                                        <a href="?edit_id=<?php echo $record['id']; ?>" 
                                                           class="inline-block px-2 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-xs transition-colors mr-1">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </a>
                                                        <a href="?delete_id=<?php echo $record['id']; ?>" 
                                                           class="inline-block px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded text-xs transition-colors"
                                                           onclick="return confirm('Are you sure you want to delete this record?');">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="px-6 py-4 border-t border-gray-200 text-sm text-gray-600">
                                    Showing <span id="food-start">0</span> to <span id="food-end">0</span> of <span id="food-total">0</span> entries
                                </div>
                            <?php else: ?>
                                <div class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-3"></i>
                                    <p class="text-lg">No records found</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Statistics Card 
                        <?php if (count($records) > 0): ?>
                            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                                    <div class="text-blue-600 text-sm font-semibold uppercase mb-2">Total Records</div>
                                    <div class="text-3xl font-bold text-blue-900"><?php echo count($records); ?></div>
                                </div>
                                
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                                    <div class="text-yellow-600 text-sm font-semibold uppercase mb-2">Total Servings</div>
                                    <div class="text-3xl font-bold text-yellow-900">
                                        <?php echo array_sum(array_column($records, 'QuantityOfServing')); ?>
                                    </div>
                                </div>
                                
                                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                                    <div class="text-green-600 text-sm font-semibold uppercase mb-2">Total Emissions</div>
                                    <div class="text-3xl font-bold text-green-900">
                                        <?php echo Helper::formatNumber(array_sum(array_column($records, 'GHGEmissionKGCO2e'))); ?> kg CO₂e
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?> -->
                    </div>

<script>
// Food pagination and filtering
let foodCurrentPage = 1;
let foodPerPage = 25;
let foodFilteredRows = [];

function applyFilters() {
    const table = document.getElementById('food-table');
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const allRows = Array.from(tbody.querySelectorAll('tr'));
    const filterInputs = table.querySelectorAll('.filter-input');
    
    foodFilteredRows = allRows.filter(row => {
        let showRow = true;
        
        filterInputs.forEach(input => {
            const colIndex = parseInt(input.dataset.col);
            const filterValue = input.value.toLowerCase().trim();
            
            if (filterValue) {
                const cell = row.cells[colIndex];
                const cellText = cell ? cell.textContent.toLowerCase() : '';
                if (!cellText.includes(filterValue)) {
                    showRow = false;
                }
            }
        });
        
        return showRow;
    });
    
    foodCurrentPage = 1;
    displayPage();
}

function displayPage() {
    const table = document.getElementById('food-table');
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const allRows = Array.from(tbody.querySelectorAll('tr'));
    const rowsToShow = foodFilteredRows.length > 0 ? foodFilteredRows : allRows;
    
    // Hide all rows
    allRows.forEach(row => row.style.display = 'none');
    
    // Calculate pagination
    const start = (foodCurrentPage - 1) * foodPerPage;
    const end = start + foodPerPage;
    
    // Show current page rows
    rowsToShow.slice(start, end).forEach(row => row.style.display = '');
    
    // Update pagination info
    document.getElementById('food-start').textContent = rowsToShow.length > 0 ? start + 1 : 0;
    document.getElementById('food-end').textContent = Math.min(end, rowsToShow.length);
    document.getElementById('food-total').textContent = rowsToShow.length;
    document.getElementById('record-count').textContent = rowsToShow.length;
}

function changePageFood(newPage) {
    foodCurrentPage = newPage;
    displayPage();
}

document.addEventListener('DOMContentLoaded', function() {
    const perPageSelect = document.getElementById('food-per-page');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            foodPerPage = parseInt(this.value);
            foodCurrentPage = 1;
            displayPage();
        });
    }
    
    const filterInputs = document.querySelectorAll('.filter-input');
    filterInputs.forEach(input => {
        input.addEventListener('keyup', applyFilters);
    });
    
    displayPage();
});
</script>

<?php require_once 'tailwind-footer.php'; ?>

