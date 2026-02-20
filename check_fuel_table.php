<?php
require_once 'includes/config.php';

echo "=== fuel_emissions TABLE STRUCTURE ===\n";
$result = $db->query("DESCRIBE fuel_emissions");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        printf("%-20s %-15s\n", $row['Field'], $row['Type']);
    }
}

$db->close();
?>
