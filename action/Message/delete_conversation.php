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
$conv_id = filter_input(INPUT_POST, 'conv_id', FILTER_VALIDATE_INT);

if ($conv_id) {
    // In a real "Delete for me" scenario, we might just remove the participant record
    // or mark it as deleted for that specific user.
    // For this implementation, we will remove the user from the conversation.
    
    $stmt = $conn->prepare("DELETE FROM conversation_participants WHERE conversation_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $conv_id, $user_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid conversation ID']);
}
exit;
