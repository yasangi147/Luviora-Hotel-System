<?php
// Start session for authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include functions (which includes database connection)
require_once 'includes/functions.php';

// Get all active rooms from database
$db = getDB();
$query = "
    SELECT DISTINCT
        r.room_id,
        r.room_number,
        r.room_name,
        r.room_type,
        r.floor,
        r.price_per_night,
        r.max_occupancy,
        r.size_sqm,
        r.bed_type,
        r.view_type,
        r.room_style,
        r.ideal_for,
        r.description,
        r.room_image,
        r.additional_images,
        r.rating,
        r.popularity_score,
        r.is_pet_friendly,
        r.is_accessible,
        r.is_smoking_allowed,
        r.free_cancellation,
        r.breakfast_included,
        GROUP_CONCAT(DISTINCT rs.spec_name SEPARATOR ', ') as amenities
    FROM rooms r
    LEFT JOIN room_spec_map rsm ON r.room_id = rsm.room_id
    LEFT JOIN room_specs rs ON rsm.spec_id = rs.spec_id
    WHERE r.is_active = TRUE
    GROUP BY r.room_id
    ORDER BY r.popularity_score DESC, r.rating DESC, r.price_per_night ASC
";

$stmt = $db->prepare($query);
$stmt->execute();
$rooms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="zxx">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>Room List 1 | Hotux</title>
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.png" />
    <!-- Bootstrap core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!--Default CSS-->
    <link href="css/default.css" rel="stylesheet" type="text/css" />
    <!--Custom CSS-->
    <link href="css/style.css" rel="stylesheet" type="text/css" />
    <!--Plugin CSS-->
    <link href="css/plugin.css" rel="stylesheet" type="text/css" />
    <!--Font Awesome-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.min.css" />
    <!-- Rounded Corners CSS -->
    <link href="css/rounded-corners.css" rel="stylesheet" type="text/css" />
    <!-- Coral Theme CSS -->

    <!-- Modern Header Theme -->
    <link href="css/modern-header.css" rel="stylesheet" type="text/css" />
    <!-- Footer Coral Theme -->
    <link href="css/footer-coral.css" rel="stylesheet" type="text/css" />
    <!-- News and Reviews Coral Theme -->
    <link href="css/news-reviews-coral.css" rel="stylesheet" type="text/css" />
    <!-- Elegant Color Scheme -->
    <link href="css/elegant-colors.css" rel="stylesheet" type="text/css" />
    <!-- Luxury About Styles -->
    <link href="css/aboutus-luxury.css" rel="stylesheet" type="text/css" />
    <!-- Elegant Rooms Styles -->
    <link href="css/elegant-rooms.css" rel="stylesheet" type="text/css" />

    <!-- Modern Reviews & Gallery Styles -->
    <link href="css/modern-reviews-gallery.css" rel="stylesheet" type="text/css" />
    
    <style>

      section.elegant-rooms .section-title {
        width: 100% !important;
        margin: 0 auto 80px !important;
        text-align: center !important;
        background: transparent !important;
      }

      section.elegant-rooms .section-title h2 {
        color: #8b6f47 !important;
        font-size: 36px !important;
        margin: 0 !important;
        font-family: 'Playfair Display', serif !important;
        font-weight: 300 !important;
        letter-spacing: 6px !important;
        text-transform: uppercase !important;
      }

      section.elegant-rooms .section-title p {
        color: #6b5744 !important;
        font-size: 16px !important;
        margin: 30px auto 0 !important;
        max-width: 600px !important;
        font-family: 'Lato', sans-serif !important;
        line-height: 1.7 !important;
      }

      section.elegant-rooms .section-title span {
        color: #a0522d !important;
        font-weight: 700 !important;
      }

      .wishlist-heart {
        display: none;
      }

      #back-to-top a {
        display: block;
        width: 40px;
        height: 40px;
        background: #a0522d;
        position: relative;
      }

      section.elegant-rooms .section-title h2{
        color: #2E2E2E !important;
        font-size: 36px !important;
        font-weight: bold;
        margin: 0 !important;
        font-family: 'Playfair Display', serif !important;
        font-weight: 900 !important;
        letter-spacing: 5px !important;
        text-transform: uppercase !important;
      }

      section.elegant-rooms .section-title span{
        color: #C38370 !important;
        font-weight: 700 !important;
      }

      h1, h2 {
        font-weight: 700;
        color: #343a40;
        font-family: 'Playfair Display', serif;
        margin: 0 0 15px;
        line-height: 1.4;
        text-transform: uppercase;
      }

      .nav-btn .btn-orange {
        background: #C38370 !important; 
        color: #FAF9F6 !important;
        border: none !important;
        padding: 12px 28px;
        border-radius: 4px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        transition: all 0.3s ease;
        margin-left: 15px;
        box-shadow: 0 2px 10px rgba(195, 131, 112, 0.2);
      }

      /* Hover effect */
      .nav-btn .btn-orange:hover {
        background: #FFFFFF !important;  /* White background */
        color: #000000 !important;       /* Black text */
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15); /* Optional hover shadow for better effect */
      }

      .table-item .form-btn .btn-orange {
        background-color:  #C38370 !important;
        color: #FAF9F6 !important;
        padding: 10px 25px;
        transition: all 0.3s ease;
      }

      .table-item .form-btn .btn.btn-orange:hover {
        background-color:  #a55d42 !important; /* deep brown */
        color:  #ffffff !important;     /* keeps warm brown tone for text */
        border-color:  #d19a7a00 !important;
        box-shadow:  0 8px 15px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
      } 

      /* Override template hover */
      .btn-orange:not(.navbar .btn-orange):not(.nav .btn-orange):hover {
        background: #a55d42 !important; /* deep brown */
        color: #ffffff !important;     /* keeps warm brown tone for text */
        border-color: #d19a7a00 !important;
        box-shadow:  0 8px 15px rgba(0, 0, 0, 0.15);
        transition: 0.3s ease;
      }

      .btn-book-elegant {
        background: #a55d42 !important;
        color: #ffffff !important;     /* keeps warm brown tone for text */
        padding: 12px 24px;
        border-radius: 8px;
        font-family: 'Lato', sans-serif;
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        border: none;
        cursor: pointer;
        transition: all 0.3s 
    ease;
        text-decoration: none;
        display: inline-block;
        box-shadow: 0 4px 16px rgba(160, 82, 45, 0.3);
     }
      /* Hover effect for Book Now button */
      .btn-book-elegant:hover {
        background: #C38370 !important; /* deep brown */
        color: #ffffff !important;     /* keeps warm brown tone for text */
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2); /* Optional: subtle shadow on hover */
        transition: 0.3s ease;
      }

       .slider-btn {
        background: #a55d42 !important; /* deep brown */
        color: #ffffff !important;     /* keeps warm brown tone for text */
        padding: 12px 24px;
        border-radius: 8px;
        font-family: 'Lato', sans-serif;
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        border: none;
        cursor: pointer;
        transition: all 0.3s 
    ease;
        text-decoration: none;
        display: inline-block;
        box-shadow: 0 4px 16px rgba(160, 82, 45, 0.3);
     }
      /* Hover effect for Book Now button */
      .slider-btn:hover {
        background: #C38370 !important;
        color: #ffffff !important;     /* keeps warm brown tone for text */
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2); /* Optional: subtle shadow on hover */
        transition: 0.3s ease;
      }


      .footer-logo{
        padding-bottom: 90px;
        padding-top: 10px;
        margin-top: -10px;
      }

      .section-btn {
        text-align: center;
        padding-top: 40px;
      }

      section.breadcrumb-outer {
        background: url(../images/breadcrumb.jpg) no-repeat;
        background-size: cover;
        background-position: center;
        position: relative;
        text-align: center;
        padding: 260px 0 150px;
      }

      .award-content{
        background-color: #a55d42;
      }

    </style>
