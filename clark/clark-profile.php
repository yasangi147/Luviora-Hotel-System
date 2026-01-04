<?php
/**
 * Clark Profile Page - Luviora Hotel System
 * Allows clark to view and edit their profile details
 */

require_once 'auth_check.php';
require_once '../config/database.php';

$db = getDB();

// Get clark email from session
$clarkEmail = $_SESSION['clark_email'] ?? null;

if (!$clarkEmail) {
    header('Location: login.php');
    exit;
}

// Fetch clark profile data using email
$stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'clark'");
$stmt->execute([$clarkEmail]);
$clark = $stmt->fetch();

if (!$clark) {
    header('Location: index.php');
    exit;
}

$clarkId = $clark['user_id'];

$message = '';
$messageType = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');

    if (empty($name) || empty($email)) {
        $message = 'Name and Email are required!';
        $messageType = 'danger';
    } else {
        try {
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, city = ?, country = ?, postal_code = ? WHERE user_id = ?");
            $stmt->execute([$name, $email, $phone, $address, $city, $country, $postal_code, $clarkId]);
            
            $_SESSION['clark_name'] = $name;
            $message = 'Profile updated successfully!';
            $messageType = 'success';
            
            // Refresh clark data
            $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$clarkId]);
            $clark = $stmt->fetch();
        } catch (Exception $e) {
            $message = 'Error updating profile: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Luviora Hotel System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/clark-style.css" rel="stylesheet">
    <link rel="stylesheet" href="common-design-system.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #a0522d 0%, #C38370 100%);
            color: white;
            padding: 40px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin: 0 auto 20px;
        }
        .profile-form {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .form-section {
            margin-bottom: 30px;
        }
        .form-section h5 {
            color: #a0522d;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #C38370;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/topbar.php'; ?>
            
            <div class="container-fluid">
                <!-- Page Header -->
                <div class="page-header">
                    <h1><i class="fas fa-user-circle"></i> My Profile</h1>
                    <p>Manage your profile information</p>
                </div>

                <!-- Alert Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h2><?php echo htmlspecialchars($clark['name']); ?></h2>
                    <p class="mb-0"><?php echo htmlspecialchars($clark['email']); ?></p>
                </div>

                <!-- Profile Form -->
                <div class="profile-form">
                    <form method="POST" action="">
                        <!-- Personal Information -->
                        <div class="form-section">
                            <h5><i class="fas fa-user"></i> Personal Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($clark['name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($clark['email']); ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($clark['phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Address Information -->
                        <div class="form-section">
                            <h5><i class="fas fa-map-marker-alt"></i> Address Information</h5>
                            <div class="mb-3">
                                <label class="form-label">Street Address</label>
                                <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($clark['address'] ?? ''); ?>">
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">City</label>
                                    <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($clark['city'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Country</label>
                                    <input type="text" class="form-control" name="country" value="<?php echo htmlspecialchars($clark['country'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Postal Code</label>
                                    <input type="text" class="form-control" name="postal_code" value="<?php echo htmlspecialchars($clark['postal_code'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Account Information (Read-only) -->
                        <div class="form-section">
                            <h5><i class="fas fa-lock"></i> Account Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Role</label>
                                    <input type="text" class="form-control" value="<?php echo ucfirst($clark['role']); ?>" disabled>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <input type="text" class="form-control" value="<?php echo ucfirst($clark['status']); ?>" disabled>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Member Since</label>
                                    <input type="text" class="form-control" value="<?php echo date('M d, Y', strtotime($clark['created_at'])); ?>" disabled>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Last Updated</label>
                                    <input type="text" class="form-control" value="<?php echo date('M d, Y H:i', strtotime($clark['updated_at'])); ?>" disabled>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="form-section">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

