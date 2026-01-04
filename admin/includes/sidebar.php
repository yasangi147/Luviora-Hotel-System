<aside class="sidebar">
    <div class="sidebar-header">
        <a href="index.php" class="sidebar-brand">
            <img src="../images/logonew.png" alt="Luviora Hotel" onerror="this.style.display='none'">
            <span>Luviora Admin</span>
        </a>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <a href="index.php" class="nav-link" style="margin-left: -20px;">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="reservations.php" class="nav-link" style="margin-left: -20px;">
                    <i class="fas fa-calendar-check"></i>
                    <span>Reservations</span>
                </a>
            </li>
            
            <li class="nav-item has-submenu">
                <a href="#" class="nav-link" style="margin-left: -20px;">
                    <i class="fas fa-hotel"></i>
                    <span>Rooms</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <li><a href="rooms.php" style="margin-left: -40px;">All Rooms</a></li>
                    <li><a href="room-form.php" style="margin-left: -40px;">Add Rooms</a></li>
                </ul>
            </li>
            
            <li class="nav-item">
                <a href="guests.php" class="nav-link" style="margin-left: -20px;">
                    <i class="fas fa-users"></i>
                    <span>Guests</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="staff.php" class="nav-link" style="margin-left: -20px;">
                    <i class="fas fa-user-tie"></i>
                    <span>Staff Management</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="maintenance.php" class="nav-link" style="margin-left: -20px;">
                    <i class="fas fa-tools"></i>
                    <span>Maintenance</span>
                </a>
            </li>

           

            <li class="nav-item">
                <a href="qr-codes.php" class="nav-link" style="margin-left: -20px;">
                    <i class="fas fa-qrcode"></i>
                    <span>QR Code Management</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="payments.php" class="nav-link" style="margin-left: -20px;">
                    <i class="fas fa-credit-card"></i>
                    <span>Payments</span>
                </a>
            </li>
            
            <li class="nav-item has-submenu">
                <a href="#" class="nav-link" style="margin-left: -20px;">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports & Analytics</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <li><a href="occupancy-reports.php" style="margin-left: -40px;">Occupancy Reports</a></li>
                    <li><a href="revenue-reports.php" style="margin-left: -40px;">Revenue Reports</a></li>
                </ul>
            </li>
            
            <li class="nav-item has-submenu">
                <a href="#" class="nav-link" style="margin-left: -20px;">
                    <i class="fas fa-comments"></i>
                    <span>Feedback & Reviews</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <li><a href="feedback.php" style="margin-left: -40px;">Guest Feedback</a></li>

                </ul>
            </li>
            
            <li class="nav-item" style="margin-left: -20px;">
                <a href="admin-profile.php" class="nav-link">
                    <i class="fas fa-user-circle"></i>
                    <span>Admin Profile</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="logout.php" class="nav-link" style="margin-left: -20px;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

<script>
// Sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        });
    }
    
    // Submenu toggle
    const submenuToggles = document.querySelectorAll('.has-submenu > .nav-link');
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.parentElement;
            parent.classList.toggle('open');
        });
    });
});
</script>

