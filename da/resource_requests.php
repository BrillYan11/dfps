<?php
session_start();
include '../includes/db.php';

// Authentication and Authorization Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DA', 'DA_SUPER_ADMIN'])) {
    header("Location: ../login.php");
    exit;
}

$officer_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle processing (approve/reject)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['process_request'])) {
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_UNSAFE_RAW);
    $remarks = filter_input(INPUT_POST, 'remarks', FILTER_UNSAFE_RAW);

    if (!$request_id || !in_array($status, ['approved', 'rejected'])) {
        $error_message = "Invalid processing data.";
    } else {
        $stmt = $conn->prepare("UPDATE resource_requests SET status = ?, remarks = ?, processed_by = ?, processed_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssii", $status, $remarks, $officer_id, $request_id);

        if ($stmt->execute()) {
            $success_message = "Request " . ($status === 'approved' ? 'approved' : 'rejected') . " successfully!";
            
            // Optional: Create notification for the farmer
            $req_stmt = $conn->prepare("SELECT farmer_id, resource_type FROM resource_requests WHERE id = ?");
            $req_stmt->bind_param("i", $request_id);
            $req_stmt->execute();
            $req_data = dfps_fetch_assoc($req_stmt);
            $req_stmt->close();
            
            if ($req_data) {
                include_once '../includes/NotificationModel.php';
                $notif_title = "Resource Request Update";
                $notif_message = "Your request for " . $req_data['resource_type'] . " has been " . $status . ".";
                NotificationModel::createNotification($conn, $req_data['farmer_id'], 'RESOURCE_REQUEST', $notif_title, $notif_message, 'farmer/resource_requests.php');
            }
        } else {
            $error_message = "Error processing request: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch requests
$requests = [];
$query = "
    SELECT rr.*, f.first_name as farmer_first, f.last_name as farmer_last, f.phone as farmer_phone,
           o.first_name as officer_first, o.last_name as officer_last
    FROM resource_requests rr
    JOIN users f ON rr.farmer_id = f.id
    LEFT JOIN users o ON rr.processed_by = o.id
    ORDER BY CASE WHEN rr.status = 'pending' THEN 0 ELSE 1 END, rr.created_at DESC
";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

include '../includes/universal_header.php';
?>

<main class="container py-4">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h2 class="fw-bold mb-0">Farmer Resource Requests</h2>
            <p class="text-muted">Review and process requests for agricultural resources</p>
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
                        <th class="ps-4">Farmer</th>
                        <th>Resource</th>
                        <th>Qty</th>
                        <th>Date Submitted</th>
                        <th>Status</th>
                        <th class="pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-clipboard-x display-4 d-block mb-3"></i>
                                No resource requests to display.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 38px; height: 38px;">
                                            <i class="bi bi-person-fill"></i>
                                        </div>
                                        <div>
                                            <span class="d-block fw-bold"><?php echo htmlspecialchars($req['farmer_first'] . ' ' . $req['farmer_last']); ?></span>
                                            <small class="text-muted"><?php echo htmlspecialchars($req['farmer_phone']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold"><?php echo htmlspecialchars($req['resource_type']); ?></span>
                                    <?php if ($req['description']): ?>
                                        <i class="bi bi-info-circle ms-1 text-primary" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($req['description']); ?>"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($req['quantity']); ?></td>
                                <td>
                                    <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($req['created_at'])); ?></small>
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
                                    <?php if ($req['status'] !== 'pending'): ?>
                                        <div class="small text-muted mt-1" style="font-size: 0.7rem;">
                                            By: <?php echo htmlspecialchars($req['officer_first']); ?> on <?php echo date('M j', strtotime($req['processed_at'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <?php if ($req['status'] === 'pending'): ?>
                                        <button class="btn btn-sm btn-outline-primary rounded-pill px-3" 
                                                onclick="openProcessModal(<?php echo $req['id']; ?>, '<?php echo htmlspecialchars($req['farmer_first'] . ' ' . $req['farmer_last']); ?>', '<?php echo htmlspecialchars($req['resource_type']); ?>', <?php echo $req['quantity']; ?>)">
                                            Process
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-light rounded-pill px-3" 
                                                onclick="viewDetails(<?php echo htmlspecialchars(json_encode($req)); ?>)">
                                            Details
                                        </button>
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

<!-- Process Request Modal -->
<div class="modal fade" id="processModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <input type="hidden" name="request_id" id="modal_request_id">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Process Resource Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="p-3 bg-light rounded-3 mb-4">
                        <div class="row g-2">
                            <div class="col-6">
                                <small class="text-muted d-block">Farmer</small>
                                <span class="fw-bold" id="modal_farmer_name"></span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Resource</small>
                                <span class="fw-bold" id="modal_resource_info"></span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Action</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="status_approve" value="approved" checked>
                                <label class="form-check-label text-success fw-bold" for="status_approve">Approve</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="status_reject" value="rejected">
                                <label class="form-check-label text-danger fw-bold" for="status_reject">Reject</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold">Remarks / Reason</label>
                        <textarea name="remarks" class="form-control rounded-3" rows="3" placeholder="Add remarks for the farmer..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="process_request" class="btn btn-primary rounded-pill px-4">Confirm Action</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4" id="details_body">
                <!-- Content injected via JS -->
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function openProcessModal(id, farmer, resource, qty) {
    document.getElementById('modal_request_id').value = id;
    document.getElementById('modal_farmer_name').textContent = farmer;
    document.getElementById('modal_resource_info').textContent = qty + ' ' + resource;
    new bootstrap.Modal(document.getElementById('processModal')).show();
}

function viewDetails(req) {
    const body = document.getElementById('details_body');
    let statusBadge = req.status === 'approved' ? 'bg-success' : (req.status === 'rejected' ? 'bg-danger' : 'bg-warning');
    
    body.innerHTML = `
        <div class="mb-4">
            <span class="badge ${statusBadge} rounded-pill px-3 mb-2">${req.status.toUpperCase()}</span>
            <h4 class="fw-bold mb-1">${req.resource_type}</h4>
            <p class="text-muted">Quantity: ${req.quantity}</p>
        </div>
        
        <div class="mb-4">
            <h6 class="fw-bold small text-uppercase text-muted">Farmer Description</h6>
            <p class="bg-light p-3 rounded-3">${req.description || 'No description provided.'}</p>
        </div>
        
        <div class="mb-0">
            <h6 class="fw-bold small text-uppercase text-muted">Officer Remarks</h6>
            <p class="border p-3 rounded-3">${req.remarks || 'No remarks provided.'}</p>
            <small class="text-muted">Processed by ${req.officer_first} ${req.officer_last} on ${req.processed_at}</small>
        </div>
    `;
    new bootstrap.Modal(document.getElementById('detailsModal')).show();
}

// Initialize Tooltips
document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
});
</script>

<?php include '../includes/universal_footer.php'; ?>
