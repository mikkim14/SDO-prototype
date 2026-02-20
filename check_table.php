<?php
require_once 'includes/config.php';

// Check for triggers
echo "=== DATABASE TRIGGERS ===\n";
$result = $db->query("SHOW TRIGGERS");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "\nTrigger: " . $row['Trigger'] . " on " . $row['Table'] . " (" . $row['Timing'] . " " . $row['Event'] . ")\n";
    }
} else {
    echo "No triggers found.\n";
}

// Check electricity - hardcoded calculation
echo "\n\n=== ELECTRICITY (should use hardcoded: consumption * 0.7264) ===\n";
$result = $db->query("SELECT campus, consumption, kg_co2_per_kwh FROM electricity_consumption WHERE consumption IS NOT NULL LIMIT 3");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $computed = $row['consumption'] * 0.7264;
        echo sprintf("Campus: %s, Consumption: %.2f kWh, DB: %.4f kg, Computed: %.4f kg, Match: %s\n", 
            $row['campus'], $row['consumption'], $row['kg_co2_per_kwh'], $computed,
            abs($row['kg_co2_per_kwh'] - $computed) < 0.01 ? 'YES' : 'NO');
    }
}

$db->close();
?>
