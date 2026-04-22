<?php
session_start();
include '../../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DA', 'DA_SUPER_ADMIN'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$target_role = filter_input(INPUT_GET, 'target_role', FILTER_UNSAFE_RAW) ?: 'FARMER';
$target_area = filter_input(INPUT_GET, 'target_area', FILTER_VALIDATE_INT) ?: null;

$query = "SELECT id, phone, first_name, last_name FROM users WHERE is_active = 1";
$params = [];
$types = '';

if ($target_role === 'ALL') {
    $query .= " AND role IN ('FARMER', 'BUYER')";
} else {
    $query .= " AND role = ?";
    $params[] = $target_role;
    $types .= 's';
}

if ($target_area) {
    $query .= " AND area_id = ?";
    $params[] = $target_area;
    $types .= 'i';
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
$stmt->execute();
$users = dfps_fetch_all($stmt);
$stmt->close();

echo json_encode([
    'success' => true,
    'users' => $users,
    'count' => count($users)
]);
