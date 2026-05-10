<?php
session_start();
include '../includes/db.php';
include '../includes/NotificationModel.php';
include '../includes/pagination.php';

// Authentication and Authorization Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DA', 'DA_SUPER_ADMIN'])) {
    header("Location: ../login.php");
    exit;
}

csrf_guard();

$da_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Handle Announcement Deletion via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_announcement'])) {
    $id_to_delete = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id_to_delete) {
        $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->bind_param("i", $id_to_delete);
        if ($stmt->execute()) {
            $success_msg = "Announcement deleted successfully.";
        } else {
            $error_msg = "Error deleting announcement: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle Announcement Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_announcement'])) {
    $title = trim($_POST['title']);
    $body = trim($_POST['body']);
    $area_id = filter_input(INPUT_POST, 'area_id', FILTER_VALIDATE_INT) ?: null;

    if (empty($title) || empty($body)) {
        $error_msg = "Title and body are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO announcements (da_id, area_id, title, body) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $da_id, $area_id, $title, $body);
        if ($stmt->execute()) {
            $success_msg = "Announcement posted successfully!";
            
            // Create a system notification for all relevant users
            $notif_query = "SELECT id FROM users WHERE role != 'DA'";
            $n_params = [];
            $n_types = "";
            if ($area_id) {
                $notif_query .= " AND area_id = ?";
                $n_params[] = $area_id;
                $n_types .= "i";
            }
            
            if (!empty($n_params)) {
                $n_stmt = $conn->prepare($notif_query);
                $n_stmt->bind_param($n_types, ...$n_params);
                $n_stmt->execute();
                $user_res = dfps_fetch_all($n_stmt);
                foreach($user_res as $u) {
                    NotificationModel::createNotification($conn, $u['id'], 'ANNOUNCEMENT', $title, $body, 'notification.php');
                }
                $n_stmt->close();
            } else {
                $user_res = $conn->query($notif_query);
                $users = dfps_fetch_all($user_res);
                foreach($users as $u) {
                    NotificationModel::createNotification($conn, $u['id'], 'ANNOUNCEMENT', $title, $body, 'notification.php');
                }
            }
        } else {
            $error_msg = "Error: " . $conn->error;
        }
        $stmt->close();
    }
}

// --- Pagination Logic ---
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$total_rows = $conn->query("SELECT COUNT(*) FROM announcements")->fetch_row()[0];
$total_pages = ceil($total_rows / $limit);

// Fetch existing announcements with pagination
$ann_stmt = $conn->prepare("
    SELECT a.*, ar.name as area_name, u.first_name, u.last_name 
    FROM announcements a 
    LEFT JOIN areas ar ON a.area_id = ar.id 
    JOIN users u ON a.da_id = u.id 
    ORDER BY a.created_at DESC
    LIMIT ? OFFSET ?
");
$ann_stmt->bind_param("ii", $limit, $offset);
$ann_stmt->execute();
$announcements = dfps_fetch_all($ann_stmt);
$ann_stmt->close();

// Fetch areas for the dropdown
$areas = dfps_fetch_all($conn->query("SELECT id, name FROM areas ORDER BY name ASC"));

include '../includes/universal_header.php';
?>

<main class="container-fluid px-4 my-4">
    <div class="row g-4">
        <!-- Create Announcement Form -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-primary text-white py-3 border-0 rounded-top-4">
                    <h5 class="mb-0 fw-bold">Post New Announcement</h5>
                </div>
                <div class="card-body">
                    <?php if ($success_msg): ?>
                        <div class="alert alert-success"><?php echo $success_msg; ?></div>
                    <?php endif; ?>
                    <?php if ($error_msg): ?>
                        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo dfps_helper_url('da/announcements'); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control rounded-3" placeholder="Urgent: Market Update" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Target Area</label>
                            <select name="area_id" class="form-select rounded-3">
                                <option value="">All Areas (Global)</option>
                                <?php foreach($areas as $area): ?>
                                    <option value="<?php echo $area['id']; ?>"><?php echo htmlspecialchars($area['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message Body</label>
                            <textarea name="body" class="form-control rounded-3" rows="5" placeholder="Details of the announcement..." required></textarea>
                        </div>
                        <button type="submit" name="create_announcement" class="btn btn-primary w-100 py-2 rounded-pill fw-bold">Post Announcement</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Announcements History -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent py-3 border-0">
                    <h5 class="mb-0 fw-bold">Announcement History</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted">
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Area</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($announcements)): ?>
                                    <tr><td colspan="5" class="text-center py-4">No announcements posted yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach($announcements as $ann): ?>
                                        <tr>
                                            <td class="ps-4 text-muted"><small><i class="bi bi-calendar3 me-1"></i> <?php echo date('M j, Y h:i A', strtotime($ann['created_at'])); ?></small></td>
                                            <td>
                                                <span class="badge rounded-pill <?php echo $ann['area_id'] ? 'bg-info-subtle text-info border border-info' : 'bg-secondary-subtle text-secondary border border-secondary'; ?> px-3">
                                                    <i class="bi <?php echo $ann['area_id'] ? 'bi-geo-alt-fill' : 'bi-globe'; ?> me-1"></i>
                                                    <?php echo htmlspecialchars($ann['area_name'] ?: 'Global'); ?>
                                                </span>
                                            </td>
                                            <td class="fw-semibold text-dark"><?php echo htmlspecialchars($ann['title']); ?></td>
                                            <td><div class="small fw-bold"><i class="bi bi-person-badge me-1"></i> <?php echo htmlspecialchars($ann['first_name'].' '.$ann['last_name']); ?></div></td>
                                            <td class="text-end pe-4">
                                                <div class="d-flex justify-content-end gap-1">
                                                    <button class="btn btn-sm btn-outline-primary rounded-circle view-details" 
                                                            data-title="<?php echo htmlspecialchars($ann['title']); ?>"
                                                            data-body="<?php echo htmlspecialchars($ann['body']); ?>"
                                                            title="View details"><i class="bi bi-eye"></i></button>
                                                    <form method="POST" onsubmit="return confirm('Delete this announcement?');" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
                                                        <input type="hidden" name="delete_announcement" value="1">
                                                        <input type="hidden" name="id" value="<?php echo $ann['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle" title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($total_pages > 1): ?>
                <div class="card-footer bg-white py-3 border-0 border-top">
                    <?php renderPagination($page, $total_pages); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include '../modal/announcement_modal.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const viewBtns = document.querySelectorAll('.view-details');
        const modal = new bootstrap.Modal(document.getElementById('announcementModal'));
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');

        viewBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                modalTitle.innerText = this.dataset.title;
                modalBody.innerText = this.dataset.body;
                modal.show();
            });
        });
    });
</script>

<?php include '../includes/universal_footer.php'; ?>

