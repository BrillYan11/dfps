<?php
// universal_header.php - Consolidated header for all roles
require_once __DIR__ . '/url_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$url = static function (string $path = ''): string {
    if (function_exists('dfps_helper_url')) {
        return dfps_helper_url($path);
    }
    return '/';
};

$asset = static function (string $path): string {
    if (function_exists('dfps_helper_asset')) {
        return dfps_helper_asset($path);
    }
    return '/' . trim(str_replace('\\', '/', $path), '/');
};

// Define configuration based on role
$role = $_SESSION['role'] ?? 'GUEST';
$da_links = [
    ['url' => $url('da/'), 'icon' => 'bi-speedometer2', 'label' => 'Dashboard'],
    ['url' => $url('profile/'), 'icon' => 'bi-person-circle', 'label' => 'My Profile'],
    ['url' => $url('da/users'), 'icon' => 'bi-people', 'label' => 'Users Management'],
    ['url' => $url('da/listings'), 'icon' => 'bi-card-list', 'label' => 'Listings Overview'],
    ['url' => $url('da/reports'), 'icon' => 'bi-file-earmark-bar-graph', 'label' => 'System Reports'],
    ['url' => $url('da/message'), 'icon' => 'bi-chat-dots', 'label' => 'Messages'],
    ['url' => $url('da/produce'), 'icon' => 'bi-egg-fried', 'label' => 'Produce Master List'],
    ['url' => $url('da/resource_requests'), 'icon' => 'bi-file-earmark-text', 'label' => 'Resource Requests'],
    ['url' => $url('da/announcements'), 'icon' => 'bi-megaphone', 'label' => 'Announcements'],
    ['url' => $url('da/send_notification'), 'icon' => 'bi-broadcast', 'label' => 'Broadcast Alert'],
];

$config = [
    'FARMER' => [
        'primary_color' => '#28a745',
        'secondary_color' => '#218838',
        'title' => 'Farmer Dashboard',
        'brand' => 'DFPS Farmer',
        'menu_header' => 'Farmer Menu',
        'links' => [
            ['url' => $url('farmer/'), 'icon' => 'bi-speedometer2', 'label' => 'Dashboard'],
            ['url' => $url('buyer/'), 'icon' => 'bi-shop', 'label' => 'Marketplace'],
            ['url' => $url('profile/'), 'icon' => 'bi-person-circle', 'label' => 'My Profile'],
            ['url' => $url('farmer/add_post'), 'icon' => 'bi-plus-square', 'label' => 'Add New Post'],
            ['url' => $url('farmer/resource_requests'), 'icon' => 'bi-file-earmark-text', 'label' => 'Resource Requests'],
            ['url' => $url('farmer/message'), 'icon' => 'bi-chat-dots', 'label' => 'Messages'],
            ['url' => $url('farmer/notification'), 'icon' => 'bi-bell', 'label' => 'Notifications'],
        ]
    ],
    'DA' => [
        'primary_color' => '#1b5e20',
        'secondary_color' => '#2e7d32',
        'title' => 'DA Portal | Department of Agriculture',
        'brand' => 'DA Portal',
        'menu_header' => 'DA Menu',
        'links' => $da_links
    ],
    'DA_SUPER_ADMIN' => [
        'primary_color' => '#1a237e',
        'secondary_color' => '#283593',
        'title' => 'DA Super Admin Portal',
        'brand' => 'DA Super Admin',
        'menu_header' => 'Super Admin Menu',
        'links' => array_merge($da_links, [
            ['url' => $url('da/create_da'), 'icon' => 'bi-person-plus-fill', 'label' => 'DA Accounts Management'],
            ['url' => $url('da/backup'), 'icon' => 'bi-database-fill-gear', 'label' => 'Backup & Restore'],
        ])
    ],
    'BUYER' => [
        'primary_color' => '#007bff',
        'secondary_color' => '#0056b3',
        'title' => 'Buyer Dashboard',
        'brand' => 'DFPS',
        'menu_header' => 'Buyer Menu',
        'links' => [
            ['url' => $url('buyer/'), 'icon' => 'bi-shop', 'label' => 'Marketplace'],
            ['url' => $url('profile/'), 'icon' => 'bi-person-circle', 'label' => 'My Profile'],
            ['url' => $url('buyer/message'), 'icon' => 'bi-chat-dots', 'label' => 'Messages'],
            ['url' => $url('buyer/notification'), 'icon' => 'bi-bell', 'label' => 'Notifications'],
        ]
    ],
    'GUEST' => [
        'primary_color' => '#6c757d',
        'secondary_color' => '#5a6268',
        'title' => 'DFPS',
        'brand' => 'DFPS',
        'menu_header' => 'Menu',
        'links' => [
            ['url' => $url(), 'icon' => 'bi-house', 'label' => 'Home'],
        ]
    ]
];

