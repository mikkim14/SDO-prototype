<?php
$page_title = 'Fuel Consumption';
require_once 'tailwind-header.php';

$message = '';
$error_msg = '';

$module = 'fuel';
$can_input = AccessControl::canInput($user['office'], $module);

// Fetch all Fuel consumption records
$query = "SELECT * FROM fuel_emissions WHERE campus = ? ORDER BY date DESC, id DESC";
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
    $query = "SELECT * FROM fuel_emissions WHERE id = ? AND campus = ?";
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
        $error_msg = 'Your office is read-only for Fuel. Contact Central to modify data.';
    }

    $date = Helper::getPost('date', '');
    $year = Helper::getPost('year', '');
    $quarter = Helper::getPost('quarter', '');
    $month = Helper::getPost('month', '');
    $driver = Helper::getPost('driver', '');
    $type = Helper::getPost('type', '');
    $vehicle_equipment = Helper::getPost('vehicle_equipment', '');
    $plate_no = Helper::getPost('plate_no', '');
    $category = Helper::getPost('category', '');
    $fuel_type = Helper::getPost('fuel_type', '');
    $item_description = Helper::getPost('item_description', '');
    $transaction_no = Helper::getPost('transaction_no', '');
    $odometer = Helper::getPost('odometer', 0);
    $quantity_liters = Helper::getPost('quantity_liters', 0);
    $total_amount = Helper::getPost('total_amount', 0);
    $record_id = Helper::getPost('record_id', '');
    
    // Auto-set vehicle_equipment based on type
    if ($type === 'Equipment') {
        $vehicle_equipment = 'Generator';
    }

    // Calculate GHG Emissions (using standard fuel emission factors)
    $emission_factors = [
        'Diesel' => 2.68,
        'Gasoline' => 2.31,
        'LPG' => 1.51,
        'Other' => 2.5
    ];
    $factor = $emission_factors[$fuel_type] ?? 2.5;
    $co2_emission = $quantity_liters * $factor;
    $nh4_emission = $co2_emission * 0.001; // Simplified
    $n2o_emission = $co2_emission * 0.0001; // Simplified
    $total_emission = $co2_emission + $nh4_emission + $n2o_emission;
    $total_emission_t = $total_emission / 1000;

    // Validation
    $validator = new Validator();
    $validator->validate('date', $date, 'required');
    $validator->validate('year', $year, 'required');
    $validator->validate('quarter', $quarter, 'required');
    $validator->validate('month', $month, 'required');
    $validator->validate('type', $type, 'required');
    $validator->validate('category', $category, 'required');
    $validator->validate('fuel_type', $fuel_type, 'required');
    $validator->validate('quantity_liters', $quantity_liters, 'required|numeric');
    
    if ($validator->fails()) {
        $error_msg = array_values($validator->errors())[0];
    } elseif ($can_input) {
        try {
            if ($record_id) {
                // Update
                $query = "UPDATE fuel_emissions SET date = ?, Year = ?, Quarter = ?, Month = ?, driver = ?, type = ?, vehicle_equipment = ?, plate_no = ?, category = ?, fuel_type = ?, item_description = ?, transaction_no = ?, odometer = ?, quantity_liters = ?, total_amount = ?, co2_emission = ?, nh4_emission = ?, n2o_emission = ?, total_emission = ?, total_emission_t = ? WHERE id = ? AND campus = ?";
                $stmt = $db->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("sisssssssssidddddddis", $date, $year, $quarter, $month, $driver, $type, $vehicle_equipment, $plate_no, $category, $fuel_type, $item_description, $transaction_no, $odometer, $quantity_liters, $total_amount, $co2_emission, $nh4_emission, $n2o_emission, $total_emission, $total_emission_t, $record_id, $user['campus']);
                    if ($stmt->execute()) {
                        $message = 'Record updated successfully';
                        Helper::logActivity($db, 'Updated Fuel Consumption Record (ID: ' . $record_id . ')', 'Fuel Report');
                        $edit_record = null;
                        header('Location: fuel_consumption_v2.php');
                        exit;
                    } else {
                        $error_msg = 'Failed to update record: ' . $stmt->error;
                    }
                    $stmt->close();
                }
            } else {
                // Insert
                $query = "INSERT INTO fuel_emissions (campus, date, Year, Quarter, Month, driver, type, vehicle_equipment, plate_no, category, fuel_type, item_description, transaction_no, odometer, quantity_liters, total_amount, co2_emission, nh4_emission, n2o_emission, total_emission, total_emission_t) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("ssisssssssssidddddd", $user['campus'], $date, $year, $quarter, $month, $driver, $type, $vehicle_equipment, $plate_no, $category, $fuel_type, $item_description, $transaction_no, $odometer, $quantity_liters, $total_amount, $co2_emission, $nh4_emission, $n2o_emission, $total_emission, $total_emission_t);
                    if ($stmt->execute()) {
                        $message = 'Record added successfully';
                        Helper::logActivity($db, 'Added Fuel Consumption Record for ' . $user['campus'] . ' - ' . $date, 'Fuel Report');
                    } else {
                        $error_msg = 'Failed to add record: ' . $stmt->error;
                    }
                    $stmt->close();
                }
            }
            
            // Refresh records
            $query = "SELECT * FROM fuel_emissions WHERE campus = ? ORDER BY date DESC, id DESC";
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
    if ($can_input) {
        $delete_id = (int)$_GET['delete_id'];
        $query = "DELETE FROM fuel_emissions WHERE id = ? AND campus = ?";
        $stmt = $db->prepare($query);
        if ($stmt) {
            $stmt->bind_param("is", $delete_id, $user['campus']);
            if ($stmt->execute()) {
                $message = 'Record deleted successfully';
                Helper::logActivity($db, 'Deleted Fuel Consumption Record (ID: ' . $delete_id . ')', 'Fuel Report');
            }
            $stmt->close();
            header('Location: fuel_consumption_v2.php');
            exit;
        }
    }
}

