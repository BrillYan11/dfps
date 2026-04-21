<?php
session_start();
include '../../includes/db.php';
include '../../includes/NotificationModel.php';
require_once '../../includes/url_helpers.php';

if (!isset($_SESSION['user_id'])) {
    // If user not logged in, do nothing or redirect to login
    header("Location: " . dfps_helper_url('login'));
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role']; // To redirect back to the correct notification page

// Mark all notifications as read
NotificationModel::markAllAsRead($conn, $user_id);

// Determine the correct redirect path
$redirect_path = dfps_helper_url(); // Default fallback
if ($role === 'BUYER') {
    $redirect_path = dfps_helper_url('buyer/notification');
} elseif ($role === 'FARMER') {
    $redirect_path = dfps_helper_url('farmer/notification');
} elseif (in_array($role, ['DA', 'DA_SUPER_ADMIN'])) {
    $redirect_path = dfps_helper_url('da/notification');
}

header("Location: " . $redirect_path);
exit;