$current_config = $config[$role] ?? $config['GUEST'];

// Fetch unread counts
$unread_notif_count = 0;
$unread_msg_count = 0;
if (isset($_SESSION['user_id']) && isset($conn)) {
    include_once __DIR__ . '/NotificationModel.php';
    $unread_notif_count = NotificationModel::countUnread($conn, $_SESSION['user_id']);
    
    if ($role === 'FARMER' || $role === 'BUYER' || $role === 'DA' || $role === 'DA_SUPER_ADMIN') {
        $msg_count_sql = "SELECT COUNT(m.id) as c FROM messages m JOIN conversation_participants cp ON m.conversation_id = cp.conversation_id WHERE cp.user_id = ? AND m.sender_id != ? AND m.read_at IS NULL AND m.is_deleted = 0";
        $msg_count_stmt = $conn->prepare($msg_count_sql);
        if ($msg_count_stmt) {
            $msg_count_stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
            $msg_count_stmt->execute();
            $msg_row = dfps_fetch_assoc($msg_count_stmt);
            $unread_msg_count = $msg_row['c'] ?? 0;
            $msg_count_stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo $current_config['title']; ?></title>

  <link rel="stylesheet" href="<?php echo $asset('bootstrap/css/bootstrap.css'); ?>">
  <link rel="stylesheet" href="<?php echo $asset('css/style.css'); ?>?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="<?php echo $asset('css/header.css'); ?>?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="<?php echo $asset('css/message.css'); ?>?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="<?php echo $asset('css/notification.css'); ?>?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="<?php echo $asset('css/auth.css'); ?>?v=<?php echo time(); ?>">
  <link rel="icon" type="image/svg+xml" href="<?php echo $asset('pic/image/Da_logo.svg'); ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

  <script src="<?php echo $asset('bootstrap/js/bootstrap.bundle.min.js'); ?>" defer></script>
  <script src="<?php echo $asset('js/realtime.js'); ?>?v=<?php echo time(); ?>" defer></script>
  
  <style>
    :root {
      --primary-color: <?php echo $current_config['primary_color']; ?>;
      --secondary-color: <?php echo $current_config['secondary_color']; ?>;
    }
    /* Hide Google Translate UI elements */
    .goog-te-banner-frame.skiptranslate, .goog-te-gadget-icon { display: none !important; }
    body { top: 0px !important; }
    .goog-te-gadget-simple { background-color: transparent !important; border: none !important; }
    #google_translate_element { display: none; }
    .skiptranslate > iframe { display: none !important; }
  </style>
</head>
<body>

<div id="google_translate_element"></div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="app-sidebar" id="appSidebar">
  <div class="sidebar-header">
    <h5 class="mb-0 fw-bold"><?php echo $current_config['menu_header']; ?></h5>
    <button type="button" class="btn-close btn-close-white" id="closeSidebar"></button>
  </div>
  <div class="sidebar-content">
    <?php foreach ($current_config['links'] as $link): ?>
    <a href="<?php echo $link['url']; ?>" class="sidebar-link">
      <i class="bi <?php echo $link['icon']; ?>"></i>
      <span><?php echo $link['label']; ?></span>
    </a>
    <?php endforeach; ?>
    <hr>
    <?php if (isset($_SESSION['user_id'])): ?>
    <a href="<?php echo $url('logout'); ?>" class="sidebar-link text-danger">
      <i class="bi bi-box-arrow-right"></i>
      <span>Logout</span>
    </a>
    <?php else: ?>
    <a href="<?php echo $url('login'); ?>" class="sidebar-link text-primary">
      <i class="bi bi-box-arrow-in-right"></i>
      <span>Login</span>
    </a>
    <?php endif; ?>
  </div>
</aside>

<header class="app-header">
  <div class="header-left">
    <button type="button" class="hamburger-btn" id="menuBtn">
      <i class="bi bi-list"></i>
    </button>
    <?php 
      $brand_url = $url();
      if ($role === 'FARMER') $brand_url = $url('farmer/');
      elseif ($role === 'BUYER') $brand_url = $url('buyer/');
      elseif (in_array($role, ['DA', 'DA_SUPER_ADMIN'])) $brand_url = $url('da/');
    ?>
    <a href="<?php echo $brand_url; ?>" class="app-title-link">
      <img src="<?php echo $asset('pic/image/Da_logo.svg'); ?>" alt="Logo" class="header-logo">
      <span class="app-title"><?php echo $current_config['brand']; ?></span>
    </a>
  </div>

  <div class="header-right">
    <!-- Blended Language Switcher Icon -->
    <div class="dropdown">
      <a href="#" class="header-item dropdown-toggle border-0" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-translate"></i>
        <span class="d-none d-md-block" id="current-lang-label">English</span>
      </a>
      <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
        <li><a class="dropdown-item" href="javascript:void(0);" onclick="changeLanguage('en', 'English')">English</a></li>
        <li><a class="dropdown-item" href="javascript:void(0);" onclick="changeLanguage('ceb', 'Bisaya')">Bisaya (Cebuano)</a></li>
      </ul>
    </div>

    <?php if ($role !== 'GUEST'): ?>
    <?php 
        if ($role === 'DA' || $role === 'DA_SUPER_ADMIN') $msg_url = $url('da/message');
        elseif ($role === 'FARMER') $msg_url = $url('farmer/message');
        else $msg_url = $url('buyer/message');
    ?>
    <a href="<?php echo $msg_url; ?>" class="header-item">
      <i class="bi bi-chat-dots"></i>
      <span class="d-none d-md-block">Message</span>
      <?php if ($unread_msg_count > 0): ?>
          <span class="badge rounded-pill bg-danger"><?php echo $unread_msg_count; ?></span>
      <?php endif; ?>
    </a>

    <?php 
      $notif_url = ($role === 'FARMER') ? $url('farmer/notification') : (($role === 'BUYER') ? $url('buyer/notification') : $url('da/notification'));
    ?>
    <a href="<?php echo $notif_url; ?>" class="header-item">
      <i class="bi bi-bell"></i>
      <span class="d-none d-md-block">Alerts</span>
      <?php if ($unread_notif_count > 0): ?>
          <span class="badge rounded-pill bg-danger"><?php echo $unread_notif_count; ?></span>
      <?php endif; ?>
    </a>
    <?php endif; ?>

    <?php if ($role === 'GUEST'): ?>
    <a href="<?php echo $url('login'); ?>" class="header-item"><i class="bi bi-box-arrow-in-right"></i><span class="d-none d-md-block">Login</span></a>
    <?php else: ?>
    <a href="<?php echo $url('logout'); ?>" class="header-item"><i class="bi bi-box-arrow-right"></i><span class="d-none d-md-block">Logout</span></a>
    <?php endif; ?>
  </div>
</header>

<script type="text/javascript">
window.DFPS_APP_ROOT = <?php echo json_encode(function_exists('dfps_helper_app_root') ? dfps_helper_app_root() : ''); ?>;

function googleTranslateElementInit() {
  new google.translate.TranslateElement({pageLanguage: 'en', includedLanguages: 'en,ceb', autoDisplay: false}, 'google_translate_element');
}

function changeLanguage(langCode, label) {
    var selectField = document.querySelector(".goog-te-combo");
    if (selectField) {
        selectField.value = langCode;
        selectField.dispatchEvent(new Event('change'));
        document.getElementById('current-lang-label').textContent = label;
        localStorage.setItem('dfps_lang', langCode);
        localStorage.setItem('dfps_lang_label', label);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var savedLang = localStorage.getItem('dfps_lang') || 'en';
    var savedLabel = localStorage.getItem('dfps_lang_label') || 'English';
    document.getElementById('current-lang-label').textContent = savedLabel;
    
    setTimeout(function() {
        if(savedLang !== 'en') changeLanguage(savedLang, savedLabel);
    }, 1500);

    const menuBtn = document.getElementById('menuBtn');
    const closeSidebar = document.getElementById('closeSidebar');
    const appSidebar = document.getElementById('appSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const appRoot = (window.DFPS_APP_ROOT || '').replace(/\/$/, '');
    const currentPath = window.location.pathname;
    const relativePath = appRoot && currentPath.startsWith(appRoot) ? currentPath.slice(appRoot.length) : currentPath;
    const pathSegments = relativePath.replace(/^\/+/, '').split('/').filter(Boolean);
    const currentSection = ['buyer', 'farmer', 'da', 'profile'].includes(pathSegments[0]) ? pathSegments[0] : '';

    const routeMap = {
      'index.php': currentSection ? currentSection + '/' : '',
      'add_post.php': 'farmer/add_post',
      'edit_post.php': 'farmer/edit_post',
      'message.php': currentSection ? currentSection + '/message' : 'buyer/message',
      'notification.php': currentSection ? currentSection + '/notification' : 'buyer/notification',
      'announcements.php': currentSection ? currentSection + '/announcements' : 'buyer/announcements',
      'view_post.php': 'buyer/view_post',
      'view_interests.php': 'farmer/view_interests',
      'users.php': 'da/users',
      'listings.php': 'da/listings',
      'reports.php': 'da/reports',
      'produce.php': 'da/produce',
      'send_notification.php': 'da/send_notification',
      'create_da.php': 'da/create_da',
      'backup.php': 'da/backup',
      'login.php': 'login',
      'register.php': 'register',
      'forgot_password.php': 'forgot_password',
      'reset_password.php': 'reset_password',
      'logout.php': 'logout'
    };

    function appUrl(path) {
      if (!path) {
        return appRoot || '/';
      }

      if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('mailto:') || path.startsWith('tel:') || path.startsWith('#') || path.startsWith('javascript:')) {
        return path;
      }

      if (path.startsWith(appRoot + '/')) {
        return path;
      }

      const appRootWithoutSlash = appRoot.replace(/^\/+/, '');
      if (appRootWithoutSlash && path.startsWith(appRootWithoutSlash + '/')) {
        return '/' + path.replace(/^\/+/, '');
      }

      if (path.startsWith('/')) {
        return path;
      }

      const normalized = path.replace(/^\.\/+/, '').replace(/^(\.\.\/)+/, '');
      const [pathOnly, suffix = ''] = normalized.split(/([?#].*)/, 2);

      // Preserve explicit nested PHP endpoints such as action/Notification/mark_read.php.
      if (pathOnly.includes('/')) {
        return (appRoot ? appRoot : '') + '/' + normalized.replace(/^\/+/, '');
      }

      const target = routeMap[pathOnly] || pathOnly.replace(/\.php$/, '');
      const finalTarget = suffix ? target + suffix : target;
      return (appRoot ? appRoot : '') + '/' + finalTarget.replace(/^\/+/, '');
    }

    document.querySelectorAll('a[href], form[action]').forEach(function(element) {
      const attr = element.tagName === 'FORM' ? 'action' : 'href';
      const value = element.getAttribute(attr);

      if (!value) {
        return;
      }

      // Leave any explicit path alone. Only rewrite simple route-like values.
      if (
        value.startsWith('/') ||
        value.startsWith('./') ||
        value.startsWith('../') ||
        value.startsWith('?') ||
        value.startsWith('#') ||
        value.startsWith('http://') ||
        value.startsWith('https://') ||
        value.startsWith('mailto:') ||
        value.startsWith('tel:') ||
        value.startsWith('javascript:')
      ) {
        return;
      }

      element.setAttribute(attr, appUrl(value));
    });

    function toggleSidebar() {
      appSidebar.classList.toggle('active');
      sidebarOverlay.classList.toggle('active');
    }

    if(menuBtn) menuBtn.addEventListener('click', toggleSidebar);
    if(closeSidebar) closeSidebar.addEventListener('click', toggleSidebar);
    if(sidebarOverlay) sidebarOverlay.addEventListener('click', toggleSidebar);
});
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

<!-- Global System Alert Modal -->
<div class="modal fade" id="systemAlertModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="alertTitle">Notification</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body py-4">
        <p class="mb-0 text-secondary" id="alertBody"></p>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
        <a href="#" id="alertLink" class="btn btn-primary rounded-pill px-4">View Details</a>
      </div>
    </div>
  </div>
</div>
