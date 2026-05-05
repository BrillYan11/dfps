<?php
include '../../includes/db.php';
include_once '../../includes/Logger.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method Not Allowed");
}

csrf_guard_ajax();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DA', 'DA_SUPER_ADMIN'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if ($id) {
    // 1. Check if produce is being used in any posts
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM posts WHERE produce_id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $usage_count = dfps_fetch_assoc($check_stmt)['count'] ?? 0;
    $check_stmt->close();

    if ($usage_count > 0) {
        // If used, don't hard delete.
        echo json_encode(['success' => false, 'error' => "Cannot delete produce because it is being used in $usage_count product posts. Deactivate it instead."]);
        exit;
    } else {
        // 2. Fetch name for logging before deletion
        $name_stmt = $conn->prepare("SELECT name FROM produce WHERE id = ?");
        $name_stmt->bind_param("i", $id);
        $name_stmt->execute();
        $produce = dfps_fetch_assoc($name_stmt);
        $name_stmt->close();

        if ($produce) {
            // 3. Perform soft delete
            $stmt = $conn->prepare("UPDATE produce SET is_deleted = 1, is_active = 0 WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                Logger::log($conn, $_SESSION['user_id'], "Soft deleted produce from master list: " . $produce['name']);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => "Error deleting produce: " . $conn->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'error' => 'Produce not found']);
        }
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
}
exit;
