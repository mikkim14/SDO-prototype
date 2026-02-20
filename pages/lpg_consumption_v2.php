<?php
$page_title = 'LPG Consumption';
require_once 'tailwind-header.php';

$message = '';
$error_msg = '';

$module = 'lpg';
$can_input = AccessControl::canInput($user['office'], $module);

// Fetch all LPG consumption records
$query = "SELECT * FROM tbllpg WHERE Campus = ? ORDER BY YearTransact DESC, Month DESC, id DESC";
$stmt = $db->prepare($query);
$stmt->bind_param("s", $user['campus']);
$stmt->execute();
$result = $stmt->get_result();
$records = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Load record for editing
$edit_record = null;
if (Helper::isGetRequest() && isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $query = "SELECT * FROM tbllpg WHERE id = ? AND Campus = ?";
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
        $error_msg = 'Your office is read-only for LPG. Contact Central to modify data.';
    }

    $year = Helper::getPost('year', '');
    $month = Helper::getPost('month', '');
    $quarter = Helper::getPost('quarter', '');
    $office = Helper::getPost('office', '');
    $concessionaries = Helper::getPost('concessionaries', '');
    $tank_quantity = Helper::getPost('tank_quantity', 0);
    $tank_weight = Helper::getPost('tank_weight', 0);
    $tank_volume = Helper::getPost('tank_volume', 0);
    $record_id = Helper::getPost('record_id', '');

    // Calculate Total Tank Volume and GHG Emissions
    $total_tank_volume = $tank_quantity * $tank_volume;
    $ghg_kg = $total_tank_volume * 3.000; // LPG emission factor
    $ghg_t = $ghg_kg / 1000;

    // Validation
    $validator = new Validator();
    $validator->validate('year', $year, 'required');
    $validator->validate('month', $month, 'required');
    $validator->validate('quarter', $quarter, 'required');
    $validator->validate('office', $office, 'required');
    $validator->validate('concessionaries', $concessionaries, 'required');
    $validator->validate('tank_quantity', $tank_quantity, 'required|numeric');
    $validator->validate('tank_weight', $tank_weight, 'required|numeric');
    $validator->validate('tank_volume', $tank_volume, 'required|numeric');
    
    if ($validator->fails()) {
        $error_msg = array_values($validator->errors())[0];
    } elseif ($can_input) {
        try {
            if ($record_id) {
                // Update
                $query = "UPDATE tbllpg SET YearTransact = ?, Month = ?, Quarter = ?, Office = ?, ConcessionariesType = ?, TankQuantity = ?, TankWeight = ?, TankVolume = ?, TotalTankVolume = ?, GHGEmissionKGCO2e = ?, GHGEmissionTCO2e = ? WHERE id = ? AND Campus = ?";
                $stmt = $db->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("sssssdddddddis", $year, $month, $quarter, $office, $concessionaries, $tank_quantity, $tank_weight, $tank_volume, $total_tank_volume, $ghg_kg, $ghg_t, $record_id, $user['campus']);
                    if ($stmt->execute()) {
                        $message = 'Record updated successfully';
                        Helper::logActivity($db, 'Updated LPG Consumption Record (ID: ' . $record_id . ')', 'LPG Report');
                        $edit_record = null;
                        header('Location: lpg_consumption_v2.php');
                        exit;
                    } else {
                        $error_msg = 'Failed to update record: ' . $stmt->error;
                    }
                    $stmt->close();
                }
            } else {
                // Insert
                $query = "INSERT INTO tbllpg (Campus, Office, YearTransact, Month, Quarter, ConcessionariesType, TankQuantity, TankWeight, TankVolume, TotalTankVolume, GHGEmissionKGCO2e, GHGEmissionTCO2e) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("ssssssdddddd", $user['campus'], $office, $year, $month, $quarter, $concessionaries, $tank_quantity, $tank_weight, $tank_volume, $total_tank_volume, $ghg_kg, $ghg_t);
                    if ($stmt->execute()) {
                        $message = 'Record added successfully';
                        Helper::logActivity($db, 'Added LPG Consumption Record for ' . $user['campus'] . ' - ' . $year . '/' . $month, 'LPG Report');
                    } else {
                        $error_msg = 'Failed to add record: ' . $stmt->error;
                    }
                    $stmt->close();
                }
            }
            
            // Refresh records
            $query = "SELECT * FROM tbllpg WHERE Campus = ? ORDER BY YearTransact DESC, Month DESC, id DESC LIMIT 100";
            $stmt = $db->prepare($query);
            $stmt->bind_param("s", $user['campus']);
            $stmt->execute();
            $result = $stmt->get_result();
            $records = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
        } catch (Exception $e) {
            $error_msg = 'Error: ' . $e->getMessage();
        }
    }
}

