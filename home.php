<?php
session_start();
include 'includes/universal_header.php';
?>

<div class="hero-section">
    <div class="container text-center py-5">
        <img src="pic/image/Da_logo.svg" alt="DA Logo" class="hero-logo animate__animated animate__fadeInDown">
        <h1 class="display-3 fw-bold mb-3">Welcome to DFPS</h1>
        <p class="lead fs-3 mb-5 mx-auto" style="max-width: 800px;">
            Digital Farming Platform System. Empowering local agriculture by connecting farmers and buyers in one seamless digital marketplace.
        </p>

        <div class="d-flex justify-content-center gap-3">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php
                    $dashboard_link = function_exists('dfps_url') ? dfps_url() : 'index.php';
                    if (isset($_SESSION['role'])) {
                        if ($_SESSION['role'] == 'FARMER') $dashboard_link = function_exists('dfps_url') ? dfps_url('farmer/') : 'farmer/index.php';
                        elseif ($_SESSION['role'] == 'BUYER') $dashboard_link = function_exists('dfps_url') ? dfps_url('buyer/') : 'buyer/index.php';
                        elseif ($_SESSION['role'] == 'DA') $dashboard_link = function_exists('dfps_url') ? dfps_url('da/') : 'da/index.php';
                    }
                ?>
                <a href="<?php echo $dashboard_link; ?>" class="btn btn-hero-primary text-white">Go to Dashboard</a>
                <a href="<?php echo function_exists('dfps_url') ? dfps_url('logout') : 'logout.php'; ?>" class="btn btn-hero-outline">Logout</a>
            <?php else: ?>
                <a href="<?php echo function_exists('dfps_url') ? dfps_url('login') : 'login.php'; ?>" class="btn btn-hero-primary text-white">Login Now</a>
                <a href="<?php echo function_exists('dfps_url') ? dfps_url('register') : 'register.php'; ?>" class="btn btn-hero-outline">Join as Member</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container my-5 py-5">
    <div class="row g-4 text-center">
        <div class="col-md-4">
            <div class="card feature-card shadow-sm h-100 p-4">
                <div class="mb-3">
                    <i class="bi bi-shop text-success" style="font-size: 3rem;"></i>
                </div>
                <h4 class="fw-bold">Direct Access</h4>
                <p class="text-muted">Eliminate middlemen and buy directly from our local farmers for fresher produce.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card feature-card shadow-sm h-100 p-4">
                <div class="mb-3">
                    <i class="bi bi-shield-check text-success" style="font-size: 3rem;"></i>
                </div>
                <h4 class="fw-bold">Verified Users</h4>
                <p class="text-muted">Secure platform monitored by the Department of Agriculture for safe transactions.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card feature-card shadow-sm h-100 p-4">
                <div class="mb-3">
                    <i class="bi bi-graph-up-arrow text-success" style="font-size: 3rem;"></i>
                </div>
                <h4 class="fw-bold">Market Stability</h4>
                <p class="text-muted">Real-time listing management helps stabilize market supply and demand.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/universal_footer.php'; ?>
