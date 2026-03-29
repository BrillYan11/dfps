<?php
session_start();
include '../../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$view = filter_input(INPUT_GET, 'view', FILTER_UNSAFE_RAW) ?: 'active';
$search = filter_input(INPUT_GET, 'q', FILTER_UNSAFE_RAW) ?: '';
$is_archived_filter = ($view === 'archived') ? 1 : 0;

$where_clause = "cp_me.is_archived = ?";
$params = [$user_id, $user_id, $user_id, $is_archived_filter];
$types = "iiii";

if (!empty($search)) {
    $search_term = "%$search%";
    $where_clause .= " AND (
        other_user.first_name LIKE ? OR 
        other_user.last_name LIKE ? OR 
        EXISTS (SELECT 1 FROM messages WHERE conversation_id = c.id AND body LIKE ? AND is_deleted = 0)
    )";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

$conv_query = "
    SELECT
        c.id as conversation_id,
        other_user.id as participant_id,
        other_user.first_name,
        other_user.last_name,
        other_user.role as participant_role,
        other_user.profile_picture as participant_profile_picture,
        cp_me.is_archived,
        (SELECT body FROM messages WHERE conversation_id = c.id AND is_deleted = 0 ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
        (SELECT is_deleted FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_deleted,
        (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND sender_id != ? AND read_at IS NULL AND is_deleted = 0) as unread_count
    FROM conversations c
    JOIN conversation_participants cp_me ON c.id = cp_me.conversation_id AND cp_me.user_id = ?
    JOIN conversation_participants cp_other ON c.id = cp_other.conversation_id AND cp_other.user_id != ?
    JOIN users other_user ON cp_other.user_id = other_user.id
    WHERE $where_clause
    ORDER BY last_message_time DESC
";

$conv_stmt = $conn->prepare($conv_query);
$conv_stmt->bind_param($types, ...$params);
$conv_stmt->execute();
$conv_result = $conv_stmt->get_result();
$conversations = [];
while ($row = $conv_result->fetch_assoc()) {
    $conversations[] = $row;
}
$conv_stmt->close();

echo json_encode(['conversations' => $conversations]);
?>
