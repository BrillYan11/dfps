<?php
// includes/db.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = getenv('DB_HOST') ?: "localhost";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') ?: "";
$dbname = getenv('DB_NAME') ?: "dfps";
$port = getenv('DB_PORT') ?: 3306;

// Initialize mysqli
$conn = mysqli_init();

// Use SSL if connecting to a remote host (like Aiven)
if (getenv('DB_HOST') && getenv('DB_HOST') !== 'localhost' && getenv('DB_HOST') !== 'db') {
    mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
    $conn->real_connect($servername, $username, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL);
} else {
    // Standard connection for local/docker
    $conn->real_connect($servername, $username, $password, $dbname, $port);
}

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Set character set
$conn->set_charset("utf8mb4");

// --- SELF-HEALING: Ensure new messaging columns exist ---
if (!function_exists('table_exists')) {
    function table_exists($conn, $table) {
        $res = $conn->query("SHOW TABLES LIKE '$table'");
        return $res && $res->num_rows > 0;
    }
}

/**
 * Compatibility helper to replace fetch_all(MYSQLI_ASSOC)
 * which requires mysqlnd driver.
 */
if (!function_exists('dfps_fetch_all')) {
    function dfps_fetch_all($result) {
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }
}

if (table_exists($conn, 'conversation_participants')) {
    // Check for is_archived in conversation_participants
    $res = $conn->query("SHOW COLUMNS FROM conversation_participants LIKE 'is_archived'");
    if ($res->num_rows == 0) {
        $conn->query("ALTER TABLE conversation_participants ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0");
    }
}

if (table_exists($conn, 'messages')) {
    // Check for is_deleted in messages
    $res = $conn->query("SHOW COLUMNS FROM messages LIKE 'is_deleted'");
    if ($res->num_rows == 0) {
        $conn->query("ALTER TABLE messages ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0");
    }
}

if (table_exists($conn, 'produce')) {
    // Check for srp in produce
    $res = $conn->query("SHOW COLUMNS FROM produce LIKE 'srp'");
    if ($res->num_rows == 0) {
        $conn->query("ALTER TABLE produce ADD COLUMN srp DECIMAL(10,2) DEFAULT 0.00");
    }
}

if (table_exists($conn, 'users')) {
    // Check for reset_token in users
    $res = $conn->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
    if ($res->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) DEFAULT NULL");
    }

    // Check for token_expires in users
    $res = $conn->query("SHOW COLUMNS FROM users LIKE 'token_expires'");
    if ($res->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN token_expires DATETIME DEFAULT NULL");
    }

    // Check for barangay in users
    $res = $conn->query("SHOW COLUMNS FROM users LIKE 'barangay'");
    if ($res->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN barangay VARCHAR(255) DEFAULT NULL");
    }
}
?>
