<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/pagination.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DA', 'DA_SUPER_ADMIN'])) {
    header("Location: ../login.php");
    exit;
}

$role_filter = filter_input(INPUT_GET, 'role', FILTER_UNSAFE_RAW);
$search = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW);
$area_filter = filter_input(INPUT_GET, 'area_id', FILTER_VALIDATE_INT);
$status_filter = filter_input(INPUT_GET, 'status', FILTER_UNSAFE_RAW);
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Fetch areas for filter
$areas_res = $conn->query("SELECT id, name FROM areas ORDER BY name ASC");
$areas_list = [];
if ($areas_res) {
    while ($row = $areas_res->fetch_assoc()) {
        $areas_list[] = $row;
    }
}

// 1. Analytics for the header
$total_farmers = 0;
$total_buyers = 0;
$active_accounts = 0;

$res1 = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'FARMER'");
if ($res1) $total_farmers = $res1->fetch_row()[0];

$res2 = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'BUYER'");
if ($res2) $total_buyers = $res2->fetch_row()[0];

$res3 = $conn->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
if ($res3) $active_accounts = $res3->fetch_row()[0];

// 2. Count total for pagination
$count_query = "SELECT COUNT(*) FROM users WHERE 1=1";
if ($role_filter) $count_query .= " AND role = '$role_filter'";
if ($area_filter) $count_query .= " AND area_id = $area_filter";
if ($status_filter !== null && $status_filter !== '') {
    $is_active_val = ($status_filter === 'active') ? 1 : 0;
    $count_query .= " AND is_active = $is_active_val";
}
if ($search) {
    $search_safe = $conn->real_escape_string($search);
    $count_query .= " AND (first_name LIKE '%$search_safe%' OR last_name LIKE '%$search_safe%' OR email LIKE '%$search_safe%' OR username LIKE '%$search_safe%')";
}
$total_rows = $conn->query($count_query)->fetch_row()[0];
$total_pages = ceil($total_rows / $limit);

// 3. Data fetching for the list
$query = "
    SELECT u.*, a.name as area_name, 
           (SELECT COUNT(*) FROM posts WHERE farmer_id = u.id) as post_count
    FROM users u 
    LEFT JOIN areas a ON u.area_id = a.id 
    WHERE 1=1
";
$params = [];
$types = "";

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
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = dfps_fetch_all($stmt->get_result());
$stmt->close();

include __DIR__ . '/../includes/universal_header.php';
?>

<style>
    .user-featured-header {
        background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 100%);
        color: #fff;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
    }
    
    .clear-search-btn {
        position: absolute;
        right: 45px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #6c757d;
        display: none;
        cursor: pointer;
        padding: 0 5px;
    }
    
    .clear-search-btn:hover {
        color: #dc3545;
    }
</style>

