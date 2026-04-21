<?php
session_start();
include '../../includes/db.php';
require_once '../../includes/SMSModel.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DA', 'DA_SUPER_ADMIN'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $message = trim($_POST['message'] ?? '');

    if (!$user_id || empty($message)) {
        echo json_encode(['success' => false, 'error' => 'User ID and message are required.']);
        exit;
    }

    // Fetch user phone
    $stmt = $conn->prepare("SELECT phone, first_name, last_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || empty($user['phone'])) {
        echo json_encode(['success' => false, 'error' => 'User not found or has no phone number.']);
        exit;
    }

    // CRITICAL: Release session lock
    session_write_close();

    $res = SMSModel::sendSMS($user['phone'], $message);
    
    if ($res['success']) {
        echo json_encode(['success' => true, 'message' => 'SMS sent successfully to ' . $user['first_name'] . '.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'SMS Error: ' . ($res['error'] ?: 'Failed to send')]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
