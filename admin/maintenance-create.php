<?php
/**
 * Create Maintenance Issue
 * Luviora Hotel Management System
 */

require_once 'auth_check.php';
require_once '../config/database.php';

$db = getDB();
$error = '';
$success = '';

// Get rooms and staff for dropdowns
try {
    $rooms = $db->query("SELECT room_id, room_number, room_name FROM rooms ORDER BY room_number")->fetchAll();
    $staff = $db->query("SELECT user_id, name FROM users WHERE role IN ('staff', 'admin', 'maintenance') ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    $error = "Error loading data: " . $e->getMessage();
    $rooms = [];
    $staff = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $room_id = $_POST['room_id'] ?? null;
        $issue_title = trim($_POST['issue_title'] ?? '');
        $issue_description = trim($_POST['issue_description'] ?? '');
        $priority = $_POST['priority'] ?? 'medium';
        $category = $_POST['category'] ?? 'other';
        $assigned_to = $_POST['assigned_to'] ?? null;
        $estimated_cost = $_POST['estimated_cost'] ?? 0;

        if (!$room_id || !$issue_title) {
            $error = 'Room and Issue Title are required.';
        } else {
            $stmt = $db->prepare("
                INSERT INTO maintenance_issues 
                (room_id, reported_by, issue_title, issue_description, priority, category, assigned_to, estimated_cost, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'reported')
            ");

            $stmt->execute([
                $room_id,
                $_SESSION['admin_id'] ?? 1,
                $issue_title,
                $issue_description,
                $priority,
                $category,
                $assigned_to ?: null,
                $estimated_cost
            ]);

            $success = 'Maintenance issue created successfully!';
            header("refresh:2;url=maintenance.php");
        }
    } catch (PDOException $e) {
        $error = 'Error creating maintenance issue: ' . $e->getMessage();
    }
}

$pageTitle = "Create Maintenance Issue";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | Luviora Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-style.css">

    <style>
        /* Modern Form Card Design */
        .modern-form-container {
            background: white;
            border-radius: 20px;
            padding: 0;
            margin-bottom: 30px;
            box-shadow: 0 8px 30px rgba(160, 82, 45, 0.12);
            border: 1px solid rgba(160, 82, 45, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .modern-form-container:hover {
            box-shadow: 0 12px 40px rgba(160, 82, 45, 0.18);
            transform: translateY(-2px);
        }

        /* Form Header with Gradient */
        .form-header {
            background: linear-gradient(135deg, #a0522d 0%, #8b6f47 100%);
            padding: 30px 35px;
            color: white;
            border-bottom: 3px solid #d4a574;
        }

        .form-header h2 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .form-header h2 i {
            background: rgba(255, 255, 255, 0.2);
            padding: 12px;
            border-radius: 12px;
            font-size: 1.5rem;
        }

        .form-header p {
            margin: 10px 0 0 0;
            opacity: 0.95;
            font-size: 0.95rem;
        }

        /* Form Body */
        .form-body {
            padding: 40px 35px;
        }

        /* Section Headers */
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-header i {
            color: #a0522d;
            font-size: 1.3rem;
            background: rgba(160, 82, 45, 0.1);
            padding: 10px;
            border-radius: 10px;
        }

        .section-header h5 {
            margin: 0;
            color: #6b5744;
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Enhanced Form Controls */
        .form-label {
            font-weight: 600;
            color: #6b5744;
            margin-bottom: 8px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .form-label i {
            color: #a0522d;
            font-size: 0.9rem;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background-color: #fafafa;
        }

        .form-control:focus, .form-select:focus {
            border-color: #a0522d;
            box-shadow: 0 0 0 0.2rem rgba(160, 82, 45, 0.15);
            background-color: white;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        /* Priority & Category Visual Indicators */
        .priority-select, .category-select {
            position: relative;
        }

        .priority-select::before {
            content: '🔥';
            position: absolute;
            right: 45px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
            pointer-events: none;
        }

        .category-select::before {
            content: '🔧';
            position: absolute;
            right: 45px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
            pointer-events: none;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
            margin-top: 30px;
        }

        .form-actions .btn {
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .form-actions .btn-primary {
            background: linear-gradient(135deg, #a0522d 0%, #8b6f47 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(160, 82, 45, 0.3);
        }

        .form-actions .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(160, 82, 45, 0.4);
        }

        .form-actions .btn-secondary {
            background: #6c757d;
            border: none;
        }

        .form-actions .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* Alert Enhancements */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 16px 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .alert i {
            margin-right: 8px;
            font-size: 1.1rem;
        }

        /* Input Groups */
        .input-group-text {
            background: #a0522d;
            color: white;
            border: none;
            border-radius: 10px 0 0 10px;
            font-weight: 600;
        }

        /* Required Field Indicator */
        .text-danger {
            color: #dc3545 !important;
            font-weight: 700;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .form-header {
                padding: 25px 20px;
            }

            .form-header h2 {
                font-size: 1.5rem;
            }

            .form-body {
                padding: 25px 20px;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* Page Header Enhancement */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #f0f0f0;
        }

        .page-header h1 {
            margin: 0;
            color: #6b5744;
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header h1 i {
            color: #a0522d;
            background: rgba(160, 82, 45, 0.1);
            padding: 15px;
            border-radius: 15px;
        }

        .page-header .btn {
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .page-header .btn:hover {
            transform: translateX(-5px);
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>

        <div class="content-wrapper">
            <div class="container-fluid">
                <!-- Page Header -->
                <div class="page-header">
                    <h1>
                        <i class="fas fa-tools"></i>
                        Maintenance Management
                    </h1>
                    <a href="maintenance.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>

                <!-- Alerts -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>Error!</strong> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i>
                        <strong>Success!</strong> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Modern Form Container -->
                <div class="modern-form-container">
                    <!-- Form Header -->
                    <div class="form-header">
                        <h2>
                            <i class="fas fa-plus-circle"></i>
                            Create New Maintenance Issue
                        </h2>
                        <p>Report and track maintenance issues for hotel rooms and facilities</p>
                    </div>

                    <!-- Form Body -->
                    <div class="form-body">
                        <form method="POST" class="needs-validation" novalidate>

                            <!-- Section 1: Basic Information -->
                            <div class="section-header">
                                <i class="fas fa-info-circle"></i>
                                <h5>Basic Information</h5>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="room_id" class="form-label">
                                        <i class="fas fa-door-open"></i>
                                        Room <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="room_id" name="room_id" required>
                                        <option value="">Select Room</option>
                                        <?php foreach ($rooms as $room): ?>
                                            <option value="<?php echo $room['room_id']; ?>">
                                                Room <?php echo htmlspecialchars($room['room_number'] . ' - ' . $room['room_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="priority" class="form-label">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Priority Level
                                    </label>
                                    <div class="priority-select">
                                        <select class="form-select" id="priority" name="priority">
                                            <option value="low">🟢 Low - Can wait</option>
                                            <option value="medium" selected>🟡 Medium - Normal priority</option>
                                            <option value="high">🟠 High - Important</option>
                                            <option value="urgent">🔴 Urgent - Immediate attention</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label">
                                        <i class="fas fa-tags"></i>
                                        Issue Category
                                    </label>
                                    <div class="category-select">
                                        <select class="form-select" id="category" name="category">
                                            <option value="plumbing">🚰 Plumbing</option>
                                            <option value="electrical">⚡ Electrical</option>
                                            <option value="hvac">❄️ HVAC (Heating/Cooling)</option>
                                            <option value="furniture">🪑 Furniture</option>
                                            <option value="cleaning">🧹 Cleaning</option>
                                            <option value="other" selected>📋 Other</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="assigned_to" class="form-label">
                                        <i class="fas fa-user-cog"></i>
                                        Assign To Staff
                                    </label>
                                    <select class="form-select" id="assigned_to" name="assigned_to">
                                        <option value="">Unassigned</option>
                                        <?php foreach ($staff as $member): ?>
                                            <option value="<?php echo $member['user_id']; ?>">
                                                <?php echo htmlspecialchars($member['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Section 2: Issue Details -->
                            <div class="section-header">
                                <i class="fas fa-clipboard-list"></i>
                                <h5>Issue Details</h5>
                            </div>

                            <div class="mb-4">
                                <label for="issue_title" class="form-label">
                                    <i class="fas fa-heading"></i>
                                    Issue Title <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control"
                                       id="issue_title"
                                       name="issue_title"
                                       placeholder="e.g., Leaking faucet in bathroom"
                                       required>
                            </div>

                            <div class="mb-4">
                                <label for="issue_description" class="form-label">
                                    <i class="fas fa-align-left"></i>
                                    Detailed Description
                                </label>
                                <textarea class="form-control"
                                          id="issue_description"
                                          name="issue_description"
                                          rows="5"
                                          placeholder="Provide detailed information about the issue, including location, severity, and any other relevant details..."></textarea>
                            </div>

                            <!-- Section 3: Cost Estimation -->
                            <div class="section-header">
                                <i class="fas fa-dollar-sign"></i>
                                <h5>Cost Estimation</h5>
                            </div>

                            <div class="mb-4">
                                <label for="estimated_cost" class="form-label">
                                    <i class="fas fa-money-bill-wave"></i>
                                    Estimated Repair Cost (Optional)
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number"
                                           class="form-control"
                                           id="estimated_cost"
                                           name="estimated_cost"
                                           step="0.01"
                                           min="0"
                                           placeholder="0.00">
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="form-actions">
                                <a href="maintenance.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Create Issue
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function() {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>

