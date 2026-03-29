<?php
session_start();
include 'includes/db.php';

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error_message = "Please enter both email and password.";
    } else {
        // Prepare a select statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, password_hash, role FROM users WHERE email = ? AND is_active = 1 LIMIT 1");

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows == 1) {
                $stmt->bind_result($user_id, $password_hash, $role);
                $stmt->fetch();

                // Verify the password
                if (password_verify($password, $password_hash)) {
                    // Password is correct, start a new session
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['role'] = $role;

                    // Redirect user based on role
                    if ($role == 'FARMER') {
                        header("Location: farmer/index.php");
                    } elseif ($role == 'BUYER') {
                        header("Location: buyer/index.php");
                    } elseif (in_array($role, ['DA', 'DA_SUPER_ADMIN'])) {
                        header("Location: da/index.php");
                    } else {
                        header("Location: index.php");
                    }
                    exit();
                } else {
                    $error_message = "Invalid Credentials.";
                }
            } else {
                $error_message = "No account found with Credentials.";
            }
            $stmt->close();
        } else {
            $error_message = "Database error. Please try again later.";
        }
    }
    $conn->close();
}

include 'includes/universal_header.php';
?>

<div class="login-page">
    <div class="login-card p-4">
        <div class="text-center">
            <img src="pic/image/Da_logo.svg" alt="DA Logo" class="login-logo">
            <h3 class="fw-bold mb-4">Login to DFPS</h3>
        </div>
        
        <div class="card-body p-0">
            <?php if ($error_message): ?>
                <div class="alert alert-danger" id="loginAlert"><?php echo $error_message; ?></div>
                <script>
                    setTimeout(function() {
                        var alert = document.getElementById('loginAlert');
                        if (alert) {
                            alert.style.transition = "opacity 0.5s ease";
                            alert.style.opacity = "0";
                            setTimeout(function() { alert.style.display = 'none'; }, 500);
                        }
                    }, 3000);
                </script>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="position-relative">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        <i class="bi bi-eye-slash password-toggle" id="togglePassword"></i>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary login-btn">Login</button>
                </div>
                <div class="mt-4 text-center">
                    <a href="forgot_password.php" class="text-decoration-none text-muted small">Forgot Password?</a>
                </div>
                <hr>
                <div class="text-center">
                    <p class="mb-0 small text-muted">Don't have an account?</p>
                    <a href="register.php" class="text-decoration-none fw-bold text-success">Register here</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        if (togglePassword && password) {
            togglePassword.addEventListener('click', function() {
                // toggle the type attribute
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                
                // toggle the eye slash icon
                this.classList.toggle('bi-eye');
                this.classList.toggle('bi-eye-slash');
            });
        }
    });
</script>

<?php include 'includes/universal_footer.php'; ?>
