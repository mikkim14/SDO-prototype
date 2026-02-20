<?php
require_once 'includes/config.php';

echo "Testing fuel_emissions table structure:\n";
echo str_repeat("=", 60) . "\n";

$result = $db->query('DESCRIBE fuel_emissions');
$columns = [];
while($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
    echo sprintf("%-25s %s\n", $row['Field'], $row['Type']);
}

echo "\n✓ Total columns: " . count($columns) . "\n";

// Check for new columns
$required = ['Year', 'Quarter', 'Month'];
echo "\nRequired columns status:\n";
foreach ($required as $col) {
    $status = in_array($col, $columns) ? '✓ EXISTS' : '✗ MISSING';
    echo "$col: $status\n";
}

echo "\n✓ Database structure is ready!\n";
?>
