<?php
/**
 * Admin Login Page - Luviora Hotel System
 * Hardcoded admin credentials: admin@luviora.com / admin@123
 */

session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Hardcoded admin credentials
    if ($email === 'admin@luviora.com' && $password === 'admin@123') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email'] = $email;
        $_SESSION['admin_name'] = 'System Administrator';
        $_SESSION['admin_role'] = 'admin';
        
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid email or password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Luviora Hotel System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #a0522d;
            --secondary-color: #8b6f47;
            --accent-color: #d4a574;
            --dark-color: #2e2e2e;
            --light-bg: #f8f9fa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .login-header p {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }

        .login-body {
            padding: 40px 30px;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(160, 82, 45, 0.15);
        }

        .input-group-text {
            background: var(--light-bg);
            border: 2px solid #e0e0e0;
            border-right: none;
            border-radius: 10px 0 0 10px;
            color: var(--primary-color);
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(160, 82, 45, 0.3);
            color: white;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .alert-danger {
            background: #fee;
            color: #c33;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-section img {
            max-width: 180px;
            height: auto;
        }

        .back-to-site {
            text-align: center;
            margin-top: 20px;
        }

        .back-to-site a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            transition: opacity 0.3s ease;
        }

        .back-to-site a:hover {
            opacity: 0.8;
        }

        .role-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            margin-top: 10px;
        }

        .logo-section img{
            max-width: 100px;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <img src="../images/logonew.png" alt="Luviora Hotel" onerror="this.style.display='none'">
        </div>
        
        <div class="login-card">
            <div class="login-header">
                <h1><i class="fas fa-shield-alt"></i> Admin Portal</h1>
                <p>Luviora Hotel Management System</p>
                <span class="role-badge">Admin Dashboard</span>
            </div>

            <div class="login-body">
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control" id="email" name="email"
                                   placeholder="admin@luviora.com" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="Enter your password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                    </button>
                </form>

                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        Default: admin@luviora.com / admin@123
                    </small>
                </div>
            </div>
        </div>
        
        <div class="back-to-site">
            <a href="../index.html">
                <i class="fas fa-arrow-left"></i> Back to Main Website
            </a>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

