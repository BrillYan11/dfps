<?php
session_start();
include '../../includes/db.php';
include '../../includes/NotificationModel.php';
require_once '../../includes/url_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . dfps_helper_url('login'));
    exit;
}

$user_id = $_SESSION['user_id'];
$notification_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($notification_id) {
    // Dismiss the specific notification
    NotificationModel::dismissNotification($conn, $notification_id, $user_id);
}

// Determine the correct redirect path
$role = $_SESSION['role'];
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
