<?php
require_once 'includes/config.php';

echo "Current fuel_emissions table structure:\n";
echo str_repeat("=", 50) . "\n";

$result = $db->query('DESCRIBE fuel_emissions');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\nChecking if Year, Quarter, Month columns exist...\n";
$columns = ['Year', 'Quarter', 'Month'];
foreach ($columns as $col) {
    $check = $db->query("SHOW COLUMNS FROM fuel_emissions LIKE '$col'");
    echo "$col: " . ($check->num_rows > 0 ? "EXISTS" : "DOES NOT EXIST") . "\n";
}
?>
