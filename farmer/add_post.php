<?php
session_start();
include '../includes/db.php';
require_once '../includes/ImageUtil.php';
require_once '../includes/url_helpers.php';

// Authentication and Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FARMER') {
    header("Location: " . dfps_helper_url('login'));
    exit;
}

csrf_guard();

$farmer_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Fetch produce options for the dropdown
$produce_options = '';
$produce_result = $conn->query("SELECT id, name, unit, srp FROM produce WHERE is_active = 1 ORDER BY name ASC");
if ($produce_result && $produce_result->num_rows > 0) {
    while ($row = $produce_result->fetch_assoc()) {
        $produce_options .= '<option value="' . htmlspecialchars($row['id']) . '" data-unit="' . htmlspecialchars($row['unit']) . '" data-srp="' . htmlspecialchars($row['srp']) . '">' . htmlspecialchars($row['name']) . '</option>';
    }
}

// Fetch the farmer's area
$area_id = null;
$area_name = 'N/A';
$user_stmt = $conn->prepare("SELECT a.id, a.name FROM users u JOIN areas a ON u.area_id = a.id WHERE u.id = ?");
$user_stmt->bind_param("i", $farmer_id);
$user_stmt->execute();
if ($user_row = dfps_fetch_assoc($user_stmt)) {
    $area_id = $user_row['id'];
    $area_name = $user_row['name'];
}
$user_stmt->close();


// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and retrieve form data
    $title = filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW);
    $description = filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW);
    $produce_id = filter_input(INPUT_POST, 'produce_id', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_FLOAT);
    $unit = filter_input(INPUT_POST, 'unit', FILTER_UNSAFE_RAW);
    $post_area_id = $area_id; // Area is pre-determined by farmer's profile

    // Image Upload Handling
    $image_paths = [];
    if (isset($_FILES['post_images']) && !empty($_FILES['post_images']['name'][0])) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Limit to 10 images
        $file_count = min(count($_FILES['post_images']['name']), 10);

        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['post_images']['error'][$i] == UPLOAD_ERR_OK) {
                // Use a unique filename with .jpg extension for better compression
                $filename = uniqid() . '_' . $i . '.jpg';
                $target_file = $upload_dir . $filename;

                // Compress and save. Quality 70 is usually a good balance.
                if (ImageUtil::compressImage($_FILES['post_images']['tmp_name'][$i], $target_file, 70, 1000)) {
                    $image_paths[] = 'uploads/' . $filename;
                } else {
                    // Fallback if compression fails
                    $filename = uniqid() . '-' . $i . '-' . basename($_FILES['post_images']['name'][$i]);
                    $target_file = $upload_dir . $filename;
                    if (move_uploaded_file($_FILES['post_images']['tmp_name'][$i], $target_file)) {
                        $image_paths[] = 'uploads/' . $filename;
                    }
                }
            }
        }
    }


    // Validation
    if (!$title || !$produce_id || $price === false || $quantity === false || !$unit) {
        $error_message = "Please fill in all required fields correctly.";
    } elseif ($price <= 0) {
        $error_message = "Price must be a positive number.";
    } else {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Prepare an insert statement for the post
            $stmt = $conn->prepare("INSERT INTO posts (farmer_id, produce_id, title, description, price, quantity, unit, area_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissddsi", $farmer_id, $produce_id, $title, $description, $price, $quantity, $unit, $post_area_id);

            if (!$stmt->execute()) {
                throw new Exception("Error creating post: " . $stmt->error);
            }

            $post_id = $stmt->insert_id;
            $stmt->close();

            // If images were uploaded, insert them into the post_images table
            if (!empty($image_paths) && $post_id) {
                $img_stmt = $conn->prepare("INSERT INTO post_images (post_id, file_path) VALUES (?, ?)");
                foreach ($image_paths as $path) {
                    $img_stmt->bind_param("is", $post_id, $path);
                    if (!$img_stmt->execute()) {
                        throw new Exception("Error saving image reference: " . $img_stmt->error);
                    }
                }
                $img_stmt->close();
            }

            // Commit transaction
            $conn->commit();
            $success_message = "Your product has been posted successfully!";
            // Redirect after a 3 second delay
            header("Refresh: 3; url=" . dfps_helper_url('farmer/'));

        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "An error occurred. " . $e->getMessage();
            // If images were uploaded but DB failed, delete the orphaned files
            foreach ($image_paths as $path) {
                if (file_exists('../' . $path)) {
                    unlink('../' . $path);
                }
            }
        }
    }
}


