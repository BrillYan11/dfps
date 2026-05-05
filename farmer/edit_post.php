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
$post_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$post_id) {
    header("Location: " . dfps_helper_url('farmer/'));
    exit;
}

// 1. Fetch the existing post data and verify ownership
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ? AND farmer_id = ? AND is_deleted = 0");
$stmt->bind_param("ii", $post_id, $farmer_id);
$stmt->execute();
$post = dfps_fetch_assoc($stmt);
$stmt->close();

if (!$post) {
    header("Location: " . dfps_helper_url('farmer/'));
    exit;
}

// Fetch all images for this post
$img_stmt = $conn->prepare("SELECT id, file_path FROM post_images WHERE post_id = ? ORDER BY id ASC");
$img_stmt->bind_param("i", $post_id);
$img_stmt->execute();
$images = dfps_fetch_all($img_stmt);
$img_stmt->close();

// Fetch produce options for the dropdown
$produce_options = '';
$produce_result = $conn->query("SELECT id, name, unit, srp FROM produce WHERE is_active = 1 ORDER BY name ASC");
if ($produce_result) {
    while ($row = $produce_result->fetch_assoc()) {
        $selected = ($row['id'] == $post['produce_id']) ? 'selected' : '';
        $produce_options .= '<option value="' . htmlspecialchars($row['id']) . '" data-unit="' . htmlspecialchars($row['unit']) . '" data-srp="' . htmlspecialchars($row['srp']) . '" ' . $selected . '>' . htmlspecialchars($row['name']) . '</option>';
    }
}

