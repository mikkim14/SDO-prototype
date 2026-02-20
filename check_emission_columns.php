<?php
$db = new mysqli('127.0.0.1', 'root', '', 'ghg_database');

$tables = [
    'electricity_consumption',
    'tblwater', 
    'tbltreatedwater',
    'tblsolidwastesegregated',
    'tblsolidwasteunsegregated',
    'tbllpg',
    'tblfoodwaste',
    'fuel_emissions',
    'tblflight',
    'tblaccommodation'
];

foreach ($tables as $table) {
    $result = $db->query("SHOW COLUMNS FROM $table");
    echo "\n=== $table ===\n";
    
    while ($row = $result->fetch_assoc()) {
        $field = $row['Field'];
        if (stripos($field, 'emission') !== false || 
            stripos($field, 'co2') !== false || 
            stripos($field, 'ghg') !== false) {
            echo "  - " . $field . " (" . $row['Type'] . ")\n";
        }
    }
}
?>
