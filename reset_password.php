<?php
include 'includes/db.php';

$appUrl = static function (string $path = ''): string {
    if (function_exists('dfps_url')) {
        return dfps_url($path);
    }

    $normalized = trim(str_replace('\\', '/', $path), '/');
    return $normalized === '' ? '/' : '/' . $normalized;
};

$error_message = '';
$success_message = '';
$token = $_GET['token'] ?? ($_POST['token'] ?? '');

if (!$token) {
    header("Location: " . $appUrl('login'));
    exit;
}

// Verify token
$stmt = $conn->prepare("SELECT id, token_expires FROM users WHERE reset_token = ? LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$user = dfps_fetch_assoc($stmt);

if (!$user) {
    $error_message = "Invalid or expired token.";
} else {
    $user_id = $user['id'];
    $expires = strtotime($user['token_expires']);

    if ($expires < time()) {
        $error_message = "The reset link has expired. Please request a new one.";
    }
}
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($error_message)) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        // Clear reset token and update password
        $update_stmt = $conn->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, token_expires = NULL WHERE id = ?");
        $update_stmt->bind_param("si", $password_hash, $user_id);

        if ($update_stmt->execute()) {
            $success_message = "Your password has been reset successfully. <a href='" . htmlspecialchars($appUrl('login'), ENT_QUOTES, 'UTF-8') . "'>Login here</a>.";
        } else {
            $error_message = "Error updating password. Please try again later.";
        }
        $update_stmt->close();
    }
}
$conn->close();

include 'includes/universal_header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white border-0 pt-4 text-center">
                    <h3 class="fw-bold">Reset Password</h3>
                    <p class="text-muted small">Enter your new password below</p>
                </div>
                <div class="card-body p-4">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success" id="resetSuccessAlert"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" id="resetErrorAlert"><?php echo $error_message; ?></div>
                    <?php endif; ?>

                    <?php if ($success_message || $error_message): ?>
                        <script>
                            setTimeout(function() {
                                var successAlert = document.getElementById('resetSuccessAlert');
                                var errorAlert = document.getElementById('resetErrorAlert');
                                var alert = successAlert || errorAlert;
                                if (alert) {
                                    alert.style.transition = "opacity 0.5s ease";
                                    alert.style.opacity = "0";
                                    setTimeout(function() { alert.style.display = 'none'; }, 500);
                                }
                            }, 3000);
                        </script>
                    <?php endif; ?>

                    <?php if (empty($success_message) && !($error_message === "Invalid or expired token." || $error_message === "The reset link has expired. Please request a new one.")): ?>
                    <form action="<?php echo $appUrl('reset_password'); ?>" method="POST">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                        </div>
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control form-control-lg" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Reset Password</button>
                        </div>
                    </form>
                    <?php elseif (!empty($error_message)): ?>
                        <div class="mt-3 text-center">
                            <a href="<?php echo $appUrl('forgot_password'); ?>" class="btn btn-outline-primary btn-sm">Request New Link</a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4 text-center">
                        <a href="<?php echo $appUrl('login'); ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/universal_footer.php'; ?>
