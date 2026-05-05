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

// Improved helper for date filtering
$params = [];
$types = "";
$date_clause = "";
if ($date_from || $date_to) {
    if ($date_from && $date_to) {
        $date_clause = " AND created_at BETWEEN ? AND ?";
        $params[] = "$date_from 00:00:00";
        $params[] = "$date_to 23:59:59";
        $types .= "ss";
    } elseif ($date_from) {
        $date_clause = " AND created_at >= ?";
        $params[] = "$date_from 00:00:00";
        $types .= "s";
    } elseif ($date_to) {
        $date_clause = " AND created_at <= ?";
        $params[] = "$date_to 23:59:59";
        $types .= "s";
    }
}

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // We re-run the switch logic to get the data, then output CSV
    // (This part will be handled within the switch or after)
}

switch ($report_type) {
    case 'users':
        $report_title = "User Directory & Statistics";
        $query = "SELECT u.*, a.name as area_name FROM users u LEFT JOIN areas a ON u.area_id = a.id WHERE role != 'DA' $date_clause ORDER BY role, last_name";
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $data = dfps_fetch_all($stmt);
        $stmt->close();
        break;
    
    case 'produce':
        $report_title = "Produce Master List & SRP Standards";
        $data = dfps_fetch_all($conn->query("SELECT * FROM produce ORDER BY name"));
        break;

    case 'listings':
        $report_title = "Market Listing Activity Report";
        $query = "
            SELECT p.*, pr.name as produce_name, u.first_name, u.last_name, a.name as area_name 
            FROM posts p 
            JOIN produce pr ON p.produce_id = pr.id 
            JOIN users u ON p.farmer_id = u.id 
            LEFT JOIN areas a ON p.area_id = a.id 
            WHERE 1=1 $date_clause 
            ORDER BY p.created_at DESC
        ";
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $data = dfps_fetch_all($stmt);
        $stmt->close();
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
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $data = dfps_fetch_all($stmt);
        $stmt->close();
        break;

    default:
    $report_title = "DA System Overview";
    break;
    }

    // Handle CSV Export
    if (isset($_GET['export']) && $_GET['export'] === 'csv' && !empty($data)) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . strtolower(str_replace(' ', '_', $report_title)) . '_' . date('Ymd') . '.csv"');
    $output = fopen('php://output', 'w');

    if ($report_type === 'users') {
    fputcsv($output, ['Full Name', 'Role', 'Location', 'Email', 'Phone', 'Status']);
    foreach ($data as $u) {
        fputcsv($output, [$u['first_name'] . ' ' . $u['last_name'], $u['role'], $u['area_name'] ?? '', $u['email'], $u['phone'], $u['is_active'] ? 'Active' : 'Deactivated']);
    }
    } elseif ($report_type === 'produce') {
    fputcsv($output, ['Produce Name', 'Unit', 'SRP', 'Status']);
    foreach ($data as $p) {
        fputcsv($output, [$p['name'], $p['unit'], $p['srp'], $p['is_active'] ? 'Active' : 'Inactive']);
    }
    } elseif ($report_type === 'listings') {
    fputcsv($output, ['Post Title', 'Produce', 'Farmer', 'Location', 'Price', 'Unit', 'Status', 'Date Posted']);
    foreach ($data as $l) {
        fputcsv($output, [$l['title'], $l['produce_name'], $l['first_name'] . ' ' . $l['last_name'], $l['area_name'] ?? '', $l['price'], $l['unit'], $l['status'], $l['created_at']]);
    }
    } elseif ($report_type === 'price_analysis') {
    fputcsv($output, ['Produce', 'SRP', 'Avg Market Price', 'Min Price', 'Max Price', 'Listings', 'Variance']);
    foreach ($data as $pa) {
        $has_data = !is_null($pa['avg_market_price']);
        $variance = $has_data ? ($pa['avg_market_price'] - $pa['srp']) : 0;
        fputcsv($output, [$pa['name'], $pa['srp'], $pa['avg_market_price'] ?? 'N/A', $pa['min_price'] ?? 'N/A', $pa['max_price'] ?? 'N/A', $pa['listing_count'], $variance]);
    }
    }
    fclose($output);
    exit;
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
            <?php if ($report_type !== 'overview'): ?>
            <a href="da/reports?type=<?php echo htmlspecialchars($report_type); ?>&from=<?php echo htmlspecialchars($date_from ?? ''); ?>&to=<?php echo htmlspecialchars($date_to ?? ''); ?>&export=csv" class="btn btn-outline-success rounded-pill px-4">
                <i class="bi bi-file-earmark-spreadsheet-fill me-2"></i> Export CSV
            </a>
            <?php endif; ?>
            <button onclick="window.print()" class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-printer-fill me-2"></i> Print Report
            </button>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 no-print">
        <div class="card-body p-4">
            <form method="GET" action="da/reports" class="row g-3 align-items-end">
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($report_type); ?>">
                
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Search within report</label>
                    <div class="input-group border rounded-pill overflow-hidden">
                        <span class="input-group-text border-0 bg-transparent ps-3"><i class="bi bi-search"></i></span>
                        <input type="text" id="reportSearch" class="form-control border-0 bg-transparent" placeholder="Type to filter rows...">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">From Date</label>
                    <input type="date" name="from" class="form-control rounded-pill" value="<?php echo htmlspecialchars($date_from ?? ''); ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">To Date</label>
                    <input type="date" name="to" class="form-control rounded-pill" value="<?php echo htmlspecialchars($date_to ?? ''); ?>">
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-success rounded-pill flex-grow-1">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                    <a href="da/reports?type=<?php echo htmlspecialchars($report_type); ?>" class="btn btn-outline-secondary rounded-pill" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4 mb-5 no-print">
        <div class="col-md-3">
            <a href="da/reports?type=users" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm report-card p-3 <?php echo $report_type === 'users' ? 'border-start border-4 border-primary' : ''; ?>">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded-3 p-2"><i class="bi bi-people-fill"></i></div>
                        <h6 class="mb-0 fw-bold text-dark">User Directory</h6>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="da/reports?type=produce" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm report-card p-3 <?php echo $report_type === 'produce' ? 'border-start border-4 border-success' : ''; ?>">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-success bg-opacity-10 text-success rounded-3 p-2"><i class="bi bi-egg-fried"></i></div>
                        <h6 class="mb-0 fw-bold text-dark">Produce & SRP</h6>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="da/reports?type=listings" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm report-card p-3 <?php echo $report_type === 'listings' ? 'border-start border-4 border-warning' : ''; ?>">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning rounded-3 p-2"><i class="bi bi-card-list"></i></div>
                        <h6 class="mb-0 fw-bold text-dark">Market Listings</h6>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="da/reports?type=price_analysis" class="text-decoration-none">
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
                <img src="<?php echo dfps_helper_url('pic/image/DAlogo.png'); ?>" alt="DA Logo Left" style="height: 100px;">
                <div class="text-center">
                    <h5 class="fw-bold mb-0 text-success">Republic of the Philippines</h5>
                    <h2 class="fw-bold mb-0 text-success">Department of Agriculture</h2>
                    <p class="mb-0 text-muted">Bureau of Plant Industry</p>
                    <p class="mb-0 text-muted">Direct Farmer-to-Buyer Platform (DFPS)</p>
                </div>
                <img src="<?php echo dfps_helper_url('pic/image/Department_of_Agriculture_of_the_Philippines.png'); ?>" alt="DA Logo Right" style="height: 100px;">
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
                                <td><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></td>
                                <td class="text-center"><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($u['role']); ?></span></td>
                                <td><?php echo htmlspecialchars($u['area_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?><br><small><?php echo htmlspecialchars($u['phone']); ?></small></td>
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
                                <td class="fw-bold"><?php echo htmlspecialchars($p['name']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($p['unit']); ?></td>
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
                                <td><?php echo htmlspecialchars($l['title']); ?><br><small class="text-muted"><?php echo htmlspecialchars($l['produce_name']); ?></small></td>
                                <td><?php echo htmlspecialchars($l['first_name'] . ' ' . $l['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($l['area_name'] ?? ''); ?></td>
                                <td class="text-center fw-bold">₱<?php echo number_format($l['price'], 2); ?> / <?php echo htmlspecialchars($l['unit']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($l['status']); ?></td>
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
                                <td class="fw-bold"><?php echo htmlspecialchars($pa['name']); ?></td>
                                <td class="text-center">₱<?php echo number_format($pa['srp'], 2); ?></td>
                                <td class="text-center fw-bold">
                                    <?php echo $has_data ? '₱' . number_format($pa['avg_market_price'], 2) : '<span class="text-muted small">No Active Listings</span>'; ?>
                                </td>
                                <td class="text-center small">
                                    <?php echo $has_data ? '₱' . number_format($pa['min_price'], 2) . ' - ₱' . number_format($pa['max_price'], 2) : '-'; ?>
                                </td>
                                <td class="text-center"><?php echo (int)$pa['listing_count']; ?></td>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const reportSearch = document.getElementById('reportSearch');
    const table = document.querySelector('.printable-area table');
    
    if (reportSearch && table) {
        reportSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});
</script>

<?php include '../includes/universal_footer.php'; ?>

