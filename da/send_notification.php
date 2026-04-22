<?php
session_start();
include '../includes/db.php';
include_once '../includes/Logger.php';

// Authentication and Authorization Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DA', 'DA_SUPER_ADMIN'])) {
    header("Location: ../login.php");
    exit;
}

// Fetch areas for the dropdown
$areas_res = $conn->query("SELECT id, name FROM areas ORDER BY name ASC");
$areas = ($areas_res) ? dfps_fetch_all($areas_res) : [];

include '../includes/universal_header.php';
?>

<main class="container-fluid px-4 my-4">
    <div class="row g-4">
        <!-- Notification Dispatch Form -->
        <div class="col-lg-7 mx-auto">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-success text-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-send-fill me-2"></i>Dispatch Communication</h5>
                    <div class="btn-group btn-group-sm rounded-pill shadow-sm overflow-hidden" role="group">
                        <input type="radio" class="btn-check" name="dispatch_toggle" id="mode_system" value="system_alert" checked>
                        <label class="btn btn-outline-light border-0 px-3" for="mode_system">System Alert</label>
                        
                        <input type="radio" class="btn-check" name="dispatch_toggle" id="mode_sim" value="sim_sms">
                        <label class="btn btn-outline-light border-0 px-3" for="mode_sim">SMS Notification</label>
                    </div>
                </div>
                <div class="card-body p-4">
                    <!-- Status Alerts -->
                    <div id="smsStatusAlert" class="alert alert-warning d-none align-items-center py-2">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <span>SMS Server is currently offline. SMS sending will fail.</span>
                    </div>

                    <form id="dispatchForm">
                        <input type="hidden" name="dispatch_type" id="dispatch_type" value="system_alert">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Target Role</label>
                                <select name="target_role" id="target_role" class="form-select rounded-3 shadow-none border-2" required>
                                    <option value="FARMER">All Farmers</option>
                                    <option value="BUYER">All Buyers</option>
                                    <option value="ALL">All Users (Farmers & Buyers)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Target Area</label>
                                <select name="target_area" id="target_area" class="form-select rounded-3 shadow-none border-2">
                                    <option value="">All Locations (Global)</option>
                                    <?php foreach($areas as $area): ?>
                                        <option value="<?php echo $area['id']; ?>"><?php echo htmlspecialchars($area['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3" id="titleGroup">
                            <label class="form-label fw-bold">Alert Title</label>
                            <input type="text" name="title" id="alertTitle" class="form-control rounded-3 shadow-none border-2" placeholder="Urgent System Alert" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold" id="bodyLabel">Alert Message</label>
                            <textarea name="body" id="messageBody" class="form-control rounded-3 shadow-none border-2" rows="6" placeholder="Details for the communication..." required></textarea>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted" id="helpText">Users will see this as a high-priority system notification.</small>
                                <small class="text-muted" id="charCount">0 characters</small>
                            </div>
                        </div>

                        <div id="broadcastProgress" class="d-none mb-4">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-bold" id="progressStatus">Preparing...</span>
                                <span class="text-muted" id="progressPercent">0%</span>
                            </div>
                            <div class="progress rounded-pill" style="height: 10px;">
                                <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%"></div>
                            </div>
                            <div class="mt-2 small text-muted text-center" id="progressDetails">
                                Sent to 0 / 0 users
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" id="submitBtn" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm">
                                <i class="bi bi-broadcast me-2"></i> Broadcast Alert
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Batch Result Summary (Hidden by default) -->
            <div id="resultSummary" class="card border-0 shadow-sm rounded-4 overflow-hidden mt-4 d-none">
                <div class="card-header bg-light py-3 border-0">
                    <h6 class="mb-0 fw-bold">Broadcast Result Summary</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row text-center g-3">
                        <div class="col-4">
                            <div class="h3 fw-bold mb-0" id="resTotal">0</div>
                            <small class="text-muted">Total Targets</small>
                        </div>
                        <div class="col-4">
                            <div class="h3 fw-bold mb-0 text-success" id="resSuccess">0</div>
                            <small class="text-muted">Successful</small>
                        </div>
                        <div class="col-4">
                            <div class="h3 fw-bold mb-0 text-danger" id="resFailed">0</div>
                            <small class="text-muted">Failed</small>
                        </div>
                    </div>
                    <div class="mt-4 d-none" id="errorLogContainer">
                        <h7 class="fw-bold text-danger d-block mb-2">Error Details:</h7>
                        <div class="bg-light p-3 rounded-3 small overflow-auto" style="max-height: 200px;" id="errorLog">
                        </div>
                    </div>
                    <div class="mt-4 text-center">
                        <button class="btn btn-outline-secondary rounded-pill px-4" onclick="location.reload()">Start New Broadcast</button>
                    </div>
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
    const titleGroup = document.getElementById('titleGroup');
    const bodyLabel = document.getElementById('bodyLabel');
    const helpText = document.getElementById('helpText');
    const submitBtn = document.getElementById('submitBtn');
    const titleInput = document.getElementById('alertTitle');
    const charCount = document.getElementById('charCount');
    const messageBody = document.getElementById('messageBody');
    const smsStatusAlert = document.getElementById('smsStatusAlert');
    
    const broadcastProgress = document.getElementById('broadcastProgress');
    const progressBar = document.getElementById('progressBar');
    const progressStatus = document.getElementById('progressStatus');
    const progressPercent = document.getElementById('progressPercent');
    const progressDetails = document.getElementById('progressDetails');
    
    const resultSummary = document.getElementById('resultSummary');
    const resTotal = document.getElementById('resTotal');
    const resSuccess = document.getElementById('resSuccess');
    const resFailed = document.getElementById('resFailed');
    const errorLogContainer = document.getElementById('errorLogContainer');
    const errorLog = document.getElementById('errorLog');

    function updateCharCount() {
        const length = messageBody.value.length;
        charCount.textContent = `${length} characters`;
        if (dispatchType.value === 'sim_sms' && length > 160) {
            charCount.classList.add('text-danger', 'fw-bold');
            charCount.textContent += ' (Multiple SMS)';
        } else {
            charCount.classList.remove('text-danger', 'fw-bold');
        }
    }

    messageBody.addEventListener('input', updateCharCount);

    function checkSmsServer() {
        if (dispatchType.value === 'sim_sms') {
            fetch('../action/DA/check_sms_status.php')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        smsStatusAlert.classList.add('d-none');
                    } else {
                        smsStatusAlert.classList.remove('d-none');
                        smsStatusAlert.querySelector('span').textContent = 'SMS Server is currently offline. SMS sending will fail.';
                    }
                })
                .catch(() => {
                    smsStatusAlert.classList.remove('d-none');
                });
        } else {
            smsStatusAlert.classList.add('d-none');
        }
    }

    function updateUI(mode) {
        dispatchType.value = mode;
        if (mode === 'sim_sms') {
            bodyLabel.textContent = 'SMS Message';
            helpText.textContent = 'Message will be sent via SMS Server to users\' registered phone numbers.';
            submitBtn.innerHTML = '<i class="bi bi-phone-fill me-2"></i> Send SMS Notification';
            submitBtn.classList.replace('btn-primary', 'btn-info');
            submitBtn.classList.add('text-white');
            titleInput.placeholder = 'Optional Prefix (e.g. [DA-ALERT])';
            titleInput.required = false;
            checkSmsServer();
        } else {
            bodyLabel.textContent = 'Alert Message';
            helpText.textContent = 'Users will see this as a high-priority system notification.';
            submitBtn.innerHTML = '<i class="bi bi-broadcast me-2"></i> Broadcast Alert';
            submitBtn.classList.replace('btn-info', 'btn-primary');
            submitBtn.classList.remove('text-white');
            titleInput.placeholder = 'Urgent System Alert';
            titleInput.required = true;
            smsStatusAlert.classList.add('d-none');
        }
    }

    modeSystem.addEventListener('change', () => {
        updateUI('system_alert');
        updateCharCount();
    });
    modeSim.addEventListener('change', () => {
        updateUI('sim_sms');
        updateCharCount();
    });

    // Handle Form Submission via AJAX
    document.getElementById('dispatchForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to broadcast this communication to all matching users?')) return;

        const targetRole = document.getElementById('target_role').value;
        const targetArea = document.getElementById('target_area').value;
        const title = titleInput.value;
        const body = messageBody.value;
        const type = dispatchType.value;

        // 1. Get user list
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Fetching Users...';
        
        try {
            const usersRes = await fetch(`../action/DA/get_broadcast_users.php?target_role=${targetRole}&target_area=${targetArea}`);
            const usersData = await usersRes.json();
            
            if (!usersData.success || usersData.count === 0) {
                alert(usersData.error || 'No active users found matching the selected criteria.');
                submitBtn.disabled = false;
                updateUI(type);
                return;
            }

            const users = usersData.users;
            const total = users.length;
            let successCount = 0;
            let failedCount = 0;
            let errors = [];

            // Show Progress Bar
            broadcastProgress.classList.remove('d-none');
            submitBtn.innerHTML = '<i class="bi bi-stop-fill me-2"></i> Broadcasting...';

            for (let i = 0; i < total; i++) {
                const user = users[i];
                const currentPercent = Math.round((i / total) * 100);
                
                progressBar.style.width = currentPercent + '%';
                progressPercent.textContent = currentPercent + '%';
                progressStatus.textContent = `Sending to ${user.first_name} ${user.last_name}...`;
                progressDetails.textContent = `Processed ${i} / ${total} users`;

                const formData = new FormData();
                formData.append('user_id', user.id);
                formData.append('dispatch_type', type);
                formData.append('title', title);
                formData.append('body', body);
                formData.append('phone', user.phone);

                try {
                    const sendRes = await fetch('../action/DA/send_broadcast_item.php', {
                        method: 'POST',
                        body: formData
                    });
                    const sendData = await sendRes.json();
                    
                    if (sendData.success) {
                        successCount++;
                    } else {
                        failedCount++;
                        errors.push(`${user.first_name} ${user.last_name}: ${sendData.error}`);
                    }
                } catch (err) {
                    failedCount++;
                    errors.push(`${user.first_name} ${user.last_name}: Network Error`);
                }

                // If it's SMS, wait a bit for modem safety even though server handles busy state
                if (type === 'sim_sms') {
                    await new Promise(r => setTimeout(r, 1000));
                }
            }

            // Final Progress
            progressBar.style.width = '100%';
            progressPercent.textContent = '100%';
            progressStatus.textContent = 'Broadcast Complete!';
            progressDetails.textContent = `Processed ${total} / ${total} users`;
            progressBar.classList.remove('progress-bar-animated');

            // Show Summary
            resultSummary.classList.remove('d-none');
            resTotal.textContent = total;
            resSuccess.textContent = successCount;
            resFailed.textContent = failedCount;

            if (errors.length > 0) {
                errorLogContainer.classList.remove('d-none');
                errorLog.innerHTML = errors.map(e => `<div>• ${e}</div>`).join('');
            }

            // Log the summary
            const logData = new FormData();
            logData.append('dispatch_type', type);
            logData.append('target_desc', document.getElementById('target_role').selectedOptions[0].text + (targetArea ? ' in selected area' : ' (Global)'));
            logData.append('count', successCount);
            logData.append('errors', failedCount);
            logData.append('title', title);
            fetch('../action/DA/log_broadcast.php', { method: 'POST', body: logData });

            submitBtn.innerHTML = '<i class="bi bi-check-all me-2"></i> Finished';
            
        } catch (err) {
            console.error(err);
            alert('A critical error occurred during the broadcast process.');
            submitBtn.disabled = false;
            updateUI(type);
        }
    });
});
</script>

<?php include '../includes/universal_footer.php'; ?>
