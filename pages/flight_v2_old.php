<?php
$page_title = 'Flight Records';
require_once 'tailwind-header.php';

$message = '';
$error_msg = '';

$module = 'flight';
$can_input = AccessControl::canInput($user['office'], $module);
$filter = AccessControl::getGHGFilterClause($user['office'], $user['campus'], $module);

// Fetch all flight records (apply office/campus visibility)
$records = AccessControl::fetchRecords($db, 'tblflight', $filter, 'date DESC', 100);

// Handle form submission
if (Helper::isPostRequest()) {
    if (!$can_input) {
        $error_msg = 'Your office is read-only for Flight. Contact Central to modify data.';
    }

    $date = Helper::getPost('date', '');
    $destination = Helper::getPost('destination', '');
    $travelers = Helper::getPost('travelers', 0);
    $fuel_consumed = Helper::getPost('fuel_consumed', 0);
    $record_id = Helper::getPost('record_id', '');

    // Validation
    $validator = new Validator();
    $validator->validate('date', $date, 'required|date');
    $validator->validate('destination', $destination, 'required');
    $validator->validate('travelers', $travelers, 'required|numeric');
    $validator->validate('fuel_consumed', $fuel_consumed, 'required|numeric');
    
    if ($validator->fails()) {
        $error_msg = array_values($validator->errors())[0];
    } elseif ($can_input) {
        try {
            if ($record_id) {
                // Update (enforce campus + office)
                $query = "UPDATE tblflight SET date = ?, destination = ?, travelers = ?, fuel_consumed = ? WHERE id = ? AND campus = ? AND office = ?";
                $stmt = $db->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("ssiisis", $date, $destination, $travelers, $fuel_consumed, $record_id, $user['campus'], $user['office']);
                    if ($stmt->execute()) {
                        $message = 'Record updated successfully';
                        Helper::logActivity($db, 'Updated Flight Record (ID: ' . $record_id . ')', 'Flight Report');
                    } else {
                        $error_msg = 'Failed to update record';
                    }
                    $stmt->close();
                }
            } else {
                // Insert (include office)
                $query = "INSERT INTO tblflight (campus, office, date, destination, travelers, fuel_consumed) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("ssssis", $user['campus'], $user['office'], $date, $destination, $travelers, $fuel_consumed);
                    if ($stmt->execute()) {
                        $message = 'Record added successfully';
                        Helper::logActivity($db, 'Added Flight Record for ' . $user['campus'] . ' - ' . $date, 'Flight Report');
                    } else {
                        $error_msg = 'Failed to add record';
                    }
                    $stmt->close();
                }
            }
            
            // Refresh records
            $records = AccessControl::fetchRecords($db, 'tblflight', $filter, 'date DESC', 100);
            
        } catch (Exception $e) {
            $error_msg = 'Error: ' . $e->getMessage();
        }
    }
}

// Handle delete
if (Helper::isGetRequest() && isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    if (!$can_input) {
        $error_msg = 'You do not have permission to delete flight records.';
    } else {
        // Delete with campus + office enforcement
        $query = "DELETE FROM tblflight WHERE id = ? AND campus = ? AND office = ?";
        $stmt = $db->prepare($query);
        if ($stmt) {
            $stmt->bind_param("iss", $delete_id, $user['campus'], $user['office']);
            if ($stmt->execute()) {
                $message = 'Record deleted successfully';
                Helper::logActivity($db, 'Deleted Flight Record (ID: ' . $delete_id . ')', 'Flight Report');
            }
            $stmt->close();
        }
    }
    
    // Refresh records
    $records = AccessControl::fetchRecords($db, 'tblflight', $filter, 'date DESC', 100);
}
?>

                    <div class="max-w-6xl">
                        <!-- Page Header -->
                        <div class="mb-6">
                            <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                                <i class="fas fa-plane text-blue-500 mr-3"></i>
                                Flight Records
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
                                        <label for="destination" class="block text-sm font-medium text-gray-700 mb-2">
                                            Destination <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            id="destination" 
                                            name="destination" 
                                            required
                                            data-rules="required"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="e.g., Manila"
                                        >
                                    </div>

                                    <div>
                                        <label for="travelers" class="block text-sm font-medium text-gray-700 mb-2">
                                            Number of Travelers <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="number" 
                                            id="travelers" 
                                            name="travelers" 
                                            min="1"
                                            required
                                            data-rules="required|numeric"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="0"
                                        >
                                    </div>

                                    <div>
                                        <label for="fuel_consumed" class="block text-sm font-medium text-gray-700 mb-2">
                                            Fuel Consumed (liters) <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="number" 
                                            id="fuel_consumed" 
                                            name="fuel_consumed" 
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
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                                    <i class="fas fa-list mr-2"></i>
                                    Existing Records (<?php echo count($records); ?>)
                                </h2>
                            </div>

                            <?php if (count($records) > 0): ?>
                                <div class="overflow-x-auto">
                                    <table class="w-full data-table">
                                        <thead class="bg-gray-100 border-b border-gray-200">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Date</th>
                                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Destination</th>
                                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Travelers</th>
                                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Fuel (liters)</th>
                                                <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <?php foreach ($records as $record): ?>
                                                <tr class="hover:bg-gray-50 transition-colors">
                                                    <td class="px-6 py-4 text-sm text-gray-800"><?php echo Helper::formatDate($record['date']); ?></td>
                                                    <td class="px-6 py-4 text-sm text-gray-800"><?php echo htmlspecialchars($record['destination']); ?></td>
                                                    <td class="px-6 py-4 text-sm text-gray-800"><?php echo $record['travelers']; ?></td>
                                                    <td class="px-6 py-4 text-sm text-gray-800"><?php echo Helper::formatNumber($record['fuel_consumed']); ?></td>
                                                    <td class="px-6 py-4 text-center">
                                                        <a href="?delete_id=<?php echo $record['id']; ?>" 
                                                           class="inline-block px-3 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg text-sm transition-colors"
                                                           onclick="return confirm('Are you sure you want to delete this record?');">
                                                            <i class="fas fa-trash mr-1"></i>Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
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
                            <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                                    <div class="text-blue-600 text-sm font-semibold uppercase mb-2">Total Records</div>
                                    <div class="text-3xl font-bold text-blue-900"><?php echo count($records); ?></div>
                                </div>
                                
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                                    <div class="text-yellow-600 text-sm font-semibold uppercase mb-2">Total Travelers</div>
                                    <div class="text-3xl font-bold text-yellow-900">
                                        <?php echo array_sum(array_column($records, 'travelers')); ?>
                                    </div>
                                </div>
                                
                                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                                    <div class="text-green-600 text-sm font-semibold uppercase mb-2">Total Fuel</div>
                                    <div class="text-3xl font-bold text-green-900">
                                        <?php echo Helper::formatNumber(array_sum(array_column($records, 'fuel_consumed'))); ?> L
                                    </div>
                                </div>
                                
                                <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
                                    <div class="text-purple-600 text-sm font-semibold uppercase mb-2">Avg Fuel/Traveler</div>
                                    <div class="text-3xl font-bold text-purple-900">
                                        <?php $total_travelers = array_sum(array_column($records, 'travelers')); echo Helper::formatNumber($total_travelers > 0 ? array_sum(array_column($records, 'fuel_consumed')) / $total_travelers : 0); ?> L
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

<?php require_once 'tailwind-footer.php'; ?>
