<?php
session_start();
include '../../includes/db.php';
include_once '../../includes/Logger.php';

header('Content-Type: application/json');

// Use CSRF guard for AJAX
csrf_guard_ajax();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DA', 'DA_SUPER_ADMIN'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit;
}

// Support both POST (new) and GET (legacy fallback, but now guarded by CSRF which mostly requires POST)
$method = $_SERVER['REQUEST_METHOD'];
$user_id = filter_input($method === 'POST' ? INPUT_POST : INPUT_GET, 'id', FILTER_VALIDATE_INT);
$status = filter_input($method === 'POST' ? INPUT_POST : INPUT_GET, 'status', FILTER_VALIDATE_INT);

if ($user_id !== null && $status !== null) {
    // Get user info for logging before update
    $user_info_stmt = $conn->prepare("SELECT first_name, last_name, role, is_active FROM users WHERE id = ?");
    $user_info_stmt->bind_param("i", $user_id);
    $user_info_stmt->execute();
    $target_user = dfps_fetch_assoc($user_info_stmt);
    $user_info_stmt->close();

    if (!$target_user) {
        echo json_encode(['success' => false, 'error' => 'User not found.']);
        exit;
    }

    $current_role = $_SESSION['role'];
    
    if ($current_role === 'DA_SUPER_ADMIN') {
        // Super Admin can toggle everyone EXCEPT other Super Admins
        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role != 'DA_SUPER_ADMIN'");
    } else {
        // Standard DA can only toggle non-administrative roles
        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role NOT IN ('DA', 'DA_SUPER_ADMIN')");
    }
    
    $stmt->bind_param("ii", $status, $user_id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $action_type = ($status == 1) ? "Activated" : "Deactivated";
            $log_msg = "$action_type user account: " . $target_user['first_name'] . " " . $target_user['last_name'] . " (" . $target_user['role'] . ")";
            Logger::log($conn, $_SESSION['user_id'], $log_msg);
            
            echo json_encode([
                'success' => true, 
                'message' => "User account successfully " . strtolower($action_type) . "."
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No changes made or insufficient permissions.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
}
exit;
