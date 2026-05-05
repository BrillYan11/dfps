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
$action = filter_input(INPUT_POST, 'action', FILTER_UNSAFE_RAW) ?: 'archive'; // 'archive' or 'unarchive'

if ($conv_id) {
    $is_archived = ($action === 'archive') ? 1 : 0;
    $stmt = $conn->prepare("UPDATE conversation_participants SET is_archived = ? WHERE conversation_id = ? AND user_id = ?");
    $stmt->bind_param("iii", $is_archived, $conv_id, $user_id);
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
