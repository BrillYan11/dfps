<?php
session_start();
include '../includes/db.php';
require_once '../includes/ImageUtil.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$error_message = '';
$success_message = '';

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = "All password fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } elseif (!password_verify($current_password, $user['password_hash'])) {
            $error_message = "Incorrect current password.";
        } else {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_password_hash, $user_id);
            if ($update_stmt->execute()) {
                $success_message = "Password updated successfully!";
            } else {
                $error_message = "Error updating password.";
            }
            $update_stmt->close();
        }
    } elseif (isset($_POST['delete_picture'])) {
        if ($user['profile_picture'] && file_exists('../' . $user['profile_picture'])) {
            unlink('../' . $user['profile_picture']);
        }
        $update_stmt = $conn->prepare("UPDATE users SET profile_picture = NULL WHERE id = ?");
        $update_stmt->bind_param("i", $user_id);
        if ($update_stmt->execute()) {
            $success_message = "Profile picture removed.";
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
        $update_stmt->close();
    } else {
        $first_name = filter_input(INPUT_POST, 'first_name', FILTER_UNSAFE_RAW);
        $last_name = filter_input(INPUT_POST, 'last_name', FILTER_UNSAFE_RAW);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_UNSAFE_RAW);
        $address = filter_input(INPUT_POST, 'address', FILTER_UNSAFE_RAW);
        $barangay = filter_input(INPUT_POST, 'barangay', FILTER_UNSAFE_RAW);
        $bio = filter_input(INPUT_POST, 'bio', FILTER_UNSAFE_RAW);
        $additional_details = filter_input(INPUT_POST, 'additional_details', FILTER_UNSAFE_RAW);

        // Profile picture upload
        $profile_picture = $user['profile_picture'];
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/profile_pics/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            // Use .jpg for profile pics too, or keep original if you want but JPEG is smaller
            $filename = uniqid() . '.jpg';
            $target_file = $upload_dir . $filename;

            if (ImageUtil::compressImage($_FILES['profile_picture']['tmp_name'], $target_file, 80, 500)) {
                $profile_picture = 'uploads/profile_pics/' . $filename;
                // Delete old picture if exists
                if ($user['profile_picture'] && file_exists('../' . $user['profile_picture'])) {
                    unlink('../' . $user['profile_picture']);
                }
            } else {
                // Fallback
                $filename = uniqid() . '-' . basename($_FILES['profile_picture']['name']);
                $target_file = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                    $profile_picture = 'uploads/profile_pics/' . $filename;
                    // Delete old picture if exists
                    if ($user['profile_picture'] && file_exists('../' . $user['profile_picture'])) {
                        unlink('../' . $user['profile_picture']);
                    }
                } else {
                    $error_message = "Failed to upload profile picture.";
                }
            }
        }

        if (empty($error_message)) {
            $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, barangay = ?, bio = ?, additional_details = ?, profile_picture = ? WHERE id = ?");
            $update_stmt->bind_param("sssssssssi", $first_name, $last_name, $email, $phone, $address, $barangay, $bio, $additional_details, $profile_picture, $user_id);

            try {
                if ($update_stmt->execute()) {
                    $success_message = "Profile updated successfully!";
                    // Refresh user data
                    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $user = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                } else {
                    $error_message = "Error updating profile: " . $update_stmt->error;
                }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1062) {
                    $error_message = "This email address is already in use by another account.";
                } else {
                    $error_message = "Error updating profile: " . $e->getMessage();
                }
            }
            $update_stmt->close();
        }
    }
}

// Use universal header
include '../includes/universal_header.php';
?>

