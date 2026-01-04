<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];

// Get user details
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        session_destroy();
        header('Location: ../index.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Profile error: " . $e->getMessage());
    $error = "An error occurred while loading your profile";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Luviora Hotel</title>
    <link rel="shortcut icon" type="image/x-icon" href="../images/favicon.png" />
    <!-- Bootstrap core CSS -->
    <link href="../css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!--Default CSS-->
    <link href="../css/default.css" rel="stylesheet" type="text/css" />
    <!--Custom CSS-->
    <link href="../css/style.css" rel="stylesheet" type="text/css" />
    <!--Plugin CSS-->
    <link href="../css/plugin.css" rel="stylesheet" type="text/css" />
    <!--Font Awesome-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.min.css" />
    <!-- Modern Header Theme -->
    <link href="../css/modern-header.css" rel="stylesheet" type="text/css" />
    <!-- Footer Coral Theme -->
    <link href="../css/footer-coral.css" rel="stylesheet" type="text/css" />
<!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@400;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-brown: #a0522d;
            --secondary-brown: #8b6f47;
            --accent-coral: #C38370;
            --light-bg: #FAF9F6;
            --dark-text: #2E2E2E;
            --white: #FFFFFF;
        }

        * {
            font-family: 'Lato', sans-serif;
        }

        h1, h2, h3, .profile-name {
            font-family: 'Playfair Display', serif;
        }

        .profile-container {
            padding: 60px 0 80px 0;
            background: var(--light-bg);
            min-height: 100vh;
        }

        .profile-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(160, 82, 45, 0.08);
            padding: 40px;
            margin-bottom: 30px;
            border-left: 5px solid var(--accent-coral);
            transition: all 0.3s ease;
        }

        .profile-card:hover {
            box-shadow: 0 6px 20px rgba(160, 82, 45, 0.12);
            transform: translateY(-2px);
        }

        .profile-header {
            text-align: center;
            padding-bottom: 25px;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 0;
        }

        .profile-avatar {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-brown) 0%, var(--accent-coral) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 56px;
            color: var(--white);
            box-shadow: 0 6px 20px rgba(160, 82, 45, 0.3);
            border: 4px solid var(--white);
        }

        .profile-name {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .profile-email {
            color: var(--secondary-brown);
            font-size: 16px;
            margin-bottom: 15px;
            font-weight: 500;
        }

        .profile-badge {
            display: inline-block;
            padding: 10px 24px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 12px;
            background: linear-gradient(135deg, var(--primary-brown) 0%, var(--accent-coral) 100%);
            color: var(--white);
            letter-spacing: 1px;
            box-shadow: 0 4px 12px rgba(160, 82, 45, 0.2);
        }

        .info-group {
            margin-bottom: 28px;
        }

        .info-label {
            font-weight: 700;
            color: var(--primary-brown);
            font-size: 13px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info-value {
            font-size: 16px;
            color: var(--dark-text);
            font-weight: 500;
            line-height: 1.6;
        }

        .form-control {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 12px 15px;
            font-family: 'Lato', sans-serif;
            transition: all 0.3s ease;
            font-size: 15px;
        }

        .form-control:focus {
            border-color: var(--accent-coral);
            box-shadow: 0 0 0 0.2rem rgba(195, 131, 112, 0.25);
        }

        .edit-btn {
            background: linear-gradient(135deg, var(--primary-brown) 0%, var(--accent-coral) 100%);
            color: var(--white);
            border: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(160, 82, 45, 0.2);
            font-size: 13px;
        }

        .edit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(160, 82, 45, 0.3);
            background: linear-gradient(135deg, var(--accent-coral) 0%, var(--primary-brown) 100%);
        }

        .nav-tabs {
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 30px;
        }

        .nav-tabs .nav-link {
            color: var(--secondary-brown);
            font-weight: 700;
            border: none;
            padding: 15px 25px;
            font-family: 'Lato', sans-serif;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 13px;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary-brown);
            border-bottom: 3px solid var(--accent-coral);
            background: transparent;
        }

        .btn-outline-secondary {
            color: var(--primary-brown);
            border-color: var(--primary-brown);
            background: transparent;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-outline-secondary:hover {
            background: var(--primary-brown);
            border-color: var(--primary-brown);
            color: var(--white);
        }

        .btn-outline-primary {
            color: var(--accent-coral);
            border-color: var(--accent-coral);
            background: transparent;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-outline-primary:hover {
            background: var(--accent-coral);
            border-color: var(--accent-coral);
            color: var(--white);
        }

        .btn-outline-danger {
            color: #e74c3c;
            border-color: #e74c3c;
            background: transparent;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-outline-danger:hover {
            background: #e74c3c;
            border-color: #e74c3c;
            color: var(--white);
        }

        .button-group {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }

        .button-group .btn {
            padding: 14px 24px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border: 2px solid;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            background: linear-gradient(135deg, var(--primary-brown) 0%, var(--accent-coral) 100%);;
        }

        .button-group .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .button-group .btn i {
            font-size: 14px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include '../includes/profile_header.php'; ?>

    <div class="profile-container" style="margin-top: 100px;">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <div class="profile-card">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="profile-name"><?php echo htmlspecialchars($user['name']); ?></div>
                            <div class="profile-email"><?php echo htmlspecialchars($user['email']); ?></div>
                            <span class="profile-badge badge-<?php echo $user['role']; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </div>
                        <div class="button-group">
                            <a href="../index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-home"></i> Back to Home
                            </a>
                            <a href="bookings.php" class="btn btn-outline-primary">
                                <i class="fas fa-calendar"></i> My Bookings
                            </a>
                            <a href="#" onclick="handleLogout(); return false;" class="btn btn-outline-danger">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="profile-card">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#profile-info">
                                    <i class="fas fa-user-circle"></i> Profile Information
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#security">
                                    <i class="fas fa-lock"></i> Security
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- Profile Information Tab -->
                            <div id="profile-info" class="tab-pane fade show active">
                                <form id="profileForm">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="info-group">
                                                <label class="info-label">Full Name</label>
                                                <input type="text" class="form-control" id="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-group">
                                                <label class="info-label">Email Address</label>
                                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-group">
                                                <label class="info-label">Phone Number</label>
                                                <input type="text" class="form-control" id="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-group">
                                                <label class="info-label">City</label>
                                                <input type="text" class="form-control" id="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-group">
                                                <label class="info-label">Country</label>
                                                <input type="text" class="form-control" id="country" value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-group">
                                                <label class="info-label">Postal Code</label>
                                                <input type="text" class="form-control" id="postal_code" value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="info-group">
                                                <label class="info-label">Address</label>
                                                <textarea class="form-control" id="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="edit-btn">
                                        <i class="fas fa-save"></i> Update Profile
                                    </button>
                                </form>
                            </div>

                            <!-- Security Tab -->
                            <div id="security" class="tab-pane fade">
                                <h5 class="mb-4">Change Password</h5>
                                <form id="passwordForm">
                                    <div class="info-group">
                                        <label class="info-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" required>
                                    </div>
                                    <div class="info-group">
                                        <label class="info-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" required>
                                    </div>
                                    <div class="info-group">
                                        <label class="info-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" required>
                                    </div>
                                    <button type="submit" class="edit-btn">
                                        <i class="fas fa-key"></i> Change Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/profile_footer.php'; ?>

    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugin.js"></script>
    <script src="../js/auth.js"></script>
    <script>
        // Update profile
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const data = {
                name: document.getElementById('name').value,
                phone: document.getElementById('phone').value,
                address: document.getElementById('address').value,
                city: document.getElementById('city').value,
                country: document.getElementById('country').value,
                postal_code: document.getElementById('postal_code').value
            };

            fetch('../api/auth.php?action=update_profile', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showAlert('success', result.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('error', result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'An error occurred');
            });
        });

        // Change password
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const data = {
                current_password: document.getElementById('current_password').value,
                new_password: document.getElementById('new_password').value,
                confirm_password: document.getElementById('confirm_password').value
            };

            fetch('../api/auth.php?action=change_password', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showAlert('success', result.message);
                    document.getElementById('passwordForm').reset();
                } else {
                    showAlert('error', result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'An error occurred');
            });
        });
    </script>

    <!-- Additional Scripts -->
    <script src="../js/main.js"></script>
    <script src="../js/custom-nav.js"></script>
</body>
</html>

