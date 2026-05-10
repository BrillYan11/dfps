<?php
session_start();
include '../includes/db.php';
include '../includes/pagination.php';

require_once '../includes/url_helpers.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DA', 'DA_SUPER_ADMIN'])) {
    header("Location: " . dfps_helper_url('login'));
    exit;
}

$status_filter = filter_input(INPUT_GET, 'status', FILTER_UNSAFE_RAW);
$farmer_id = filter_input(INPUT_GET, 'farmer_id', FILTER_VALIDATE_INT);
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$listings = [];

// 1. Analytics for header
$active_listings = $conn->query("SELECT COUNT(*) FROM posts WHERE status = 'ACTIVE' AND is_deleted = 0")->fetch_row()[0];
$sold_listings = $conn->query("SELECT COUNT(*) FROM posts WHERE status = 'SOLD' AND is_deleted = 0")->fetch_row()[0];
$flagged_listings = $conn->query("SELECT COUNT(*) FROM posts WHERE status = 'FLAGGED' AND is_deleted = 0")->fetch_row()[0];

// 2. Count total for pagination
$count_query = "SELECT COUNT(*) as total FROM posts WHERE is_deleted = 0";
$c_params = [];
$c_types = "";

if ($status_filter) {
    $count_query .= " AND status = ?";
    $c_params[] = $status_filter;
    $c_types .= "s";
}
if ($farmer_id) {
    $count_query .= " AND farmer_id = ?";
    $c_params[] = $farmer_id;
    $c_types .= "i";
}

$total_rows = 0;
if (!empty($c_params)) {
    $c_stmt = $conn->prepare($count_query);
    $c_stmt->bind_param($c_types, ...$c_params);
    $c_stmt->execute();
    $c_res = dfps_fetch_assoc($c_stmt);
    $total_rows = (int)($c_res['total'] ?? 0);
    $c_stmt->close();
} else {
    $c_res = $conn->query($count_query)->fetch_assoc();
    $total_rows = (int)($c_res['total'] ?? 0);
}
$total_pages = ceil($total_rows / $limit);

// 3. Main query
$query = "
    SELECT p.*, pr.name as produce_name, u.first_name, u.last_name, a.name as area_name 
    FROM posts p 
    JOIN produce pr ON p.produce_id = pr.id 
    JOIN users u ON p.farmer_id = u.id 
    LEFT JOIN areas a ON p.area_id = a.id 
    WHERE p.is_deleted = 0
";
$params = [];
$types = "";

if ($status_filter) {
    $query .= " AND p.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($farmer_id) {
    $query .= " AND p.farmer_id = ?";
    $params[] = $farmer_id;
    $types .= "i";
}

$query .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$listings = dfps_fetch_all($stmt);
$stmt->close();

include '../includes/universal_header.php';
?>

<style>
    .listing-featured-header {
        background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 100%);
        color: #fff;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
    }
</style>

