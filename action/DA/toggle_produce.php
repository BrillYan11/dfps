<?php
session_start();
include '../../includes/db.php';
include_once '../../includes/Logger.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DA', 'DA_SUPER_ADMIN'])) {
    header("Location: ../../login.php");
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$status = filter_input(INPUT_GET, 'status', FILTER_VALIDATE_INT);

if ($id !== null && $status !== null) {
    // 1. Get name for logging
    $name_stmt = $conn->prepare("SELECT name FROM produce WHERE id = ?");
    $name_stmt->bind_param("i", $id);
    $name_stmt->execute();
    $produce = $name_stmt->get_result()->fetch_assoc();
    $name_stmt->close();

    if ($produce) {
        // 2. Toggle status
        $stmt = $conn->prepare("UPDATE produce SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $status, $id);
        if ($stmt->execute()) {
            $action = ($status == 1) ? "activated" : "deactivated";
            $_SESSION['success_message'] = "Produce '" . $produce['name'] . "' has been $action.";
            Logger::log($conn, $_SESSION['user_id'], "Toggled produce status ($action): " . $produce['name']);
        } else {
            $_SESSION['error_message'] = "Error toggling produce status: " . $conn->error;
        }
        $stmt->close();
    }
}

header("Location: ../../da/produce.php");
exit;
