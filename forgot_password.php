<?php
include 'includes/db.php';

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $error_message = "Please enter a valid email address.";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows == 1) {
            $user = $res->fetch_assoc();
            $user_id = $user['id'];

            // Generate a random token
            $token = bin2hex(random_bytes(32));
            // Set expiration to 1 hour from now
            $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));

            // Store token in database
            $update_stmt = $conn->prepare("UPDATE users SET reset_token = ?, token_expires = ? WHERE id = ?");
            $update_stmt->bind_param("ssi", $token, $expires, $user_id);

            if ($update_stmt->execute()) {
                // Prepare the email
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'];
                $reset_link = "$protocol://$host" . dirname($_SERVER['SCRIPT_NAME']) . "/reset_password.php?token=$token";

                // --- PHPMailer Implementation ---
                require 'phpmailer/Exception.php';
                require 'phpmailer/PHPMailer.php';
                require 'phpmailer/SMTP.php';

                $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                try {
                    // Server settings
                    $mail->SMTPDebug = 0; // Turn off debug output
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; 
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'zzwapak2@gmail.com';    
                    $mail->Password   = 'ynukkvyuupokajac';       
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    // Recipients
                    $mail->setFrom('zzwapak2@gmail.com', 'DFPS Admin'); 
                    $mail->addAddress($email);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Request - DFPS';
                    $mail->Body    = "Hello,<br><br>You recently requested to reset your password for your DFPS account. Click the link below to reset it:<br><br>
                                     <a href='$reset_link'>$reset_link</a><br><br>
                                     This link will expire in 1 hour.<br><br>If you did not request a password reset, please ignore this email.";
                    $mail->AltBody = "Hello,\n\nYou recently requested to reset your password for your DFPS account. Click the link below to reset it:\n\n" . $reset_link;

                    $mail->send();
                    $success_message = "A password reset link has been sent to your email. Check your inbox and spam folder.";
                } catch (PHPMailer\PHPMailer\Exception $e) {
                    // Display detailed error if it fails
                    $error_message = "Mailer Error: " . $mail->ErrorInfo;
                }
            } else {
                $error_message = "An error occurred. Please try again later.";
            }
            $update_stmt->close();
        } else {
            // For security, don't reveal if the email exists or not
            $success_message = "If an account exists with that email, a reset link has been sent.";
        }
        $stmt->close();
    }
    $conn->close();
}

include 'includes/universal_header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white border-0 pt-4 text-center">
                    <h3 class="fw-bold">Forgot Password</h3>
                    <p class="text-muted small">Enter your email to receive a reset link</p>
                </div>
                <div class="card-body p-4">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success" id="forgotSuccessAlert"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" id="forgotErrorAlert"><?php echo $error_message; ?></div>
                    <?php endif; ?>

                    <?php if ($success_message || $error_message): ?>
                        <script>
                            setTimeout(function() {
                                var successAlert = document.getElementById('forgotSuccessAlert');
                                var errorAlert = document.getElementById('forgotErrorAlert');
                                var alert = successAlert || errorAlert;
                                if (alert) {
                                    alert.style.transition = "opacity 0.5s ease";
                                    alert.style.opacity = "0";
                                    setTimeout(function() { alert.style.display = 'none'; }, 500);
                                }
                            }, 3000);
                        </script>
                    <?php endif; ?>

                    <?php if (!$success_message || strpos($success_message, 'Local Dev Mode') !== false): ?>
                    <form action="forgot_password.php" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" placeholder="name@example.com" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Send Reset Link</button>
                        </div>
                    </form>
                    <?php endif; ?>
                    
                    <div class="mt-4 text-center">
                        <a href="login.php" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/universal_footer.php'; ?>
