<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($conn)) {
    echo json_encode(['unread_count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];
$count = 0;

$sql = "
    SELECT COUNT(m.id) as unread_count
    FROM messages m
    JOIN conversation_participants cp ON m.conversation_id = cp.conversation_id
    WHERE cp.user_id = ? 
    AND m.sender_id != ? 
    AND m.read_at IS NULL
    AND m.is_deleted = 0
    AND cp.is_archived = 0
";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $row = dfps_fetch_assoc($stmt);
    if ($row) {
        $count = intval($row['unread_count'] ?? 0);
    }
    $stmt->close();
}

echo json_encode(['unread_count' => $count]);
?>
