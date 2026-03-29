<?php
session_start();
include '../../includes/db.php';
include_once '../../includes/Logger.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DA', 'DA_SUPER_ADMIN'])) {
    header("Location: ../../login.php");
    exit;
}

$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$status = filter_input(INPUT_GET, 'status', FILTER_VALIDATE_INT);
$role_redirect = filter_input(INPUT_GET, 'role', FILTER_UNSAFE_RAW);

if ($user_id !== null && $status !== null) {
    // Get user info for logging before update
    $user_info_stmt = $conn->prepare("SELECT first_name, last_name, role FROM users WHERE id = ?");
    $user_info_stmt->bind_param("i", $user_id);
    $user_info_stmt->execute();
    $target_user = $user_info_stmt->get_result()->fetch_assoc();
    $user_info_stmt->close();

    $current_role = $_SESSION['role'];
    
    if ($current_role === 'DA_SUPER_ADMIN') {
        // Super Admin can toggle everyone EXCEPT other Super Admins
        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role != 'DA_SUPER_ADMIN'");
    } else {
        // Standard DA can only toggle non-administrative roles
        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role NOT IN ('DA', 'DA_SUPER_ADMIN')");
    }
    
    $stmt->bind_param("ii", $status, $user_id);
    if ($stmt->execute() && $stmt->affected_rows > 0 && $target_user) {
        $action_type = ($status == 1) ? "Activated" : "Deactivated";
        $log_msg = "$action_type user account: " . $target_user['first_name'] . " " . $target_user['last_name'] . " (" . $target_user['role'] . ")";
        Logger::log($conn, $_SESSION['user_id'], $log_msg);
    }
    $stmt->close();
}

$redirect_url = "../../da/users.php";
if ($role_redirect) {
    $redirect_url .= "?role=" . $role_redirect;
}

header("Location: " . $redirect_url);
exit;
