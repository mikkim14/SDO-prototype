<?php
require_once 'includes/config.php';
require_once 'includes/Database.php';
require_once 'includes/AccessControl.php';

$database = new Database();
$db = $database->connect();

// Check user info for emu-lipa
echo "<h2>User Info for emu-lipa</h2>";
$stmt = $db->prepare("SELECT * FROM tblsignin WHERE username = ?");
$username = 'emu-lipa';
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
echo "<pre>";
print_r($user);
echo "</pre>";
$stmt->close();

// Check what's in the user session
echo "<h2>Session User Info</h2>";
echo "<pre>";
print_r($_SESSION['user'] ?? 'Not logged in');
echo "</pre>";

// Check ALL tables in database
echo "<h2>All Tables in Database</h2>";
$result = $db->query("SHOW TABLES");
echo "<ul>";
while ($row = $result->fetch_array()) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";

// Check all EMU table structures
$emu_tables = [
    'electricity_consumption',
    'tblwater',
    'tbltreatedwater',
    'tblsolidwastesegregated',
    'tblsolidwasteunsegregated'
];

foreach ($emu_tables as $table) {
    echo "<h2>$table Table Structure</h2>";
    $result = $db->query("DESCRIBE $table");
    echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
    }
    echo "</table><br>";
}

// Show all distinct campus values in electricity_consumption
echo "<h2>Distinct Campus Values in electricity_consumption</h2>";
$result = $db->query("SELECT DISTINCT campus, COUNT(*) as count FROM electricity_consumption GROUP BY campus");
echo "<table border='1'><tr><th>Campus</th><th>Count</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>" . htmlspecialchars($row['campus'] ?? 'NULL') . "</td><td>{$row['count']}</td></tr>";
}
echo "</table>";

// Check for Lipa campus specifically
echo "<h2>Records for 'Lipa' Campus</h2>";
$stmt = $db->prepare("SELECT COUNT(*) as count FROM electricity_consumption WHERE campus = ?");
$lipa = 'Lipa';
$stmt->bind_param("s", $lipa);
$stmt->execute();
$result = $stmt->get_result();
$count = $result->fetch_assoc()['count'];
echo "Count with 'Lipa': " . $count . "<br>";
$stmt->close();

// Check with lowercase
$stmt = $db->prepare("SELECT COUNT(*) as count FROM electricity_consumption WHERE campus = ?");
$lipa_lower = 'lipa';
$stmt->bind_param("s", $lipa_lower);
$stmt->execute();
$result = $stmt->get_result();
$count = $result->fetch_assoc()['count'];
echo "Count with 'lipa': " . $count . "<br>";
$stmt->close();

// Check other EMU tables
echo "<h2>Other EMU Tables Record Counts</h2>";
$tables = [
    'tblwater' => 'Water',
    'tbltreatedwater' => 'Treated Water',
    'tblsolidwastesegregated' => 'Waste Segregated',
    'tblsolidwasteunsegregated' => 'Waste Unsegregated'
];

echo "<table border='1'><tr><th>Table</th><th>Campus='Lipa'</th><th>Total Records</th></tr>";
foreach ($tables as $table => $name) {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM $table WHERE Campus = ?");
    $stmt->bind_param("s", $lipa);
    $stmt->execute();
    $result = $stmt->get_result();
    $count_lipa = $result->fetch_assoc()['count'];
    $stmt->close();
    
    $total = $db->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'];
    
    echo "<tr><td>$name</td><td>$count_lipa</td><td>$total</td></tr>";
}
echo "</table>";

// Test GHG Breakdown Calculation
echo "<h2>GHG Breakdown Test for Lipa Campus</h2>";
$breakdown = AccessControl::getGHGBreakdownByCategory($db, 'Lipa', null);
echo "<table border='1'><tr><th>Category</th><th>Records</th><th>Consumption</th><th>Unit</th><th>kg CO₂</th></tr>";
foreach ($breakdown as $key => $data) {
    echo "<tr>";
    echo "<td>{$data['name']}</td>";
    echo "<td>{$data['records']}</td>";
    echo "<td>" . number_format($data['consumption'], 2) . "</td>";
    echo "<td>{$data['unit']}</td>";
    echo "<td>" . number_format($data['kg_co2'], 2) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test GHG Totals
echo "<h2>GHG Totals for EMU-Lipa</h2>";
$ghg_stats = AccessControl::getRoleBasedGHGTotals($db, 'Environmental Management Unit', 'Lipa', null);
echo "<pre>";
print_r($ghg_stats);
echo "</pre>";
