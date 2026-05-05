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
$count_query = "SELECT COUNT(*) as total FROM users u WHERE 1=1";
$count_params = [];
$count_types = "";

if ($role_filter) {
    $count_query .= " AND u.role = ?";
    $count_params[] = $role_filter;
    $count_types .= "s";
}
if ($area_filter) {
    $count_query .= " AND u.area_id = ?";
    $count_params[] = $area_filter;
    $count_types .= "i";
}
if ($status_filter !== null && $status_filter !== '') {
    $is_active_val = ($status_filter === 'active') ? 1 : 0;
    $count_query .= " AND u.is_active = ?";
    $count_params[] = $is_active_val;
    $count_types .= "i";
}
if ($search) {
    $count_query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)";
    $search_param = "%$search%";
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_types .= "ssss";
}

$total_rows = 0;
$count_stmt = $conn->prepare($count_query);
if ($count_stmt) {
    if (!empty($count_params)) {
        $count_stmt->bind_param($count_types, ...$count_params);
    }
    $count_stmt->execute();
    $count_res = dfps_fetch_assoc($count_stmt);
    if ($count_res) {
        $total_rows = (int)($count_res['total'] ?? 0);
    }
    $count_stmt->close();
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
    $users = dfps_fetch_all($stmt);
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

