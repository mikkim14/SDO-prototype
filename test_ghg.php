<?php
require_once 'includes/config.php';
require_once 'includes/GHGCalculator.php';

// Test GHG calculations
$testConsumption = 1000;
$ghg = GHGCalculator::calculateAll($testConsumption);

echo "=== GHG Calculator Test ===\n";
echo "Input Consumption: $testConsumption kWh\n";
echo "Calculated kg CO2: " . $ghg['kg_co2'] . " kg\n";
echo "Calculated t CO2: " . $ghg['t_co2'] . " t\n";
echo "Tree Offset: " . $ghg['tree_offset'] . " trees\n";

// Check electricity records in DB
$stmt = $db->prepare("SELECT COUNT(*) as count FROM electricity_consumption");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

echo "\n=== Database Status ===\n";
echo "Total electricity records: " . $row['count'] . "\n";

// Check sample record with GHG data
$stmt = $db->prepare("SELECT campus, date, consumption, kg_co2_per_kwh, t_co2_per_kwh, tree_offset FROM electricity_consumption LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $record = $result->fetch_assoc();
    echo "\nSample Record:\n";
    echo "  Campus: " . $record['campus'] . "\n";
    echo "  Date: " . $record['date'] . "\n";
    echo "  Consumption: " . $record['consumption'] . " kWh\n";
    echo "  kg CO2: " . $record['kg_co2_per_kwh'] . " kg\n";
    echo "  t CO2: " . $record['t_co2_per_kwh'] . " t\n";
    echo "  Trees: " . $record['tree_offset'] . "\n";
} else {
    echo "\nNo electricity records found\n";
}
$stmt->close();

$db->close();
?>
