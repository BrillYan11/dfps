<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../../includes/db.php';
include_once __DIR__ . '/../../includes/NotificationModel.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($conn)) {
    echo json_encode(['unread_count' => 0]);
    exit;
}

$unread_count = NotificationModel::countUnread($conn, $_SESSION['user_id']);
echo json_encode(['unread_count' => (int)$unread_count]);
?>
