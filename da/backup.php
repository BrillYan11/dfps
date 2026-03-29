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

// Handle Backup Action
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    // Log the backup download
    Logger::log($conn, $_SESSION['user_id'], "Database Backup Downloaded", "Generated full SQL backup file.");

    // Clear any previous output or whitespace
    if (ob_get_level()) ob_end_clean();
    
    $tables = array();
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }

    $return = "-- DFPS Database Backup\n";
    $return .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $return .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        $result = $conn->query("SELECT * FROM " . $table);
        $num_fields = $result->field_count;

        $return .= "DROP TABLE IF EXISTS " . $table . ";\n";
        $row2 = $conn->query("SHOW CREATE TABLE " . $table)->fetch_row();
        $return .= $row2[1] . ";\n\n";

        while ($row = $result->fetch_row()) {
            $return .= "INSERT INTO " . $table . " VALUES(";
            for ($j = 0; $j < $num_fields; $j++) {
                if ($row[$j] === null) {
                    $return .= 'NULL';
                } else {
                    $val = addslashes($row[$j]);
                    $val = str_replace("\n", "\\n", $val);
                    $return .= '"' . $val . '"';
                }
                
                if ($j < ($num_fields - 1)) {
                    $return .= ',';
                }
            }
            $return .= ");\n";
        }
        $return .= "\n\n";
    }

    $return .= "SET FOREIGN_KEY_CHECKS=1;\n";

    // Download headers
    $filename = 'db-backup-' . date('Y-m-d-His') . '.sql';
    header('Content-Description: File Transfer');
    header('Content-Type: application/sql');
    header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($return));
    
    echo $return;
    exit;
}

// Handle Restore Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore'])) {
    if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
        $file_content = file_get_contents($_FILES['backup_file']['tmp_name']);
        
        // Split by semicolon but ignore those inside quotes (simplified)
        // A better way is using a proper SQL parser or executing via command line
        // But for a simple prototype, we'll try multi_query
        if ($conn->multi_query($file_content)) {
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());
            $success_msg = "Database restored successfully!";
            Logger::log($conn, $_SESSION['user_id'], "Database Restored", "System restored from uploaded SQL file.");
        } else {
            $error_msg = "Error restoring database: " . $conn->error;
            Logger::log($conn, $_SESSION['user_id'], "Database Restore Failed", "Error: " . $conn->error);
        }
    } else {
        $error_msg = "Please upload a valid backup file.";
    }
}

include '../includes/universal_header.php';
?>

<main class="container-fluid px-4 my-4">
    <div class="row g-4">
        <div class="col-lg-6 mx-auto">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white py-3 border-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-database-fill-gear me-2"></i>Database Backup & Restore</h5>
                </div>
                <div class="card-body p-4">
                    <?php if ($success_msg): ?>
                        <div class="alert alert-success d-flex align-items-center"><i class="bi bi-check-circle-fill me-2"></i><?php echo $success_msg; ?></div>
                    <?php endif; ?>
                    <?php if ($error_msg): ?>
                        <div class="alert alert-danger d-flex align-items-center"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error_msg; ?></div>
                    <?php endif; ?>

                    <div class="mb-5 text-center p-4 bg-light rounded-4 border">
                        <i class="bi bi-cloud-download text-primary display-4 mb-3"></i>
                        <h4>Download Backup</h4>
                        <p class="text-muted">Generate a full SQL backup of the current database.</p>
                        <a href="backup.php?action=download" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold mt-2">
                            <i class="bi bi-download me-2"></i> Download .SQL File
                        </a>
                    </div>

                    <div class="p-4 bg-light rounded-4 border">
                        <div class="text-center mb-3">
                            <i class="bi bi-cloud-upload text-danger h1 mb-2"></i>
                            <h4>Restore Data</h4>
                            <p class="text-muted small">Warning: This will overwrite all current data. Use with caution.</p>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Upload Backup File (.sql)</label>
                                <input type="file" name="backup_file" class="form-control rounded-3 shadow-none border-2" accept=".sql" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="restore" class="btn btn-danger btn-lg rounded-pill fw-bold shadow-sm" onclick="return confirm('ARE YOU ABSOLUTELY SURE? This will overwrite the entire database.')">
                                    <i class="bi bi-arrow-counterclockwise me-2"></i> Restore Database
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/universal_footer.php'; ?>