// Handle delete
if (Helper::isGetRequest() && isset($_GET['delete_id'])) {
    if (!$can_input) {
        $error_msg = 'Your office is read-only for LPG. Contact Central to modify data.';
    } else {
        $delete_id = (int)$_GET['delete_id'];
        $query = "DELETE FROM tbllpg WHERE id = ? AND Campus = ?";
        $stmt = $db->prepare($query);
        if ($stmt) {
            $stmt->bind_param("is", $delete_id, $user['campus']);
            if ($stmt->execute()) {
                $message = 'Record deleted successfully';
                Helper::logActivity($db, 'Deleted LPG Consumption Record (ID: ' . $delete_id . ')', 'LPG Report');
            }
            $stmt->close();
            
            // Refresh records
            $query = "SELECT * FROM tbllpg WHERE Campus = ? ORDER BY YearTransact DESC, Month DESC, id DESC";
            $stmt = $db->prepare($query);
            $stmt->bind_param("s", $user['campus']);
            $stmt->execute();
            $result = $stmt->get_result();
            $records = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            header('Location: lpg_consumption_v2.php');
            exit;
        }
    }
}
?>

                    <div class="max-w-6xl">
                        <!-- Page Header -->
                        <div class="mb-6">
                            <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                                <i class="fas fa-fire text-red-500 mr-3"></i>
                                LPG Consumption Records
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
                                            value="<?php //echo htmlspecialchars($edit_record['Office'] ?? $user['office']); ?>"
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

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
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
                                            value="<?php echo htmlspecialchars($edit_record['YearTransact'] ?? date('Y')); ?>"
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

                                    <div>
                                        <label for="concessionaries" class="block text-sm font-medium text-gray-700 mb-2">
                                            Concessionaries Type <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            id="concessionaries" 
                                            name="concessionaries" 
                                            required
                                            data-rules="required"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="e.g., Canteen, Cafeteria"
                                            value="<?php echo htmlspecialchars($edit_record['ConcessionariesType'] ?? ''); ?>"
                                        >
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="tank_quantity" class="block text-sm font-medium text-gray-700 mb-2">
                                            Tank Quantity <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="number" 
                                            id="tank_quantity" 
                                            name="tank_quantity" 
                                            step="1"
                                            min="0"
                                            required
                                            data-rules="required|numeric"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="0"
                                            value="<?php echo htmlspecialchars($edit_record['TankQuantity'] ?? ''); ?>"
                                        >
                                    </div>

                                    <div>
                                        <label for="tank_weight" class="block text-sm font-medium text-gray-700 mb-2">
                                            Tank Weight (kg) <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="number" 
                                            id="tank_weight" 
                                            name="tank_weight" 
                                            step="0.01"
                                            min="0"
                                            required
                                            data-rules="required|numeric"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="0.00"
                                            value="<?php echo htmlspecialchars($edit_record['TankWeight'] ?? ''); ?>"
                                        >
                                    </div>

                                    <div>
                                        <label for="tank_volume" class="block text-sm font-medium text-gray-700 mb-2">
                                            Tank Volume (L) <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="number" 
                                            id="tank_volume" 
                                            name="tank_volume" 
                                            step="0.01"
                                            min="0"
                                            required
                                            data-rules="required|numeric"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="0.00"
                                            value="<?php echo htmlspecialchars($edit_record['TankVolume'] ?? ''); ?>"
                                        >
                                        <p class="text-xs text-gray-500 mt-1">Total volume will be calculated automatically</p>
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
                                        <a href="lpg_consumption_v2.php" class="px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition-colors flex items-center">
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
                                    <select id="lpg-per-page" class="px-2 py-1 border border-gray-300 rounded text-sm">
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
                                    <table class="w-full" id="lpg-table">
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
                                                        <input type="text" placeholder="Filter..." data-table="lpg-table" data-col="<?php echo $idx; ?>" class="filter-input w-full px-2 py-1 text-xs border border-gray-300 rounded">
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

                                    <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                                        <div class="text-sm text-gray-600">
                                            Showing <span id="lpg-start">1</span> to <span id="lpg-end">25</span> of <span id="lpg-total"><?php echo count($records); ?></span> records
                                        </div>
                                        <div class="flex gap-1" id="lpg-pagination"></div>
                                    </div>
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
                                    <div class="text-yellow-600 text-sm font-semibold uppercase mb-2">Total Volume</div>
                                    <div class="text-3xl font-bold text-yellow-900">
                                        <?php echo Helper::formatNumber(array_sum(array_column($records, 'TotalTankVolume'))); ?> L
                                    </div>
                                </div>
                                
                                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                                    <div class="text-green-600 text-sm font-semibold uppercase mb-2">Total Emissions</div>
                                    <div class="text-3xl font-bold text-green-900">
                                        <?php echo Helper::formatNumber(array_sum(array_column($records, 'GHGEmissionKGCO2e'))); ?> kg CO₂e
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>-->
                    </div>

