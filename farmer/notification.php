<?php
session_start();
include '../includes/db.php';
include '../includes/NotificationModel.php';
require_once '../includes/url_helpers.php';

// Authentication and Authorization Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strtoupper($_SESSION['role']) !== 'FARMER') {
    header("Location: ../login.php");
    exit;
}

$farmer_id = $_SESSION['user_id'];

// Remove automatic markAllAsRead so users can see unread items darkened
// NotificationModel::markAllAsRead($conn, $farmer_id);

$notifications = NotificationModel::getNotificationsForUser($conn, $farmer_id);

function get_notification_icon($type) {
    switch ($type) {
        case 'NEW_MESSAGE': return 'bi-chat-dots-fill';
        case 'NEW_INTEREST': return 'bi-heart-fill';
        case 'POST_UPDATE': return 'bi-arrow-up-circle-fill';
        case 'ANNOUNCEMENT': return 'bi-megaphone-fill';
        default: return 'bi-info-circle-fill';
    }
}

include '../includes/universal_header.php';

$pageUrl = static function (string $path = ''): string {
    if (function_exists('dfps_url')) {
        return dfps_url($path);
    }
    $normalized = trim(str_replace('\\', '/', $path), '/');
    return $normalized === '' ? '/' : '/' . $normalized;
};

$assetUrl = static function (string $path): string {
    if (function_exists('dfps_asset')) {
        return dfps_asset($path);
    }
    return '/' . trim(str_replace('\\', '/', $path), '/');
};
?>

<link rel="stylesheet" href="<?php echo $assetUrl('css/notification.css'); ?>?v=<?php echo time(); ?>">

<main class="container-fluid px-4 my-3">
  <div class="row g-3">

    <!-- Sidebar -->
    <aside class="col-12 col-md-3 col-lg-2">
      <div class="panel h-100 p-3">
        <nav class="nav flex-column">
          <a class="nav-link" href="<?php echo $pageUrl('farmer/'); ?>"><i class="bi bi-card-list me-2"></i>My Products</a>
          <a class="nav-link" href="<?php echo $pageUrl('farmer/message'); ?>"><i class="bi bi-chat-dots me-2"></i>Messages</a>
        </nav>
      </div>
    </aside>

    <!-- Main Content -->
    <section class="col-12 col-md-9 col-lg-10">
      <div class="panel p-3">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h4 class="mb-0">Notifications</h4>
          <div class="dropdown">
              <button class="btn btn-sm btn-light rounded-circle" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
              <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item" href="../action/Notification/mark_all_read.php"><i class="bi bi-check-all me-2"></i>Mark all as read</a></li>
                  <li><a class="dropdown-item text-danger" href="../action/Notification/clear_all.php" onclick="return confirm('Clear all notifications?')"><i class="bi bi-trash3-fill me-2"></i>Clear all</a></li>
              </ul>
          </div>
        </div>

        <div class="notification-list">
          <?php if (empty($notifications)): ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-bell-slash" style="font-size: 2rem; opacity: 0.2;"></i>
                <p class="mt-2">You have no notifications.</p>
            </div>
          <?php else: ?>
            <?php foreach ($notifications as $notif): 
                // Skip rendering NEW_MESSAGE notifications
                if ($notif['type'] === 'NEW_MESSAGE') {
                    continue;
                }
                $has_link = !empty($notif['link']);
                $view_link = $has_link ? dfps_helper_url('action/Notification/mark_read.php') . '?id=' . $notif['id'] . '&redirect=' . urlencode($notif['link']) : 'javascript:void(0)';
            ?>
                <div class="notification-item <?php echo !$notif['is_read'] ? 'notification-unread' : ''; ?> clickable" 
                     data-id="<?php echo $notif['id']; ?>"
                     data-title="<?php echo htmlspecialchars($notif['title']); ?>"
                     data-body="<?php echo htmlspecialchars($notif['body']); ?>"
                     data-link="<?php echo $has_link ? $view_link : ''; ?>">
                    <div class="notification-icon">
                        <i class="bi <?php echo get_notification_icon($notif['type']); ?>"></i>
                    </div>
                    <div class="notification-content">
                        <h6 class="mb-0"><?php echo htmlspecialchars($notif['title']); ?></h6>
                        <p class="mb-0 text-truncate" style="max-width: 500px;"><?php echo htmlspecialchars($notif['body']); ?></p>
                        <span class="time"><?php echo date('M j, g:i a', strtotime($notif['created_at'])); ?></span>
                    </div>
                        <div class="notification-actions">
                            <?php if ($has_link): ?>
                                <a href="<?php echo $view_link; ?>" class="btn btn-sm btn-primary">View</a>
                            <?php endif; ?>
                        <a href="<?php echo dfps_helper_url('action/Notification/dismiss.php'); ?>?id=<?php echo $notif['id']; ?>" 
                           class="btn btn-sm btn-outline-secondary" 
                           title="Dismiss"
                           onclick="event.stopPropagation();">
                           <i class="bi bi-x"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

      </div>
    </section>

  </div>
</main>

<?php include '../includes/universal_footer.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Clear the notification badge instantly for the current user
        if (typeof updateNotificationBadge === 'function') {
            updateNotificationBadge();
        } else {
            const bellLinks = document.querySelectorAll('.header-item[href*="/notification"], .sidebar-link[href*="/notification"]');
            bellLinks.forEach(link => {
                const badge = link.querySelector('.badge');
                if (badge) badge.style.display = 'none';
            });
        }
    });
</script>
