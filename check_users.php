<?php
$servername = "localhost";
$username = "root";
$password = "";

// Check if dfps_db exists
$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$db_to_use = "dfps"; // Default from db.php
$result = $conn->query("SHOW DATABASES LIKE 'dfps_db'");
if ($result && $result->num_rows > 0) {
    $db_to_use = "dfps_db";
}

$conn->select_db($db_to_use);
echo "Using database: $db_to_use\n";

$sql = "SELECT COUNT(*) as farmer_count FROM users WHERE role = 'FARMER' AND phone IS NOT NULL AND phone != ''";
$result = $conn->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    echo "Count of users with role='FARMER' and non-empty 'phone': " . $row['farmer_count'] . "\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>
