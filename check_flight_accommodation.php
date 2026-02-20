<?php
require_once 'includes/Database.php';
$db = (new Database())->getConnection();

echo "=== FLIGHT TABLE ===\n";
$result = $db->query('DESCRIBE tblflight');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n=== ACCOMMODATION TABLE ===\n";
$result = $db->query('DESCRIBE tblaccommodation');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