$error_message = '';
$success_message = '';

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // A. Handle Image Deletion
    if (isset($_POST['delete_image_id'])) {
        $del_img_id = filter_input(INPUT_POST, 'delete_image_id', FILTER_VALIDATE_INT);
        if ($del_img_id) {
            // Verify image belongs to this post
            $check_img = $conn->prepare("SELECT file_path FROM post_images WHERE id = ? AND post_id = ?");
            $check_img->bind_param("ii", $del_img_id, $post_id);
            $check_img->execute();
            $img_data = dfps_fetch_assoc($check_img);
            $check_img->close();

            if ($img_data) {
                if (file_exists('../' . $img_data['file_path'])) {
                    unlink('../' . $img_data['file_path']);
                }
                $del_stmt = $conn->prepare("DELETE FROM post_images WHERE id = ?");
                $del_stmt->bind_param("i", $del_img_id);
                $del_stmt->execute();
                $del_stmt->close();
                
                // Refresh image list
                $img_stmt = $conn->prepare("SELECT id, file_path FROM post_images WHERE post_id = ? ORDER BY id ASC");
                $img_stmt->bind_param("i", $post_id);
                $img_stmt->execute();
                $images = dfps_fetch_all($img_stmt);
                $img_stmt->close();
            }
        }
    }

    // B. Handle Main Post Update
    if (isset($_POST['update_post'])) {
        $title = filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW);
        $description = filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW);
        $produce_id = filter_input(INPUT_POST, 'produce_id', FILTER_VALIDATE_INT);
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_FLOAT);
        $unit = filter_input(INPUT_POST, 'unit', FILTER_UNSAFE_RAW);
        $status = filter_input(INPUT_POST, 'status', FILTER_UNSAFE_RAW);

        if (!$title || !$produce_id || $price === false || $quantity === false || !$unit || !$status) {
            $error_message = "Please fill in all required fields correctly.";
        } else {
            $conn->begin_transaction();
            try {
                $update_stmt = $conn->prepare("UPDATE posts SET title = ?, description = ?, produce_id = ?, price = ?, quantity = ?, unit = ?, status = ? WHERE id = ? AND farmer_id = ?");
                $update_stmt->bind_param("ssiddssii", $title, $description, $produce_id, $price, $quantity, $unit, $status, $post_id, $farmer_id);
                if (!$update_stmt->execute()) {
                    throw new Exception("Error updating post details: " . $update_stmt->error);
                }
                $update_stmt->close();

                // Handle New Image Uploads
                if (isset($_FILES['post_images']) && !empty($_FILES['post_images']['name'][0])) {
                    $upload_dir = '../uploads/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    
                    $current_count = count($images);
                    $allowed_count = 10 - $current_count;
                    $upload_count = min(count($_FILES['post_images']['name']), $allowed_count);

                    for ($i = 0; $i < $upload_count; $i++) {
                        if ($_FILES['post_images']['error'][$i] == UPLOAD_ERR_OK) {
                            $filename = uniqid() . '_' . $i . '.jpg';
                            $target_file = $upload_dir . $filename;
                            $db_path = 'uploads/' . $filename;

                            if (ImageUtil::compressImage($_FILES['post_images']['tmp_name'][$i], $target_file, 70, 1000)) {
                                $img_ins = $conn->prepare("INSERT INTO post_images (post_id, file_path) VALUES (?, ?)");
                                $img_ins->bind_param("is", $post_id, $db_path);
                                $img_ins->execute();
                                $img_ins->close();
                            }
                        }
                    }
                }

                $conn->commit();
                $success_message = "Post updated successfully!";
                header("Refresh: 3; url=" . dfps_helper_url('farmer/'));
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "An error occurred: " . $e->getMessage();
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
        <h3 class="mb-0">Edit Product Post</h3>
      </div>

      <?php if ($success_message): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
      <?php endif; ?>
      <?php if ($error_message): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
      <?php endif; ?>

      <?php if (empty($success_message)): ?>
      <div class="card">
        <div class="card-body p-4">
          
          <div class="mb-4">
            <label class="form-label fw-bold">Current Images (<?php echo count($images); ?>/10)</label>
            <div class="row g-2">
                <?php foreach ($images as $img): ?>
                <div class="col-4 col-md-3">
                    <div class="position-relative">
                        <img src="<?php echo dfps_helper_asset($img['file_path']); ?>" class="img-fluid rounded border shadow-sm" style="height: 120px; width: 100%; object-fit: cover;">
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this image?');" class="position-absolute top-0 end-0 m-1">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
                            <input type="hidden" name="delete_image_id" value="<?php echo $img['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm p-1 leading-none rounded-circle" title="Delete Image">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($images)): ?>
                    <div class="col-12 text-muted small">No images uploaded yet.</div>
                <?php endif; ?>
            </div>
          </div>

          <form method="POST" action="<?php echo dfps_helper_url('farmer/edit_post'); ?>?id=<?php echo $post_id; ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
            <input type="hidden" name="update_post" value="1">
            
            <div class="mb-3">
              <label class="form-label">Post Title</label>
              <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($post['title']); ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($post['description']); ?></textarea>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Produce Type</label>
                    <select name="produce_id" id="produce-select" class="form-select" required>
                        <?php echo $produce_options; ?>
                    </select>
                </div>
                 <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="ACTIVE" <?php echo ($post['status'] == 'ACTIVE') ? 'selected' : ''; ?>>Active</option>
                        <option value="SOLD" <?php echo ($post['status'] == 'SOLD') ? 'selected' : ''; ?>>Sold</option>
                        <option value="HIDDEN" <?php echo ($post['status'] == 'HIDDEN') ? 'selected' : ''; ?>>Hidden</option>
                        <option value="ARCHIVED" <?php echo ($post['status'] == 'ARCHIVED') ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label class="form-label">Price</label>
                <div class="input-group">
                  <span class="input-group-text">₱</span>
                  <input type="number" name="price" step="0.01" class="form-control" value="<?php echo htmlspecialchars($post['price']); ?>" required>
                </div>
                <small id="srp-display" class="form-text text-muted d-block mt-1"></small>
              </div>
              <div class="col-md-4">
                <label class="form-label">Quantity</label>
                <input type="number" name="quantity" step="0.01" class="form-control" value="<?php echo htmlspecialchars($post['quantity']); ?>" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Unit</label>
                <input type="text" name="unit" id="unit-input" class="form-control" value="<?php echo htmlspecialchars($post['unit']); ?>" required>
              </div>
            </div>

            <div class="mb-4">
              <label for="post_images" class="form-label">Add More Images (Up to 10 total)</label>
              <input class="form-control" type="file" id="post_images" name="post_images[]" accept="image/png, image/jpeg, image/gif" multiple <?php echo count($images) >= 10 ? 'disabled' : ''; ?>>
              <?php if (count($images) >= 10): ?>
                <small class="text-danger">Maximum of 10 images reached. Delete some to add new ones.</small>
              <?php else: ?>
                <small class="form-text text-muted">You can upload up to <?php echo 10 - count($images); ?> more photos.</small>
              <?php endif; ?>
            </div>

            <div class="d-flex justify-content-between">
                <button type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" class="btn btn-outline-danger shadow-sm px-4 rounded-pill">
                    <i class="bi bi-trash me-2"></i> Delete Post
                </button>
                <button type="submit" class="btn btn-primary shadow-sm px-5 rounded-pill fw-bold">
                    Save Changes
                </button>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<!-- Custom Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="deleteConfirmModalLabel">Confirm Deletion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body py-4">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill text-danger fs-1 me-3"></i>
            <div>
                <p class="mb-1 fw-bold">Are you sure you want to remove this listing?</p>
                <p class="mb-0 text-secondary small">This will hide it from the marketplace and cannot be undone.</p>
            </div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
        <button type="button" onclick="executeDelete()" class="btn btn-danger rounded-pill px-4 shadow-sm">Delete Permanently</button>
      </div>
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
let deletePostId = <?php echo $post_id; ?>;

function executeDelete() {
    const formData = new FormData();
    formData.append('post_id', deletePostId);
    formData.append('csrf_token', '<?php echo get_csrf_token(); ?>');

    // Close the modal first
    const modalEl = document.getElementById('deleteConfirmModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();

    fetch('action/farmer/delete_post', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'farmer/';
        } else {
            showSystemAlert('Error', data.error || 'Failed to delete post');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        showSystemAlert('System Error', 'A network error occurred. Please try again.');
    });
}

function showSystemAlert(title, message) {
    const modalEl = document.getElementById('systemAlertModal');
    if (modalEl) {
        document.getElementById('alertTitle').textContent = title;
        document.getElementById('alertBody').textContent = message;
        const alertLink = document.getElementById('alertLink');
        if (alertLink) alertLink.style.display = 'none';
        
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    } else {
        alert(message);
    }
}

function updateSRP() {
    var select = document.getElementById('produce-select');
    if (!select) return;
    var selectedOption = select.options[select.selectedIndex];
    
    // Auto-populate unit
    var unit = selectedOption.getAttribute('data-unit');
    var unitInput = document.getElementById('unit-input');
    if (unit && unitInput && !unitInput.value) {
        unitInput.value = unit;
    }

    // Show SRP
    var srp = selectedOption.getAttribute('data-srp');
    var srpDisplay = document.getElementById('srp-display');
    if (srpDisplay) {
        if (srp && srp !== "null") {
            srpDisplay.innerHTML = '<i class="bi bi-info-circle me-1"></i>SRP: ₱' + parseFloat(srp).toLocaleString() + ' / ' + (unit || 'unit');
            srpDisplay.classList.add('text-primary');
            srpDisplay.classList.remove('text-muted');
        } else {
            srpDisplay.innerHTML = '';
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    <?php if ($success_message): ?>
    var toastEl = document.getElementById('successToast');
    if (toastEl) {
        var toast = new bootstrap.Toast(toastEl, { delay: 4000 });
        toast.show();
    }
    <?php endif; ?>

    var produceSelect = document.getElementById('produce-select');
    if (produceSelect) {
        produceSelect.addEventListener('change', updateSRP);
    }
    updateSRP();
});
</script>

<?php include '../includes/universal_footer.php'; ?>