<script>
// Pagination and filtering for LPG table
document.addEventListener('DOMContentLoaded', function() {
    const tables = { 'lpg-table': { currentPage: 1, perPage: 25, filteredRows: [] } };
    
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
        
        document.getElementById('lpg-start').textContent = filteredRows.length > 0 ? start + 1 : 0;
        document.getElementById('lpg-end').textContent = end;
        document.getElementById('lpg-total').textContent = filteredRows.length;
        
        renderPagination(tableId, filteredRows.length);
    }
    
    function renderPagination(tableId, totalRows) {
        const config = tables[tableId];
        const totalPages = Math.ceil(totalRows / config.perPage);
        const paginationDiv = document.getElementById('lpg-pagination');
        
        let html = '';
        html += `<button onclick="changePageLPG('${tableId}', ${config.currentPage - 1})" ${config.currentPage === 1 ? 'disabled' : ''} class="px-3 py-1 border rounded text-sm ${config.currentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-100'}">&laquo; Prev</button>`;
        
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= config.currentPage - 2 && i <= config.currentPage + 2)) {
                html += `<button onclick="changePageLPG('${tableId}', ${i})" class="px-3 py-1 border rounded text-sm ${i === config.currentPage ? 'bg-blue-600 text-white font-semibold' : 'bg-white hover:bg-gray-100'}">${i}</button>`;
            } else if (i === config.currentPage - 3 || i === config.currentPage + 3) {
                html += `<span class="px-2 text-gray-500">...</span>`;
            }
        }
        
        html += `<button onclick="changePageLPG('${tableId}', ${config.currentPage + 1})" ${config.currentPage === totalPages || totalPages === 0 ? 'disabled' : ''} class="px-3 py-1 border rounded text-sm ${config.currentPage === totalPages || totalPages === 0 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-100'}">Next &raquo;</button>`;
        
        paginationDiv.innerHTML = html;
    }
    
    window.changePageLPG = function(tableId, page) {
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
    
    const select = document.getElementById('lpg-per-page');
    if (select) {
        select.addEventListener('change', function() {
            tables['lpg-table'].perPage = parseInt(this.value);
            tables['lpg-table'].currentPage = 1;
            displayPage('lpg-table');
        });
    }
    
    const filterInputs = document.querySelectorAll('.filter-input');
    filterInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const tableId = this.getAttribute('data-table');
            applyFilters(tableId);
        });
    });
    
    if (document.getElementById('lpg-table')) {
        displayPage('lpg-table');
    }
});
</script>

<?php require_once 'tailwind-footer.php'; ?>

