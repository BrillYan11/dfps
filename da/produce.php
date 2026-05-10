<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DA', 'DA_SUPER_ADMIN'])) {
    header("Location: ../login.php");
    exit;
}

csrf_guard();

$success_msg = $_SESSION['success_message'] ?? '';
$error_msg = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Handle Adding New Produce
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_produce'])) {
    $name = trim($_POST['name']);
    $unit = trim($_POST['unit']) ?: 'kg';
    $srp = filter_input(INPUT_POST, 'srp', FILTER_VALIDATE_FLOAT) ?: 0.00;

    if (empty($name)) {
        $error_msg = "Produce name is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO produce (name, unit, srp) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE srp = VALUES(srp), unit = VALUES(unit)");
        $stmt->bind_param("ssd", $name, $unit, $srp);
        if ($stmt->execute()) {
            $success_msg = "Produce updated in the master list!";
        } else {
            $error_msg = "Error: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all produce
$produce_list = dfps_fetch_all($conn->query("SELECT * FROM produce WHERE is_deleted = 0 ORDER BY name ASC"));

include '../includes/universal_header.php';
?>

<main class="container-fluid px-4 my-4">
    <div class="row g-4">
        <!-- Add/Edit Produce Form -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-primary text-white py-3 border-0 rounded-top-4">
                    <h5 class="mb-0 fw-bold">Manage Produce & SRP</h5>
                </div>
                <div class="card-body">
                    <?php if ($success_msg): ?>
                        <div class="alert alert-success d-flex align-items-center"><i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success_msg); ?></div>
                    <?php endif; ?>
                    <?php if ($error_msg): ?>
                        <div class="alert alert-danger d-flex align-items-center"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error_msg); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="da/produce">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Produce Name</label>
                            <input type="text" name="name" id="produce_name" class="form-control rounded-3 shadow-none border-2" placeholder="e.g. Potato, Rice, Corn" required>
                            <small class="text-muted">Duplicates will update SRP/Unit.</small>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Default Unit</label>
                                <input type="text" name="unit" id="produce_unit" class="form-control rounded-3 shadow-none border-2" placeholder="kg, sack" value="kg">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">SRP (₱)</label>
                                <input type="number" step="0.01" name="srp" id="produce_srp" class="form-control rounded-3 shadow-none border-2" placeholder="0.00" required>
                            </div>
                        </div>
                        <button type="submit" name="add_produce" class="btn btn-primary w-100 py-2 rounded-pill fw-bold">
                            <i class="bi bi-save me-2"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Produce List Table -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold">Master Produce List</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted">
                                <tr>
                                    <th class="ps-4">Produce Name</th>
                                    <th>Unit</th>
                                    <th>SRP (Suggested)</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($produce_list)): ?>
                                    <tr><td colspan="5" class="text-center py-4">No produce types registered.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($produce_list as $item): ?>
                                        <tr>
                                            <td class="ps-4 fw-semibold text-dark"><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($item['unit']); ?></span></td>
                                            <td class="fw-bold text-primary">₱<?php echo number_format($item['srp'], 2); ?></td>
                                            <td>
                                                <span class="badge rounded-pill <?php echo $item['is_active'] ? 'bg-success-subtle text-success border border-success' : 'bg-danger-subtle text-danger border border-danger'; ?> px-3">
                                                    <i class="bi <?php echo $item['is_active'] ? 'bi-check-circle-fill' : 'bi-x-circle-fill'; ?> me-1"></i>
                                                    <?php echo $item['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <button class="btn btn-sm btn-outline-primary rounded-pill edit-btn px-3" 
                                                            data-name="<?php echo htmlspecialchars($item['name']); ?>" 
                                                            data-unit="<?php echo htmlspecialchars($item['unit']); ?>" 
                                                            data-srp="<?php echo $item['srp']; ?>">
                                                        <i class="bi bi-pencil me-1"></i> Edit
                                                    </button>
                                                    <button type="button" 
                                                       onclick="toggleProduce(<?php echo $item['id']; ?>, <?php echo $item['is_active'] ? '0' : '1'; ?>)"
                                                       class="btn btn-sm <?php echo $item['is_active'] ? 'btn-outline-warning' : 'btn-outline-success'; ?> rounded-pill px-3"
                                                       title="<?php echo $item['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                        <i class="bi <?php echo $item['is_active'] ? 'bi-slash-circle' : 'bi-check-circle'; ?> me-1"></i> <?php echo $item['is_active'] ? 'Disable' : 'Enable'; ?>
                                                    </button>
                                                    <button type="button" 
                                                       onclick="deleteProduce(<?php echo $item['id']; ?>)"
                                                       class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                        <i class="bi bi-trash me-1"></i> Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="statusToast" class="toast border-0 shadow-lg rounded-4" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header border-0 rounded-top-4 bg-white">
            <i class="bi bi-info-circle-fill me-2" id="toastIcon"></i>
            <strong class="me-auto" id="toastTitle">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body bg-white rounded-bottom-4" id="toastBody"></div>
    </div>
</div>

<script>
    function showNotification(title, message, type = 'info') {
        const toastEl = document.getElementById('statusToast');
        const toastBody = document.getElementById('toastBody');
        const toastTitle = document.getElementById('toastTitle');
        const toastIcon = document.getElementById('toastIcon');
        
        toastTitle.textContent = title;
        toastBody.textContent = message;
        
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

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('produce_name').value = this.dataset.name;
            document.getElementById('produce_unit').value = this.dataset.unit;
            document.getElementById('produce_srp').value = this.dataset.srp;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    function toggleProduce(id, status) {
        confirmAction({
            title: status == 1 ? 'Enable Produce' : 'Disable Produce',
            body: `Are you sure you want to ${status == 1 ? 'enable' : 'disable'} this produce item?`,
            confirmText: status == 1 ? 'Enable' : 'Disable',
            onConfirm: () => {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('status', status);
                formData.append('csrf_token', '<?php echo get_csrf_token(); ?>');

                fetch(`${window.DFPS_APP_ROOT}/action/DA/toggle_produce.php`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showNotification('Success', 'Produce status updated!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification('Error', data.error || 'Failed to toggle status', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showNotification('System Error', 'An unexpected error occurred.', 'error');
                });
            }
        });
    }

    function deleteProduce(id) {
        confirmAction({
            title: 'Delete Produce',
            body: 'Are you sure you want to delete this produce? This action cannot be undone and will only succeed if the produce is not linked to any posts.',
            confirmText: 'Delete',
            isDanger: true,
            onConfirm: () => {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('csrf_token', '<?php echo get_csrf_token(); ?>');

                fetch(`${window.DFPS_APP_ROOT}/action/DA/delete_produce.php`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showNotification('Deleted', 'Produce removed from list.', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification('Error', data.error || 'Failed to delete produce', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showNotification('System Error', 'An unexpected error occurred.', 'error');
                });
            }
        });
    }
</script>

<?php include '../includes/universal_footer.php'; ?>

