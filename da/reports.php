<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DA', 'DA_SUPER_ADMIN'])) {
    header("Location: ../login.php");
    exit;
}

$report_type = filter_input(INPUT_GET, 'type', FILTER_UNSAFE_RAW) ?: 'overview';
$date_from = filter_input(INPUT_GET, 'from', FILTER_UNSAFE_RAW);
$date_to = filter_input(INPUT_GET, 'to', FILTER_UNSAFE_RAW);

$report_title = "System Report";
$data = [];

// Helper for date filtering
$date_clause = "";
if ($date_from && $date_to) {
    $date_clause = " AND created_at BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59'";
}

switch ($report_type) {
    case 'users':
        $report_title = "User Directory & Statistics";
        $data = dfps_fetch_all($conn->query("SELECT u.*, a.name as area_name FROM users u LEFT JOIN areas a ON u.area_id = a.id WHERE role != 'DA' $date_clause ORDER BY role, last_name"));
        break;
    
    case 'produce':
        $report_title = "Produce Master List & SRP Standards";
        $data = dfps_fetch_all($conn->query("SELECT * FROM produce ORDER BY name"));
        break;

    case 'listings':
        $report_title = "Market Listing Activity Report";
        $data = dfps_fetch_all($conn->query("
            SELECT p.*, pr.name as produce_name, u.first_name, u.last_name, a.name as area_name 
            FROM posts p 
            JOIN produce pr ON p.produce_id = pr.id 
            JOIN users u ON p.farmer_id = u.id 
            LEFT JOIN areas a ON p.area_id = a.id 
            WHERE 1=1 $date_clause 
            ORDER BY p.created_at DESC
        "));
        break;

    case 'price_analysis':
        $report_title = "Market Price vs SRP Analysis";
        // We use the date clause on the posts table in the LEFT JOIN
        $p_date_clause = str_replace('AND created_at', 'AND p.created_at', $date_clause);
        $query = "
            SELECT pr.name, pr.srp, AVG(p.price) as avg_market_price, MIN(p.price) as min_price, MAX(p.price) as max_price, COUNT(p.id) as listing_count
            FROM produce pr
            LEFT JOIN posts p ON pr.id = p.produce_id AND p.status = 'ACTIVE' $p_date_clause
            GROUP BY pr.id, pr.name, pr.srp
            ORDER BY pr.name
        ";
        $result = $conn->query($query);
        if ($result) {
            $data = dfps_fetch_all($result);
        }
        break;

    default:
        $report_title = "DA System Overview";
        break;
}

include '../includes/universal_header.php';
?>

<style>
    @media print {
        .no-print, .app-header, .app-sidebar, .sidebar-overlay, .chat-footer {
            display: none !important;
        }
        body {
            padding-top: 0 !important;
            background-color: white !important;
        }
        .container-fluid {
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .printable-area {
            display: block !important;
        }
    }
    .report-card {
        border-radius: 15px;
        transition: all 0.2s;
    }
    .report-card:hover {
        transform: translateY(-5px);
        background-color: #f8f9fa;
    }
</style>

<main class="container-fluid px-4 my-4">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h2 class="fw-bold mb-0">System Reports</h2>
            <p class="text-muted">Generate and export official Department of Agriculture data.</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-printer-fill me-2"></i> Print Report
            </button>
        </div>
    </div>

    <div class="row g-4 mb-5 no-print">
        <div class="col-md-3">
            <a href="reports.php?type=users" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm report-card p-3 <?php echo $report_type === 'users' ? 'border-start border-4 border-primary' : ''; ?>">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded-3 p-2"><i class="bi bi-people-fill"></i></div>
                        <h6 class="mb-0 fw-bold text-dark">User Directory</h6>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="reports.php?type=produce" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm report-card p-3 <?php echo $report_type === 'produce' ? 'border-start border-4 border-success' : ''; ?>">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-success bg-opacity-10 text-success rounded-3 p-2"><i class="bi bi-egg-fried"></i></div>
                        <h6 class="mb-0 fw-bold text-dark">Produce & SRP</h6>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="reports.php?type=listings" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm report-card p-3 <?php echo $report_type === 'listings' ? 'border-start border-4 border-warning' : ''; ?>">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning rounded-3 p-2"><i class="bi bi-card-list"></i></div>
                        <h6 class="mb-0 fw-bold text-dark">Market Listings</h6>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="reports.php?type=price_analysis" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm report-card p-3 <?php echo $report_type === 'price_analysis' ? 'border-start border-4 border-info' : ''; ?>">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-info bg-opacity-10 text-info rounded-3 p-2"><i class="bi bi-graph-up-arrow"></i></div>
                        <h6 class="mb-0 fw-bold text-dark">Price Analysis</h6>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Report Area -->
    <div class="card border-0 shadow rounded-4 printable-area">
        <div class="card-body p-5">
            <!-- Report Header -->
            <div class="d-flex justify-content-center align-items-center mb-5 pb-4 border-bottom gap-4">
                <img src="../pic/image/DAlogo.png" alt="DA Logo Left" style="height: 100px;">
                <div class="text-center">
                    <h5 class="fw-bold mb-0 text-success">Republic of the Philippines</h5>
                    <h2 class="fw-bold mb-0 text-success">Department of Agriculture</h2>
                    <p class="mb-0 text-muted">Bureau of Plant Industry</p>
                    <p class="mb-0 text-muted">Direct Farmer-to-Buyer Platform (DFPS)</p>
                </div>
                <img src="../pic/image/Department_of_Agriculture_of_the_Philippines.png" alt="DA Logo Right" style="height: 100px;">
            </div>

            <div class="text-center mb-5">
                <h3 class="fw-bold mb-1"><?php echo $report_title; ?></h3>
                <hr class="w-25 mx-auto mb-3">
                <div class="small text-muted">
                    Generated on: <?php echo date('F j, Y, g:i a'); ?> by DA Portal<br>
                    <?php if ($date_from): ?>
                        Period: <?php echo date('M j, Y', strtotime($date_from)); ?> to <?php echo date('M j, Y', strtotime($date_to)); ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Report Content -->
            <?php if ($report_type === 'users'): ?>
                <table class="table table-bordered align-middle">
                    <thead class="table-light text-center">
                        <tr>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Location</th>
                            <th>Contact Info</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data as $u): ?>
                            <tr>
                                <td><?php echo $u['first_name'] . ' ' . $u['last_name']; ?></td>
                                <td class="text-center"><span class="badge bg-light text-dark border"><?php echo $u['role']; ?></span></td>
                                <td><?php echo $u['area_name']; ?></td>
                                <td><?php echo $u['email']; ?><br><small><?php echo $u['phone']; ?></small></td>
                                <td class="text-center"><?php echo $u['is_active'] ? 'Active' : 'Deactivated'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php elseif ($report_type === 'produce'): ?>
                <table class="table table-bordered align-middle">
                    <thead class="table-light text-center">
                        <tr>
                            <th>Produce Name</th>
                            <th>Default Unit</th>
                            <th>Suggested Retail Price (SRP)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data as $p): ?>
                            <tr>
                                <td class="fw-bold"><?php echo $p['name']; ?></td>
                                <td class="text-center"><?php echo $p['unit']; ?></td>
                                <td class="text-center fw-bold text-primary">₱<?php echo number_format($p['srp'], 2); ?></td>
                                <td class="text-center"><?php echo $p['is_active'] ? 'Active' : 'Inactive'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php elseif ($report_type === 'listings'): ?>
                <table class="table table-bordered align-middle">
                    <thead class="table-light text-center">
                        <tr>
                            <th>Post Title</th>
                            <th>Farmer</th>
                            <th>Location</th>
                            <th>Price / Unit</th>
                            <th>Status</th>
                            <th>Date Posted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data as $l): ?>
                            <tr>
                                <td><?php echo $l['title']; ?><br><small class="text-muted"><?php echo $l['produce_name']; ?></small></td>
                                <td><?php echo $l['first_name'] . ' ' . $l['last_name']; ?></td>
                                <td><?php echo $l['area_name']; ?></td>
                                <td class="text-center fw-bold">₱<?php echo number_format($l['price'], 2); ?> / <?php echo $l['unit']; ?></td>
                                <td class="text-center"><?php echo $l['status']; ?></td>
                                <td class="text-center small"><?php echo date('M j, Y', strtotime($l['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php elseif ($report_type === 'price_analysis'): ?>
                <table class="table table-bordered align-middle">
                    <thead class="table-light text-center">
                        <tr>
                            <th>Produce</th>
                            <th>Current SRP</th>
                            <th>Avg Market Price</th>
                            <th>Price Range</th>
                            <th>Listings</th>
                            <th>Variance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data as $pa): 
                            $has_data = !is_null($pa['avg_market_price']);
                            $variance = $has_data ? ($pa['avg_market_price'] - $pa['srp']) : 0;
                            $variance_class = $variance > 0 ? 'text-danger' : ($variance < 0 ? 'text-success' : 'text-muted');
                        ?>
                            <tr>
                                <td class="fw-bold"><?php echo $pa['name']; ?></td>
                                <td class="text-center">₱<?php echo number_format($pa['srp'], 2); ?></td>
                                <td class="text-center fw-bold">
                                    <?php echo $has_data ? '₱' . number_format($pa['avg_market_price'], 2) : '<span class="text-muted small">No Active Listings</span>'; ?>
                                </td>
                                <td class="text-center small">
                                    <?php echo $has_data ? '₱' . number_format($pa['min_price'], 2) . ' - ₱' . number_format($pa['max_price'], 2) : '-'; ?>
                                </td>
                                <td class="text-center"><?php echo $pa['listing_count']; ?></td>
                                <td class="text-center fw-bold <?php echo $variance_class; ?>">
                                    <?php 
                                        if (!$has_data) echo '-';
                                        else echo ($variance > 0 ? '+' : '') . number_format($variance, 2); 
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php else: ?>
                <div class="row g-4 text-center py-5">
                    <div class="col-md-6 mx-auto">
                        <i class="bi bi-file-earmark-text text-muted display-1"></i>
                        <h4 class="mt-3">Please select a report type above</h4>
                        <p class="text-muted">Use the navigation icons to generate specific data visualizations.</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Report Footer -->
            <div class="mt-5 pt-5 border-top d-flex justify-content-between">
                <div>
                    <p class="mb-0 small"><strong>Department of Agriculture</strong></p>
                    <p class="text-muted small">Digital Farming Platform System (DFPS)</p>
                </div>
                <div class="text-end">
                    <div style="width: 200px; border-bottom: 1px solid #000;" class="ms-auto mb-1"></div>
                    <p class="mb-0 small"><strong>Official Signature</strong></p>
                    <p class="text-muted small">Authorized DA Personnel</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/universal_footer.php'; ?>