<main class="container-fluid px-4 my-4">
    <!-- Featured Analytics Header -->
    <div class="user-featured-header">
        <div class="row g-4 align-items-center">
            <div class="col-md-6">
                <h2 class="fw-bold mb-1">User Ecosystem Management</h2>
                <p class="opacity-75 mb-0">Overseeing all farmers and buyers to ensure a secure and stable marketplace.</p>
            </div>
            <div class="col-md-6">
                <div class="row g-3">
                    <div class="col-4">
                        <div class="bg-white bg-opacity-10 p-3 rounded-4 text-center">
                            <div class="h3 fw-bold mb-0"><?php echo number_format($total_farmers); ?></div>
                            <small class="opacity-75">Farmers</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="bg-white bg-opacity-10 p-3 rounded-4 text-center">
                            <div class="h3 fw-bold mb-0"><?php echo number_format($total_buyers); ?></div>
                            <small class="opacity-75">Buyers</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="bg-white bg-opacity-10 p-3 rounded-4 text-center">
                            <div class="h3 fw-bold mb-0 text-warning"><?php echo number_format($active_accounts); ?></div>
                            <small class="opacity-75">Active</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Management Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-0 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <h5 class="mb-0 fw-bold" id="tableTitle"><?php echo $role_filter ? ucfirst(strtolower($role_filter)) . 's' : 'All Marketplace Participants'; ?></h5>
            
            <div class="d-flex align-items-center gap-2">
                <!-- Filter Dropdown -->
                <div class="dropdown">
                  <button class="btn btn-light rounded-circle shadow-sm position-relative" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Filters" id="filterDropdownBtn">
                    <i class="bi bi-filter"></i>
                    <span id="filterBadge" class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle <?php echo ($area_filter || $status_filter) ? '' : 'd-none'; ?>"></span>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end p-3 shadow-lg border-0" style="width: 280px; border-radius: 15px;">
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                      <h6 class="fw-bold mb-0">Advanced Filters</h6>
                      <button type="button" id="resetFilters" class="btn btn-sm text-success p-0">Reset</button>
                    </div>
                    <form id="filterForm">
                      <div class="mb-3">
                        <label class="form-label small fw-bold">Account Status</label>
                        <select name="status" id="statusFilter" class="form-select form-select-sm">
                          <option value="">All Status</option>
                          <option value="active" <?php echo ($status_filter === 'active') ? 'selected' : ''; ?>>Active</option>
                          <option value="deactivated" <?php echo ($status_filter === 'deactivated') ? 'selected' : ''; ?>>Deactivated</option>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label class="form-label small fw-bold">Location / Area</label>
                        <select name="area_id" id="areaFilter" class="form-select form-select-sm">
                          <option value="">All Areas</option>
                          <?php foreach ($areas_list as $ar): ?>
                            <option value="<?php echo $ar['id']; ?>" <?php echo ($area_filter == $ar['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($ar['name']); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="d-grid mt-3">
                        <button type="button" id="applyFilters" class="btn btn-primary btn-sm rounded-pill">Apply Filters</button>
                      </div>
                    </form>
                  </div>
                </div>

                <!-- Search Form -->
                <div class="search-wrapper position-relative">
                    <input type="text" id="liveSearch" class="search-pill" 
                           placeholder="Search users..." 
                           value="<?php echo htmlspecialchars($search ?? ''); ?>">
                    <button type="button" id="clearSearch" class="clear-search-btn" title="Clear search">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                    <div class="search-btn">
                        <i class="bi bi-search"></i>
                    </div>
                </div>

                <div class="d-flex gap-1 ms-2" id="roleFilterButtons">
                    <button type="button" data-role="" class="btn btn-sm <?php echo empty($role_filter) ? 'btn-secondary' : 'btn-outline-secondary'; ?> rounded-pill px-3">All</button>
                    <button type="button" data-role="FARMER" class="btn btn-sm <?php echo ($role_filter === 'FARMER') ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-pill px-3">Farmers</button>
                    <button type="button" data-role="BUYER" class="btn btn-sm <?php echo ($role_filter === 'BUYER') ? 'btn-success' : 'btn-outline-success'; ?> rounded-pill px-3">Buyers</button>
                    <?php if($_SESSION['role'] === 'DA_SUPER_ADMIN'): ?>
                        <button type="button" data-role="DA" class="btn btn-sm <?php echo ($role_filter === 'DA') ? 'btn-dark' : 'btn-outline-dark'; ?> rounded-pill px-3">Staff</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted">
                        <tr>
                            <th class="ps-4">Profile & Activity</th>
                            <th>Account Info</th>
                            <th>Location</th>
                            <th>Account Status</th>
                            <th class="text-end pe-4">Management Actions</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <?php if (empty($users)): ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">No users found matching your criteria.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <?php 
                                    // Super Admin can toggle standard DA, but standard DA cannot toggle DA.
                                    // No one can toggle Super Admin.
                                    $can_toggle = false;
                                    if ($_SESSION['role'] === 'DA_SUPER_ADMIN') {
                                        $can_toggle = ($user['role'] !== 'DA_SUPER_ADMIN');
                                    } else {
                                        $can_toggle = !in_array($user['role'], ['DA', 'DA_SUPER_ADMIN']);
                                    }
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center overflow-hidden" style="width: 48px; height: 48px; border: 2px solid #eef0f2;">
                                                <?php if (!empty($user['profile_picture'])): ?>
                                                    <img src="../<?php echo $user['profile_picture']; ?>" class="w-100 h-100" style="object-fit: cover;">
                                                <?php else: ?>
                                                    <i class="bi bi-person-circle text-secondary" style="font-size: 32px;"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                                <div class="d-flex gap-2 mt-1">
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.7rem;"><?php echo $user['role']; ?></span>
                                                    <?php if($user['role'] === 'FARMER'): ?>
                                                        <span class="badge bg-success bg-opacity-10 text-success" style="font-size: 0.7rem;"><?php echo $user['post_count']; ?> Posts</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-dark small"><i class="bi bi-envelope me-1"></i> <?php echo htmlspecialchars($user['email']); ?></div>
                                        <div class="text-muted small mt-1"><i class="bi bi-telephone me-1"></i> <?php echo htmlspecialchars($user['phone']); ?></div>
                                    </td>
                                    <td>
                                        <div class="small"><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?php echo htmlspecialchars(($user['barangay'] ? $user['barangay'] . ', ' : '') . ($user['area_name'] ?: 'Not set')); ?></div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></div>
                                    </td>
                                    <td>
                                        <?php if($user['is_active']): ?>
                                            <span class="badge rounded-pill bg-success-subtle text-success border border-success px-3">Active</span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger px-3">Deactivated</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                            <a href="message.php?receiver_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-white border" title="Message User"><i class="bi bi-chat-dots"></i></a>
                                            <button type="button" class="btn btn-sm btn-white border send-sms-btn" 
                                                    data-id="<?php echo $user['id']; ?>" 
                                                    data-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>"
                                                    data-phone="<?php echo htmlspecialchars($user['phone']); ?>"
                                                    title="Send Individual SMS">
                                                <i class="bi bi-phone"></i>
                                            </button>
                                            <?php if($user['role'] === 'FARMER'): ?>
                                                <a href="listings.php?farmer_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-white border" title="View Listings"><i class="bi bi-grid-3x3"></i></a>
                                            <?php endif; ?>
                                            
                                            <?php if ($can_toggle): ?>
                                            <a href="../action/DA/toggle_user.php?id=<?php echo $user['id']; ?>&status=<?php echo $user['is_active'] ? '0' : '1'; ?>&role=<?php echo $role_filter; ?>" 
                                               class="btn btn-sm <?php echo $user['is_active'] ? 'btn-outline-danger' : 'btn-outline-success'; ?> border" 
                                               onclick="return confirm('<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?> this user?')" 
                                               title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?> Account">
                                                <i class="bi <?php echo $user['is_active'] ? 'bi-person-x-fill' : 'bi-person-check-fill'; ?>"></i> <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="paginationContainer" class="card-footer bg-white py-3 border-0 border-top <?php echo ($total_pages <= 1) ? 'd-none' : ''; ?>">
            <?php renderPagination($page, $total_pages); ?>
        </div>
    </div>
</main>

<!-- Individual SMS Modal -->
<div class="modal fade" id="individualSmsModal" tabindex="-1" aria-labelledby="individualSmsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white py-3 border-0">
                <h5 class="modal-title fw-bold" id="individualSmsModalLabel"><i class="bi bi-phone me-2"></i>Send Direct SMS</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <div class="p-2 bg-light rounded-3 d-flex align-items-center mb-3">
                        <i class="bi bi-person-circle fs-4 me-3 text-secondary"></i>
                        <div>
                            <div class="fw-bold text-dark" id="smsTargetName">Recipient Name</div>
                            <div class="text-muted small" id="smsTargetPhone">09123456789</div>
                        </div>
                    </div>
                </div>

                <form id="individualSmsForm">
                    <input type="hidden" name="user_id" id="smsUserId">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">SMS Message Content</label>
                        <textarea name="message" class="form-control rounded-3 border-2 shadow-none" rows="4" placeholder="Type your message here..." required></textarea>
                        <div class="text-end mt-1">
                            <small class="text-muted" id="charCount">0 characters</small>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm" id="smsSubmitBtn">
                            <i class="bi bi-send me-2"></i> Send SMS Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container for modern notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="statusToast" class="toast border-0 shadow-lg rounded-4" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header border-0 rounded-top-4 bg-white">
            <i class="bi bi-info-circle-fill me-2" id="toastIcon"></i>
            <strong class="me-auto" id="toastTitle">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body bg-white rounded-bottom-4" id="toastBody">
            Message goes here.
        </div>
    </div>
</div>

<script>
    // Global notification function
    function showNotification(title, message, type = 'info') {
        const toastEl = document.getElementById('statusToast');
        const toastBody = document.getElementById('toastBody');
        const toastTitle = document.getElementById('toastTitle');
        const toastIcon = document.getElementById('toastIcon');
        
        toastTitle.textContent = title;
        toastBody.textContent = message;
        
        // Set icon/color based on type
        toastIcon.className = 'bi me-2 ';
        if (type === 'success') {
            toastIcon.classList.add('bi-check-circle-fill', 'text-success');
        } else if (type === 'error') {
            toastIcon.classList.add('bi-exclamation-triangle-fill', 'text-danger');
        } else {
            toastIcon.classList.add('bi-info-circle-fill', 'text-primary');
        }

        const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
        toast.show();
    }

    document.addEventListener('DOMContentLoaded', function() {
        // ... (existing modal/form references)
        const individualSmsModal = new bootstrap.Modal(document.getElementById('individualSmsModal'));
        const individualSmsForm = document.getElementById('individualSmsForm');
        const smsTargetName = document.getElementById('smsTargetName');
        const smsTargetPhone = document.getElementById('smsTargetPhone');
        const smsUserId = document.getElementById('smsUserId');
        const smsSubmitBtn = document.getElementById('smsSubmitBtn');
        const charCount = document.getElementById('charCount');
        const messageArea = individualSmsForm.querySelector('textarea');

        // Character counter
        messageArea.addEventListener('input', () => {
            const length = messageArea.value.length;
            charCount.textContent = `${length} characters`;
            if (length > 160) {
                charCount.classList.add('text-danger', 'fw-bold');
                charCount.textContent += ' (Will be split into multiple SMS)';
            } else {
                charCount.classList.remove('text-danger', 'fw-bold');
            }
        });

        // Delegate listener for Send SMS buttons
        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.send-sms-btn');
            if (btn) {
                smsTargetName.textContent = btn.getAttribute('data-name');
                smsTargetPhone.textContent = btn.getAttribute('data-phone');
                smsUserId.value = btn.getAttribute('data-id');
                messageArea.value = '';
                charCount.textContent = '0 characters';
                individualSmsModal.show();
            }
        });

        // Individual SMS Form Submission
        individualSmsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const originalBtnContent = smsSubmitBtn.innerHTML;
            smsSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Connecting to GSM...';
            smsSubmitBtn.disabled = true;

            const formData = new FormData(this);

            fetch('../action/DA/send_sms.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('SMS Sent', data.message, 'success');
                    individualSmsModal.hide();
                } else {
                    showNotification('Transmission Error', data.error, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('System Error', 'An unexpected error occurred.', 'error');
            })
            .finally(() => {
                smsSubmitBtn.innerHTML = originalBtnContent;
                smsSubmitBtn.disabled = false;
            });
        });
        const userTableBody = document.getElementById('userTableBody');
        const paginationContainer = document.getElementById('paginationContainer');
        const liveSearch = document.getElementById('liveSearch');
        const clearSearch = document.getElementById('clearSearch');
        const statusFilter = document.getElementById('statusFilter');
        const areaFilter = document.getElementById('areaFilter');
        const applyFilters = document.getElementById('applyFilters');
        const resetFilters = document.getElementById('resetFilters');
        const roleButtons = document.querySelectorAll('#roleFilterButtons button');
        const filterBadge = document.getElementById('filterBadge');
        const tableTitle = document.getElementById('tableTitle');

        let currentRole = '<?php echo $role_filter; ?>';
        let debounceTimer;

        function updateUI() {
            // Update Clear Search button visibility
            clearSearch.style.display = liveSearch.value ? 'block' : 'none';
            
            // Update Filter Badge
            if (statusFilter.value || areaFilter.value) {
                filterBadge.classList.remove('d-none');
            } else {
                filterBadge.classList.add('d-none');
            }

            // Update Table Title
            let title = 'All Marketplace Participants';
            if (currentRole === 'FARMER') title = 'Farmers';
            else if (currentRole === 'BUYER') title = 'Buyers';
            tableTitle.innerText = title;

            // Update Role Buttons
            roleButtons.forEach(btn => {
                const btnRole = btn.getAttribute('data-role');
                btn.className = 'btn btn-sm rounded-pill px-3';
                if (btnRole === currentRole) {
                    if (!btnRole) btn.classList.add('btn-secondary');
                    else if (btnRole === 'FARMER') btn.classList.add('btn-primary');
                    else if (btnRole === 'BUYER') btn.classList.add('btn-success');
                } else {
                    if (!btnRole) btn.classList.add('btn-outline-secondary');
                    else if (btnRole === 'FARMER') btn.classList.add('btn-outline-primary');
                    else if (btnRole === 'BUYER') btn.classList.add('btn-outline-success');
                }
            });
        }

        function fetchUsers(page = 1) {
            updateUI();
            const params = new URLSearchParams({
                role: currentRole,
                search: liveSearch.value,
                status: statusFilter.value,
                area_id: areaFilter.value,
                page: page
            });

            fetch(`get_users.php?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    updateTable(data);
                })
                .catch(error => console.error('Error fetching users:', error));
        }

        function updateTable(data) {
            const users = data.users;
            const pagination = data.pagination;
            const currentUserRole = '<?php echo $_SESSION['role']; ?>';

            if (users.length === 0) {
                userTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted">No users found matching your criteria.</td></tr>';
                paginationContainer.classList.add('d-none');
                return;
            }

            let html = '';
            users.forEach(user => {
                const fullName = `${user.first_name} ${user.last_name}`;
                const profilePic = user.profile_picture 
                    ? `<img src="../${user.profile_picture}" class="w-100 h-100" style="object-fit: cover;">`
                    : '<i class="bi bi-person-circle text-secondary" style="font-size: 32px;"></i>';
                
                const roleBadge = `<span class="badge bg-light text-dark border" style="font-size: 0.7rem;">${user.role}</span>`;
                const postBadge = user.role === 'FARMER' 
                    ? `<span class="badge bg-success bg-opacity-10 text-success" style="font-size: 0.7rem;">${user.post_count} Posts</span>`
                    : '';
                
                const statusBadge = user.is_active == 1
                    ? '<span class="badge rounded-pill bg-success-subtle text-success border border-success px-3">Active</span>'
                    : '<span class="badge rounded-pill bg-danger-subtle text-danger border border-danger px-3">Deactivated</span>';

                const toggleBtnClass = user.is_active == 1 ? 'btn-outline-danger' : 'btn-outline-success';
                const toggleIcon = user.is_active == 1 ? 'bi-person-x-fill' : 'bi-person-check-fill';
                const toggleText = user.is_active == 1 ? 'Deactivate' : 'Activate';
                const newStatus = user.is_active == 1 ? '0' : '1';

                const farmerListingBtn = user.role === 'FARMER'
                    ? `<a href="listings.php?farmer_id=${user.id}" class="btn btn-sm btn-white border" title="View Listings"><i class="bi bi-grid-3x3"></i></a>`
                    : '';

                // Permission logic for toggle button
                let canToggle = false;
                if (currentUserRole === 'DA_SUPER_ADMIN') {
                    canToggle = (user.role !== 'DA_SUPER_ADMIN');
                } else {
                    canToggle = (user.role !== 'DA' && user.role !== 'DA_SUPER_ADMIN');
                }

                const toggleBtnHtml = canToggle 
                    ? `<a href="../action/DA/toggle_user.php?id=${user.id}&status=${newStatus}&role=${currentRole}" 
                          class="btn btn-sm ${toggleBtnClass} border" 
                          onclick="return confirm('${toggleText} this user?')" 
                          title="${toggleText} Account">
                           <i class="bi ${toggleIcon}"></i> ${toggleText}
                       </a>`
                    : '';

                html += `
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center overflow-hidden" style="width: 48px; height: 48px; border: 2px solid #eef0f2;">
                                    ${profilePic}
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">${fullName}</div>
                                    <div class="d-flex gap-2 mt-1">
                                        ${roleBadge}
                                        ${postBadge}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="text-dark small"><i class="bi bi-envelope me-1"></i> ${user.email}</div>
                            <div class="text-muted small mt-1"><i class="bi bi-telephone me-1"></i> ${user.phone}</div>
                        </td>
                        <td>
                            <div class="small"><i class="bi bi-geo-alt-fill text-danger me-1"></i> ${(user.barangay ? user.barangay + ', ' : '') + (user.area_name || 'Not set')}</div>
                            <div class="text-muted" style="font-size: 0.7rem;">Member since ${new Date(user.created_at).toLocaleDateString('en-US', {month: 'short', year: 'numeric'})}</div>
                        </td>
                        <td>${statusBadge}</td>
                        <td class="text-end pe-4">
                            <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                <a href="message.php?receiver_id=${user.id}" class="btn btn-sm btn-white border" title="Message User"><i class="bi bi-chat-dots"></i></a>
                                <button type="button" class="btn btn-sm btn-white border send-sms-btn" 
                                        data-id="${user.id}" 
                                        data-name="${fullName}"
                                        data-phone="${user.phone}"
                                        title="Send Individual SMS">
                                    <i class="bi bi-phone"></i>
                                </button>
                                ${farmerListingBtn}
                                ${toggleBtnHtml}
                            </div>
                        </td>
                    </tr>
                `;
            });

            userTableBody.innerHTML = html;

            // Update Pagination
            if (pagination.total_pages > 1) {
                paginationContainer.classList.remove('d-none');
                let pagHtml = '<nav aria-label="Page navigation"><ul class="pagination pagination-sm justify-content-center mb-0">';
                
                pagHtml += `<li class="page-item ${pagination.current_page <= 1 ? 'disabled' : ''}">
                    <a class="page-link rounded-pill px-3 me-2" href="#" onclick="event.preventDefault(); window.DA_fetchUsers(${pagination.current_page - 1})">Previous</a>
                </li>`;

                for (let i = 1; i <= pagination.total_pages; i++) {
                    pagHtml += `<li class="page-item ${pagination.current_page == i ? 'active' : ''}">
                        <a class="page-link rounded-circle mx-1" href="#" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;" onclick="event.preventDefault(); window.DA_fetchUsers(${i})">${i}</a>
                    </li>`;
                }

                pagHtml += `<li class="page-item ${pagination.current_page >= pagination.total_pages ? 'disabled' : ''}">
                    <a class="page-link rounded-pill px-3 ms-2" href="#" onclick="event.preventDefault(); window.DA_fetchUsers(${pagination.current_page + 1})">Next</a>
                </li>`;
                pagHtml += '</ul></nav>';
                paginationContainer.innerHTML = pagHtml;
            } else {
                paginationContainer.classList.add('d-none');
            }
        }

        window.DA_fetchUsers = fetchUsers;

        liveSearch.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(fetchUsers, 300);
            updateUI();
        });

        clearSearch.addEventListener('click', () => {
            liveSearch.value = '';
            fetchUsers();
        });

        roleButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                currentRole = btn.getAttribute('data-role');
                fetchUsers();
            });
        });

        applyFilters.addEventListener('click', () => {
            fetchUsers();
            // Close dropdown manually
            const dropdownEl = document.getElementById('filterDropdownBtn');
            const dropdown = bootstrap.Dropdown.getInstance(dropdownEl);
            if (dropdown) dropdown.hide();
        });

        resetFilters.addEventListener('click', () => {
            statusFilter.value = '';
            areaFilter.value = '';
            fetchUsers();
        });

        // Initialize UI
        updateUI();
    });
</script>
<?php include '../includes/universal_footer.php'; ?>