<div class="container my-5 px-4">
    <div class="row g-4 justify-content-center">
        <!-- Edit Profile -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-0 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-person-gear me-2 text-primary"></i>My Profile Settings</h5>
                </div>
                <div class="card-body p-4">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success d-flex align-items-center alert-dismissible fade show auto-dismiss" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i><?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger d-flex align-items-center alert-dismissible fade show auto-dismiss" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row mb-4 align-items-center bg-light p-3 rounded-4 mx-0">
                            <div class="col-auto">
                                <div class="rounded-circle border bg-white d-flex align-items-center justify-content-center overflow-hidden shadow-sm" style="width: 120px; height: 120px; border: 4px solid #fff !important;">
                                    <?php if (!empty($user['profile_picture'])): ?>
                                        <img src="../<?php echo $user['profile_picture']; ?>" 
                                             class="w-100 h-100" 
                                             style="object-fit: cover;" 
                                             id="profile-preview">
                                    <?php else: ?>
                                        <i class="bi bi-person-circle text-secondary" 
                                           style="font-size: 80px;" 
                                           id="profile-preview-icon"></i>
                                        <img src="" class="w-100 h-100 d-none" style="object-fit: cover;" id="profile-preview">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col">
                                <label class="form-label fw-bold small text-muted text-uppercase">Profile Picture</label>
                                <div class="d-flex gap-2">
                                    <input type="file" name="profile_picture" class="form-control form-control-sm rounded-pill" accept="image/*" onchange="previewImage(this)">
                                    <?php if (!empty($user['profile_picture'])): ?>
                                        <button type="submit" name="delete_picture" class="btn btn-sm btn-outline-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" onclick="return confirm('Remove your profile picture?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted d-block mt-2">JPG, PNG or SVG. Max size 2MB.</small>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">First Name</label>
                                <input type="text" name="first_name" class="form-control rounded-3 border-2 shadow-none" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Last Name</label>
                                <input type="text" name="last_name" class="form-control rounded-3 border-2 shadow-none" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email Address</label>
                                <input type="email" name="email" class="form-control rounded-3 border-2 shadow-none" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone Number</label>
                                <input type="text" name="phone" class="form-control rounded-3 border-2 shadow-none" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Street Address / House No.</label>
                                <input type="text" name="address" class="form-control rounded-3 border-2 shadow-none" value="<?php echo htmlspecialchars($user['address']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Barangay</label>
                                <input type="text" name="barangay" class="form-control rounded-3 border-2 shadow-none" value="<?php echo htmlspecialchars($user['barangay'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Bio / Short Description</label>
                            <textarea name="bio" class="form-control rounded-3 border-2 shadow-none" rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Additional Details</label>
                            <textarea name="additional_details" class="form-control rounded-3 border-2 shadow-none" rows="3" placeholder="Any other relevant information..."><?php echo htmlspecialchars($user['additional_details'] ?? ''); ?></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm">Save Profile Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Security / Password -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 border-0 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-shield-lock me-2 text-danger"></i>Security Settings</h5>
                </div>
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3 text-muted small text-uppercase">Update Password</h6>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Current Password</label>
                            <input type="password" name="current_password" class="form-control rounded-3 border-2 shadow-none" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">New Password</label>
                            <input type="password" name="new_password" class="form-control rounded-3 border-2 shadow-none" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control rounded-3 border-2 shadow-none" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="change_password" class="btn btn-outline-danger rounded-pill fw-bold">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Account Summary -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-primary text-white">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3 opacity-75 small text-uppercase">Account Status</h6>
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-white bg-opacity-25 rounded-circle p-2 me-3">
                            <i class="bi bi-shield-check fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-bold"><?php echo $role; ?></div>
                            <small class="opacity-75">Verified Participant</small>
                        </div>
                    </div>
                    <div class="small opacity-75 border-top pt-2 mt-2">Member since <?php echo date('F d, Y', strtotime($user['created_at'])); ?></div>
                </div>
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
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('profile-preview');
            const icon = document.getElementById('profile-preview-icon');
            
            if (preview) {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
            }
            if (icon) icon.classList.add('d-none');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

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
    // Auto-dismiss standard alerts
    const alerts = document.querySelectorAll('.auto-dismiss');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Trigger toast for PHP-based messages
    <?php if ($success_message): ?>
        showNotification('Success', '<?php echo addslashes($success_message); ?>', 'success');
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        showNotification('Error', '<?php echo addslashes($error_message); ?>', 'error');
    <?php endif; ?>
});
</script>

<?php
include '../includes/universal_footer.php';
?>
