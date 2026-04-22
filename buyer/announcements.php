<?php
session_start();
include '../includes/db.php';

// Authentication and Authorization Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['BUYER', 'FARMER'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user's area_id
$user_stmt = $conn->prepare("SELECT area_id FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$area_id = dfps_fetch_assoc($user_stmt)['area_id'] ?? null;
$user_stmt->close();

// Fetch all applicable announcements
$announcements = [];
$ann_query = "
    SELECT a.*, ar.name as area_name 
    FROM announcements a
    LEFT JOIN areas ar ON a.area_id = ar.id
    WHERE a.area_id IS NULL OR a.area_id = ?
    ORDER BY a.created_at DESC
";
$ann_stmt = $conn->prepare($ann_query);
$ann_stmt->bind_param("i", $area_id);
$ann_stmt->execute();
$announcements = dfps_fetch_all($ann_stmt);
$ann_stmt->close();

include '../includes/universal_header.php';
?>

<main class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center mb-4">
                <a href="index.php" class="btn btn-light rounded-circle me-3 shadow-sm">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="mb-0 fw-bold">Market Announcements</h2>
            </div>

            <?php if (empty($announcements)): ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                    <i class="bi bi-megaphone text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                    <h5 class="text-secondary">No announcements found</h5>
                    <p class="text-muted">Check back later for market updates and agricultural news.</p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($announcements as $ann): ?>
                        <div class="col-12">
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3">
                                            <i class="bi bi-geo-alt me-1"></i> <?php echo $ann['area_name'] ?? 'General'; ?>
                                        </span>
                                        <small class="text-muted"><i class="bi bi-calendar3 me-1"></i> <?php echo date('M d, Y', strtotime($ann['created_at'])); ?></small>
                                    </div>
                                    <h4 class="card-title fw-bold mb-3"><?php echo htmlspecialchars($ann['title']); ?></h4>
                                    <p class="card-text text-secondary lh-base"><?php echo nl2br(htmlspecialchars($ann['body'])); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../includes/universal_footer.php'; ?>
