<?php
require_once 'includes/config.php';

echo "<h2>EMU Table Schemas</h2>";

// Check tbltreatedwater
echo "<h3>tbltreatedwater</h3>";
$result = $db->query("DESCRIBE tbltreatedwater");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table><br>";
}

// Check tblsolidwastesegregated
echo "<h3>tblsolidwastesegregated</h3>";
$result = $db->query("DESCRIBE tblsolidwastesegregated");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table><br>";
}

// Check tblsolidwasteunsegregated
echo "<h3>tblsolidwasteunsegregated</h3>";
$result = $db->query("DESCRIBE tblsolidwasteunsegregated");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table><br>";
}

// Check tbllpg
echo "<h3>tbllpg</h3>";
$result = $db->query("DESCRIBE tbllpg");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table><br>";
}

// Check tblfood
echo "<h3>tblfood</h3>";
$result = $db->query("DESCRIBE tblfood");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table><br>";
}

// Sample data from each table
echo "<h3>Sample Data</h3>";

echo "<h4>tbltreatedwater (5 records)</h4>";
$result = $db->query("SELECT * FROM tbltreatedwater LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
} else {
    echo "No records<br>";
}

echo "<h4>tblsolidwastesegregated (5 records)</h4>";
$result = $db->query("SELECT * FROM tblsolidwastesegregated LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
} else {
    echo "No records<br>";
}

echo "<h4>tblsolidwasteunsegregated (5 records)</h4>";
$result = $db->query("SELECT * FROM tblsolidwasteunsegregated LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
} else {
    echo "No records<br>";
}

$db->close();
?>
