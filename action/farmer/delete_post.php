<?php
session_start();
include '../../includes/db.php';
include_once '../../includes/Logger.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method Not Allowed");
}

csrf_guard_ajax();

// Verify authentication and role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FARMER') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$farmer_id = $_SESSION['user_id'];
$post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);

if (!$post_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid Post ID']);
    exit;
}

// 1. Verify ownership and existence
$stmt = $conn->prepare("SELECT title FROM posts WHERE id = ? AND farmer_id = ? AND is_deleted = 0");
$stmt->bind_param("ii", $post_id, $farmer_id);
$stmt->execute();
$post = dfps_fetch_assoc($stmt);
$stmt->close();

if (!$post) {
    echo json_encode(['success' => false, 'error' => 'Post not found or already deleted']);
    exit;
}

// 2. Perform Soft Delete
$del_stmt = $conn->prepare("UPDATE posts SET is_deleted = 1 WHERE id = ?");
$del_stmt->bind_param("i", $post_id);

if ($del_stmt->execute()) {
    Logger::log($conn, $farmer_id, "Soft deleted product listing: " . $post['title']);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
}
$del_stmt->close();
exit;
