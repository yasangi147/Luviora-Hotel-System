<?php
require_once 'auth_check.php';
require_once '../config/database.php';
$db = getDB();
$roomId = $_GET['id'] ?? 0;
$isEdit = $roomId > 0;

// Get all available room specs
$allSpecs = $db->query("SELECT * FROM room_specs WHERE is_active = 1 ORDER BY display_order, spec_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadedImage = null;

    // Handle image upload
    if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../images/rooms/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExtension = strtolower(pathinfo($_FILES['room_image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $fileName = 'room_' . time() . '_' . uniqid() . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['room_image']['tmp_name'], $targetPath)) {
                $uploadedImage = 'images/rooms/' . $fileName;
            }
        }
    }

    if ($isEdit) {
        // Update room
        if ($uploadedImage) {
            $stmt = $db->prepare("UPDATE rooms SET room_number = ?, room_name = ?, room_type = ?, floor = ?, price_per_night = ?, max_occupancy = ?, size_sqm = ?, bed_type = ?, description = ?, status = ?, room_image = ? WHERE room_id = ?");
            $stmt->execute([$_POST['room_number'], $_POST['room_name'], $_POST['room_type'], $_POST['floor'], $_POST['price_per_night'], $_POST['max_occupancy'], $_POST['size_sqm'], $_POST['bed_type'], $_POST['description'], $_POST['status'], $uploadedImage, $roomId]);
        } else {
            $stmt = $db->prepare("UPDATE rooms SET room_number = ?, room_name = ?, room_type = ?, floor = ?, price_per_night = ?, max_occupancy = ?, size_sqm = ?, bed_type = ?, description = ?, status = ? WHERE room_id = ?");
            $stmt->execute([$_POST['room_number'], $_POST['room_name'], $_POST['room_type'], $_POST['floor'], $_POST['price_per_night'], $_POST['max_occupancy'], $_POST['size_sqm'], $_POST['bed_type'], $_POST['description'], $_POST['status'], $roomId]);
        }

        // Update room specs
        $db->prepare("DELETE FROM room_spec_map WHERE room_id = ?")->execute([$roomId]);
    } else {
        // Insert new room
        $imageToUse = $uploadedImage ?? 'images/rooms/default.jpg';
        $stmt = $db->prepare("INSERT INTO rooms (room_number, room_name, room_type, floor, price_per_night, max_occupancy, size_sqm, bed_type, description, status, room_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['room_number'], $_POST['room_name'], $_POST['room_type'], $_POST['floor'], $_POST['price_per_night'], $_POST['max_occupancy'], $_POST['size_sqm'], $_POST['bed_type'], $_POST['description'], $_POST['status'], $imageToUse]);
        $roomId = $db->lastInsertId();
    }

    // Insert selected room specs
    if (!empty($_POST['room_specs'])) {
        $stmt = $db->prepare("INSERT INTO room_spec_map (room_id, spec_id) VALUES (?, ?)");
        foreach ($_POST['room_specs'] as $specId) {
            $stmt->execute([$roomId, $specId]);
        }
    }

    header('Location: rooms.php');
    exit;
}

$room = null;
$selectedSpecs = [];
if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM rooms WHERE room_id = ?");
    $stmt->execute([$roomId]);
    $room = $stmt->fetch();
    if (!$room) { header('Location: rooms.php'); exit; }

    // Get selected specs for this room
    $stmt = $db->prepare("SELECT spec_id FROM room_spec_map WHERE room_id = ?");
    $stmt->execute([$roomId]);
    $selectedSpecs = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Add'; ?> Room | Luviora Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <style>
        .form-section {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #f0f0f0;
        }

        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f5f5f5;
        }

        .section-header i {
            font-size: 24px;
            color: var(--primary-color);
            margin-right: 12px;
        }

        .section-header h5 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(139, 69, 19, 0.15);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .image-upload-area {
            border: 3px dashed #dee2e6;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .image-upload-area:hover {
            border-color: var(--primary-color);
            background: #fff;
        }

        .image-upload-area i {
            font-size: 48px;
            color: #adb5bd;
            margin-bottom: 15px;
        }

        .image-preview {
            max-width: 100%;
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            display: none;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
        }

        .image-preview img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .current-image-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            border: 2px solid #e9ecef;
        }

        .current-image-box img {
            width: 100%;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .spec-checkbox {
            display: flex;
            align-items: center;
            padding: 15px 18px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
            background: #fff;
            cursor: pointer;
        }

        .spec-checkbox:hover {
            background: #f8f9fa;
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .spec-checkbox input[type="checkbox"] {
            width: 22px;
            height: 22px;
            margin-right: 12px;
            cursor: pointer;
            accent-color: var(--primary-color);
        }

        .spec-checkbox input[type="checkbox"]:checked ~ label {
            color: var(--primary-color);
            font-weight: 600;
        }

        .spec-checkbox label {
            margin: 0;
            cursor: pointer;
            flex: 1;
            font-size: 15px;
            color: #495057;
            display: flex;
            align-items: center;
        }

        .spec-icon {
            margin-right: 10px;
            font-size: 18px;
            color: var(--primary-color);
        }

        .btn-primary {
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            background: var(--primary-color);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #6d4c1a;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(139, 69, 19, 0.3);
        }

        .btn-secondary {
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            border: 2px solid #6c757d;
            background: transparent;
            color: #6c757d;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #6c757d;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(108, 117, 125, 0.3);
        }

        .form-actions {
            display: flex;
            gap: 15px;
            padding-top: 20px;
            border-top: 2px solid #f5f5f5;
            margin-top: 30px;
        }

        .input-group-icon {
            position: relative;
        }

        .input-group-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
        }

        .input-group-icon .form-control {
            padding-left: 45px;
        }

        .required-badge {
            color: #dc3545;
            font-weight: bold;
            margin-left: 3px;
        }

        .help-text {
            font-size: 13px;
            color: #6c757d;
            margin-top: 5px;
            display: block;
        }

        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 12px;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>

        <div class="content-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1><i class="fas fa-<?php echo $isEdit ? 'edit' : 'plus'; ?>"></i> <?php echo $isEdit ? 'Edit' : 'Add New'; ?> Room</h1>
                    <a href="rooms.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-lg-8">
                            <!-- Basic Information Section -->
                            <div class="form-section">
                                <div class="section-header">
                                    <i class="fas fa-info-circle"></i>
                                    <h5>Basic Information</h5>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Room Number <span class="required-badge">*</span></label>
                                        <div class="input-group-icon">
                                            <i class="fas fa-door-open"></i>
                                            <input type="text" name="room_number" class="form-control"
                                                   value="<?php echo htmlspecialchars($room['room_number'] ?? ''); ?>"
                                                   placeholder="e.g., 101" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Room Name <span class="required-badge">*</span></label>
                                        <div class="input-group-icon">
                                            <i class="fas fa-tag"></i>
                                            <input type="text" name="room_name" class="form-control"
                                                   value="<?php echo htmlspecialchars($room['room_name'] ?? ''); ?>"
                                                   placeholder="e.g., Deluxe Ocean View" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Room Type <span class="required-badge">*</span></label>
                                        <select name="room_type" class="form-select" required>
                                            <option value="">Select Type</option>
                                            <option value="Single" <?php echo ($room['room_type'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                                            <option value="Double" <?php echo ($room['room_type'] ?? '') === 'Double' ? 'selected' : ''; ?>>Double</option>
                                            <option value="Suite" <?php echo ($room['room_type'] ?? '') === 'Suite' ? 'selected' : ''; ?>>Suite</option>
                                            <option value="Deluxe" <?php echo ($room['room_type'] ?? '') === 'Deluxe' ? 'selected' : ''; ?>>Deluxe</option>
                                            <option value="Presidential" <?php echo ($room['room_type'] ?? '') === 'Presidential' ? 'selected' : ''; ?>>Presidential</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Floor <span class="required-badge">*</span></label>
                                        <input type="number" name="floor" class="form-control"
                                               value="<?php echo htmlspecialchars($room['floor'] ?? '1'); ?>"
                                               min="1" placeholder="1" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Status <span class="required-badge">*</span></label>
                                        <select name="status" class="form-select" required>
                                            <option value="">Select Status</option>
                                            <option value="available" <?php echo ($room['status'] ?? '') === 'available' ? 'selected' : ''; ?>>🟢 Available</option>
                                            <option value="occupied" <?php echo ($room['status'] ?? '') === 'occupied' ? 'selected' : ''; ?>>🔴 Occupied</option>
                                            <option value="reserved" <?php echo ($room['status'] ?? '') === 'reserved' ? 'selected' : ''; ?>>🟠 Reserved</option>
                                            <option value="maintenance" <?php echo ($room['status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>🔵 Maintenance</option>
                                            <option value="cleaning" <?php echo ($room['status'] ?? '') === 'cleaning' ? 'selected' : ''; ?>>🟡 Cleaning</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Price per Night <span class="required-badge">*</span></label>
                                        <div class="input-group-icon">
                                            <i class="fas fa-dollar-sign"></i>
                                            <input type="number" name="price_per_night" class="form-control"
                                                   value="<?php echo htmlspecialchars($room['price_per_night'] ?? ''); ?>"
                                                   step="0.01" placeholder="99.99" required>
                                        </div>
                                        <small class="help-text">Price in USD per night</small>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Max Occupancy <span class="required-badge">*</span></label>
                                        <div class="input-group-icon">
                                            <i class="fas fa-users"></i>
                                            <input type="number" name="max_occupancy" class="form-control"
                                                   value="<?php echo htmlspecialchars($room['max_occupancy'] ?? '2'); ?>"
                                                   min="1" placeholder="2" required>
                                        </div>
                                        <small class="help-text">Maximum number of guests</small>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Size (sqm)</label>
                                        <div class="input-group-icon">
                                            <i class="fas fa-ruler-combined"></i>
                                            <input type="number" name="size_sqm" class="form-control"
                                                   value="<?php echo htmlspecialchars($room['size_sqm'] ?? ''); ?>"
                                                   step="0.01" placeholder="25.5">
                                        </div>
                                        <small class="help-text">Room size in square meters</small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Bed Type</label>
                                    <div class="input-group-icon">
                                        <i class="fas fa-bed"></i>
                                        <input type="text" name="bed_type" class="form-control"
                                               value="<?php echo htmlspecialchars($room['bed_type'] ?? ''); ?>"
                                               placeholder="e.g., 1 King Bed, 2 Queen Beds">
                                    </div>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="4"
                                              placeholder="Enter a detailed description of the room..."><?php echo htmlspecialchars($room['description'] ?? ''); ?></textarea>
                                    <small class="help-text">Provide a detailed description of the room features and highlights</small>
                                </div>
                            </div>

                            <!-- Room Features & Amenities Section -->
                            <div class="form-section">
                                <div class="section-header">
                                    <i class="fas fa-star"></i>
                                    <h5>Room Features & Amenities</h5>
                                </div>

                                <div class="amenities-grid">
                                    <?php foreach ($allSpecs as $spec): ?>
                                    <div class="spec-checkbox">
                                        <input type="checkbox" name="room_specs[]"
                                               value="<?php echo $spec['spec_id']; ?>"
                                               id="spec_<?php echo $spec['spec_id']; ?>"
                                               <?php echo in_array($spec['spec_id'], $selectedSpecs) ? 'checked' : ''; ?>>
                                        <label for="spec_<?php echo $spec['spec_id']; ?>">
                                            <?php if ($spec['spec_icon']): ?>
                                            <i class="<?php echo htmlspecialchars($spec['spec_icon']); ?> spec-icon"></i>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($spec['spec_name']); ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <!-- Room Image Section -->
                            <div class="form-section">
                                <div class="section-header">
                                    <i class="fas fa-image"></i>
                                    <h5>Room Image</h5>
                                </div>

                                <div class="mb-3">
                                    <label for="roomImageInput" class="image-upload-area">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <h6 style="margin: 0; color: #6c757d;">Click to Upload Image</h6>
                                        <small class="text-muted">JPG, JPEG, PNG, WEBP (Max 5MB)</small>
                                    </label>
                                    <input type="file" name="room_image" class="form-control d-none"
                                           accept="image/*" id="roomImageInput">

                                    <div class="image-preview" id="imagePreview">
                                        <img src="" alt="Preview" id="previewImg">
                                    </div>

                                    <?php if ($isEdit && !empty($room['room_image'])): ?>
                                    <div class="current-image-box">
                                        <p class="mb-2" style="font-weight: 600; color: #495057;">
                                            <i class="fas fa-check-circle" style="color: #28a745;"></i> Current Image
                                        </p>
                                        <img src="../<?php echo htmlspecialchars($room['room_image']); ?>"
                                             alt="Current Room Image">
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="form-section">
                                <button type="submit" class="btn btn-primary w-100 mb-3">
                                    <i class="fas fa-save"></i> <?php echo $isEdit ? 'Update' : 'Create'; ?> Room
                                </button>
                                <a href="rooms.php" class="btn btn-secondary w-100">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview functionality
        document.getElementById('roomImageInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>