<?php
include 'includes/db.php';

// Add profile columns to users table
$user_columns = [
    'profile_picture' => "ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL",
    'bio' => "ALTER TABLE users ADD COLUMN bio TEXT DEFAULT NULL",
    'additional_details' => "ALTER TABLE users ADD COLUMN additional_details TEXT DEFAULT NULL",
    'reset_token' => "ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) DEFAULT NULL",
    'token_expires' => "ALTER TABLE users ADD COLUMN token_expires DATETIME DEFAULT NULL",
    'barangay' => "ALTER TABLE users ADD COLUMN barangay VARCHAR(255) DEFAULT NULL"
];

foreach ($user_columns as $name => $sql) {
    $res = $conn->query("SHOW COLUMNS FROM users LIKE '$name'");
    if ($res->num_rows == 0) {
        if ($conn->query($sql)) {
            echo "Column '$name' added successfully to 'users'.<br>";
        } else {
            echo "Error adding column '$name' to 'users': " . $conn->error . "<br>";
        }
    } else {
        echo "Column '$name' already exists in 'users'.<br>";
    }
}

// Check for messages table columns
$res = $conn->query("SHOW COLUMNS FROM messages LIKE 'iv'");
if ($res->num_rows == 0) {
    if ($conn->query("ALTER TABLE messages ADD COLUMN iv VARBINARY(16) DEFAULT NULL")) {
        echo "Column 'iv' added successfully to 'messages'.<br>";
    } else {
        echo "Error adding column 'iv' to 'messages': " . $conn->error . "<br>";
    }
} else {
    echo "Column 'iv' already exists in 'messages'.<br>";
}

// Add areas table if it doesn't exist
$create_areas_sql = "
CREATE TABLE IF NOT EXISTS areas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($create_areas_sql)) {
    echo "Table 'areas' created successfully or already exists.<br>";
} else {
    echo "Error creating table 'areas': " . $conn->error . "<br>";
}

// Ensure the 'role' column can hold 'DA_SUPER_ADMIN'
$alter_role_sql = "ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL";
if ($conn->query($alter_role_sql)) {
    echo "Column 'role' updated to VARCHAR(50) to support new role types.<br>";
} else {
    echo "Warning: Could not alter 'role' column: " . $conn->error . "<br>";
}

// --- NEW: System Logs Table ---
$create_logs_sql = "
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($create_logs_sql)) {
    echo "Table 'system_logs' created successfully or already exists.<br>";
} else {
    echo "Error creating table 'system_logs': " . $conn->error . "<br>";
}

// --- NEW: Resource Requests Table ---
$create_resource_requests_sql = "
CREATE TABLE IF NOT EXISTS resource_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    resource_type VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    description TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    remarks TEXT,
    processed_by INT,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
)";

if ($conn->query($create_resource_requests_sql)) {
    echo "Table 'resource_requests' created successfully or already exists.<br>";
} else {
    echo "Error creating table 'resource_requests': " . $conn->error . "<br>";
}

// Ensure at least one Super Admin exists
$check_super = $conn->query("SELECT id FROM users WHERE role = 'DA_SUPER_ADMIN' LIMIT 1");
if ($check_super->num_rows == 0) {
    // Default Super Admin credentials
    $sa_first = "DA";
    $sa_last = "Super Admin";
    $sa_user = "superadmin";
    $sa_email = "superadmin@da.gov.ph";
    $sa_pass = "SuperAdmin123!"; // Change immediately
    $sa_pass_hash = password_hash($sa_pass, PASSWORD_DEFAULT);
    
    // Assign to first area if exists
    $area_res = $conn->query("SELECT id FROM areas LIMIT 1");
    $area_id = ($area_res->num_rows > 0) ? $area_res->fetch_row()[0] : null;

    if ($area_id) {
        $sa_address = "DA Central Office";
        $sa_phone = "00000000000";
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, email, address, phone, password_hash, role, area_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 'DA_SUPER_ADMIN', ?, 1)");
        $stmt->bind_param("sssssssi", $sa_first, $sa_last, $sa_user, $sa_email, $sa_address, $sa_phone, $sa_pass_hash, $area_id);

        if ($stmt->execute()) {
            echo "<br><strong>SUPER ADMIN CREATED:</strong><br>";
            echo "Email: $sa_email<br>";
            echo "Password: $sa_pass<br>";
            echo "<em>Please change this password after logging in!</em><br>";
        } else {
            echo "Error creating Super Admin: " . $stmt->error . "<br>";
        }
        $stmt->close();
    } else {
        echo "<br>Warning: No areas found in database. Could not create Super Admin account.<br>";
    }
} else {
    echo "Super Admin account already exists.<br>";
}
?>
