<?php
require_once 'includes/config.php';

echo "=== tbllpg COLUMNS ===\n";
$result = $db->query("DESCRIBE tbllpg");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-20s %-15s %-10s %-10s\n", 
            $row['Field'], $row['Type'], $row['Null'], $row['Key']);
    }
}

echo "\n=== tblfoodwaste COLUMNS ===\n";
$result = $db->query("DESCRIBE tblfoodwaste");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-20s %-15s %-10s %-10s\n", 
            $row['Field'], $row['Type'], $row['Null'], $row['Key']);
    }
}

$db->close();
?>
