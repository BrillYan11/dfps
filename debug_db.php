<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Database Debug Info</h3>";

$db_host = getenv('DB_HOST');
$db_port = getenv('DB_PORT');
$db_user = getenv('DB_USER');
$db_name = getenv('DB_NAME');

echo "Host: $db_host<br>";
echo "Port: $db_port<br>";
echo "User: $db_user<br>";
echo "Name: $db_name<br><br>";

include 'includes/db.php';

if ($conn->connect_error) {
    echo "CONNECTION FAILED: " . $conn->connect_error . "<br>";
} else {
    echo "CONNECTION SUCCESSFUL!<br>";
    
    $res = $conn->query("SHOW TABLES LIKE 'users'");
    if ($res->num_rows > 0) {
        echo "Table 'users' exists.<br>";
        
        $res = $conn->query("SELECT COUNT(*) FROM users");
        if ($res) {
            echo "User count: " . $res->fetch_row()[0] . "<br>";
        } else {
            echo "Error counting users: " . $conn->error . "<br>";
        }
    } else {
        echo "Table 'users' DOES NOT EXIST.<br>";
    }
}

echo "<br><hr>";
echo "PHP Version: " . phpversion() . "<br>";
echo "MySQLi Extension: " . (extension_loaded('mysqli') ? "Loaded" : "NOT LOADED") . "<br>";
echo "MySQLnd Driver: " . (extension_loaded('mysqlnd') ? "Loaded" : "NOT LOADED") . "<br>";
?>
