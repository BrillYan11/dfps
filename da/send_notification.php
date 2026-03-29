<?php
session_start();
include '../includes/db.php';
include '../includes/NotificationModel.php';
include_once '../includes/Logger.php';
require_once '../includes/sample_sms_gsm.php';

// Authentication and Authorization Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DA', 'DA_SUPER_ADMIN'])) {
    header("Location: ../login.php");
    exit;
}

$da_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Handle Notification Dispatch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
    $dispatch_type = filter_input(INPUT_POST, 'dispatch_type', FILTER_UNSAFE_RAW) ?: 'system_alert';
    $target_role = filter_input(INPUT_POST, 'target_role', FILTER_UNSAFE_RAW);
    $target_area = filter_input(INPUT_POST, 'target_area', FILTER_VALIDATE_INT) ?: null;
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body']);

    if (($dispatch_type === 'system_alert' && empty($title)) || empty($body)) {
        $error_msg = "All required fields are needed.";
    } else {
        // Build query for users to notify
        $query = "SELECT id, phone FROM users WHERE role = ?";
        $params = [$target_role];
        $types = 's';
        if ($target_area) {
            $query .= " AND area_id = ?";
            $params[] = $target_area;
            $types .= 'i';
        }

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = [];
        while($row = $result->fetch_assoc()) { $users[] = $row; }
        $stmt->close();

        if (!empty($users)) {
            $target_desc = "$target_role" . ($target_area ? " in Area #$target_area" : " (All)");
            if ($dispatch_type === 'system_alert') {
                $count = 0;
                foreach ($users as $u) {
                    if (NotificationModel::createNotification($conn, $u['id'], 'SYSTEM_ALERT', $title, $body, 'notification.php')) {
                        $count++;
                    }
                }
                $success_msg = "System alert sent to $count users successfully!";
                Logger::log($conn, $_SESSION['user_id'], "Broadcast System Alert", "Sent to $count $target_desc. Title: $title");
            } else {
                // SIM-based SMS
                $device = $_POST['gsm_device'] ?: 'COM3'; // Default to COM3 for Windows or /dev/ttyUSB0
                $baud = intval($_POST['gsm_baud'] ?: 9600);
                
                $gsm = new GSMModule($device, $baud);
                $gsm->setDebug(false);

                if ($gsm->connect()) {
                    if ($gsm->initialize()) {
                        $count = 0;
                        $errors = 0;
                        foreach ($users as $u) {
                            if (!empty($u['phone'])) {
                                $sms_body = (!empty($title) ? "[$title] " : "") . $body;
                                $res = $gsm->sendSMS($u['phone'], $sms_body);
                                if ($res['success']) {
                                    $count++;
                                } else {
                                    $errors++;
                                }
                            }
                        }
                        $gsm->disconnect();
                        $success_msg = "SMS sent to $count users successfully!" . ($errors > 0 ? " ($errors failed)" : "");
                        Logger::log($conn, $_SESSION['user_id'], "Broadcast SIM SMS", "Sent to $count $target_desc via $device. Errors: $errors");
                    } else {
                        $error_msg = "Failed to initialize GSM module.";
                    }
                } else {
                    $error_msg = "Could not connect to GSM device at $device. Error: " . $gsm->getLastError();
                }
            }
        } else {
            $error_msg = "No users found matching the selected criteria.";
        }
    }
}

