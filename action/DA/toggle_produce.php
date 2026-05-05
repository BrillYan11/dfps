<?php
session_start();
include '../../includes/db.php';
include_once '../../includes/Logger.php';

header('Content-Type: application/json');
csrf_guard_ajax();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DA', 'DA_SUPER_ADMIN'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$id = filter_input($method === 'POST' ? INPUT_POST : INPUT_GET, 'id', FILTER_VALIDATE_INT);
$status = filter_input($method === 'POST' ? INPUT_POST : INPUT_GET, 'status', FILTER_VALIDATE_INT);

if ($id !== null && $status !== null) {
    // 1. Get name for logging
    $name_stmt = $conn->prepare("SELECT name FROM produce WHERE id = ?");
    $name_stmt->bind_param("i", $id);
    $name_stmt->execute();
    $produce = dfps_fetch_assoc($name_stmt);
    $name_stmt->close();
    
    if ($produce) {
        // 2. Toggle status
        $stmt = $conn->prepare("UPDATE produce SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $status, $id);
        if ($stmt->execute()) {
            $action = ($status == 1) ? "activated" : "deactivated";
            $msg = "Produce '" . $produce['name'] . "' has been $action.";
            Logger::log($conn, $_SESSION['user_id'], "Toggled produce status ($action): " . $produce['name']);
            echo json_encode(['success' => true, 'message' => $msg]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Produce not found.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
}
exit;
