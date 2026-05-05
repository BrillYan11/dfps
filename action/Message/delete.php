<?php
include '../../includes/db.php';
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
$message_id = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);

if ($message_id) {
    // Verify the user is the sender (for "Unsend")
    $stmt = $conn->prepare("UPDATE messages SET is_deleted = 1 WHERE id = ? AND sender_id = ?");
    $stmt->bind_param("ii", $message_id, $user_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid message ID']);
}
exit;
