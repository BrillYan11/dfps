<?php
include '../../includes/db.php';
include '../../includes/NotificationModel.php';
require_once '../../includes/url_helpers.php';

$method = $_SERVER['REQUEST_METHOD'];

if (!isset($_SESSION['user_id'])) {
    if ($method === 'POST') {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    } else {
        header("Location: " . dfps_helper_url('login'));
    }
    exit;
}

$user_id = $_SESSION['user_id'];

// Support both GET and POST for mark_read
if ($method === 'POST') {
    csrf_guard_ajax();
    $notification_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $redirect_url = $_POST['redirect'] ?? null;
} else {
    $notification_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $redirect_url = $_GET['redirect'] ?? null;
}

function build_notification_redirect(string $redirect_url, string $role): string
{
    $redirect_url = trim($redirect_url);
    if ($redirect_url === '') {
        return dfps_helper_url();
    }

    if (preg_match('#^(https?:)?//#i', $redirect_url) || str_starts_with($redirect_url, '/')) {
        return $redirect_url;
    }

    $parts = parse_url($redirect_url);
    $path = $parts['path'] ?? '';
    $query = isset($parts['query']) ? '?' . $parts['query'] : '';
    $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

    $path = str_replace('\\', '/', $path);
    $path = preg_replace('#^(\./)+#', '', $path);
    $path = preg_replace('#^(\.\./)+#', '', $path);
    $path = ltrim($path, '/');

    if ($path === '' || $path === 'index.php' || $path === 'index') {
        return dfps_helper_url() . $query . $fragment;
    }

    if (preg_match('#^(farmer|buyer|da|profile|action)(/|$)#i', $path)) {
        return dfps_helper_url($path) . $query . $fragment;
    }

    $role_prefix = '';
    switch ($role) {
        case 'BUYER':
            $role_prefix = 'buyer/';
            break;
        case 'FARMER':
            $role_prefix = 'farmer/';
            break;
        case 'DA':
        case 'DA_SUPER_ADMIN':
            $role_prefix = 'da/';
            break;
    }

    return dfps_helper_url($role_prefix . $path) . $query . $fragment;
}

if ($notification_id) {
    // Mark the specific notification as read
    NotificationModel::markAsRead($conn, $notification_id, $user_id);
}

// If it's an AJAX request (POST), return JSON
if ($method === 'POST') {
    echo json_encode(['success' => true]);
    exit;
}

// Redirect the user
if ($redirect_url) {
    $role = strtoupper($_SESSION['role'] ?? '');
    $final_redirect = build_notification_redirect($redirect_url, $role);
    header("Location: " . $final_redirect);
    exit;
} else {
    // Fallback redirect if no URL is provided
    $role = strtoupper($_SESSION['role'] ?? '');
    $fallback_path = dfps_helper_url();
    if ($role === 'BUYER') {
        $fallback_path = dfps_helper_url('buyer/notification');
    } elseif ($role === 'FARMER') {
        $fallback_path = dfps_helper_url('farmer/notification');
    } elseif ($role === 'DA' || $role === 'DA_SUPER_ADMIN') {
        $fallback_path = dfps_helper_url('da/notification');
    }
    header("Location: " . $fallback_path);
    exit;
}
?>