<main class="container-fluid px-4 my-4">
    <!-- Featured Analytics Header -->
    <div class="listing-featured-header">
        <div class="row g-4 align-items-center">
            <div class="col-md-6">
                <h2 class="fw-bold mb-1">Market Listings Oversight</h2>
                <p class="opacity-75 mb-0">Monitoring product availability, sales success, and ensuring listing compliance.</p>
            </div>
            <div class="col-md-6">
                <div class="row g-3">
                    <div class="col-4">
                        <div class="bg-white bg-opacity-10 p-3 rounded-4 text-center border border-white border-opacity-10">
                            <div class="h3 fw-bold mb-0 text-white"><?php echo number_format($active_listings); ?></div>
                            <small class="opacity-75">Active</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="bg-white bg-opacity-10 p-3 rounded-4 text-center border border-white border-opacity-10">
                            <div class="h3 fw-bold mb-0 text-info"><?php echo number_format($sold_listings); ?></div>
                            <small class="opacity-75">Sold</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="bg-white bg-opacity-10 p-3 rounded-4 text-center border border-white border-opacity-10">
                            <div class="h3 fw-bold mb-0 text-warning"><?php echo number_format($flagged_listings); ?></div>
                            <small class="opacity-75">Flagged</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">
                <?php echo $status_filter ? ucfirst(strtolower($status_filter)) . ' Listings' : 'All Marketplace Listings'; ?>
                <?php if($farmer_id && !empty($listings)): ?>
                    <small class="text-muted fw-normal ms-2">by <?php echo htmlspecialchars($listings[0]['first_name'].' '.$listings[0]['last_name']); ?></small>
                <?php endif; ?>
            </h5>
            
            <div class="d-flex gap-2">
                <a href="<?php echo dfps_url('da/listings'); ?>" class="btn btn-sm <?php echo empty($status_filter) ? 'btn-secondary' : 'btn-outline-secondary'; ?> rounded-pill px-3">All</a>
                <a href="<?php echo dfps_url('da/listings'); ?>?status=ACTIVE" class="btn btn-sm <?php echo ($status_filter === 'ACTIVE') ? 'btn-success' : 'btn-outline-success'; ?> rounded-pill px-3">Active</a>
                <a href="<?php echo dfps_url('da/listings'); ?>?status=SOLD" class="btn btn-sm <?php echo ($status_filter === 'SOLD') ? 'btn-info text-white' : 'btn-outline-info'; ?> rounded-pill px-3">Sold</a>
                <a href="<?php echo dfps_url('da/listings'); ?>?status=FLAGGED" class="btn btn-sm <?php echo ($status_filter === 'FLAGGED') ? 'btn-danger' : 'btn-outline-danger'; ?> rounded-pill px-3">Flagged</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted">
                        <tr>
                            <th class="ps-4">Product Details</th>
                            <th>Produce Type</th>
                            <th>Pricing</th>
                            <th>Farmer</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($listings)): ?>
                            <tr><td colspan="9" class="text-center py-5 text-muted">No product listings found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($listings as $listing): 
                                $img_q = $conn->prepare("SELECT file_path FROM post_images WHERE post_id = ? ORDER BY id ASC LIMIT 1");
                                $img_q->bind_param("i", $listing['id']);
                                $img_q->execute();
                                $img_res = dfps_fetch_assoc($img_q);
                                $thumb = $img_res ? $img_res['file_path'] : 'pic/no-image.svg';
                                $img_q->close();
                            ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-3 overflow-hidden border bg-light" style="width: 60px; height: 45px;">
                                                <img src="<?php echo $thumb; ?>" class="w-100 h-100 object-fit-cover">
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($listing['title']); ?></div>
                                                <div class="small text-muted text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($listing['description']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($listing['produce_name']); ?></span></td>
                                    <td>
                                        <div class="fw-bold text-primary">₱<?php echo number_format($listing['price'], 2); ?></div>
                                        <small class="text-muted">per <?php echo htmlspecialchars($listing['unit']); ?></small>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold"><?php echo htmlspecialchars($listing['first_name'] . ' ' . $listing['last_name']); ?></div>
                                    </td>
                                    <td><small><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($listing['area_name'] ?: 'N/A'); ?></small></td>
                                    <td>
                                        <span class="badge rounded-pill <?php 
                                            echo $listing['status'] === 'ACTIVE' ? 'bg-success-subtle text-success border border-success' : 
                                                ($listing['status'] === 'SOLD' ? 'bg-info-subtle text-info border border-info' : 
                                                ($listing['status'] === 'FLAGGED' ? 'bg-danger-subtle text-danger border border-danger' : 'bg-warning-subtle text-warning border border-warning')); 
                                        ?> px-3">
                                            <?php echo $listing['status']; ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted">
                                        <?php echo date('M j, Y', strtotime($listing['created_at'])); ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="<?php echo dfps_url('buyer/view_post'); ?>?id=<?php echo $listing['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="card-footer bg-white py-3 border-0 border-top">
            <?php renderPagination($page, $total_pages); ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/universal_footer.php'; ?>

