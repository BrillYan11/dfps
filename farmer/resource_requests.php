<?php
session_start();
include '../includes/db.php';

// Authentication and Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FARMER') {
    header("Location: ../login.php");
    exit;
}

$farmer_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_request'])) {
    $resource_type = filter_input(INPUT_POST, 'resource_type', FILTER_UNSAFE_RAW);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $description = filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW);

    if (!$resource_type || !$quantity || $quantity <= 0) {
        $error_message = "Please fill in all required fields correctly.";
    } else {
        $stmt = $conn->prepare("INSERT INTO resource_requests (farmer_id, resource_type, quantity, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $farmer_id, $resource_type, $quantity, $description);

        if ($stmt->execute()) {
            $success_message = "Resource request submitted successfully!";
        } else {
            $error_message = "Error submitting request: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch requests
$requests = [];
$stmt = $conn->prepare("
    SELECT rr.*, u.first_name as officer_first, u.last_name as officer_last 
    FROM resource_requests rr 
    LEFT JOIN users u ON rr.processed_by = u.id 
    WHERE rr.farmer_id = ? 
    ORDER BY rr.created_at DESC
");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$requests = dfps_fetch_all($stmt);
$stmt->close();

include '../includes/universal_header.php';
?>

<main class="container py-4">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h2 class="fw-bold mb-0">Resource Requests</h2>
            <p class="text-muted">Request seeds, tools, or fertilizers from the DA</p>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#requestModal">
                <i class="bi bi-plus-lg me-2"></i>New Request
            </button>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Resource Type</th>
                        <th>Quantity</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th class="pe-4">Processed By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-clipboard-x display-4 d-block mb-3"></i>
                                No resource requests found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold"><?php echo htmlspecialchars($req['resource_type']); ?></span>
                                    <?php if ($req['description']): ?>
                                        <small class="d-block text-muted text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($req['description']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($req['quantity']); ?></td>
                                <td>
                                    <small class="text-muted"><?php echo date('M j, Y', strtotime($req['created_at'])); ?></small>
                                </td>
                                <td>
                                    <?php 
                                        $badge_class = 'bg-warning';
                                        if ($req['status'] === 'approved') $badge_class = 'bg-success';
                                        if ($req['status'] === 'rejected') $badge_class = 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?> rounded-pill px-3">
                                        <?php echo ucfirst($req['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-secondary"><?php echo $req['remarks'] ? htmlspecialchars($req['remarks']) : '---'; ?></small>
                                </td>
                                <td class="pe-4">
                                    <?php if ($req['processed_by']): ?>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-xs bg-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px;">
                                                <i class="bi bi-person text-secondary" style="font-size: 0.8rem;"></i>
                                            </div>
                                            <div style="line-height: 1.1;">
                                                <small class="d-block fw-bold"><?php echo htmlspecialchars($req['officer_first'] . ' ' . $req['officer_last']); ?></small>
                                                <small class="text-muted" style="font-size: 0.7rem;"><?php echo date('M j, Y', strtotime($req['processed_at'])); ?></small>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">Pending review</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- New Request Modal -->
<div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="requestModalLabel">New Resource Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Resource Type</label>
                        <select name="resource_type" class="form-select rounded-3" required>
                            <option value="" selected disabled>Select resource type</option>
                            <option value="Rice Seeds">Rice Seeds</option>
                            <option value="Corn Seeds">Corn Seeds</option>
                            <option value="Vegetable Seeds">Vegetable Seeds</option>
                            <option value="Fertilizer">Fertilizer</option>
                            <option value="Pesticide">Pesticide</option>
                            <option value="Hand Tractor">Hand Tractor (Loan)</option>
                            <option value="Water Pump">Water Pump (Loan)</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Quantity / Units</label>
                        <input type="number" name="quantity" class="form-control rounded-3" placeholder="Enter amount" min="1" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Description / Purpose</label>
                        <textarea name="description" class="form-control rounded-3" rows="3" placeholder="Explain why you need these resources..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="submit_request" class="btn btn-primary rounded-pill px-4">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/universal_footer.php'; ?>
