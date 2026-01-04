<header class="top-header">
    <div class="header-left">
        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="header-title">
            <h2><?php echo $pageTitle ?? 'Dashboard'; ?></h2>
        </div>
    </div>
    
    <div class="header-right">
        <!-- Quick Stats -->
        <div class="header-stats">
            <div class="stat-item">
                <i class="fas fa-calendar-check text-primary"></i>
                <span><?php echo $pendingBookings ?? 0; ?> Pending</span>
            </div>
            <div class="stat-item">
                <i class="fas fa-door-open text-success"></i>
                <span><?php echo $availableRooms ?? 0; ?> Available</span>
            </div>
        </div>
        

        
        <!-- User Profile -->
        <div class="header-profile">
            <div class="profile-info">
                <span class="profile-name"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
                <span class="profile-role"><?php echo ucfirst($_SESSION['admin_role'] ?? 'Administrator'); ?></span>
            </div>
            <div class="profile-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="profile-dropdown">
                <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</header>

<script>
// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
        });
    }
    
    // Profile dropdown toggle
    const profileAvatar = document.querySelector('.profile-avatar');
    const profileDropdown = document.querySelector('.profile-dropdown');
    
    if (profileAvatar && profileDropdown) {
        profileAvatar.addEventListener('click', function() {
            profileDropdown.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.header-profile')) {
                profileDropdown.classList.remove('show');
            }
        });
    }
});
</script>