include '../includes/universal_header.php';
?>

<div class="container my-4">
  <div class="row justify-content-center">
    <div class="col-12 col-md-10 col-lg-8">

      <div class="d-flex align-items-center mb-3">
        <a href="<?php echo dfps_helper_url('farmer/'); ?>" class="btn btn-sm btn-outline-secondary me-2"><i class="bi bi-arrow-left"></i></a>
        <h3 class="mb-0">Create a New Product Post</h3>
      </div>


      <?php if ($success_message): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
      <?php endif; ?>
      <?php if ($error_message): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
      <?php endif; ?>

      <?php if (empty($success_message)): // Hide form on success ?>
      <div class="card">
        <div class="card-body p-4">

          <form method="POST" action="<?php echo dfps_helper_url('farmer/add_post'); ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">

            <div class="mb-3">
              <label class="form-label">Post Title</label>
              <input type="text" name="title" class="form-control" placeholder="e.g., Fresh Organic Carrots" required>
              <small class="form-text text-muted">A catchy and descriptive title for your product.</small>
            </div>

            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="3" placeholder="Add details about the product, harvest date, etc."></textarea>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Produce Type</label>
                    <select name="produce_id" id="produce-select" class="form-select" required>
                        <option value="" selected disabled>Select a produce</option>
                        <?php echo $produce_options; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Your Area</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($area_name); ?>" disabled>
                    <small class="form-text text-muted">Your posts are automatically tagged to your registered area.</small>
                </div>
            </div>


            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label class="form-label">Price</label>
                <div class="input-group">
                  <span class="input-group-text">₱</span>
                  <input type="number" name="price" step="0.01" class="form-control" required>
                </div>
                <small id="srp-display" class="form-text text-muted d-block mt-1"></small>
              </div>
              <div class="col-md-4">
                <label class="form-label">Quantity</label>
                <input type="number" name="quantity" step="0.01" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Unit</label>
                <input type="text" name="unit" id="unit-input" class="form-control" placeholder="e.g., kg, bundle" required>
              </div>
            </div>

            <div class="mb-4">
              <label for="post_images" class="form-label">Product Images (Up to 10 photos)</label>
              <input class="form-control" type="file" id="post_images" name="post_images[]" accept="image/png, image/jpeg, image/gif" multiple>
              <small class="form-text text-muted">Upload clear photos of your product. You can select up to 10 files.</small>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">Post My Product</button>
            </div>

          </form>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php if ($success_message): ?>
<!-- Success Toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="successToast" class="toast align-items-center text-white bg-success border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body p-3">
        <div class="d-flex align-items-center">
            <i class="bi bi-check-circle-fill fs-4 me-3"></i>
            <div>
                <h6 class="mb-0 fw-bold">Success!</h6>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        </div>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
// Trigger Toast if success message exists
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($success_message): ?>
    var toastEl = document.getElementById('successToast');
    if (toastEl) {
        var toast = new bootstrap.Toast(toastEl, { delay: 4000 });
        toast.show();
    }
    <?php endif; ?>

    // Script to auto-populate the 'unit' and 'SRP' based on produce selection
    var produceSelect = document.getElementById('produce-select');
    if (produceSelect) {
        produceSelect.addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            
            // Auto-populate unit
            var unit = selectedOption.getAttribute('data-unit');
            if (unit) {
                document.getElementById('unit-input').value = unit;
            }

            // Show SRP
            var srp = selectedOption.getAttribute('data-srp');
            var srpDisplay = document.getElementById('srp-display');
            if (srp && srp !== "null") {
                srpDisplay.innerHTML = '<i class="bi bi-info-circle me-1"></i>SRP: ₱' + parseFloat(srp).toLocaleString() + ' / ' + (unit || 'unit');
                srpDisplay.classList.add('text-primary');
                srpDisplay.classList.remove('text-muted');
            } else {
                srpDisplay.innerHTML = '';
            }
        });
    }
});
</script>


<?php include '../includes/universal_footer.php'; ?>
