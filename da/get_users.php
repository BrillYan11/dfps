<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DA', 'DA_SUPER_ADMIN'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$role_filter = filter_input(INPUT_GET, 'role', FILTER_UNSAFE_RAW);
$search = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW);
$area_filter = filter_input(INPUT_GET, 'area_id', FILTER_VALIDATE_INT);
$status_filter = filter_input(INPUT_GET, 'status', FILTER_UNSAFE_RAW);
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$params = [];
$types = "";

// 1. Total Count Query
$count_query = "SELECT COUNT(*) FROM users u WHERE 1=1";
if ($role_filter) {
    $count_query .= " AND u.role = '$role_filter'";
}
if ($area_filter) {
    $count_query .= " AND u.area_id = $area_filter";
}
if ($status_filter !== null && $status_filter !== '') {
    $is_active_val = ($status_filter === 'active') ? 1 : 0;
    $count_query .= " AND u.is_active = $is_active_val";
}
if ($search) {
    $search_safe = $conn->real_escape_string($search);
    $count_query .= " AND (u.first_name LIKE '%$search_safe%' OR u.last_name LIKE '%$search_safe%' OR u.email LIKE '%$search_safe%' OR u.username LIKE '%$search_safe%')";
}

$total_rows = 0;
$count_res = $conn->query($count_query);
if ($count_res) {
    $count_row = $count_res->fetch_row();
    $total_rows = (int)($count_row[0] ?? 0);
}
$total_pages = ceil($total_rows / $limit);

// 2. Data fetching for the list
$query = "
    SELECT u.*, a.name as area_name, 
           (SELECT COUNT(*) FROM posts WHERE farmer_id = u.id) as post_count
    FROM users u 
    LEFT JOIN areas a ON u.area_id = a.id 
    WHERE 1=1
";

if ($role_filter) {
    $query .= " AND u.role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

if ($area_filter) {
    $query .= " AND u.area_id = ?";
    $params[] = $area_filter;
    $types .= "i";
}

if ($status_filter !== null && $status_filter !== '') {
    $query .= " AND u.is_active = ?";
    $params[] = ($status_filter === 'active') ? 1 : 0;
    $types .= "i";
}

if ($search) {
    $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

$query .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $users = dfps_fetch_all($stmt->get_result());
    $stmt->close();
} else {
    $users = [];
}

header('Content-Type: application/json');
echo json_encode([
    'users' => $users,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_rows' => $total_rows
    ]
]);
