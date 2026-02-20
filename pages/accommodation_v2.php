<?php
$page_title = 'Accommodation Records';
require_once 'tailwind-header.php';

$message = '';
$error_msg = '';

$module = 'accommodation';
$can_input = AccessControl::canInput($user['office'], $module);
$filter = AccessControl::getGHGFilterClause($user['office'], $user['campus'], $module);

// Fetch all accommodation records
$where_sql = $filter['where_clause'] ?: 'WHERE 1=1';
$query = "SELECT * FROM tblaccommodation $where_sql ORDER BY TravelDateFrom DESC";
$stmt = $db->prepare($query);
if (!empty($filter['params'])) {
    $types = str_repeat('s', count($filter['params']));
    $stmt->bind_param($types, ...$filter['params']);
}
$stmt->execute();
$result = $stmt->get_result();
$records = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$can_input) {
        $error_msg = 'Your office does not have permission to modify accommodation records.';
    } else {
        $record_id = $_POST['record_id'] ?? '';
        $form_campus = $_POST['campus'] ?? $user['campus'];
        $form_office = $_POST['office'] ?? '';
        $year = $_POST['year'] ?? '';
        $month = $_POST['month'] ?? '';
        $quarter = $_POST['quarter'] ?? '';
        $traveller_name = $_POST['traveller_name'] ?? '';
        $travel_purpose = $_POST['travel_purpose'] ?? '';
        $travel_date_from = $_POST['travel_date_from'] ?? '';
        $travel_date_to = $_POST['travel_date_to'] ?? '';
        $country = $_POST['country'] ?? '';
        $travel_type = $_POST['travel_type'] ?? '';
        $num_rooms = intval($_POST['num_rooms'] ?? 0);
        $num_nights = intval($_POST['num_nights'] ?? 0);
        $factor = floatval($_POST['factor'] ?? 0);
        $ghg_kg = floatval($_POST['ghg_kg'] ?? 0);
        $ghg_t = $ghg_kg / 1000;

        if ($record_id) {
            // Update - only check Campus for authorization, allow Office changes
            $stmt = $db->prepare("UPDATE tblaccommodation SET Office = ?, YearTransact = ?, Month = ?, Quarter = ?, TravellerName = ?, TravelPurpose = ?, TravelDateFrom = ?, TravelDateTo = ?, Country = ?, TravelType = ?, NumOccupiedRoom = ?, NumNightPerRoom = ?, Factor = ?, GHGEmissionKGC02e = ?, GHGEmissionTC02e = ? WHERE id = ? AND Campus = ?");
            $stmt->bind_param("sssssssssssiidd dis", $form_office, $year, $month, $quarter, $traveller_name, $travel_purpose, $travel_date_from, $travel_date_to, $country, $travel_type, $num_rooms, $num_nights, $factor, $ghg_kg, $ghg_t, $record_id, $user['campus']);
            if ($stmt->execute()) {
                $message = 'Record updated successfully';
                Helper::logActivity($db, $user['username'], 'Updated', "Accommodation Record ID: $record_id", $user['campus']);
            } else {
                $error_msg = 'Failed to update record';
            }
            $stmt->close();
        } else {
            // Insert - use form campus and office
            $stmt = $db->prepare("INSERT INTO tblaccommodation (Campus, Office, YearTransact, Month, Quarter, TravellerName, TravelPurpose, TravelDateFrom, TravelDateTo, Country, TravelType, NumOccupiedRoom, NumNightPerRoom, Factor, GHGEmissionKGC02e, GHGEmissionTC02e) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssssssiiddd", $form_campus, $form_office, $year, $month, $quarter, $traveller_name, $travel_purpose, $travel_date_from, $travel_date_to, $country, $travel_type, $num_rooms, $num_nights, $factor, $ghg_kg, $ghg_t);
            if ($stmt->execute()) {
                $message = 'Record added successfully';
                Helper::logActivity($db, $user['username'], 'Created', "Accommodation Record for $traveller_name", $user['campus']);
            } else {
                $error_msg = 'Failed to add record';
            }
            $stmt->close();
        }

        // Refresh records
        $stmt = $db->prepare($query);
        if (!empty($filter['params'])) {
            $types = str_repeat('s', count($filter['params']));
            $stmt->bind_param($types, ...$filter['params']);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $records = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// Handle delete
if (isset($_GET['delete_id']) && $can_input) {
    $delete_id = (int)$_GET['delete_id'];
    $stmt = $db->prepare("DELETE FROM tblaccommodation WHERE id = ? AND Campus = ?");
    $stmt->bind_param("is", $delete_id, $user['campus']);
    if ($stmt->execute()) {
        $message = 'Record deleted successfully';
        Helper::logActivity($db, $user['username'], 'Deleted', "Accommodation Record ID: $delete_id", $user['campus']);
    }
    $stmt->close();
    header("Location: accommodation_v2.php");
    exit;
}
?>

                    <div class="max-w-7xl">
                        <div class="mb-6">
                            <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                                <i class="fas fa-hotel text-purple-600 mr-3"></i>
                                Accommodation Records
                            </h1>
                            <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($user['campus']); ?> • <?php echo htmlspecialchars($user['office']); ?></p>
                        </div>

                        <?php if ($message): ?>
                            <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded">
                                <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($error_msg): ?>
                            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded">
                                <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error_msg); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($can_input): ?>
                        <!-- Emission Factors Reference -->
                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                            <h3 class="text-sm font-semibold text-blue-800 mb-2">
                                <i class="fas fa-info-circle mr-2"></i>Accommodation Emission Factors Reference
                            </h3>
                            <div class="text-xs text-blue-700 space-y-1">
                                <p><strong>Calculation Formula:</strong> GHG Emission = Rooms × Nights × Emission Factor</p>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-2">
                                    <div class="bg-white p-2 rounded">
                                        <div class="font-semibold">Budget Hotel</div>
                                        <div>12 kg CO₂e/room-night</div>
                                    </div>
                                    <div class="bg-white p-2 rounded">
                                        <div class="font-semibold">3-Star Hotel</div>
                                        <div>25 kg CO₂e/room-night</div>
                                    </div>
                                    <div class="bg-white p-2 rounded">
                                        <div class="font-semibold">4-Star Hotel</div>
                                        <div>35 kg CO₂e/room-night</div>
                                    </div>
                                    <div class="bg-white p-2 rounded">
                                        <div class="font-semibold">5-Star/Luxury</div>
                                        <div>50 kg CO₂e/room-night</div>
                                    </div>
                                </div>
                                <p class="mt-2"><em>Note: These are standard emission factors. Select "Custom Factor" if you have specific data for the accommodation.</em></p>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">
                                <i class="fas fa-plus-circle text-purple-600 mr-2"></i>Add New Accommodation Record
                            </h2>
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="record_id" id="record_id" value="">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Campus <span class="text-red-500">*</span></label>
                                        <input type="text" name="campus" id="campus" value="<?php echo htmlspecialchars($user['campus']); ?>" readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed focus:ring-2 focus:ring-purple-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Office <span class="text-red-500">*</span></label>
                                        <input type="text" name="office" id="office" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Year <span class="text-red-500">*</span></label>
                                        <input type="number" name="year" id="year" required min="2000" max="2100" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                                        <select name="month" id="month" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
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
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Quarter</label>
                                        <select name="quarter" id="quarter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                            <option value="">Select Quarter</option>
                                            <option value="Q1">Q1 (Jan-Mar)</option>
                                            <option value="Q2">Q2 (Apr-Jun)</option>
                                            <option value="Q3">Q3 (Jul-Sep)</option>
                                            <option value="Q4">Q4 (Oct-Dec)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Traveller Name <span class="text-red-500">*</span></label>
                                        <input type="text" name="traveller_name" id="traveller_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Travel Purpose <span class="text-red-500">*</span></label>
                                        <input type="text" name="travel_purpose" id="travel_purpose" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Travel Date From <span class="text-red-500">*</span></label>
                                        <input type="date" name="travel_date_from" id="travel_date_from" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Travel Date To <span class="text-red-500">*</span></label>
                                        <input type="date" name="travel_date_to" id="travel_date_to" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Country <span class="text-red-500">*</span></label>
                                        <input type="text" name="country" id="country" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Travel Type <span class="text-red-500">*</span></label>
                                        <select name="travel_type" id="travel_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                            <option value="">Select Travel Type</option>
                                            <option value="Local">Local</option>
                                            <option value="International">International</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Number of Rooms <span class="text-red-500">*</span></label>
                                        <input type="number" name="num_rooms" id="num_rooms" required min="1" onchange="calculateGHG()" oninput="calculateGHG()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nights per Room <span class="text-red-500">*</span></label>
                                        <input type="number" name="num_nights" id="num_nights" required min="1" onchange="calculateGHG()" oninput="calculateGHG()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Hotel Type / Emission Factor <span class="text-red-500">*</span></label>
                                        <select name="factor" id="factor" required onchange="calculateGHG()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                            <option value="">Select Hotel Type</option>
                                            <option value="12">Budget Hotel (12 kg CO₂e/room-night)</option>
                                            <option value="25">3-Star Hotel (25 kg CO₂e/room-night)</option>
                                            <option value="35">4-Star Hotel (35 kg CO₂e/room-night)</option>
                                            <option value="50">5-Star/Luxury (50 kg CO₂e/room-night)</option>
                                            <option value="other">Custom Factor</option>
                                        </select>
                                    </div>
                                    <div id="custom_factor_div" style="display:none;">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Custom Factor (kg CO₂e/room-night)</label>
                                        <input type="number" id="custom_factor" step="0.01" onchange="calculateGHG()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">GHG Emission (kg CO₂e) <span class="text-red-500">*</span></label>
                                        <input type="number" name="ghg_kg" id="ghg_kg" required step="0.01" readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 focus:ring-2 focus:ring-purple-500">
                                        <p class="text-xs text-gray-500 mt-1">Auto-calculated: Rooms × Nights × Factor</p>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                                        <i class="fas fa-save mr-2"></i>Save Record
                                    </button>
                                    <button type="button" onclick="resetForm()" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                        <i class="fas fa-redo mr-2"></i>Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>

                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                <h2 class="text-lg font-semibold text-gray-800">
                                    <i class="fas fa-list mr-2"></i>Accommodation Records
                                </h2>
                                <div class="flex items-center gap-2">
                                    <label class="text-sm text-gray-600">Show</label>
                                    <select id="accommodation-per-page" class="border border-gray-300 rounded px-2 py-1 text-sm">
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
                                    <table class="w-full" id="accommodation-table">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Campus</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Office</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Year</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Quarter</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Traveller</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Purpose</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date From</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date To</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Country</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Rooms</th>
                                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Nights</th>
                                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Factor</th>
                                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">GHG (kg)</th>
                                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">GHG (t)</th>
                                                <?php if ($can_input): ?>
                                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                                                <?php endif; ?>
                                            </tr>
                                            <tr class="bg-gray-100">
                                                <th class="px-3 py-2"><input type="text" class="filter-input w-full px-2 py-1 text-xs border rounded" data-col="0" placeholder="Search..."></th>
                                                <th class="px-3 py-2"><input type="text" class="filter-input w-full px-2 py-1 text-xs border rounded" data-col="1" placeholder="Search..."></th>
                                                <th class="px-3 py-2"><input type="text" class="filter-input w-full px-2 py-1 text-xs border rounded" data-col="2" placeholder="Search..."></th>
                                                <th class="px-3 py-2"><input type="text" class="filter-input w-full px-2 py-1 text-xs border rounded" data-col="3" placeholder="Search..."></th>
                                                <th class="px-3 py-2"><input type="text" class="filter-input w-full px-2 py-1 text-xs border rounded" data-col="4" placeholder="Search..."></th>
                                                <th class="px-3 py-2"><input type="text" class="filter-input w-full px-2 py-1 text-xs border rounded" data-col="5" placeholder="Search..."></th>
                                                <th class="px-3 py-2"><input type="text" class="filter-input w-full px-2 py-1 text-xs border rounded" data-col="6" placeholder="Search..."></th>
                                                <th class="px-3 py-2"><input type="text" class="filter-input w-full px-2 py-1 text-xs border rounded" data-col="5" placeholder="Search..."></th>
                                                <th class="px-3 py-2"><input type="text" class="filter-input w-full px-2 py-1 text-xs border rounded" data-col="6" placeholder="Search..."></th>
                                                <th class="px-3 py-2"><input type="text" class="filter-input w-full px-2 py-1 text-xs border rounded" data-col="7" placeholder="Search..."></th>
                                                <th class="px-3 py-2"><input type="text" class="filter-input w-full px-2 py-1 text-xs border rounded" data-col="8" placeholder="Search..."></th>
                                                <th class="px-3 py-2"><input type="text" class="filter-input w-full px-2 py-1 text-xs border rounded" data-col="9" placeholder="Search..."></th>
                                                <th class="px-3 py-2"><input type="text" class="filter-input w-full px-2 py-1 text-xs border rounded" data-col="10" placeholder="Search..."></th>
                                                <th class="px-3 py-2"><input type="text" class="filter-input w-full px-2 py-1 text-xs border rounded" data-col="11" placeholder="Search..."></th>
                                                <th class="px-3 py-2"><input type="text" class="filter-input w-full px-2 py-1 text-xs border rounded" data-col="12" placeholder="Search..."></th>
                                                <th class="px-3 py-2"><input type="text" class="filter-input w-full px-2 py-1 text-xs border rounded" data-col="13" placeholder="Search..."></th>
                                                <th class="px-3 py-2"><input type="text" class="filter-input w-full px-2 py-1 text-xs border rounded" data-col="14" placeholder="Search..."></th>
                                                <?php if ($can_input): ?>
                                                <th class="px-3 py-2"></th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <?php foreach ($records as $record): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-3 py-2 text-sm"><?php echo $record['id']; ?></td>
                                                <td class="px-3 py-2 text-sm"><?php echo htmlspecialchars($record['Campus']); ?></td>
                                                <td class="px-3 py-2 text-sm"><?php echo htmlspecialchars($record['Office']); ?></td>
                                                <td class="px-3 py-2 text-sm text-center"><?php echo $record['YearTransact']; ?></td>
                                                <td class="px-3 py-2 text-sm"><?php echo htmlspecialchars($record['Month'] ?? '-'); ?></td>
                                                <td class="px-3 py-2 text-sm"><?php echo htmlspecialchars($record['Quarter'] ?? '-'); ?></td>
                                                <td class="px-3 py-2 text-sm"><?php echo htmlspecialchars($record['TravellerName']); ?></td>
                                                <td class="px-3 py-2 text-sm"><?php echo htmlspecialchars($record['TravelPurpose']); ?></td>
                                                <td class="px-3 py-2 text-sm"><?php echo date('Y-m-d', strtotime($record['TravelDateFrom'])); ?></td>
                                                <td class="px-3 py-2 text-sm"><?php echo date('Y-m-d', strtotime($record['TravelDateTo'])); ?></td>
                                                <td class="px-3 py-2 text-sm"><?php echo htmlspecialchars($record['Country']); ?></td>
                                                <td class="px-3 py-2 text-sm"><?php echo htmlspecialchars($record['TravelType']); ?></td>
                                                <td class="px-3 py-2 text-sm text-center"><?php echo $record['NumOccupiedRoom']; ?></td>
                                                <td class="px-3 py-2 text-sm text-center"><?php echo $record['NumNightPerRoom']; ?></td>
                                                <td class="px-3 py-2 text-sm text-right"><?php echo number_format($record['Factor'], 2); ?></td>
                                                <td class="px-3 py-2 text-sm text-right font-semibold"><?php echo number_format($record['GHGEmissionKGC02e'], 2); ?></td>
                                                <td class="px-3 py-2 text-sm text-right"><?php echo number_format($record['GHGEmissionTC02e'], 2); ?></td>
                                                <?php if ($can_input): ?>
                                                <td class="px-3 py-2 text-center">
                                                    <button onclick='editRecord(<?php echo json_encode($record); ?>)' class="text-blue-600 hover:text-blue-900 mr-2">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="?delete_id=<?php echo $record['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Delete this record?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center">
                                    <div class="text-sm text-gray-600" id="accommodation-info"></div>
                                    <div class="flex gap-2" id="accommodation-pagination"></div>
                                </div>
                            <?php else: ?>
                                <div class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p>No accommodation records found</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

<script>
function calculateGHG() {
    const rooms = parseFloat(document.getElementById('num_rooms').value) || 0;
    const nights = parseFloat(document.getElementById('num_nights').value) || 0;
    const factorSelect = document.getElementById('factor');
    const customFactorDiv = document.getElementById('custom_factor_div');
    const customFactorInput = document.getElementById('custom_factor');
    
    let factor = 0;
    
    if (factorSelect.value === 'other') {
        customFactorDiv.style.display = 'block';
        factor = parseFloat(customFactorInput.value) || 0;
    } else {
        customFactorDiv.style.display = 'none';
        factor = parseFloat(factorSelect.value) || 0;
    }
    
    const ghg = rooms * nights * factor;
    document.getElementById('ghg_kg').value = ghg.toFixed(2);
}

function editRecord(record) {
    document.getElementById('record_id').value = record.id;
    document.getElementById('office').value = record.Office;
    document.getElementById('year').value = record.YearTransact;
    document.getElementById('month').value = record.Month || '';
    document.getElementById('quarter').value = record.Quarter || '';
    document.getElementById('traveller_name').value = record.TravellerName;
    document.getElementById('travel_purpose').value = record.TravelPurpose;
    document.getElementById('travel_date_from').value = record.TravelDateFrom;
    document.getElementById('travel_date_to').value = record.TravelDateTo;
    document.getElementById('country').value = record.Country;
    document.getElementById('travel_type').value = record.TravelType;
    document.getElementById('num_rooms').value = record.NumOccupiedRoom;
    document.getElementById('num_nights').value = record.NumNightPerRoom;
    
    // Set factor - check if it matches predefined values
    const factorValue = parseFloat(record.Factor);
    const factorSelect = document.getElementById('factor');
    const predefinedValues = [12, 25, 35, 50];
    
    if (predefinedValues.includes(factorValue)) {
        factorSelect.value = factorValue.toString();
    } else {
        factorSelect.value = 'other';
        document.getElementById('custom_factor_div').style.display = 'block';
        document.getElementById('custom_factor').value = factorValue;
    }
    
    document.getElementById('ghg_kg').value = record.GHGEmissionKGC02e;
    window.scrollTo({top: 0, behavior: 'smooth'});
}

function resetForm() {
    document.getElementById('record_id').value = '';
    document.querySelector('form').reset();
    document.getElementById('custom_factor_div').style.display = 'none';
    document.getElementById('ghg_kg').value = '';
    document.getElementById('campus').value = '<?php echo htmlspecialchars($user['campus']); ?>';
}

// Pagination and Filtering
const accommodationTable = {
    currentPage: 1,
    perPage: 25,
    filteredRows: []
};

function hasActiveFilters() {
    const filterInputs = document.querySelectorAll('.filter-input');
    return Array.from(filterInputs).some(input => input.value.trim() !== '');
}

function applyFilters() {
    const table = document.getElementById('accommodation-table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const filterInputs = document.querySelectorAll('.filter-input');
    
    const filters = {};
    filterInputs.forEach(input => {
        const col = input.dataset.col;
        const value = input.value.toLowerCase().trim();
        if (value) filters[col] = value;
    });
    
    accommodationTable.filteredRows = rows.filter(row => {
        const cells = row.querySelectorAll('td');
        return Object.keys(filters).every(col => {
            const cellText = cells[col]?.textContent.toLowerCase() || '';
            return cellText.includes(filters[col]);
        });
    });
    
    accommodationTable.currentPage = 1;
    displayPage();
}

function displayPage() {
    const table = document.getElementById('accommodation-table');
    const tbody = table.querySelector('tbody');
    const allRows = Array.from(tbody.querySelectorAll('tr'));
    
    const rowsToDisplay = hasActiveFilters() ? accommodationTable.filteredRows : allRows;
    const totalRows = rowsToDisplay.length;
    const startIndex = (accommodationTable.currentPage - 1) * accommodationTable.perPage;
    const endIndex = startIndex + accommodationTable.perPage;
    
    allRows.forEach(row => row.style.display = 'none');
    rowsToDisplay.slice(startIndex, endIndex).forEach(row => row.style.display = '');
    
    document.getElementById('accommodation-info').textContent = 
        `Showing ${totalRows > 0 ? startIndex + 1 : 0} to ${Math.min(endIndex, totalRows)} of ${totalRows} entries`;
    
    renderPagination(totalRows);
}

function renderPagination(totalRows) {
    const totalPages = Math.ceil(totalRows / accommodationTable.perPage);
    const paginationDiv = document.getElementById('accommodation-pagination');
    paginationDiv.innerHTML = '';
    
    if (totalPages <= 1) return;
    
    const prevBtn = document.createElement('button');
    prevBtn.textContent = 'Previous';
    prevBtn.className = 'px-3 py-1 border rounded ' + 
        (accommodationTable.currentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-50');
    prevBtn.disabled = accommodationTable.currentPage === 1;
    prevBtn.onclick = () => changePage(accommodationTable.currentPage - 1);
    paginationDiv.appendChild(prevBtn);
    
    const startPage = Math.max(1, accommodationTable.currentPage - 2);
    const endPage = Math.min(totalPages, accommodationTable.currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.textContent = i;
        pageBtn.className = 'px-3 py-1 border rounded ' + 
            (i === accommodationTable.currentPage ? 'bg-purple-600 text-white' : 'bg-white hover:bg-gray-50');
        pageBtn.onclick = () => changePage(i);
        paginationDiv.appendChild(pageBtn);
    }
    
    const nextBtn = document.createElement('button');
    nextBtn.textContent = 'Next';
    nextBtn.className = 'px-3 py-1 border rounded ' + 
        (accommodationTable.currentPage === totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-50');
    nextBtn.disabled = accommodationTable.currentPage === totalPages;
    nextBtn.onclick = () => changePage(accommodationTable.currentPage + 1);
    paginationDiv.appendChild(nextBtn);
}

function changePage(page) {
    const table = document.getElementById('accommodation-table');
    const tbody = table.querySelector('tbody');
    const rowsToDisplay = hasActiveFilters() ? accommodationTable.filteredRows : Array.from(tbody.querySelectorAll('tr'));
    const totalPages = Math.ceil(rowsToDisplay.length / accommodationTable.perPage);
    
    if (page < 1 || page > totalPages) return;
    
    accommodationTable.currentPage = page;
    displayPage();
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    displayPage();
    
    document.getElementById('accommodation-per-page').addEventListener('change', function() {
        accommodationTable.perPage = parseInt(this.value);
        accommodationTable.currentPage = 1;
        displayPage();
    });
    
    const filterInputs = document.querySelectorAll('.filter-input');
    filterInputs.forEach(input => {
        input.addEventListener('keyup', applyFilters);
    });
});
</script>

<?php require_once 'tailwind-footer.php'; ?>
