<?php
/**
 * Dynamic User Header Component
 * Shows login/register buttons or user profile menu based on login status
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $_SESSION['user_name'] ?? '';
$userEmail = $_SESSION['user_email'] ?? '';
?>

<!-- Dynamic Header Navigation -->
<style>
    .user-nav-section {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .user-profile-menu {
        position: relative;
        display: inline-block;
    }
    .user-profile-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 15px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 25px;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
        transition: transform 0.2s;
    }
    .user-profile-btn:hover {
        transform: translateY(-2px);
    }
    .user-profile-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(255,255,255,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }
    .dropdown-menu-user {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        min-width: 200px;
        display: none;
        z-index: 1000;
        margin-top: 10px;
    }
    .dropdown-menu-user.show {
        display: block;
    }
    .dropdown-menu-user a,
    .dropdown-menu-user button {
        display: block;
        width: 100%;
        padding: 12px 20px;
        text-align: left;
        border: none;
        background: none;
        color: #333;
        text-decoration: none;
        font-size: 14px;
        cursor: pointer;
        transition: background 0.2s;
    }
    .dropdown-menu-user a:hover,
    .dropdown-menu-user button:hover {
        background: #f5f5f5;
    }
    .dropdown-menu-user a:first-child {
        border-radius: 8px 8px 0 0;
    }
    .dropdown-menu-user a:last-child,
    .dropdown-menu-user button:last-child {
        border-radius: 0 0 8px 8px;
    }
    .dropdown-divider {
        height: 1px;
        background: #eee;
        margin: 5px 0;
    }
    .auth-buttons {
        display: flex;
        gap: 10px;
    }
    .btn-login-header {
        padding: 8px 20px;
        background: transparent;
        color: #667eea;
        border: 2px solid #667eea;
        border-radius: 25px;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
        text-decoration: none;
        transition: all 0.2s;
    }
    .btn-login-header:hover {
        background: #667eea;
        color: white;
    }
    .btn-register-header {
        padding: 8px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 25px;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
        text-decoration: none;
        transition: transform 0.2s;
    }
    .btn-register-header:hover {
        transform: translateY(-2px);
    }
</style>

<div class="user-nav-section">
    <?php if ($isLoggedIn): ?>
        <!-- User is logged in - show profile menu -->
        <div class="user-profile-menu">
            <button class="user-profile-btn" onclick="toggleUserMenu(event)">
                <div class="user-profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <span><?php echo htmlspecialchars(explode(' ', $userName)[0]); ?></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="dropdown-menu-user" id="userDropdownMenu">
                <a href="<?php echo dirname($_SERVER['PHP_SELF']) === '/' ? 'profile/index.php' : '../profile/index.php'; ?>">
                    <i class="fas fa-user-circle"></i> My Profile
                </a>
                <a href="<?php echo dirname($_SERVER['PHP_SELF']) === '/' ? 'profile/bookings.php' : '../profile/bookings.php'; ?>">
                    <i class="fas fa-calendar-check"></i> My Bookings
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?php echo dirname($_SERVER['PHP_SELF']) === '/' ? 'logout.php' : '../logout.php'; ?>">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- User is not logged in - show login/register buttons -->
        <div class="auth-buttons">
            <a href="login.php" class="btn-login-header">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
            <a href="register.php" class="btn-register-header">
                <i class="fas fa-user-plus"></i> Register
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleUserMenu(event) {
    event.stopPropagation();
    const menu = document.getElementById('userDropdownMenu');
    menu.classList.toggle('show');
}

// Close menu when clicking outside
document.addEventListener('click', function(event) {
    const menu = document.getElementById('userDropdownMenu');
    if (menu && !event.target.closest('.user-profile-menu')) {
        menu.classList.remove('show');
    }
});
</script>

