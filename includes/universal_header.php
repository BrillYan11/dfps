<?php
// universal_header.php - Consolidated header for all roles
require_once __DIR__ . '/url_helpers.php';
require_once __DIR__ . '/security.php';

// Internal helpers for the header
$url = static function (string $path = ''): string {
    return dfps_helper_url($path);
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
        'brand' => 'DFPS',
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
$sys_version = '1.1.1'; 

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
  <title><?php echo htmlspecialchars($current_config['title']); ?></title>

  <?php
    $base_path = rtrim(dfps_helper_url('/'), '/') . '/';
  ?>
  <base href="<?php echo htmlspecialchars($base_path); ?>">

  <link rel="stylesheet" href="bootstrap/css/bootstrap.css">
  <link rel="stylesheet" href="css/style.css?v=<?php echo $sys_version; ?>">
  <link rel="stylesheet" href="css/header.css?v=<?php echo $sys_version; ?>">
  <link rel="stylesheet" href="css/message.css?v=<?php echo $sys_version; ?>">
  <link rel="stylesheet" href="css/notification.css?v=<?php echo $sys_version; ?>">
  <link rel="stylesheet" href="css/auth.css?v=<?php echo $sys_version; ?>">

  <link rel="icon" type="image/svg+xml" href="<?php echo dfps_helper_url('pic/image/Da_logo.svg'); ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" crossorigin="anonymous">

  <script src="bootstrap/js/bootstrap.bundle.min.js" defer></script>
  <script src="js/realtime.js?v=<?php echo $sys_version; ?>" defer></script>

  <script>
    window.CSRF_TOKEN = '<?php echo get_csrf_token(); ?>';
    window.DFPS_APP_ROOT = '<?php echo rtrim(dfps_helper_app_root(), '/'); ?>';
  </script>

  <style>
    :root {
      --primary-color: <?php echo $current_config['primary_color']; ?>;
      --secondary-color: <?php echo $current_config['secondary_color']; ?>;
    }
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
    <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($current_config['menu_header']); ?></h5>
    <button type="button" class="btn-close btn-close-white" id="closeSidebar"></button>
  </div>
  <div class="sidebar-content">
    <?php foreach ($current_config['links'] as $link): ?>
    <a href="<?php echo htmlspecialchars($link['url']); ?>" class="sidebar-link">
      <i class="bi <?php echo $link['icon']; ?>"></i>
      <span><?php echo htmlspecialchars($link['label']); ?></span>
    </a>
    <?php endforeach; ?>
    <hr>
    <?php if (isset($_SESSION['user_id'])): ?>
    <a href="logout" class="sidebar-link text-danger">
      <i class="bi bi-box-arrow-right"></i>
      <span>Logout</span>
    </a>
    <?php else: ?>
    <a href="login" class="sidebar-link text-primary">
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
    <a href="<?php echo htmlspecialchars($brand_url); ?>" class="app-title-link">
      <img src="<?php echo dfps_helper_url('pic/image/Da_logo.svg'); ?>" alt="Logo" class="header-logo">        
      <span class="app-title"><?php echo htmlspecialchars($current_config['brand']); ?></span>
    </a>
  </div>

  <div class="header-right">
    <!-- Language Switcher -->
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
    <a href="<?php echo htmlspecialchars($msg_url); ?>" class="header-item">
      <i class="bi bi-chat-dots"></i>
      <span class="d-none d-md-block">Messages</span>
      <?php if ($unread_msg_count > 0): ?>
          <span class="badge rounded-pill bg-danger"><?php echo $unread_msg_count; ?></span>
      <?php endif; ?>
    </a>

    <?php
      $notif_url = ($role === 'FARMER') ? $url('farmer/notification') : (($role === 'BUYER') ? $url('buyer/notification') : $url('da/notification'));
    ?>
    <a href="<?php echo htmlspecialchars($notif_url); ?>" class="header-item">
      <i class="bi bi-bell"></i>
      <span class="d-none d-md-block">Alerts</span>
      <?php if ($unread_notif_count > 0): ?>
          <span class="badge rounded-pill bg-danger"><?php echo $unread_notif_count; ?></span>
      <?php endif; ?>
    </a>
    <?php endif; ?>

    <?php if ($role === 'GUEST'): ?>
    <a href="login" class="header-item"><i class="bi bi-box-arrow-in-right"></i><span class="d-none d-md-block">Login</span></a>
    <?php else: ?>
    <a href="logout" class="header-item"><i class="bi bi-box-arrow-right"></i><span class="d-none d-md-block">Logout</span></a>
    <?php endif; ?>
  </div>
</header>

<script type="text/javascript">
function googleTranslateElementInit() {
  new google.translate.TranslateElement({pageLanguage: 'en', includedLanguages: 'en,ceb', autoDisplay: false}, 'google_translate_element');
}

function changeLanguage(langCode, label) {
    document.getElementById('current-lang-label').textContent = label;
    localStorage.setItem('dfps_lang', langCode);
    localStorage.setItem('dfps_lang_label', label);

    var selectField = document.querySelector(".goog-te-combo");
    if (selectField) {
        selectField.value = langCode;
        selectField.dispatchEvent(new Event('change'));
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var savedLang = localStorage.getItem('dfps_lang') || 'en';
    var savedLabel = localStorage.getItem('dfps_lang_label') || 'English';

    document.getElementById('current-lang-label').textContent = savedLabel;

    function sync() {
        var selectField = document.querySelector(".goog-te-combo");
        if (selectField && savedLang !== 'en') {
            selectField.value = savedLang;
            selectField.dispatchEvent(new Event('change'));
            return true;
        }
        return !!selectField;
    }

    let attempts = 0;
    const interval = setInterval(() => {
        if (sync() || attempts > 30) clearInterval(interval);
        attempts++;
    }, 500);

    const menuBtn = document.getElementById('menuBtn');
    const closeSidebar = document.getElementById('closeSidebar');
    const appSidebar = document.getElementById('appSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
      if(appSidebar) appSidebar.classList.toggle('active');
      if(sidebarOverlay) sidebarOverlay.classList.toggle('active');
    }

    if(menuBtn) menuBtn.addEventListener('click', toggleSidebar);
    if(closeSidebar) closeSidebar.addEventListener('click', toggleSidebar);
    if(sidebarOverlay) sidebarOverlay.addEventListener('click', toggleSidebar);
});
</script>
<script type="text/javascript" src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

<div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-body p-4 text-center">
        <div class="mb-3">
          <i class="bi bi-question-circle text-warning" style="font-size: 3rem;"></i>
        </div>
        <h5 class="fw-bold mb-2" id="confirmTitle">Are you sure?</h5>
        <p class="text-secondary mb-4" id="confirmBody">Do you really want to proceed with this action?</p>
        <div class="d-flex gap-2 justify-content-center">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary rounded-pill px-4" id="confirmActionBtn">Confirm</button>
        </div>
      </div>
    </div>
  </div>
</div>

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
</body>
</html>