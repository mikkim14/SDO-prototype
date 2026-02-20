<?php
$page_title = 'Fuel Emissions';
require_once 'tailwind-header.php';

$message = '';
$error_msg = '';

// Fetch all fuel records
$records = [];
$query = "// Fetch records with office/campus visibility
 = AccessControl::getGHGFilterClause(['office'], ['campus']);
 = [];
 = "SELECT * FROM fuel_emissions";
if (['where_clause']) {
     .= " " . ['where_clause'];
}
 .= " ORDER BY date DESC LIMIT 100";
 = ->prepare();
if () {
    if (count(['params']) == 2) {
        ->bind_param("ss", ['params'][0], ['params'][1]);
    } elseif (count(['params']) == 1) {
        ->bind_param("s", ['params'][0]);
    }
    ->execute();
     = ->get_result();
     = ->fetch_all(MYSQLI_ASSOC);
    ->close();
}";
$stmt = $db->prepare($query);
if ($stmt) {
    $stmt->bind_param("s", $user['campus']);
    $stmt->execute();
    $result = $stmt->get_result();
    $records = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Handle form submission
if (Helper::isPostRequest()) {
    $date = Helper::getPost('date', '');
    $fuel_type = Helper::getPost('fuel_type', '');
    $amount = Helper::getPost('amount', '');
    $record_id = Helper::getPost('record_id', '');

    // Validation
    $validator = new Validator();
    $validator->validate('date', $date, 'required|date');
    $validator->validate('fuel_type', $fuel_type, 'required');
    $validator->validate('amount', $amount, 'required|numeric');
    
    if ($validator->fails()) {
        $error_msg = array_values($validator->errors())[0];
    } else {
        try {
            if ($record_id) {
                // Update
                $query = "UPDATE fuel_emissions SET date = ?, fuel_type = ?, amount = ? WHERE id = ? AND campus = ?";
                $stmt = $db->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("sdsis", $date, $fuel_type, $amount, $record_id, $user['campus']);
                    if ($stmt->execute()) {
                        $message = 'Record updated successfully';
                        Helper::logActivity($db, 'Updated Fuel Emissions Record (ID: ' . $record_id . ')', 'Fuel Report');
                    } else {
                        $error_msg = 'Failed to update record';
                    }
                    $stmt->close();
                }
            } else {
                // Insert
                $query = "INSERT INTO fuel_emissions (campus, date, fuel_type, amount) VALUES (?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("sssd", $user['campus'], $date, $fuel_type, $amount);
                    if ($stmt->execute()) {
                        $message = 'Record added successfully';
                        Helper::logActivity($db, 'Added Fuel Emissions Record for ' . $user['campus'] . ' - ' . $date, 'Fuel Report');
                    } else {
                        $error_msg = 'Failed to add record';
                    }
                    $stmt->close();
                }
            }
            
            // Refresh records
            $stmt = $db->prepare("// Fetch records with office/campus visibility
 = AccessControl::getGHGFilterClause(['office'], ['campus']);
 = [];
 = "SELECT * FROM fuel_emissions";
if (['where_clause']) {
     .= " " . ['where_clause'];
}
 .= " ORDER BY date DESC LIMIT 100";
 = ->prepare();
if () {
    if (count(['params']) == 2) {
        ->bind_param("ss", ['params'][0], ['params'][1]);
    } elseif (count(['params']) == 1) {
        ->bind_param("s", ['params'][0]);
    }
    ->execute();
     = ->get_result();
     = ->fetch_all(MYSQLI_ASSOC);
    ->close();
}");
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
    $delete_id = (int)$_GET['delete_id'];
    $query = "DELETE FROM fuel_emissions WHERE id = ? AND campus = ?";
    $stmt = $db->prepare($query);
    if ($stmt) {
        $stmt->bind_param("is", $delete_id, $user['campus']);
        if ($stmt->execute()) {
            $message = 'Record deleted successfully';
            Helper::logActivity($db, 'Deleted Fuel Emissions Record (ID: ' . $delete_id . ')', 'Fuel Report');
        }
        $stmt->close();
        
        // Refresh records
        $stmt = $db->prepare("// Fetch records with office/campus visibility
 = AccessControl::getGHGFilterClause(['office'], ['campus']);
 = [];
 = "SELECT * FROM fuel_emissions";
if (['where_clause']) {
     .= " " . ['where_clause'];
}
 .= " ORDER BY date DESC LIMIT 100";
 = ->prepare();
if () {
    if (count(['params']) == 2) {
        ->bind_param("ss", ['params'][0], ['params'][1]);
    } elseif (count(['params']) == 1) {
        ->bind_param("s", ['params'][0]);
    }
    ->execute();
     = ->get_result();
     = ->fetch_all(MYSQLI_ASSOC);
    ->close();
}");
        $stmt->bind_param("s", $user['campus']);
        $stmt->execute();
        $result = $stmt->get_result();
        $records = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
?>

                    <div class="max-w-6xl">
                        <!-- Page Header -->
                        <div class="mb-6">
                            <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                                <i class="fas fa-gas-pump text-orange-500 mr-3"></i>
                                Fuel Emissions Records
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
                                        <label for="fuel_type" class="block text-sm font-medium text-gray-700 mb-2">
                                            Fuel Type <span class="text-red-500">*</span>
                                        </label>
                                        <select 
                                            id="fuel_type" 
                                            name="fuel_type" 
                                            required
                                            data-rules="required"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        >
                                            <option value="">-- Select Fuel Type --</option>
                                            <option value="Diesel">Diesel</option>
                                            <option value="Petrol">Petrol</option>
                                            <option value="LPG">LPG</option>
                                            <option value="Natural Gas">Natural Gas</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                                            Amount (liters) <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="number" 
                                            id="amount" 
                                            name="amount" 
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
                                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Fuel Type</th>
                                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Amount (liters)</th>
                                                <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <?php foreach ($records as $record): ?>
                                                <tr class="hover:bg-gray-50 transition-colors">
                                                    <td class="px-6 py-4 text-sm text-gray-800"><?php echo Helper::formatDate($record['date']); ?></td>
                                                    <td class="px-6 py-4 text-sm text-gray-800">
                                                        <span class="px-3 py-1 rounded-full bg-orange-100 text-orange-800 text-xs font-semibold">
                                                            <?php echo htmlspecialchars($record['fuel_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-800"><?php echo Helper::formatNumber($record['amount']); ?></td>
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
                            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                                    <div class="text-blue-600 text-sm font-semibold uppercase mb-2">Total Records</div>
                                    <div class="text-3xl font-bold text-blue-900"><?php echo count($records); ?></div>
                                </div>
                                
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                                    <div class="text-yellow-600 text-sm font-semibold uppercase mb-2">Total Amount</div>
                                    <div class="text-3xl font-bold text-yellow-900">
                                        <?php echo Helper::formatNumber(array_sum(array_column($records, 'amount'))); ?> L
                                    </div>
                                </div>
                                
                                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                                    <div class="text-green-600 text-sm font-semibold uppercase mb-2">Average Amount</div>
                                    <div class="text-3xl font-bold text-green-900">
                                        <?php echo Helper::formatNumber(array_sum(array_column($records, 'amount')) / max(1, count($records))); ?> L
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

<?php require_once 'tailwind-footer.php'; ?>

