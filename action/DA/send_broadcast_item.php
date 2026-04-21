<?php
session_start();
include '../../includes/db.php';
include '../../includes/NotificationModel.php';
require_once '../../includes/SMSModel.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DA', 'DA_SUPER_ADMIN'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$dispatch_type = filter_input(INPUT_POST, 'dispatch_type', FILTER_UNSAFE_RAW);
$title = trim($_POST['title'] ?? '');
$body = trim($_POST['body'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if (!$user_id || empty($body)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
    exit;
}

// CRITICAL: Release session lock to allow parallel processing (though we call them sequentially for SMS modem safety)
session_write_close();

if ($dispatch_type === 'system_alert') {
    if (NotificationModel::createNotification($conn, $user_id, 'SYSTEM_ALERT', $title, $body, 'notification.php')) {
        echo json_encode(['success' => true, 'message' => 'System alert sent.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create notification.']);
    }
} else if ($dispatch_type === 'sim_sms') {
    if (empty($phone)) {
        echo json_encode(['success' => false, 'error' => 'User has no phone number.']);
        exit;
    }

    $sms_body = (!empty($title) ? "[$title] " : "") . $body;
    $res = SMSModel::sendSMS($phone, $sms_body);
    
    if ($res['success']) {
        echo json_encode(['success' => true, 'message' => 'SMS sent.']);
    } else {
        echo json_encode(['success' => false, 'error' => $res['error'] ?: 'SMS failure.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid dispatch type.']);
}
