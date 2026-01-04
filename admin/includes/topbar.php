<div class="topbar">
    <div class="topbar-left">
        <h4><?php echo isset($page_title) ? $page_title : 'Admin Dashboard'; ?></h4>
    </div>
    <div class="topbar-right">
        <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
        <a href="../api/auth.php?action=logout" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to logout?');">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

