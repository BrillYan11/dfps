<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'BUYER' && $_SESSION['role'] !== 'FARMER')) {
    exit;
}

$search_term = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW) ?? '';
$filter_produce = filter_input(INPUT_GET, 'produce_id', FILTER_VALIDATE_INT);
$filter_area = filter_input(INPUT_GET, 'area_id', FILTER_VALIDATE_INT);
$min_price = filter_input(INPUT_GET, 'min_price', FILTER_VALIDATE_FLOAT);
$max_price = filter_input(INPUT_GET, 'max_price', FILTER_VALIDATE_FLOAT);
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Special filter for Farmer's own posts
$farmer_id = ($_SESSION['role'] === 'FARMER') ? $_SESSION['user_id'] : null;

$params = [];
$types = '';

// 1. Total Count Query
$count_query = "SELECT COUNT(*) as total FROM posts p WHERE p.is_deleted = 0";
$c_params = [];
$c_types = '';

if ($farmer_id) { 
    $count_query .= " AND p.farmer_id = ?"; 
    $c_params[] = $farmer_id;
    $c_types .= 'i';
} else { 
    $count_query .= " AND p.status = 'ACTIVE' AND p.status != 'ARCHIVED'"; 
}

if (!empty($search_term)) {
    $count_query .= " AND (p.title LIKE ?)";
    $c_params[] = "%" . $search_term . "%";
    $c_types .= 's';
}
if ($filter_produce) { 
    $count_query .= " AND p.produce_id = ?"; 
    $c_params[] = $filter_produce;
    $c_types .= 'i';
}
if ($filter_area) { 
    $count_query .= " AND p.area_id = ?"; 
    $c_params[] = $filter_area;
    $c_types .= 'i';
}
if ($min_price) { 
    $count_query .= " AND p.price >= ?"; 
    $c_params[] = $min_price;
    $c_types .= 'd';
}
if ($max_price) { 
    $count_query .= " AND p.price <= ?"; 
    $c_params[] = $max_price;
    $c_types .= 'd';
}

$total_rows = 0;
if (!empty($c_params)) {
    $c_stmt = $conn->prepare($count_query);
    $c_stmt->bind_param($c_types, ...$c_params);
    $c_stmt->execute();
    $c_res = dfps_fetch_assoc($c_stmt);
    $total_rows = (int)($c_res['total'] ?? 0);
    $c_stmt->close();
} else {
    $c_res = $conn->query($count_query)->fetch_assoc();
    $total_rows = (int)($c_res['total'] ?? 0);
}
$total_pages = ceil($total_rows / $limit);

// 2. Data Query
$base_query = "
    SELECT
        p.id, p.title, p.price, p.unit, p.quantity, p.status,
        pr.name AS produce_name,
        a.name AS area_name,
        u.first_name AS farmer_first_name,
        u.last_name AS farmer_last_name,
        (SELECT pi.file_path FROM post_images pi WHERE pi.post_id = p.id ORDER BY pi.id ASC LIMIT 1) AS image_path
    FROM posts p
    JOIN produce pr ON p.produce_id = pr.id
    JOIN users u ON p.farmer_id = u.id
    LEFT JOIN areas a ON p.area_id = a.id
    WHERE p.is_deleted = 0
";

if ($farmer_id) {
    $base_query .= " AND p.farmer_id = ?";
    $params[] = $farmer_id;
    $types .= 'i';
} else {
    $base_query .= " AND p.status = 'ACTIVE'";
}

if (!empty($search_term)) {
    $base_query .= " AND (p.title LIKE ? OR pr.name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR a.name LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
    $like_term = "%" . $search_term . "%";
    for($i=0; $i<6; $i++) { $params[] = $like_term; $types .= 's'; }
}

if ($filter_produce) { $base_query .= " AND p.produce_id = ?"; $params[] = $filter_produce; $types .= 'i'; }
if ($filter_area) { $base_query .= " AND p.area_id = ?"; $params[] = $filter_area; $types .= 'i'; }
if ($min_price) { $base_query .= " AND p.price >= ?"; $params[] = $min_price; $types .= 'd'; }
if ($max_price) { $base_query .= " AND p.price <= ?"; $params[] = $max_price; $types .= 'd'; }

$base_query .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($base_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$posts = dfps_fetch_all($stmt);
$stmt->close();

header('Content-Type: application/json');
echo json_encode([
    'posts' => $posts,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_rows' => $total_rows
    ]
]);
