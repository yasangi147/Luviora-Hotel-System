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

    <title>Testimonial | Hotux</title>
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
    <!-- Reviews Grid Styles -->
    <link href="css/reviews-grid.css" rel="stylesheet" type="text/css" />
    
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

                    <li class="submenu dropdown">
                      <a href="roomlist-1.php" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                        >Rooms</a>
                    </li>

                    <li class="submenu dropdown  active">
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

    <!-- breadcrumb Starts -->
    <section class="breadcrumb-outer" style="object-fit: cover; background-image: url('images/test.jpeg')">
      <div class="container">
        <div class="breadcrumb-content">
          <h2>Testimonials</h2>
          <nav aria-label="breadcrumb">
          </nav>
        </div>
      </div>
    </section>
    <!-- breadcrumb Ends -->

    <!-- Reviews Starts -->
    <section class="modern-reviews animate-on-scroll">
      <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 40px;">
          <h2>
            <span class="main-text" style="color: #2E2E2E;">GUEST</span> <span class="accent-text" style="color: #C38370;">EXPERIENCES</span>
          </h2>
          <p>
            Hear from our delighted guests who've experienced the elegance, comfort, and exceptional service at our luxurious hotel.
          </p>
        </div>
        <div class="reviews-grid" style="margin-left: -70px;">
            <!-- Review Card 1 -->
            <div class="review-card">
              <div class="review-stars">
                <span class="star">⭐</span>
                <span class="star">⭐</span>
                <span class="star">⭐</span>
                <span class="star">⭐</span>
                <span class="star">⭐</span>
                
              </div>
              <div class="review-text">
                <p>Absolutely loved our stay! The staff were incredibly attentive, the room spotless, and the view breathtaking. Truly a luxurious experience we'll definitely return for another relaxing escape soon.</p>
              </div>
              <div class="review-profile">
                <div class="profile-image">
                  <img src="images/test2.jpg" alt="Michael Clordy" />
                </div>
                <div class="profile-info">
                  <h4>Michael Clordy</h4>
                  <span>Verified Guest Germany</span>
                </div>
              </div>
            </div>
            <!-- Review Card 2 -->
            <div class="review-card">
              <div class="review-stars">
                <span class="star">⭐</span>
                <span class="star">⭐</span>
                <span class="star">⭐</span>
                <span class="star">⭐</span>
                <span class="star">⭐</span>
              </div>
              <div class="review-text">
                <p>From check-in to check-out, everything was perfect. The food was delicious, the ambiance serene, and the service top-notch. A five-star experience we"ll always remember and highly recommend.</p>
              </div>
              <div class="review-profile">
                <div class="profile-image">
                  <img src="images/test4.jpg" alt="Ketty Perry" />
                </div>
                <div class="profile-info">
                  <h4>Ketty Perry</h4>
                  <span>Verified Guest Australia</span>
                </div>
              </div>
            </div>
            <!-- Review Card 3 -->
            <div class="review-card">
              <div class="review-stars">
                <span class="star">⭐</span>
                <span class="star">⭐</span>
                <span class="star">⭐</span>
                <span class="star">⭐</span>
                <span class="star">⭐</span>
              </div>
              <div class="review-text">
                <p>A hidden gem! Elegant design, peaceful surroundings, and genuinely warm hospitality. Every moment felt special and well-catered. We left feeling refreshed, pampered, and eager to return again soon.</p>
              </div>
              <div class="review-profile">
                <div class="profile-image">
                  <img src="images/test3.jpg" alt="Sarah Johnson" />
                </div>
                <div class="profile-info">
                  <h4>Sarah Johnson</h4>
                  <span>Verified Guest United States</span>
                </div>
              </div>
            </div>

            <!-- Review Card 4 -->
            <div class="review-card">
              <div class="review-stars">
                <span class="star">⭐</span>
                <span class="star">⭐</span>
                <span class="star">⭐</span>
                <span class="star">⭐</span>
                <span class="star">⭐</span>
              </div>
              <div class="review-text">
                <p>Exceptional service and stunning views! The attention to detail was remarkable, from the beautifully appointed rooms to the exquisite dining. An unforgettable luxury experience that exceeded all expectations.</p>
              </div>
              <div class="review-profile">
                <div class="profile-image">
                  <img src="images/test2.jpg" alt="David Chen" />
                </div>
                <div class="profile-info">
                  <h4>David Chen</h4>
                  <span>Verified Guest Singapore</span>
                </div>
              </div>
            </div>

            <!-- Review Card 5 -->
            <div class="review-card">
              <div class="review-stars">
                <span class="star">⭐</span>
                <span class="star">⭐</span>
                <span class="star">⭐</span>
                <span class="star">⭐</span>
                <span class="star">⭐</span>
              </div>
              <div class="review-text">
                <p>Exceptional service and stunning views! The attention to detail was remarkable, from the beautifully appointed rooms to the exquisite dining. An unforgettable luxury experience that exceeded all expectations.</p>
              </div>
              <div class="review-profile">
                <div class="profile-image">
                  <img src="images/test2.jpg" alt="David Chen" />
                </div>
                <div class="profile-info">
                  <h4>David Chen</h4>
                  <span>Verified Guest Singapore</span>
                </div>
              </div>
            </div>

            <!-- Review Card 6 -->
            <div class="review-card">
              <div class="review-stars">
                <span class="star">⭐</span>
                <span class="star">⭐</span>
                <span class="star">⭐</span>
                <span class="star">⭐</span>
                <span class="star">⭐</span>
              </div>
              <div class="review-text">
                <p>Exceptional service and stunning views! The attention to detail was remarkable, from the beautifully appointed rooms to the exquisite dining. An unforgettable luxury experience that exceeded all expectations.</p>
              </div>
              <div class="review-profile">
                <div class="profile-image">
                  <img src="images/test2.jpg" alt="David Chen" />
                </div>
                <div class="profile-info">
                  <h4>David Chen</h4>
                  <span>Verified Guest Singapore</span>
                </div>
              </div>
            </div>

          </div>

        </div>
      </div>
    </section>
    <!-- Reviews Ends -->

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
                <p>Copyright 2025. Made with <span>â™¥</span>. All Rights Reserved. <a href="#">Luviora</a></p>
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

