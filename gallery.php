<?php
// Start session for authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="zxx">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>Gallery | Hotux</title>
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
      #back-to-top a {
        display: block;
        width: 40px;
        height: 40px;
        background: #a0522d;
        position: relative;
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

                <li class="submenu dropdown">
                  <a href="aboutus.php" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                    >About Us</a>
                </li>

                <li class="submenu dropdown">
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

                <li class="submenu dropdown  active">
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

    <!-- breadcrumb Starts -->
    <section class="breadcrumb-outer" style="object-fit: cover; background-image: url('images/gal4.jpeg')">
      <div class="container">
        <div class="breadcrumb-content">
          <h2>Gallery</h2>
          <nav aria-label="breadcrumb">
          </nav>
        </div>
      </div>
    </section>
    <!-- breadcrumb Ends -->

    <!-- Gallery starts -->
    <section class="modern-gallery animate-on-scroll">
      <div class="" style="text-align: center; margin-bottom: 40px;">
        <h2>
          <span class="main-text" style="color: #2E2E2E;">BEAUTIFUL VIEWS OF</span> <span class="accent-text" style="color: #C38370;;">HOTUX</span>
        </h2> <hr style=" width: 60px; border: 2px solid #bd7c21; margin-top: -10px; margin-bottom: 20px; margin-left: 650px; margin-top: 10px;">
        <p style=" padding-left: 300px; padding-right: 300px; color: #555; padding-bottom: 50px;">
          Enjoy the stunning views from the top of our one-of-a-kind rooms. Our nature views and natural skies create the perfect backdrop for treasured, photographable, and unforgettable moments.
        </p>
      </div>
      <div class="container">
        <div class="gallery-grid stagger-animation">

          <!-- Gallery Item 1  -->
          <div class="gallery-item featured" tabindex="0">
            <img src="images/slider1.jpeg" alt="Luxury Hotel Room" />
            <div class="gallery-overlay">
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">RESTURENT</div>
            <div class="image-caption">
              <div class="caption-title">Luxury Suite</div>
              <div class="caption-subtitle">Premium Ocean View</div>
            </div>
          </div>

          <!-- Gallery Item 2 -->
          <div class="gallery-item" tabindex="0">
            <img src="images/gallery/gallery2.jpg" alt="Hotel Conference Room" />
            <div class="gallery-overlay">
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">DINING</div>
            <div class="image-caption">
              <div class="caption-title">Conference Hall</div>
              <div class="caption-subtitle">Business Center</div>
            </div>
          </div>

          <!-- Gallery Item 3 -->
          <div class="gallery-item" tabindex="0">
            <img src="images/gallery/gallery3.jpg" alt="Hotel Conference Room" />
            <div class="gallery-overlay">
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">DINING</div>
            <div class="image-caption">
              <div class="caption-title">Conference Hall</div>
              <div class="caption-subtitle">Business Center</div>
            </div>
          </div>

          <!-- Gallery Item 4 - Wide -->
          <div class="gallery-item wide" tabindex="0">
            <img src="images/gallery/gallery4.jpg" alt="Deluxe Hotel Room" />
            <div class="gallery-overlay">
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">BAR</div>
            <div class="image-caption">
              <div class="caption-title">Deluxe Room</div>
              <div class="caption-subtitle">Garden View</div>
            </div>
          </div>

          <!-- Gallery Item 5 -->
          <div class="gallery-item" tabindex="0">
            <img src="images/gallery/gallery7.jpg" alt="Hotel Dining Area" />
            <div class="gallery-overlay">
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">BATHROOM</div>
            <div class="image-caption">
              <div class="caption-title">Restaurant</div>
              <div class="caption-subtitle">Fine Dining Experience</div>
            </div>
          </div>

          <!-- Gallery Item 5 - Tall -->
          <div class="gallery-item tall" tabindex="0">
            <img src="images/gallery/gallery5.jpg" alt="Hotel Pool Area" />
            <div class="gallery-overlay">
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">DINING</div>
            <div class="image-caption">
              <div class="caption-title">Swimming Pool</div>
              <div class="caption-subtitle">Relaxation Zone</div>
            </div>
          </div>

          <!-- Gallery Item 6 -->
          <div class="gallery-item" tabindex="0">
            <img src="images/gallery/gallery14.jpg" alt="Hotel Spa" />
            <div class="gallery-overlay">
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">ROOMS</div>
            <div class="image-caption">
              <div class="caption-title">Wellness Center</div>
              <div class="caption-subtitle">Spa & Massage</div>
            </div>
          </div>

          <!-- Gallery Item 7 - Tall -->
          <div class="gallery-item tall" tabindex="0">
            <img src="images/gallery/gallery6.jpg" alt="Hotel Pool Area" />
            <div class="gallery-overlay">
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">DINING</div>
            <div class="image-caption">
              <div class="caption-title">Swimming Pool</div>
              <div class="caption-subtitle">Relaxation Zone</div>
            </div>
          </div>

          <!-- Gallery Item 8 -->
          <div class="gallery-item" tabindex="0">
            <img src="images/gallery/gallery1.jpg" alt="Hotel Spa" />
            <div class="gallery-overlay">
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">SPA</div>
            <div class="image-caption">
              <div class="caption-title">Wellness Center</div>
              <div class="caption-subtitle">Spa & Massage</div>
            </div>
          </div>

          <!-- Gallery Item 9 -->
          <div class="gallery-item" tabindex="0">
            <img src="images/gallery/gallery12.jpg" alt="Hotel Conference Room" />
            <div class="gallery-overlay">
                <div class="overlay-text">View Full Size</div>
              </div>
              <div class="category-badge">Conference</div>
              <div class="image-caption">
                <div class="caption-title">Conference Hall</div>
                <div class="caption-subtitle">Business Center</div>
              </div>
          </div>

          <!-- Gallery Item 10 - Featured -->
          <div class="gallery-item featured" tabindex="0">
            <img src="images/gallery/gallery20.jpg" alt="Luxury Hotel Room" />
            <div class="gallery-overlay">
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">INTIEIOR</div>
            <div class="image-caption">
              <div class="caption-title">Luxury Suite</div>
              <div class="caption-subtitle">Premium Ocean View</div>
            </div>
          </div>

        <!-- Gallery Item 11 -->
          <div class="gallery-item" tabindex="0">
            <img src="images/gallery/gallery9.jpg" alt="Hotel Conference Room" />
            <div class="gallery-overlay">
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">DINING</div>
            <div class="image-caption">
              <div class="caption-title">Conference Hall</div>
              <div class="caption-subtitle">Business Center</div>
            </div>
          </div>

          <!-- Gallery Item 12 -->
          <div class="gallery-item" tabindex="0">
            <img src="images/gallery/gallery10.jpg" alt="Hotel Dining Area" />
            <div class="gallery-overlay">
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">Dining</div>
            <div class="image-caption">
              <div class="caption-title">Restaurant</div>
              <div class="caption-subtitle">Fine Dining Experience</div>
            </div>
          </div>

          <!-- Gallery Item 13 - Wide -->
          <div class="gallery-item wide" tabindex="0">
            <img src="images/gallery/gallery25.jpg" alt="Deluxe Hotel Room" />
            <div class="gallery-overlay">
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">BAR</div>
            <div class="image-caption">
              <div class="caption-title">Deluxe Room</div>
              <div class="caption-subtitle">Garden View</div>
            </div>
          </div>

          <!-- Gallery Item 14 - Tall -->
          <div class="gallery-item tall" tabindex="0">
            <img src="images/gallery/gallery23.jpg" alt="Hotel Pool Area" />
            <div class="gallery-overlay">
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">Pool</div>
            <div class="image-caption">
              <div class="caption-title">Swimming Pool</div>
              <div class="caption-subtitle">Relaxation Zone</div>
            </div>
          </div>
          
          <!-- Gallery Item 15 -->
          <div class="gallery-item" tabindex="0">
            <img src="images/gallery/gallery16.jpg" alt="Hotel Spa" />
            <div class="gallery-overlay">
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">DINING</div>
            <div class="image-caption">
              <div class="caption-title">Wellness Center</div>
              <div class="caption-subtitle">Spa & Massage</div>
            </div>
          </div>

          <!-- Gallery Item 16 - Tall -->
          <div class="gallery-item tall" tabindex="0">
            <img src="images/gallery/gallery18.jpg" alt="Hotel Pool Area" />
            <div class="gallery-overlay">
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">Pool</div>
            <div class="image-caption">
              <div class="caption-title">Swimming Pool</div>
              <div class="caption-subtitle">Relaxation Zone</div>
            </div>
          </div>

          <!-- Gallery Item 17 -->
          <div class="gallery-item" tabindex="0">
            <img src="images/gallery/gallery19.jpg" alt="Hotel Spa" />
            <div class="gallery-overlay">
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">SALOON</div>
            <div class="image-caption">
              <div class="caption-title">Wellness Center</div>
              <div class="caption-subtitle">Spa & Massage</div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- Gallery Ends -->

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
    <script src="js/custom-mixitup.js"></script>
    <script src="js/modern-reviews-gallery.js"></script>
    <script src="js/auth.js"></script>
</body>
</html>


