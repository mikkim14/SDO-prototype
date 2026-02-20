<?php
require_once 'includes/config.php';

echo "Adding Quarter column to tbllpg...\n";
$query1 = "ALTER TABLE tbllpg ADD COLUMN Quarter VARCHAR(10) AFTER Month";
if ($db->query($query1)) {
    echo "✓ Quarter column added to tbllpg\n";
} else {
    echo "✗ Error adding Quarter to tbllpg: " . $db->error . "\n";
}

echo "\nAdding Quarter column to tblfoodwaste...\n";
$query2 = "ALTER TABLE tblfoodwaste ADD COLUMN Quarter VARCHAR(10) AFTER Month";
if ($db->query($query2)) {
    echo "✓ Quarter column added to tblfoodwaste\n";
} else {
    echo "✗ Error adding Quarter to tblfoodwaste: " . $db->error . "\n";
}

echo "\n=== Verifying tbllpg columns ===\n";
$result = $db->query("DESCRIBE tbllpg");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-20s %-15s\n", $row['Field'], $row['Type']);
    }
}

echo "\n=== Verifying tblfoodwaste columns ===\n";
$result = $db->query("DESCRIBE tblfoodwaste");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-20s %-15s\n", $row['Field'], $row['Type']);
    }
}

$db->close();
?>
