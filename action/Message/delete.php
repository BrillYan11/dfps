<?php
session_start();
include '../../includes/db.php';
require_once '../../includes/url_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . dfps_helper_url('login'));
    exit;
}

$user_id = $_SESSION['user_id'];
$message_id = filter_input(INPUT_GET, 'message_id', FILTER_VALIDATE_INT);
$conv_id = filter_input(INPUT_GET, 'conv_id', FILTER_VALIDATE_INT);
$role = $_SESSION['role'];

$is_ajax = filter_input(INPUT_GET, 'ajax', FILTER_VALIDATE_BOOLEAN);

if ($message_id) {
    // Verify the user is the sender (for "Unsend")
    $stmt = $conn->prepare("UPDATE messages SET is_deleted = 1 WHERE id = ? AND sender_id = ?");
    $stmt->bind_param("ii", $message_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

if ($is_ajax) {
    echo json_encode(['success' => true]);
    exit;
}

// Redirect back to message.php with the conversation ID
$redirect_path = (in_array($role, ['DA', 'DA_SUPER_ADMIN'])) ? dfps_helper_url('da/message') : ((strtoupper($role) === 'FARMER') ? dfps_helper_url('farmer/message') : dfps_helper_url('buyer/message'));
$redirect_url = $redirect_path . ($conv_id ? "?conv_id=" . $conv_id : "");

header("Location: " . $redirect_url);
exit;