// Fetch areas for the dropdown
$areas = $conn->query("SELECT id, name FROM areas ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

include '../includes/universal_header.php';
?>

<main class="container-fluid px-4 my-4">
    <div class="row g-4">
        <!-- Notification Dispatch Form -->
        <div class="col-lg-6 mx-auto">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-success text-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-send-fill me-2"></i>Dispatch Communication</h5>
                    <div class="btn-group btn-group-sm rounded-pill shadow-sm overflow-hidden" role="group">
                        <input type="radio" class="btn-check" name="dispatch_toggle" id="mode_system" value="system_alert" checked>
                        <label class="btn btn-outline-light border-0 px-3" for="mode_system">System Alert</label>
                        
                        <input type="radio" class="btn-check" name="dispatch_toggle" id="mode_sim" value="sim_sms">
                        <label class="btn btn-outline-light border-0 px-3" for="mode_sim">SIM SMS</label>
                    </div>
                </div>
                <div class="card-body p-4">
                    <?php if ($success_msg): ?>
                        <div class="alert alert-success d-flex align-items-center"><i class="bi bi-check-circle-fill me-2"></i><?php echo $success_msg; ?></div>
                    <?php endif; ?>
                    <?php if ($error_msg): ?>
                        <div class="alert alert-danger d-flex align-items-center"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error_msg; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="send_notification.php" id="dispatchForm">
                        <input type="hidden" name="dispatch_type" id="dispatch_type" value="system_alert">
                        
                        <!-- GSM Configuration (Hidden by default) -->
                        <div id="gsmConfig" class="mb-4 p-3 bg-light rounded-3 d-none border">
                            <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-gear-fill me-2"></i>GSM Settings</h6>
                            <div class="row g-2">
                                <div class="col-md-8">
                                    <label class="form-label small fw-bold">Device Port</label>
                                    <input type="text" name="gsm_device" class="form-control form-control-sm rounded-3" placeholder="/dev/ttyUSB0 or COM3" value="COM3">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Baud Rate</label>
                                    <select name="gsm_baud" class="form-select form-select-sm rounded-3">
                                        <option value="9600" selected>9600</option>
                                        <option value="19200">19200</option>
                                        <option value="38400">38400</option>
                                        <option value="57600">57600</option>
                                        <option value="115200">115200</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Target Role</label>
                                <select name="target_role" class="form-select rounded-3 shadow-none border-2" required>
                                    <option value="FARMER">All Farmers</option>
                                    <option value="BUYER">All Buyers</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Target Area</label>
                                <select name="target_area" class="form-select rounded-3 shadow-none border-2">
                                    <option value="">All Locations (Global)</option>
                                    <?php foreach($areas as $area): ?>
                                        <option value="<?php echo $area['id']; ?>"><?php echo htmlspecialchars($area['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3" id="titleGroup">
                            <label class="form-label fw-bold">Alert Title</label>
                            <input type="text" name="title" class="form-control rounded-3 shadow-none border-2" placeholder="Urgent System Alert">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold" id="bodyLabel">Alert Message</label>
                            <textarea name="body" id="messageBody" class="form-control rounded-3 shadow-none border-2" rows="6" placeholder="Details for the communication..." required></textarea>
                            <small class="text-muted" id="helpText">Users will see this as a high-priority system notification.</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="send_notification" id="submitBtn" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm">
                                <i class="bi bi-broadcast me-2"></i> Broadcast Alert
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modeSystem = document.getElementById('mode_system');
    const modeSim = document.getElementById('mode_sim');
    const dispatchType = document.getElementById('dispatch_type');
    const gsmConfig = document.getElementById('gsmConfig');
    const titleGroup = document.getElementById('titleGroup');
    const bodyLabel = document.getElementById('bodyLabel');
    const helpText = document.getElementById('helpText');
    const submitBtn = document.getElementById('submitBtn');
    const titleInput = titleGroup.querySelector('input');

    function updateUI(mode) {
        dispatchType.value = mode;
        if (mode === 'sim_sms') {
            gsmConfig.classList.remove('d-none');
            bodyLabel.textContent = 'SMS Message';
            helpText.textContent = 'Message will be sent via GSM module to users\' registered phone numbers.';
            submitBtn.innerHTML = '<i class="bi bi-phone-fill me-2"></i> Send SIM SMS';
            submitBtn.classList.replace('btn-primary', 'btn-info');
            submitBtn.classList.add('text-white');
            titleInput.placeholder = 'Optional Prefix (e.g. [DA-ALERT])';
            titleInput.required = false;
        } else {
            gsmConfig.classList.add('d-none');
            bodyLabel.textContent = 'Alert Message';
            helpText.textContent = 'Users will see this as a high-priority system notification.';
            submitBtn.innerHTML = '<i class="bi bi-broadcast me-2"></i> Broadcast Alert';
            submitBtn.classList.replace('btn-info', 'btn-primary');
            submitBtn.classList.remove('text-white');
            titleInput.placeholder = 'Urgent System Alert';
            titleInput.required = true;
        }
    }

    modeSystem.addEventListener('change', () => updateUI('system_alert'));
    modeSim.addEventListener('change', () => updateUI('sim_sms'));
});
</script>

<?php include '../includes/universal_footer.php'; ?>
