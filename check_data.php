<?php
/**
 * Quick data check script
 * Visit: http://localhost/GHG_PHP/check_data.php
 */
require_once 'includes/config.php';

// Check each table for data
$tables = [
    'electricity_consumption' => 'SELECT COUNT(*) as count, SUM(mains) as total FROM electricity_consumption',
    'tblwater' => 'SELECT COUNT(*) as count, SUM(amount) as total FROM tblwater',
    'tbltreatedwater' => 'SELECT COUNT(*) as count, SUM(amount) as total FROM tbltreatedwater',
    'tbllpg' => 'SELECT COUNT(*) as count, SUM(amount) as total FROM tbllpg',
    'tblfoodwaste' => 'SELECT COUNT(*) as count, SUM(servings) as total FROM tblfoodwaste',
    'fuel_emissions' => 'SELECT COUNT(*) as count, SUM(amount) as total FROM fuel_emissions',
    'tblsolidwastesegregated' => 'SELECT COUNT(*) as count, SUM(IFNULL(green,0)+IFNULL(blue,0)+IFNULL(yellow,0)+IFNULL(red,0)) as total FROM tblsolidwastesegregated',
    'tblsolidwasteunsegregated' => 'SELECT COUNT(*) as count, SUM(amount) as total FROM tblsolidwasteunsegregated',
    'tblflight' => 'SELECT COUNT(*) as count, SUM(travelers) as total FROM tblflight',
    'tblaccommodation' => 'SELECT COUNT(*) as count, SUM(guests*nights) as total FROM tblaccommodation',
];

echo "<!DOCTYPE html>";
echo "<html><head><title>GHG Data Check</title>";
echo "<style>body{font-family:Arial;padding:20px;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#4CAF50;color:white;} tr:nth-child(even){background:#f2f2f2;}</style>";
echo "</head><body>";
echo "<h1>GHG Database Data Check</h1>";
echo "<table><thead><tr><th>Table</th><th>Record Count</th><th>Total Value</th><th>Status</th></tr></thead><tbody>";

foreach ($tables as $table => $query) {
    $result = $db->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $count = $row['count'] ?? 0;
        $total = $row['total'] ?? 0;
        $status = $count > 0 ? "<span style='color:green;'>✓ Has Data</span>" : "<span style='color:red;'>✗ Empty</span>";
        
        echo "<tr>";
        echo "<td><strong>$table</strong></td>";
        echo "<td>$count records</td>";
        echo "<td>" . number_format($total, 2) . "</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    } else {
        echo "<tr>";
        echo "<td><strong>$table</strong></td>";
        echo "<td colspan='3' style='color:red;'>Error: " . $db->error . "</td>";
        echo "</tr>";
    }
}

echo "</tbody></table>";
echo "<br><p><a href='index.php'>← Back to System</a></p>";
echo "</body></html>";
?>
