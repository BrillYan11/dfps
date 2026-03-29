<?php
session_start();
include '../includes/db.php';
include_once '../includes/Logger.php';

// Authorization Check: Only DA_SUPER_ADMIN can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'DA_SUPER_ADMIN') {
    header("Location: index.php");
    exit;
}

$success_msg = '';
$error_msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_da'])) {
    $first_name = filter_input(INPUT_POST, 'first_name', FILTER_UNSAFE_RAW);
    $last_name = filter_input(INPUT_POST, 'last_name', FILTER_UNSAFE_RAW);
    $username = filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_UNSAFE_RAW);
    $password = $_POST['password'] ?? '';
    $area_id = filter_input(INPUT_POST, 'area_id', FILTER_VALIDATE_INT);
    $da_role = filter_input(INPUT_POST, 'da_role', FILTER_UNSAFE_RAW) ?: 'DA';

    if (!$first_name || !$last_name || !$username || !$email || !$password) {
        $error_msg = "Please fill in all required fields.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $address = "Department of Agriculture"; // Default address for DA staff
        
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, email, phone, address, password_hash, role, area_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("ssssssssi", $first_name, $last_name, $username, $email, $phone, $address, $password_hash, $da_role, $area_id);

        try {
            if ($stmt->execute()) {
                $success_msg = "New $da_role account created successfully!";
                Logger::log($conn, $_SESSION['user_id'], "Created DA Account", "Account: $first_name $last_name ($da_role)");
            } else {
                $error_msg = "Error creating account: " . $stmt->error;
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $error_msg = "This email or username is already registered.";
            } else {
                $error_msg = "Error creating account: " . $e->getMessage();
            }
        }
        $stmt->close();
    }
}

// Fetch areas for the dropdown
$areas = $conn->query("SELECT id, name FROM areas ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

include '../includes/universal_header.php';
?>

<main class="container-fluid px-4 my-4">
    <div class="row g-4">
        <div class="col-lg-6 mx-auto">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white py-3 border-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Create Department of Agriculture Account</h5>
                </div>
                <div class="card-body p-4">
                    <?php if ($success_msg): ?>
                        <div class="alert alert-success d-flex align-items-center justify-content-between alert-dismissible fade show" id="autoAlert">
                            <div>
                                <i class="bi bi-check-circle-fill me-2"></i><?php echo $success_msg; ?>
                            </div>
                            <a href="users.php" class="btn btn-sm btn-dark rounded-pill px-3 ms-3 shadow-sm">
                                <i class="bi bi-arrow-left me-1"></i> Back to Users
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php if ($error_msg): ?>
                        <div class="alert alert-danger d-flex align-items-center alert-dismissible fade show" id="autoAlert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error_msg; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">First Name</label>
                                <input type="text" name="first_name" class="form-control rounded-3 border-2 shadow-none" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Last Name</label>
                                <input type="text" name="last_name" class="form-control rounded-3 border-2 shadow-none" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Username</label>
                            <input type="text" name="username" class="form-control rounded-3 border-2 shadow-none" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email Address</label>
                                <input type="email" name="email" class="form-control rounded-3 border-2 shadow-none" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone Number</label>
                                <input type="text" name="phone" class="form-control rounded-3 border-2 shadow-none">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Assigned Area</label>
                                <select name="area_id" class="form-select rounded-3 border-2 shadow-none" required>
                                    <option value="" disabled selected>Select Area</option>
                                    <?php foreach($areas as $area): ?>
                                        <option value="<?php echo $area['id']; ?>"><?php echo htmlspecialchars($area['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">DA Role Level</label>
                                <select name="da_role" class="form-select rounded-3 border-2 shadow-none" required>
                                    <option value="DA">Standard DA Staff</option>
                                    <option value="DA_SUPER_ADMIN">DA Super Admin</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Temporary Password</label>
                            <input type="password" name="password" class="form-control rounded-3 border-2 shadow-none" required>
                            <small class="text-muted">User should change this after first login.</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="create_da" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm">
                                <i class="bi bi-shield-lock-fill me-2"></i> Register DA Account
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
    const autoAlert = document.getElementById('autoAlert');
    if (autoAlert) {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(autoAlert);
            bsAlert.close();
        }, 5000);
    }
});
</script>

<?php include '../includes/universal_footer.php'; ?>
