<?php
// Start session for authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user info for profile pages
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$user_name = $is_logged_in ? $_SESSION['user_name'] : '';
$user_role = $is_logged_in ? $_SESSION['user_role'] : '';

function getProfileUrl($role) {
    switch ($role) {
        case 'admin':
            return '../admin/index.php';
        case 'clark':
            return '../clark/index.php';
        case 'staff':
            return '../staff/index.php';
        default:
            return '../profile/index.php';
    }
}
?>
<header class="main_header_area">
  <div class="header-content">
    <div class="container">
      <div class="links links-left">
        <ul>
          <li>
            <a href="#"><i class="fa fa-envelope" aria-hidden="true"></i> info@luviorahotel.com</a>
          </li>
          <li>
            <a href="#"><i class="fa fa-phone" aria-hidden="true"></i> +94 082 1234 567</a>
          </li>
        </ul>
      </div>
      <!-- Profile Header User Dropdown (Always show for logged-in users) -->
      <div class="links links-right pull-right">
        <ul>
          <?php if ($is_logged_in): ?>
            <!-- Logged In User -->
            <li class="dropdown user-dropdown-profile">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-user" aria-hidden="true"></i>
                <span class="user-name-display"><?php echo htmlspecialchars($user_name); ?></span>
                <i class="fa fa-angle-down"></i>
              </a>
              <ul class="dropdown-menu dropdown-menu-right">
                <li><a href="<?php echo getProfileUrl($user_role); ?>"><i class="fa fa-user-circle"></i> My Profile</a></li>
                <?php if ($user_role === 'guest'): ?>
                  <li><a href="../profile/bookings.php"><i class="fa fa-calendar"></i> My Bookings</a></li>
                <?php endif; ?>
                <li class="divider"></li>
                <li><a href="#" onclick="handleLogout(); return false;"><i class="fa fa-sign-out"></i> Logout</a></li>
              </ul>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
  <!-- Navigation Bar -->
  <div class="header_menu">
    <div class="container">
      <nav class="navbar navbar-default">
        <div class="navbar-header">
          <a class="navbar-brand" href="../index.php">
            <img alt="logo" src="../images/luvioralogoblack.png" class="logo-black" style="width: 200px; height: 50px;"/>
          </a>
        </div>
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
          <ul class="nav navbar-nav" id="responsive-menu">
            <li class="dropdown submenu">
              <a href="../index.php">Home</a>
            </li>
            <li class="submenu dropdown">
              <a href="../aboutus.php">About Us</a>
            </li>
            <li class="submenu dropdown">
              <a href="../roomlist-1.php">Rooms</a>
            </li>
            <li class="submenu dropdown">
              <a href="../testimonial.php">Testimonials</a>
            </li>
            <li class="submenu dropdown">
              <a href="../blog-full.php">Blog</a>
            </li>
            <li class="submenu dropdown">
              <a href="../gallery.php">Gallery</a>
            </li>
            <li class="submenu dropdown">
              <a href="../service.php">Services</a>
            </li>
            <li class="submenu dropdown">
              <a href="../contact.php">Contact Us</a>
            </li>
          </ul>
          <div class="nav-btn">
            <a href="../availability.php" class="btn btn-orange" style="margin-right: 20px;">Book Now</a>
          </div>
        </div>
        <div id="slicknav-mobile"></div>
      </nav>
    </div>
  </div>
</header>

<style>
/* Hide Login and Register buttons on profile pages */
.header-content .links-right li:not(.user-dropdown-profile) {
    display: none !important;
}

.header-content .links-right::after,
.header-content .links-right::before {
    display: none !important;
}

/* User Dropdown Styling for Profile Pages */
.user-dropdown-profile {
    position: relative;
}

.user-dropdown-profile .user-name-display {
    font-weight: 600;
    color: #2E2E2E;
    margin: 0 5px;
}

.user-dropdown-profile .dropdown-toggle {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 15px;
    transition: all 0.3s ease;
}

.user-dropdown-profile .dropdown-toggle:hover {
    color: #C38370 !important;
}

.user-dropdown-profile .dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    left: auto;
    min-width: 200px;
    background: #fff;
    border: 1px solid rgba(0,0,0,0.1);
    border-radius: 4px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    padding: 5px 0;
    margin-top: 10px;
    display: none;
    z-index: 1000;
}

.user-dropdown-profile .dropdown-menu.show {
    display: block;
}

.user-dropdown-profile .dropdown-menu li {
    list-style: none;
}

.user-dropdown-profile .dropdown-menu li a {
    display: block;
    padding: 10px 20px;
    color: #2E2E2E;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 14px;
}

.user-dropdown-profile .dropdown-menu li a i {
    margin-right: 10px;
    width: 16px;
    text-align: center;
}

.user-dropdown-profile .dropdown-menu li a:hover {
    background: rgba(195, 131, 112, 0.1);
    color: #C38370;
}

.user-dropdown-profile .dropdown-menu li.divider {
    height: 1px;
    background: #e0e0e0;
    margin: 5px 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .user-dropdown-profile .user-name-display {
        display: none;
    }

    .user-dropdown-profile .dropdown-menu {
        right: -10px;
    }
}
</style>

<script>
// User Dropdown Toggle for Profile Pages
document.addEventListener('DOMContentLoaded', function() {
    const dropdownToggle = document.querySelector('.user-dropdown-profile .dropdown-toggle');
    const dropdownMenu = document.querySelector('.user-dropdown-profile .dropdown-menu');

    if (dropdownToggle && dropdownMenu) {
        // Toggle dropdown on click
        dropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-dropdown-profile')) {
                dropdownMenu.classList.remove('show');
            }
        });

        // Close dropdown when clicking on a menu item (except logout)
        const menuItems = dropdownMenu.querySelectorAll('li a');
        menuItems.forEach(function(item) {
            item.addEventListener('click', function(e) {
                if (!this.getAttribute('onclick')) {
                    dropdownMenu.classList.remove('show');
                }
            });
        });
    }
});

// Logout Function
function handleLogout() {
    if (confirm('Are you sure you want to logout?')) {
        fetch('../api/auth.php?action=logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.data.redirect;
            }
        })
        .catch(error => {
            console.error('Logout error:', error);
            window.location.href = '../index.php';
        });
    }
}
</script>