// Calculate statistics
$total_quantity = 0;
$total_emissions = 0;
foreach ($records as $record) {
    $total_quantity += $record['quantity_liters'] ?? 0;
    $total_emissions += $record['total_emission'] ?? 0;
}
?>

                    <div class="max-w-7xl">
                        <!-- Page Header -->
                        <div class="mb-6">
                            <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                                <i class="fas fa-gas-pump text-orange-500 mr-3"></i>
                                Fuel Consumption Records
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

                        <!-- Statistics Cards 
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-orange-500">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-600">Total Records</p>
                                        <p class="text-3xl font-bold text-gray-800"><?php echo count($records); ?></p>
                                    </div>
                                    <i class="fas fa-file-alt text-4xl text-orange-200"></i>
                                </div>
                            </div>
                            
                            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-600">Total Quantity</p>
                                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($total_quantity, 2); ?></p>
                                        <p class="text-xs text-gray-500">Liters</p>
                                    </div>
                                    <i class="fas fa-gas-pump text-4xl text-blue-200"></i>
                                </div>
                            </div>
                            
                            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-600">Total GHG Emissions</p>
                                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($total_emissions, 2); ?></p>
                                        <p class="text-xs text-gray-500">kg CO₂e</p>
                                    </div>
                                    <i class="fas fa-smog text-4xl text-red-200"></i>
                                </div>
                            </div>
                        </div>-->

                        <!-- Form Card -->
                        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-<?php echo $edit_record ? 'edit' : 'plus-circle'; ?> text-orange-600 mr-2"></i>
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
                                        <label for="year" class="block text-sm font-medium text-gray-700 mb-2">
                                            Year <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="number" 
                                            id="year" 
                                            name="year" 
                                            min="2020" 
                                            max="2100"
                                            required
                                            data-rules="required"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                            value="<?php echo htmlspecialchars($edit_record['Year'] ?? date('Y')); ?>"
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
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                        >
                                            <option value="">-- Select Quarter --</option>
                                            <option value="Q1" <?php echo ($edit_record['Quarter'] ?? '') == 'Q1' ? 'selected' : ''; ?>>Q1</option>
                                            <option value="Q2" <?php echo ($edit_record['Quarter'] ?? '') == 'Q2' ? 'selected' : ''; ?>>Q2</option>
                                            <option value="Q3" <?php echo ($edit_record['Quarter'] ?? '') == 'Q3' ? 'selected' : ''; ?>>Q3</option>
                                            <option value="Q4" <?php echo ($edit_record['Quarter'] ?? '') == 'Q4' ? 'selected' : ''; ?>>Q4</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <label for="month" class="block text-sm font-medium text-gray-700 mb-2">
                                            Month <span class="text-red-500">*</span>
                                        </label>
                                        <select 
                                            id="month" 
                                            name="month" 
                                            required
                                            data-rules="required"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                        >
                                            <option value="">-- Select Month --</option>
                                            <?php
                                            $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                                            foreach ($months as $m) {
                                                $selected = ($edit_record['Month'] ?? '') == $m ? 'selected' : '';
                                                echo "<option value='$m' $selected>$m</option>";
                                            }
                                            ?>
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
                                            data-rules="required"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                            value="<?php echo htmlspecialchars($edit_record['date'] ?? date('Y-m-d')); ?>"
                                        >
                                    </div>

                                    <div>
                                        <label for="driver" class="block text-sm font-medium text-gray-700 mb-2">
                                            Driver
                                        </label>
                                        <input 
                                            type="text" 
                                            id="driver" 
                                            name="driver" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                            placeholder="Driver Name"
                                            value="<?php echo htmlspecialchars($edit_record['driver'] ?? ''); ?>"
                                        >
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                                            Type <span class="text-red-500">*</span>
                                        </label>
                                        <select 
                                            id="type" 
                                            name="type" 
                                            required
                                            data-rules="required"
                                            onchange="handleTypeChange(this.value)"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                        >
                                            <option value="">-- Select Type --</option>
                                            <option value="Vehicle" <?php echo ($edit_record['type'] ?? '') == 'Vehicle' ? 'selected' : ''; ?>>Vehicle</option>
                                            <option value="Equipment" <?php echo ($edit_record['type'] ?? '') == 'Equipment' ? 'selected' : ''; ?>>Equipment</option>
                                        </select>
                                    </div>

                                    <div id="vehicle-equipment-container">
                                        <label for="vehicle_equipment" class="block text-sm font-medium text-gray-700 mb-2">
                                            Vehicle/Equipment
                                        </label>
                                        <select 
                                            id="vehicle_equipment_select" 
                                            name="vehicle_equipment" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent" 
                                            style="display: none;"
                                        >
                                            <option value="">-- Select Vehicle --</option>
                                            <option value="Toyota Grandia" <?php echo ($edit_record['vehicle_equipment'] ?? '') == 'Toyota Grandia' ? 'selected' : ''; ?>>Toyota Grandia</option>
                                            <option value="Toyota Hi-Ace" <?php echo ($edit_record['vehicle_equipment'] ?? '') == 'Toyota Hi-Ace' ? 'selected' : ''; ?>>Toyota Hi-Ace</option>
                                            <option value="Toyota Minibus" <?php echo ($edit_record['vehicle_equipment'] ?? '') == 'Toyota Minibus' ? 'selected' : ''; ?>>Toyota Minibus</option>
                                            <option value="Foton Bus" <?php echo ($edit_record['vehicle_equipment'] ?? '') == 'Foton Bus' ? 'selected' : ''; ?>>Foton Bus</option>
                                        </select>
                                        <input 
                                            type="text" 
                                            id="vehicle_equipment_text" 
                                            name="vehicle_equipment_readonly" 
                                            readonly
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 cursor-not-allowed" 
                                            style="display: none;"
                                            value="Generator"
                                        >
                                        <input 
                                            type="text" 
                                            id="vehicle_equipment_placeholder" 
                                            disabled
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed"
                                            placeholder="Select Type first"
                                            value="<?php echo htmlspecialchars($edit_record['vehicle_equipment'] ?? ''); ?>"
                                        >
                                    </div>

                                    <div>
                                        <label for="plate_no" class="block text-sm font-medium text-gray-700 mb-2">
                                            Plate Number
                                        </label>
                                        <input 
                                            type="text" 
                                            id="plate_no" 
                                            name="plate_no" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                            placeholder="ABC-1234"
                                            value="<?php echo htmlspecialchars($edit_record['plate_no'] ?? ''); ?>"
                                        >
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                                            Category <span class="text-red-500">*</span>
                                        </label>
                                        <select 
                                            id="category" 
                                            name="category" 
                                            required
                                            data-rules="required"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                        >
                                            <option value="">-- Select Category --</option>
                                            <option value="Fuel" <?php echo ($edit_record['category'] ?? '') == 'Fuel' ? 'selected' : ''; ?>>Fuel</option>
                                            <option value="Repair & Maintenance" <?php echo ($edit_record['category'] ?? '') == 'Repair & Maintenance' ? 'selected' : ''; ?>>Repair & Maintenance</option>
                                        </select>
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
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                        >
                                            <option value="">-- Select Fuel Type --</option>
                                            <option value="Diesel" <?php echo ($edit_record['fuel_type'] ?? '') == 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                                            <option value="Gasoline" <?php echo ($edit_record['fuel_type'] ?? '') == 'Gasoline' ? 'selected' : ''; ?>>Gasoline</option>
                                            <!--<option value="LPG" <?php echo ($edit_record['fuel_type'] ?? '') == 'LPG' ? 'selected' : ''; ?>>LPG</option>
                                            <option value="Other" <?php echo ($edit_record['fuel_type'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>-->
                                        </select>
                                    </div>

                                    <div>
                                        <label for="item_description" class="block text-sm font-medium text-gray-700 mb-2">
                                            Item Description
                                        </label>
                                        <input 
                                            type="text" 
                                            id="item_description" 
                                            name="item_description" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                            placeholder="Description"
                                            value="<?php echo htmlspecialchars($edit_record['item_description'] ?? ''); ?>"
                                        >
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                    <div>
                                        <label for="transaction_no" class="block text-sm font-medium text-gray-700 mb-2">
                                            Transaction No.
                                        </label>
                                        <input 
                                            type="text" 
                                            id="transaction_no" 
                                            name="transaction_no" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                            placeholder="Transaction Number"
                                            value="<?php echo htmlspecialchars($edit_record['transaction_no'] ?? ''); ?>"
                                        >
                                    </div>

                                    <div>
                                        <label for="odometer" class="block text-sm font-medium text-gray-700 mb-2">
                                            Odometer
                                        </label>
                                        <input 
                                            type="number" 
                                            id="odometer" 
                                            name="odometer" 
                                            min="0"
                                            step="1"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                            placeholder="0"
                                            value="<?php echo htmlspecialchars($edit_record['odometer'] ?? ''); ?>"
                                        >
                                        <p class="text-xs text-gray-500 mt-1">Reading in km</p>
                                    </div>

                                    <div>
                                        <label for="quantity_liters" class="block text-sm font-medium text-gray-700 mb-2">
                                            Quantity (Liters) <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="number" 
                                            id="quantity_liters" 
                                            name="quantity_liters" 
                                            min="0"
                                            step="0.01"
                                            required
                                            data-rules="required|numeric"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                            placeholder="0.00"
                                            value="<?php echo htmlspecialchars($edit_record['quantity_liters'] ?? ''); ?>"
                                        >
                                        <p class="text-xs text-gray-500 mt-1">Fuel quantity in liters</p>
                                    </div>

                                    <div>
                                        <label for="total_amount" class="block text-sm font-medium text-gray-700 mb-2">
                                            Total Amount
                                        </label>
                                        <input 
                                            type="number" 
                                            id="total_amount" 
                                            name="total_amount" 
                                            min="0"
                                            step="0.01"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                            placeholder="0.00"
                                            value="<?php echo htmlspecialchars($edit_record['total_amount'] ?? ''); ?>"
                                        >
                                        <p class="text-xs text-gray-500 mt-1">Cost in PHP</p>
                                    </div>
                                </div>

                                <div class="mt-4 flex gap-3">
                                    <button 
                                        type="submit"
                                        class="px-6 py-2 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg transition-colors flex items-center"
                                    >
                                        <i class="fas fa-save mr-2"></i><?php echo $edit_record ? 'Update Record' : 'Save Record'; ?>
                                    </button>
                                    <?php if ($edit_record): ?>
                                        <a href="fuel_consumption_v2.php" class="px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition-colors flex items-center">
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
                                    <select id="fuel-per-page" class="px-2 py-1 border border-gray-300 rounded text-sm">
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
                                    <table class="w-full" id="fuel-table">
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
                                                        <input type="text" placeholder="Filter..." data-table="fuel-table" data-col="<?php echo $idx; ?>" class="filter-input w-full px-2 py-1 text-xs border border-gray-300 rounded">
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
                                    Showing <span id="fuel-start">0</span> to <span id="fuel-end">0</span> of <span id="fuel-total">0</span> entries
                                </div>
                            <?php else: ?>
                                <div class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-3"></i>
                                    <p class="text-lg">No records found</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

<script>
// Handle Type dropdown change - show Vehicle/Equipment field conditionally
function handleTypeChange(type) {
    const vehicleSelect = document.getElementById('vehicle_equipment_select');
    const vehicleText = document.getElementById('vehicle_equipment_text');
    const vehiclePlaceholder = document.getElementById('vehicle_equipment_placeholder');
    
    if (type === 'Equipment') {
        // Show readonly text field with "Generator"
        vehicleSelect.style.display = 'none';
        vehicleText.style.display = 'block';
        vehiclePlaceholder.style.display = 'none';
        vehicleText.value = 'Generator';
    } else if (type === 'Vehicle') {
        // Show dropdown with vehicle options
        vehicleSelect.style.display = 'block';
        vehicleText.style.display = 'none';
        vehiclePlaceholder.style.display = 'none';
    } else {
        // Show placeholder when no type selected
        vehicleSelect.style.display = 'none';
        vehicleText.style.display = 'none';
        vehiclePlaceholder.style.display = 'block';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Check if editing a record and set initial state
    const typeSelect = document.getElementById('type');
    if (typeSelect && typeSelect.value) {
        handleTypeChange(typeSelect.value);
    }
});

// Fuel pagination and filtering
let fuelCurrentPage = 1;
let fuelPerPage = 25;
let fuelFilteredRows = [];

function applyFilters() {
    const table = document.getElementById('fuel-table');
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const allRows = Array.from(tbody.querySelectorAll('tr'));
    const filterInputs = table.querySelectorAll('.filter-input');
    
    fuelFilteredRows = allRows.filter(row => {
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
    
    fuelCurrentPage = 1;
    displayPage();
}

function displayPage() {
    const table = document.getElementById('fuel-table');
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const allRows = Array.from(tbody.querySelectorAll('tr'));
    const rowsToShow = fuelFilteredRows.length > 0 ? fuelFilteredRows : allRows;
    
    // Hide all rows
    allRows.forEach(row => row.style.display = 'none');
    
    // Calculate pagination
    const start = (fuelCurrentPage - 1) * fuelPerPage;
    const end = start + fuelPerPage;
    
    // Show current page rows
    rowsToShow.slice(start, end).forEach(row => row.style.display = '');
    
    // Update pagination info
    document.getElementById('fuel-start').textContent = rowsToShow.length > 0 ? start + 1 : 0;
    document.getElementById('fuel-end').textContent = Math.min(end, rowsToShow.length);
    document.getElementById('fuel-total').textContent = rowsToShow.length;
    document.getElementById('record-count').textContent = rowsToShow.length;
}

function changePageFuel(newPage) {
    fuelCurrentPage = newPage;
    displayPage();
}

document.addEventListener('DOMContentLoaded', function() {
    const perPageSelect = document.getElementById('fuel-per-page');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            fuelPerPage = parseInt(this.value);
            fuelCurrentPage = 1;
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
