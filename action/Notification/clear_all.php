<?php
include '../../includes/db.php';
include '../../includes/NotificationModel.php';
require_once '../../includes/url_helpers.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method Not Allowed");
}

csrf_guard_ajax();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Clear all notifications
NotificationModel::clearAllNotifications($conn, $user_id);

echo json_encode(['success' => true]);
exit;