</head>
  <body>
    <!-- Preloader -->
    <div id="preloader">
      <div id="status"></div>
    </div>
    <!-- Preloader Ends -->
     
    <!-- header start -->
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
          <?php include 'includes/auth_header.php'; ?>
        </div>
      </div>
      <!-- Navigation Bar -->
      <div class="header_menu">
        <div class="container">
          <nav class="navbar navbar-default">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
              <a class="navbar-brand" href="index.php">
                <img alt="logo" src="images/luvioralogoblack.png" class="logo-black" style="width: 200px; height: 50px;"/>
                <img alt="logo1" src="images/luvioralogoblack.png" class="logo-black" style="width: 200px; height: 50px;"/>
              </a>
            </div>
            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
              <ul class="nav navbar-nav" id="responsive-menu">
                <li class="dropdown submenu ">
                  <a href="index.php" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                    >Home</a>
                </li>

                <li class="submenu dropdown ">
                  <a href="aboutus.php" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                    >About Us</a>
                </li>

                <li class="submenu dropdown active">
                  <a href="roomlist-1.php" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                    >Rooms</a>
                </li>

                <li class="submenu dropdown">
                  <a href="testimonial.php" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                    >Testimonials</a>
                </li>

                <li class="submenu dropdown">
                  <a href="blog-full.php" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                    >Blog</a>
                </li>

                <li class="submenu dropdown">
                  <a href="gallery.php" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                    >Gallery</a>
                </li>

                <li class="submenu dropdown">
                  <a href="service.php" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                    >Services</a>
                </li>

                <li class="submenu dropdown">
                  <a href="contact.php" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                    >Contact Us</a>
                </li>

              </ul>
              <div class="nav-btn">
                <a href="availability.php" class="btn btn-orange" style="margin-right: 20px;">Book Now</a>
              </div>
            </div>
            <!-- /.navbar-collapse -->
            <div id="slicknav-mobile"></div>
          </nav>
        </div>
        <!-- /.container-fluid -->
      </div>
      <!-- Navigation Bar Ends -->
    </header>
    <!-- header Ends -->

    <!-- breadcrumb starts -->
    <section class="breadcrumb-outer" style="object-fit: cover; background-image: url('images/ab2.jpeg')">
      <div class="container">
        <div class="breadcrumb-content">
          <h2>Rooms</h2>
        </div>
      </div>
    </section>
    <!-- breadcrumb Ends -->

    <!-- Elegant Rooms Section -->
    <section class="elegant-rooms" style="display: block !important; visibility: visible !important;">
      <div class="container">
        <div class="section-title" style="display: block !important; visibility: visible !important; opacity: 1 !important; ">
          <h2 style="display: block !important; visibility: visible !important; opacity: 1 !important;">Explore <span>Rooms</span></h2>
          <p style="display: block !important; visibility: visible !important; opacity: 1 !important; ">Experience elegant comfort in our beautifully designed rooms with modern amenities, serene ambiance, and exceptional service at Luviora.</p>
        </div>
        
        <div class="rooms-masonry">
          <?php foreach ($rooms as $room): ?>
          <div class="room-card-elegant animate-on-scroll">
            <div class="room-image-elegant">
              <img src="<?php echo htmlspecialchars($room['room_image'] ?: 'images/room1.jpeg'); ?>" alt="<?php echo htmlspecialchars($room['room_name']); ?>" />
              <div class="luxury-badge">Luxury</div>
              <?php if (isset($room['popularity_score']) && $room['popularity_score'] > 80): ?>
              <div class="room-highlight">Most Popular</div>
              <?php endif; ?>
              <div class="wishlist-heart">
                <i class="far fa-heart"></i>
              </div>
              <button class="quick-view-btn">Quick View</button>
            </div>
            <div class="room-content-elegant">
              <div class="price-elegant">
                <span class="price-number">$<?php echo number_format($room['price_per_night'], 0); ?></span>
                <span class="price-text">Per Night</span>
              </div>
              <h3 class="room-title-elegant"><?php echo htmlspecialchars($room['room_name']); ?></h3>
              <div class="rating-elegant">
                <div class="stars-elegant">
                  <?php for ($i = 0; $i < 5; $i++): ?>
                  <i class="fas fa-star star-elegant"></i>
                  <?php endfor; ?>
                </div>
                <span class="rating-text"><?php echo number_format($room['rating'], 1); ?> rating</span>
              </div>
              <p class="room-description"><?php echo htmlspecialchars(substr($room['description'], 0, 100)); ?>...</p>
              <div class="amenities-elegant">
                <div class="amenity-icon">
                  <i class="fas fa-bed"></i>
                  <span><?php echo htmlspecialchars($room['bed_type']); ?></span>
                </div>
                <div class="amenity-icon">
                  <i class="fas fa-wifi"></i>
                  <span>Free WiFi</span>
                </div>
                <?php if ($room['view_type'] && $room['view_type'] !== 'None'): ?>
                <div class="amenity-icon">
                  <i class="fas fa-eye"></i>
                  <span><?php echo htmlspecialchars($room['view_type']); ?> View</span>
                </div>
                <?php endif; ?>
                <div class="amenity-icon">
                  <i class="fas fa-expand-arrows-alt"></i>
                  <span><?php echo $room['size_sqm']; ?>m�</span>
                </div>
              </div>
              <div class="room-actions">
                <a href="availability.php" class="btn-book-elegant">Book Now</a>
                <a href="#" class="btn-icon-elegant">
                  <i class="fas fa-bed"></i>
                </a>
                <a href="#" class="btn-icon-elegant">
                  <i class="fas fa-wifi"></i>
                </a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <!-- Elegant Rooms Section Ends -->

    <!-- Footer Starts -->
    <footer>
      <div class="footer-top pad-bottom-20">
        <div class="container">
          <div class="footer-logo text-center">
            <img src="images/luvioralogo.png" alt="Image" style="width: 200px; height: 50px; margin-top: 10px;" />
          </div>
          <div class="footer-content">
            <div class="row">
              <div class="col-lg-3 col-md-6 mar-bottom-30">
                <div class="footer-about">
                  <h4>Company Info</h4>
                  <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse varius tellus vitae justo blandit ultrices.</p>
                </div>
                <div class="footer-payment">
                  <h4>We Accept</h4>
                  <ul>
                    <li><img src="images/icons/visa.png" alt="image" /></li>
                    <li><img src="images/icons/mastercard.png" alt="image" /></li>
                    <li><img src="images/icons/americanexpress.png" alt="image" /></li>
                  </ul>
                </div>
              </div>
              <div class="col-lg-3 col-md-6 mar-bottom-30">
                <div class="quick-links">
                  <h4>Quick Links</h4>
                  <ul>
                    <li><a href="#">Home</a></li>
                    <li><a href="#">About</a></li>
                    <li><a href="#">Rooms</a></li>
                    <li><a href="#">Testimonials</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Gallery</a></li>
                    <li><a href="#">Services</a></li>
                    <li><a href="#">Contact</a></li>
                  </ul>
                </div>
              </div>
              <div class="col-lg-3 col-md-6 mar-bottom-30">
                <div class="Rooms">
                  <h4>Rooms</h4>
                  <ul>
                    <li><a href="#">Single Rooms</a></li>
                    <li><a href="#">Double Rooms</a></li>
                    <li><a href="#">Studio Rooms</a></li>
                    <li><a href="#">Kingsize Rooms</a></li>
                    <li><a href="#">Presidentsuite Rooms</a></li>
                    <li><a href="#">Luxury Kings Rooms</a></li>
                    <li><a href="#">Connecting Rooms</a></li>
                    <li><a href="#">Murphy Rooms</a></li>
                  </ul>
                </div>
              </div>
              <div class="col-lg-3 col-md-6 mar-bottom-30">
                <div class="footer-contact">
                  <h4>Contact info</h4>
                  <ul>
                    <li>Tel:  +94 082 1234 567</li>
                    <li>Email: info@luviorahotel.com</li>
                    <li>Fax:  +94 082 1234 567</li>
                    <li>Address: 23/B Galle Road, Colombo</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="footer-copyright pad-bottom-20">
        <div class="container">
          <div class="row">
            <div class="col-lg-4 mar-bottom-10">
              <div class="copyright-content">
                <p>Copyright 2025. Made with <span>♥</span>. All Rights Reserved. <a href="#">Luviora</a></p>
              </div>
            </div>
            <div class="col-lg-4 mar-bottom-10">
              <div class="tripadvisor-logo text-center">
                <img src="images/tripadvisor.png" alt="image" />
              </div>
            </div>
            <div class="col-lg-4 mar-bottom-10">
              
              <div class="playstore-links">
                <img src="images/icons/appstore.png" alt="image" class="mar-right-10" />
                <img src="images/icons/googleplay.png" alt="image" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </footer>
    <!-- Footer Ends -->

    <!-- Back to top start -->
    <div id="back-to-top">
      <a href="#"></a>
    </div>
    <!-- Back to top ends -->

    <!-- Login and Register Modals -->
    <?php include 'includes/modals.php'; ?>

    <!-- *Scripts* -->
    <script src="js/jquery-3.3.1.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/plugin.js"></script>
    <script src="js/main.js"></script>
    <script src="js/custom-nav.js"></script>
    <script src="js/custom-swiper1.js"></script>
    <script src="js/custom-singledate.js"></script>
    <script src="js/elegant-rooms.js"></script>
    <script src="js/modern-reviews-gallery.js"></script>
    <script src="js/auth.js"></script>
</body>
</html>

