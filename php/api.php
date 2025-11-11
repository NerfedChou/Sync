<?php
$mysqli = new mysqli('mysql', 'accounting_user', 'accounting_pass_123', 'accounting_system');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "Database connected successfully!\n";

$result = $mysqli->query("SELECT * FROM companies");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "Company: " . $row['company_name'] . "\n";
    }
}

$result = $mysqli->query("SELECT * FROM admin");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "Admin: " . $row['name'] . "\n";
    }
}
?>