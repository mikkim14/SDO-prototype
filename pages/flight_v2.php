<?php
$page_title = 'Flight Records';
require_once 'tailwind-header.php';

$message = '';
$error_msg = '';

$module = 'flight';
$can_input = AccessControl::canInput($user['office'], $module);
$filter = AccessControl::getGHGFilterClause($user['office'], $user['campus'], $module);

// Fetch all flight records
$where_sql = $filter['where_clause'] ?: 'WHERE 1=1';
$query = "SELECT * FROM tblflight $where_sql ORDER BY TravelDate DESC";
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
        $error_msg = 'Your office does not have permission to modify flight records.';
    } else {
        $record_id = $_POST['record_id'] ?? '';
        $campus = $_POST['campus'] ?? $user['campus'];
        $office = $_POST['office'] ?? '';
        $year = $_POST['year'] ?? '';
        $month = $_POST['month'] ?? '';
        $quarter = $_POST['quarter'] ?? '';
        $traveller_name = $_POST['traveller_name'] ?? '';
        $travel_purpose = $_POST['travel_purpose'] ?? '';
        $travel_date = $_POST['travel_date'] ?? '';
        $domestic_international = $_POST['domestic_international'] ?? '';
        $origin = $_POST['origin'] ?? '';
        $destination = $_POST['destination'] ?? '';
        $class = $_POST['class'] ?? '';
        $oneway_roundtrip = $_POST['oneway_roundtrip'] ?? '';
        $ghg_kg = floatval($_POST['ghg_kg'] ?? 0);
        $ghg_t = $ghg_kg / 1000;

        if ($record_id) {
            // Update
            $stmt = $db->prepare("UPDATE tblflight SET Office = ?, Year = ?, Month = ?, Quarter = ?, TravellerName = ?, TravelPurpose = ?, TravelDate = ?, DomesticInternational = ?, Origin = ?, Destination = ?, Class = ?, OnewayRoundTrip = ?, GHGEmissionKGC02e = ?, GHGEmissionTC02e = ? WHERE ID = ? AND Campus = ?");
            $stmt->bind_param("ssssssssssssddis", $office, $year, $month, $quarter, $traveller_name, $travel_purpose, $travel_date, $domestic_international, $origin, $destination, $class, $oneway_roundtrip, $ghg_kg, $ghg_t, $record_id, $campus);
            if ($stmt->execute()) {
                $message = 'Record updated successfully';
                Helper::logActivity($db, $user['username'], 'Updated', "Flight Record ID: $record_id", $user['campus']);
            } else {
                $error_msg = 'Failed to update record';
            }
            $stmt->close();
        } else {
            // Insert
            $stmt = $db->prepare("INSERT INTO tblflight (Campus, Office, Year, Month, Quarter, TravellerName, TravelPurpose, TravelDate, DomesticInternational, Origin, Destination, Class, OnewayRoundTrip, GHGEmissionKGC02e, GHGEmissionTC02e) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssssssdd", $campus, $office, $year, $month, $quarter, $traveller_name, $travel_purpose, $travel_date, $domestic_international, $origin, $destination, $class, $oneway_roundtrip, $ghg_kg, $ghg_t);
            if ($stmt->execute()) {
                $message = 'Record added successfully';
                Helper::logActivity($db, $user['username'], 'Created', "Flight Record for $traveller_name", $user['campus']);
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
    $stmt = $db->prepare("DELETE FROM tblflight WHERE ID = ? AND Campus = ?");
    $stmt->bind_param("is", $delete_id, $user['campus']);
    if ($stmt->execute()) {
        $message = 'Record deleted successfully';
        Helper::logActivity($db, $user['username'], 'Deleted', "Flight Record ID: $delete_id", $user['campus']);
    }
    $stmt->close();
    header("Location: flight_v2.php");
    exit;
}
?>

                    <div class="max-w-7xl">
                        <div class="mb-6">
                            <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                                <i class="fas fa-plane text-blue-600 mr-3"></i>
                                Flight Records
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
                        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">
                                <i class="fas fa-plus-circle text-blue-600 mr-2"></i>Add New Flight Record
                            </h2>
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="record_id" id="record_id" value="">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Campus <span class="text-red-500">*</span></label>
                                        <input type="text" name="campus" id="campus" value="<?php echo htmlspecialchars($user['campus']); ?>" readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Office <span class="text-red-500">*</span></label>
                                        <input type="text" name="office" id="office" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="e.g., Procurement, GSO">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Year <span class="text-red-500">*</span></label>
                                        <input type="number" name="year" id="year" required min="2000" max="2100" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                                        <select name="month" id="month" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
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
                                        <select name="quarter" id="quarter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                            <option value="">Select Quarter</option>
                                            <option value="Q1">Q1 (Jan-Mar)</option>
                                            <option value="Q2">Q2 (Apr-Jun)</option>
                                            <option value="Q3">Q3 (Jul-Sep)</option>
                                            <option value="Q4">Q4 (Oct-Dec)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Traveller Name <span class="text-red-500">*</span></label>
                                        <input type="text" name="traveller_name" id="traveller_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Travel Purpose <span class="text-red-500">*</span></label>
                                        <input type="text" name="travel_purpose" id="travel_purpose" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Travel Date <span class="text-red-500">*</span></label>
                                        <input type="date" name="travel_date" id="travel_date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
                                        <select name="domestic_international" id="domestic_international" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                            <option value="">Select</option>
                                            <option value="Domestic">Domestic</option>
                                            <option value="International">International</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Origin <span class="text-red-500">*</span></label>
                                        <input type="text" name="origin" id="origin" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Destination <span class="text-red-500">*</span></label>
                                        <input type="text" name="destination" id="destination" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Class <span class="text-red-500">*</span></label>
                                        <select name="class" id="class" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                            <option value="">Select</option>
                                            <option value="Economy">Economy</option>
                                            <option value="Business">Business</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Trip Type <span class="text-red-500">*</span></label>
                                        <select name="oneway_roundtrip" id="oneway_roundtrip" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                            <option value="">Select</option>
                                            <option value="One-way">One-way</option>
                                            <option value="Round-trip">Round-trip</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">GHG Emission (kg CO₂e) <span class="text-red-500">*</span></label>
                                        <input type="number" name="ghg_kg" id="ghg_kg" required step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="From ICAO Calculator">
                                        <p class="text-xs text-gray-500 mt-1">Calculate at: <a href="https://www.icao.int/environmental-protection/Carbonoffset/Pages/default.aspx" target="_blank" class="text-blue-600 hover:underline">ICAO Carbon Calculator</a></p>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
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
                                    <i class="fas fa-list mr-2"></i>Flight Records (<span id="flight-count"><?php echo count($records); ?></span>)
                                </h2>
                                <div class="flex items-center gap-2">
                                    <label class="text-sm text-gray-600">Rows per page:</label>
                                    <select id="flight-per-page" class="px-2 py-1 border rounded text-sm">
                                        <option value="10">10</option>
                                        <option value="25" selected>25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                            </div>
                            <?php if (count($records) > 0): ?>
                                <div class="overflow-x-auto">
                                    <table class="w-full" id="flight-table">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campus</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Office</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Year</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quarter</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Traveller Name</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Travel Purpose</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Travel Date</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Origin</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Destination</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trip Type</th>
                                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">GHG (kg CO₂e)</th>
                                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">GHG (t CO₂e)</th>
                                                <?php if ($can_input): ?>
                                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                                                <?php endif; ?>
                                            </tr>
                                            <tr class="bg-white">
                                                <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-col="0" placeholder="Search..."></th>
                                                <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-col="1" placeholder="Search..."></th>
                                                <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-col="2" placeholder="Search..."></th>
                                                <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-col="3" placeholder="Search..."></th>
                                                <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-col="4" placeholder="Search..."></th>
                                                <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-col="5" placeholder="Search..."></th>
                                                <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-col="6" placeholder="Search..."></th>
                                                <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-col="5" placeholder="Search..."></th>
                                                <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-col="6" placeholder="Search..."></th>
                                                <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-col="7" placeholder="Search..."></th>
                                                <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-col="8" placeholder="Search..."></th>
                                                <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-col="9" placeholder="Search..."></th>
                                                <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-col="10" placeholder="Search..."></th>
                                                <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-col="11" placeholder="Search..."></th>
                                                <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-col="12" placeholder="Search..."></th>
                                                <th class="px-2 py-1"><input type="text" class="w-full px-2 py-1 text-xs border rounded filter-input" data-col="13" placeholder="Search..."></th>
                                                <?php if ($can_input): ?>
                                                <th class="px-2 py-1"></th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <?php foreach ($records as $record): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($record['ID']); ?></td>
                                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($record['Campus']); ?></td>
                                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($record['Office']); ?></td>
                                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($record['Year']); ?></td>
                                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($record['Month'] ?? '-'); ?></td>
                                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($record['Quarter'] ?? '-'); ?></td>
                                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($record['TravellerName']); ?></td>
                                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($record['TravelPurpose']); ?></td>
                                                <td class="px-4 py-3 text-sm"><?php echo date('M d, Y', strtotime($record['TravelDate'])); ?></td>
                                                <td class="px-4 py-3 text-sm">
                                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $record['DomesticInternational'] === 'Domestic' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'; ?>">
                                                        <?php echo htmlspecialchars($record['DomesticInternational']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($record['Origin']); ?></td>
                                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($record['Destination']); ?></td>
                                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($record['Class']); ?></td>
                                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($record['OnewayRoundTrip']); ?></td>
                                                <td class="px-4 py-3 text-sm text-right font-semibold"><?php echo number_format($record['GHGEmissionKGC02e'], 2); ?></td>
                                                <td class="px-4 py-3 text-sm text-right"><?php echo number_format($record['GHGEmissionTC02e'], 4); ?></td>
                                                <?php if ($can_input): ?>
                                                <td class="px-4 py-3 text-center">
                                                    <button onclick='editRecord(<?php echo json_encode($record); ?>)' class="text-blue-600 hover:text-blue-900 mr-2">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="?delete_id=<?php echo $record['ID']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Delete this record?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="bg-gray-100 font-bold">
                                            <tr>
                                                <td colspan="14" class="px-4 py-3 text-sm text-right">Total GHG Emissions:</td>
                                                <td class="px-4 py-3 text-sm text-right"><?php echo number_format(array_sum(array_column($records, 'GHGEmissionKGC02e')), 2); ?> kg CO₂e</td>
                                                <td class="px-4 py-3 text-sm text-right"><?php echo number_format(array_sum(array_column($records, 'GHGEmissionTC02e')), 4); ?> t CO₂e</td>
                                                <?php if ($can_input): ?><td></td><?php endif; ?>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div class="px-6 py-4 border-t bg-gray-50 flex justify-between items-center">
                                    <div class="text-sm text-gray-600">
                                        Showing <span id="flight-start">1</span> to <span id="flight-end">25</span> of <span id="flight-total"><?php echo count($records); ?></span> entries
                                    </div>
                                    <div class="flex gap-2" id="flight-pagination"></div>
                                </div>
                            <?php else: ?>
                                <div class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p>No flight records found</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

<script>
function editRecord(record) {
    document.getElementById('record_id').value = record.ID;
    document.getElementById('campus').value = record.Campus;
    document.getElementById('office').value = record.Office;
    document.getElementById('year').value = record.Year;
    document.getElementById('month').value = record.Month || '';
    document.getElementById('quarter').value = record.Quarter || '';
    document.getElementById('traveller_name').value = record.TravellerName;
    document.getElementById('travel_purpose').value = record.TravelPurpose;
    document.getElementById('travel_date').value = record.TravelDate;
    document.getElementById('domestic_international').value = record.DomesticInternational;
    document.getElementById('origin').value = record.Origin;
    document.getElementById('destination').value = record.Destination;
    document.getElementById('class').value = record.Class;
    document.getElementById('oneway_roundtrip').value = record.OnewayRoundTrip;
    document.getElementById('ghg_kg').value = record.GHGEmissionKGC02e;
    window.scrollTo({top: 0, behavior: 'smooth'});
}

function resetForm() {
    document.getElementById('record_id').value = '';
    document.querySelector('form').reset();
}

// Pagination and filtering functionality
document.addEventListener('DOMContentLoaded', function() {
    const flightTable = { currentPage: 1, perPage: 25, totalRows: 0, filteredRows: [] };
    
    function hasActiveFilters() {
        const filterInputs = document.querySelectorAll('.filter-input');
        for (let input of filterInputs) {
            if (input.value.trim()) return true;
        }
        return false;
    }
    
    function applyFilters() {
        const table = document.getElementById('flight-table');
        if (!table) return;
        
        const tbody = table.getElementsByTagName('tbody')[0];
        const rows = tbody.getElementsByTagName('tr');
        const filterInputs = document.querySelectorAll('.filter-input');
        const filters = {};
        
        filterInputs.forEach(input => {
            const colIndex = parseInt(input.getAttribute('data-col'));
            const filterValue = input.value.toLowerCase().trim();
            if (filterValue) filters[colIndex] = filterValue;
        });
        
        flightTable.filteredRows = [];
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
            
            if (showRow) flightTable.filteredRows.push(rows[i]);
        }
        
        flightTable.currentPage = 1;
        displayPage();
    }
    
    function displayPage() {
        const table = document.getElementById('flight-table');
        if (!table) return;
        
        const tbody = table.getElementsByTagName('tbody')[0];
        const allRows = Array.from(tbody.getElementsByTagName('tr'));
        const filteredRows = hasActiveFilters() ? flightTable.filteredRows : allRows;
        
        allRows.forEach(row => row.style.display = 'none');
        
        const start = (flightTable.currentPage - 1) * flightTable.perPage;
        const end = Math.min(start + flightTable.perPage, filteredRows.length);
        
        for (let i = start; i < end; i++) {
            if (filteredRows[i]) filteredRows[i].style.display = '';
        }
        
        document.getElementById('flight-start').textContent = filteredRows.length > 0 ? start + 1 : 0;
        document.getElementById('flight-end').textContent = end;
        document.getElementById('flight-total').textContent = filteredRows.length;
        document.getElementById('flight-count').textContent = filteredRows.length;
        
        renderPagination(filteredRows.length);
    }
    
    function renderPagination(totalRows) {
        const totalPages = Math.ceil(totalRows / flightTable.perPage);
        const paginationDiv = document.getElementById('flight-pagination');
        
        let html = '';
        html += `<button onclick="changePage(${flightTable.currentPage - 1})" ${flightTable.currentPage === 1 ? 'disabled' : ''} class="px-3 py-1 border rounded text-sm ${flightTable.currentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-100'}">&laquo; Prev</button>`;
        
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= flightTable.currentPage - 2 && i <= flightTable.currentPage + 2)) {
                html += `<button onclick="changePage(${i})" class="px-3 py-1 border rounded text-sm ${i === flightTable.currentPage ? 'bg-blue-600 text-white font-semibold' : 'bg-white hover:bg-gray-100'}">${i}</button>`;
            } else if (i === flightTable.currentPage - 3 || i === flightTable.currentPage + 3) {
                html += `<span class="px-2 text-gray-500">...</span>`;
            }
        }
        
        html += `<button onclick="changePage(${flightTable.currentPage + 1})" ${flightTable.currentPage === totalPages || totalPages === 0 ? 'disabled' : ''} class="px-3 py-1 border rounded text-sm ${flightTable.currentPage === totalPages || totalPages === 0 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-100'}">Next &raquo;</button>`;
        
        paginationDiv.innerHTML = html;
    }
    
    window.changePage = function(page) {
        const table = document.getElementById('flight-table');
        if (!table) return;
        
        const tbody = table.getElementsByTagName('tbody')[0];
        const allRows = Array.from(tbody.getElementsByTagName('tr'));
        const totalRows = hasActiveFilters() ? flightTable.filteredRows.length : allRows.length;
        const totalPages = Math.ceil(totalRows / flightTable.perPage);
        
        if (page >= 1 && page <= totalPages) {
            flightTable.currentPage = page;
            displayPage();
        }
    };
    
    const select = document.getElementById('flight-per-page');
    if (select) {
        select.addEventListener('change', function() {
            flightTable.perPage = parseInt(this.value);
            flightTable.currentPage = 1;
            displayPage();
        });
    }
    
    const filterInputs = document.querySelectorAll('.filter-input');
    filterInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            applyFilters();
        });
    });
    
    if (document.getElementById('flight-table')) {
        displayPage();
    }
});
</script>

<?php require_once 'tailwind-footer.php'; ?>
