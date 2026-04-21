<?php
session_start();
include '../../includes/db.php';
include_once '../../includes/Logger.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DA', 'DA_SUPER_ADMIN'])) {
    header("Location: ../../login.php");
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    // 1. Check if produce is being used in any posts
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM posts WHERE produce_id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $usage_count = $check_stmt->get_result()->fetch_assoc()['count'];
    $check_stmt->close();

    if ($usage_count > 0) {
        // If used, don't hard delete. Suggest deactivation instead.
        $_SESSION['error_message'] = "Cannot delete produce because it is being used in $usage_count product posts. Deactivate it instead to hide it from new listings.";
    } else {
        // 2. Fetch name for logging before deletion
        $name_stmt = $conn->prepare("SELECT name FROM produce WHERE id = ?");
        $name_stmt->bind_param("i", $id);
        $name_stmt->execute();
        $produce = $name_stmt->get_result()->fetch_assoc();
        $name_stmt->close();

        if ($produce) {
            // 3. Perform hard delete
            $stmt = $conn->prepare("DELETE FROM produce WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Produce '" . $produce['name'] . "' has been deleted successfully.";
                Logger::log($conn, $_SESSION['user_id'], "Deleted produce from master list: " . $produce['name']);
            } else {
                $_SESSION['error_message'] = "Error deleting produce: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

header("Location: ../../da/produce.php");
exit;
