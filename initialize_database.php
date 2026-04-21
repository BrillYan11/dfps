<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/db.php';

echo "<h2>DFPS Database Initialization</h2>";

$tables = [
    "areas" => "CREATE TABLE IF NOT EXISTS areas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "users" => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        address TEXT,
        barangay VARCHAR(255),
        phone VARCHAR(20),
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(50) NOT NULL,
        area_id INT,
        is_active TINYINT(1) DEFAULT 1,
        profile_picture VARCHAR(255) DEFAULT NULL,
        bio TEXT DEFAULT NULL,
        additional_details TEXT DEFAULT NULL,
        reset_token VARCHAR(64) DEFAULT NULL,
        token_expires DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL
    )",
    "produce" => "CREATE TABLE IF NOT EXISTS produce (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        unit VARCHAR(20) NOT NULL,
        srp DECIMAL(10,2) DEFAULT 0.00,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "posts" => "CREATE TABLE IF NOT EXISTS posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        farmer_id INT NOT NULL,
        produce_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        quantity DECIMAL(10,2) NOT NULL,
        unit VARCHAR(20) NOT NULL,
        area_id INT,
        status ENUM('active', 'sold', 'cancelled') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (produce_id) REFERENCES produce(id) ON DELETE CASCADE,
        FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL
    )",
    "post_images" => "CREATE TABLE IF NOT EXISTS post_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
    )",
    "post_interests" => "CREATE TABLE IF NOT EXISTS post_interests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        buyer_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "announcements" => "CREATE TABLE IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        da_id INT NOT NULL,
        area_id INT DEFAULT NULL,
        title VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (da_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL
    )",
    "notifications" => "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        body TEXT,
        link VARCHAR(255),
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "conversations" => "CREATE TABLE IF NOT EXISTS conversations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "conversation_participants" => "CREATE TABLE IF NOT EXISTS conversation_participants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conversation_id INT NOT NULL,
        user_id INT NOT NULL,
        is_archived TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "messages" => "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conversation_id INT NOT NULL,
        sender_id INT NOT NULL,
        body TEXT NOT NULL,
        iv VARBINARY(16) DEFAULT NULL,
        is_deleted TINYINT(1) DEFAULT 0,
        read_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "system_logs" => "CREATE TABLE IF NOT EXISTS system_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(255) NOT NULL,
        details TEXT DEFAULT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "resource_requests" => "CREATE TABLE IF NOT EXISTS resource_requests (
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
    )"
];

foreach ($tables as $name => $sql) {
    if ($conn->query($sql)) {
        echo "Table '$name' created successfully or already exists.<br>";
    } else {
        echo "Error creating table '$name': " . $conn->error . "<br>";
    }
}

// Ensure at least one Area exists for the Super Admin
$check_area = $conn->query("SELECT id FROM areas LIMIT 1");
if ($check_area->num_rows == 0) {
    $conn->query("INSERT INTO areas (name, description) VALUES ('DA Central Office', 'Main operating area')");
    echo "Default area created.<br>";
}

// Ensure at least one Super Admin exists
$check_super = $conn->query("SELECT id FROM users WHERE role = 'DA_SUPER_ADMIN' LIMIT 1");
if ($check_super->num_rows == 0) {
    $sa_first = "DA";
    $sa_last = "Super Admin";
    $sa_user = "superadmin";
    $sa_email = "superadmin@da.gov.ph";
    $sa_pass = "SuperAdmin123!";
    $sa_pass_hash = password_hash($sa_pass, PASSWORD_DEFAULT);
    
    $area_res = $conn->query("SELECT id FROM areas LIMIT 1");
    $area_id = $area_res->fetch_row()[0];

    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, email, address, password_hash, role, area_id, is_active) VALUES (?, ?, ?, ?, 'DA Central Office', ?, 'DA_SUPER_ADMIN', ?, 1)");
    $stmt->bind_param("sssssi", $sa_first, $sa_last, $sa_user, $sa_email, $sa_pass_hash, $area_id);

    if ($stmt->execute()) {
        echo "<br><strong>SUPER ADMIN CREATED:</strong><br>";
        echo "Email: $sa_email<br>";
        echo "Password: $sa_pass<br>";
    } else {
        echo "Error creating Super Admin: " . $stmt->error . "<br>";
    }
} else {
    echo "Super Admin account already exists.<br>";
}

echo "<br><a href='login.php'>Go to Login</a>";
?>
