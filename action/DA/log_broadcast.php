<?php
session_start();
include '../../includes/db.php';
include_once '../../includes/Logger.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DA', 'DA_SUPER_ADMIN'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dispatch_type = filter_input(INPUT_POST, 'dispatch_type', FILTER_UNSAFE_RAW);
    $target_desc = filter_input(INPUT_POST, 'target_desc', FILTER_UNSAFE_RAW);
    $count = filter_input(INPUT_POST, 'count', FILTER_VALIDATE_INT);
    $errors = filter_input(INPUT_POST, 'errors', FILTER_VALIDATE_INT);
    $title = trim($_POST['title'] ?? '');

    $action = ($dispatch_type === 'system_alert') ? "Broadcast System Alert" : "Broadcast SMS";
    $details = "Sent to $count $target_desc. Errors: $errors." . ($title ? " Title: $title" : "");
    
    if (Logger::log($conn, $_SESSION['user_id'], $action, $details)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to log action.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
