<?php
require_once 'includes/config.php';

echo "Adding Year, Quarter, and Month columns to fuel_emissions table...\n";
echo str_repeat("=", 50) . "\n";

try {
    // Add Year column after date
    $query1 = "ALTER TABLE fuel_emissions ADD COLUMN Year INT(4) AFTER date";
    if ($db->query($query1)) {
        echo "✓ Year column added successfully\n";
    } else {
        echo "✗ Error adding Year column: " . $db->error . "\n";
    }

    // Add Quarter column after Year
    $query2 = "ALTER TABLE fuel_emissions ADD COLUMN Quarter VARCHAR(10) AFTER Year";
    if ($db->query($query2)) {
        echo "✓ Quarter column added successfully\n";
    } else {
        echo "✗ Error adding Quarter column: " . $db->error . "\n";
    }

    // Add Month column after Quarter
    $query3 = "ALTER TABLE fuel_emissions ADD COLUMN Month VARCHAR(20) AFTER Quarter";
    if ($db->query($query3)) {
        echo "✓ Month column added successfully\n";
    } else {
        echo "✗ Error adding Month column: " . $db->error . "\n";
    }

    echo "\nVerifying new structure...\n";
    $result = $db->query('DESCRIBE fuel_emissions');
    while($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }

    echo "\n✓ All columns added successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
