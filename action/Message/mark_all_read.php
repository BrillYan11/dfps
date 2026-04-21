<?php
session_start();
include '../../includes/db.php';
require_once '../../includes/url_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . dfps_helper_url('login'));
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Mark all messages as read across all conversations for this user
// Messages where I am the recipient (sender_id != me) and I am a participant
$sql = "
    UPDATE messages m
    JOIN conversation_participants cp ON m.conversation_id = cp.conversation_id
    SET m.read_at = CURRENT_TIMESTAMP
    WHERE cp.user_id = ?
    AND m.sender_id != ?
    AND m.read_at IS NULL
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$stmt->close();

// Mark NEW_MESSAGE notifications as read too
$notif_sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND type = 'NEW_MESSAGE' AND is_read = 0";
$notif_stmt = $conn->prepare($notif_sql);
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notif_stmt->close();

$redirect_path = (strtoupper($role) === 'FARMER') ? dfps_helper_url('farmer/message') : dfps_helper_url('buyer/message');
if (in_array($role, ['DA', 'DA_SUPER_ADMIN'])) {
    $redirect_path = dfps_helper_url('da/message');
}
header("Location: " . $redirect_path);
exit;
?>
