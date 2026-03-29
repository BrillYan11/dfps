<?php
session_start();
include '../includes/db.php';
include '../includes/NotificationModel.php';
include_once '../includes/Logger.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DA', 'DA_SUPER_ADMIN'])) {
    header("Location: ../login.php");
    exit;
}

$da_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$view = $_GET['view'] ?? 'notifications'; // Toggle between 'notifications' and 'logs'
$limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 50;

// Fetch data based on view
$notifications = [];
$recent_logs = [];

if ($view === 'logs' && $role === 'DA_SUPER_ADMIN') {
    $recent_logs = Logger::getRecentLogs($conn, $limit);
} else {
    $notifications = NotificationModel::getNotificationsForUser($conn, $da_id);
    $view = 'notifications'; // Fallback
}

function get_notification_icon($type) {
    switch ($type) {
        case 'SYSTEM_ALERT': return 'bi-exclamation-triangle-fill';
        case 'ANNOUNCEMENT': return 'bi-megaphone-fill';
        case 'NEW_MESSAGE': return 'bi-chat-dots-fill';
        default: return 'bi-info-circle-fill';
    }
}

include '../includes/universal_header.php';
?>

<link rel="stylesheet" href="../css/notification.css?v=<?php echo time(); ?>">
<style>
    .log-table-container {
        border-radius: 12px;
        overflow: hidden;
        background: #ffffff !important;
        border: 1px solid #dee2e6 !important;
    }
    .table-log {
        color: #000000 !important;
    }
    .table-log thead th {
        background-color: #e9ecef !important;
        color: #000000 !important;
        font-weight: 800 !important;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 15px 10px !important;
        border-bottom: 2px solid #adb5bd !important;
    }
    .table-log tbody td {
        color: #000000 !important;
        background-color: #ffffff !important;
        vertical-align: middle;
        padding: 12px 10px !important;
        border-bottom: 1px solid #eee !important;
    }
    .table-log tbody tr:hover td {
        background-color: #f8f9fa !important;
    }
    .log-details {
        font-size: 0.8rem;
        color: #333333 !important;
    }
    .action-badge {
        background-color: #f0f0f0;
        color: #333;
        border: 1px solid #ccc;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
    }
    .nav-pills .nav-link.active {
        background-color: var(--primary-color);
    }
    .nav-link { color: #495057; }
</style>

<main class="container-fluid px-4 my-3">
  <div class="row g-4">

    <!-- Sidebar Navigation -->
    <aside class="col-12 col-md-3 col-lg-2">
      <div class="panel p-3">
        <nav class="nav nav-pills flex-column">
          <h6 class="text-muted small fw-bold text-uppercase mb-3 px-2">System Menu</h6>
          <a class="nav-link mb-1 <?php echo ($view === 'notifications') ? 'active text-white' : ''; ?>" href="notification.php">
            <i class="bi bi-bell me-2"></i>Alerts
          </a>
          <?php if ($role === 'DA_SUPER_ADMIN'): ?>
          <a class="nav-link mb-1 <?php echo ($view === 'logs') ? 'active text-white' : ''; ?>" href="notification.php?view=logs">
            <i class="bi bi-journal-text me-2"></i>Activity Log
          </a>
          <?php endif; ?>
          <hr>
          <a class="nav-link mb-1" href="index.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
          <a class="nav-link mb-1" href="announcements.php"><i class="bi bi-megaphone me-2"></i>Announcements</a>
          <a class="nav-link mb-1" href="users.php"><i class="bi bi-people me-2"></i>User Management</a>
        </nav>
      </div>
    </aside>

    <!-- Main Content Area -->
    <section class="col-12 col-md-9 col-lg-10">
      <div class="panel p-3">
        
        <?php if ($view === 'logs'): ?>
            <!-- ACTIVITY LOG VIEW -->
            <div class="d-flex align-items-center justify-content-between mb-4">
              <h4 class="mb-0 fw-bold"><i class="bi bi-journal-text me-2 text-primary"></i>System Activity Log</h4>
              <div class="d-flex align-items-center">
                  <label class="me-2 small text-muted d-none d-sm-block">Display:</label>
                  <select class="form-select form-select-sm border-0 bg-light shadow-none" style="width: auto;" onchange="window.location.href='notification.php?view=logs&limit=' + this.value">
                      <option value="10" <?php echo ($limit == 10) ? 'selected' : ''; ?>>10 entries</option>
                      <option value="25" <?php echo ($limit == 25) ? 'selected' : ''; ?>>25 entries</option>
                      <option value="50" <?php echo ($limit == 50) ? 'selected' : ''; ?>>50 entries</option>
                      <option value="100" <?php echo ($limit == 100) ? 'selected' : ''; ?>>100 entries</option>
                  </select>
                  <span class="badge bg-light text-dark border ms-2">Total shown: <?php echo count($recent_logs); ?></span>
              </div>
            </div>

            <div class="table-responsive log-table-container border">
              <table class="table table-hover table-log mb-0">
                <thead>
                  <tr>
                    <th class="ps-3">Timestamp</th>
                    <th>Administrator</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th class="pe-3 text-end">IP Address</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($recent_logs)): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">No logs recorded yet.</td></tr>
                  <?php else: ?>
                    <?php foreach ($recent_logs as $log): ?>
                      <tr>
                        <td class="ps-3 small text-muted"><?php echo date('M j, Y | g:i a', strtotime($log['created_at'])); ?></td>
                        <td>
                          <div class="fw-bold"><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></div>
                          <div class="small text-muted" style="font-size: 0.7rem;"><?php echo $log['role']; ?></div>
                        </td>
                        <td><span class="action-badge"><?php echo htmlspecialchars($log['action']); ?></span></td>
                        <td class="log-details"><?php echo htmlspecialchars($log['details'] ?: 'No extra details'); ?></td>
                        <td class="pe-3 text-end small font-monospace text-muted"><?php echo $log['ip_address']; ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

        <?php else: ?>
            <!-- NOTIFICATIONS VIEW (Default) -->
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h4 class="mb-0 fw-bold">System Alerts & Notifications</h4>
              <div class="dropdown">
                  <button class="btn btn-sm btn-light rounded-circle border" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
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
                    <p class="mt-2">You have no system notifications.</p>
                </div>
              <?php else: ?>
                <?php foreach ($notifications as $notif): 
                    if ($notif['type'] === 'NEW_MESSAGE') continue;
                    $view_link = !empty($notif['link']) ? '../action/Notification/mark_read.php?id=' . $notif['id'] . '&redirect=' . urlencode($notif['link']) : '#';
                ?>
                    <div class="notification-item <?php echo !$notif['is_read'] ? 'notification-unread' : ''; ?>" data-id="<?php echo $notif['id']; ?>">
                        <div class="notification-icon">
                            <i class="bi <?php echo get_notification_icon($notif['type']); ?>"></i>
                        </div>
                        <div class="notification-content">
                            <h6 class="mb-0"><?php echo htmlspecialchars($notif['title']); ?></h6>
                            <p class="mb-0 small"><?php echo htmlspecialchars($notif['body']); ?></p>
                            <span class="time"><?php echo date('M j, g:i a', strtotime($notif['created_at'])); ?></span>
                        </div>
                        <div class="notification-actions">
                            <a href="<?php echo $view_link; ?>" class="btn btn-sm btn-primary <?php echo empty($notif['link']) ? 'disabled' : ''; ?>">View</a>
                            <a href="../action/Notification/dismiss.php?id=<?php echo $notif['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Dismiss"><i class="bi bi-x"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
        <?php endif; ?>

      </div>
    </section>

  </div>
</main>

<?php include '../includes/universal_footer.php'; ?>
