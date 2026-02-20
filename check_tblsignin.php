<?php
require_once 'includes/Database.php';
$db = (new Database())->getConnection();
$result = $db->query('DESCRIBE tblsignin');
echo "Table: tblsignin\n";
echo "Columns:\n";
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
