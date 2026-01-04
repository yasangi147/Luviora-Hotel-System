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

    <title>About Us | Hotux</title>
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

                <li class="submenu dropdown active">
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
    <section class="breadcrumb-outer" style="object-fit: cover; background-image: url('images/ab3.jpeg')">
      <div class="container">
        <div class="breadcrumb-content">
          <h2 >About Us</h2>
        </div>
      </div>
    </section>
    <!-- breadcrumb Ends -->

    <!-- About Us Start -->
    <section id="about-us" class="about-lux">
      <div class="container">
        
        <div class="about-lux__grid">
          <div class="about-lux__media">
            <div class="about-lux__accent"></div>
            <figure class="about-lux__hero">
              <img src="images/ab1.jpeg" alt="Lobby at Luviora" />
            </figure>
            <div class="about-lux__stack">
              <figure class="about-lux__card about-lux__card--top glass">
                <img src="images/ab2.jpeg" alt="Suite interior" />
              </figure>
              <figure class="about-lux__card about-lux__card--mid glass">
                <img src="images/ab3.jpeg" alt="Gourmet dining" />
              </figure>
              <figure class="about-lux__card about-lux__card--btm glass">
                <img src="images/ab4.jpeg" alt="Spa and wellness" />
              </figure>
              </div>
            </div>
          <div class="about-lux__content">
            <h3 class="about-lux__heading">Elegance, Reimagined for the Modern Traveler</h3>
            <p class="about-lux__lead">At Luviora, we pride ourselves on being the premier destination for luxury and elegance in the town. Our hotel offers exquisitely designed rooms, exceptional amenities, and personalized service that caters to every guests needs. <br><br>Whether you are here for business, relaxation, or a special occasion, Luviora provides a refined and comfortable environment where sophistication meets warmth. Experience the perfect blend of modern comfort and timeless style, making your stay truly unforgettable at the most luxurious hotel in town.</p>
          </div>
      </div>
    </section>
    <!-- About Us Ends -->

    <!-- counter starts -->
    <section class="about-counter pad-bottom-70">
      <div class="container">
        <div class="row">
          <div class="col-lg-3 col-md-6 mar-bottom-30">
            <div class="counter-item">
              <p class="icon1"><i class="fa fa-suitcase"></i></p>
              <h3 class="room">487</h3>
              <p>Rooms</p>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 mar-bottom-30">
            <div class="counter-item">
              <p class="icon1"><i class="fa fa-users"></i></p>
              <h3 class="staff">1256</h3>
              <p>Staffs</p>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 mar-bottom-30">
            <div class="counter-item">
              <p class="icon1"><i class="fa fa-glass-cheers"></i></p>
              <h3 class="restaurant">16</h3>
              <p>Restaurant & Bars</p>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 mar-bottom-30">
            <div class="counter-item">
              <p class="icon1"><i class="fa fa-trophy"></i></p>
              <h3 class="award">117</h3>
              <p>Awards</p>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- counter Ends -->

    <!-- about team starts-->
    <section class="about-team">
      <div class="container">
        <div class="section-title">
          <h2>Explore <span>Team</span></h2>
          <p>
            Meet the passionate team behind Luviora, dedicated to delivering exceptional service, personalized care, and unforgettable experiences with genuine warmth and professionalism.

          </p>
        </div>
        <div class="row team-slider">
          <div class="col-md-4">
            <div class="team-item">
              <div class="team-image">
                <img src="images/test2.jpg" alt="image" />
              </div>
              <div class="team-content">
                <h4 style="color: #a55d42;">John Anderson</h4>
                <p>Ceo and Founder</p>
                <ul class="social-links">
                  <li>
                    <a href="#"><i class="fab fa-facebook" aria-hidden="true"></i></a>
                  </li>
                  <li>
                    <a href="#"><i class="fab fa-twitter" aria-hidden="true"></i></a>
                  </li>
                  <li>
                    <a href="#"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                  </li>
                  <li>
                    <a href="#"><i class="fab fa-linkedin" aria-hidden="true"></i></a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="team-item">
              <div class="team-image">
                <img src="images/test4.jpg" alt="image" />
              </div>
              <div class="team-content">
                <h4 style="color: #a55d42;">Erina Gray</h4>
                <p>Managing Director</p>
                <ul class="social-links">
                  <li>
                    <a href="#"><i class="fab fa-facebook" aria-hidden="true"></i></a>
                  </li>
                  <li>
                    <a href="#"><i class="fab fa-twitter" aria-hidden="true"></i></a>
                  </li>
                  <li>
                    <a href="#"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                  </li>
                  <li>
                    <a href="#"><i class="fab fa-linkedin" aria-hidden="true"></i></a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="team-item">
              <div class="team-image">
                <img src="images/test3.jpg" alt="image" />
              </div>
              <div class="team-content">
                <h4 style="color: #a55d42;">Micheal Carter</h4>
                <p>Supervisor</p>
                <ul class="social-links">
                  <li>
                    <a href="#"><i class="fab fa-facebook" aria-hidden="true"></i></a>
                  </li>
                  <li>
                    <a href="#"><i class="fab fa-twitter" aria-hidden="true"></i></a>
                  </li>
                  <li>
                    <a href="#"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                  </li>
                  <li>
                    <a href="#"><i class="fab fa-linkedin" aria-hidden="true"></i></a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="team-item">
              <div class="team-image">
                <img src="images/test5.jpg" alt="image" />
              </div>
              <div class="team-content">
                <h4 style="color: #a55d42;">Nelson Roar</h4>
                <p>Project Manager</p>
                <ul class="social-links">
                  <li>
                    <a href="#"><i class="fab fa-facebook" aria-hidden="true"></i></a>
                  </li>
                  <li>
                    <a href="#"><i class="fab fa-twitter" aria-hidden="true"></i></a>
                  </li>
                  <li>
                    <a href="#"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                  </li>
                  <li>
                    <a href="#"><i class="fab fa-linkedin" aria-hidden="true"></i></a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- about team Ends -->

    <!-- about awards starts -->
    <section class="awards">
      <div class="container">
        <div class="section-title title-white">
          <h2>Awards and <span>Achievements</span></h2>
          <p>
            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ex neque, sodales accumsan sapien et, auctor vulputate quam donec vitae consectetur
            turpis
          </p>
        </div>
        <div class="award-slider">
          <div class="award-item">
            <div class="award-image">
              <img src="images/awards/award1.png" alt="image" />
            </div>
            <div class="award-content">
              <h5>World Luxury Award</h5>
              <p>2019</p>
            </div>
          </div>
          <div class="award-item">
            <div class="award-image">
              <img src="images/awards/award2.png" alt="image" />
            </div>
            <div class="award-content">
              <h5>Prestigious Award</h5>
              <p>2019</p>
            </div>
          </div>
          <div class="award-item">
            <div class="award-image">
              <img src="images/awards/award3.png" alt="image" />
            </div>
            <div class="award-content">
              <h5>Reader's Choice Award</h5>
              <p>2019</p>
            </div>
          </div>
          <div class="award-item">
            <div class="award-image">
              <img src="images/awards/award4.png" alt="image" />
            </div>
            <div class="award-content">
              <h5>World Best Award</h5>
              <p>2019</p>
            </div>
          </div>
          <div class="award-item">
            <div class="award-image">
              <img src="images/awards/award1.png" alt="image" />
            </div>
            <div class="award-content">
              <h5>World Luxury Award</h5>
              <p>2019</p>
            </div>
          </div>
          <div class="award-item">
            <div class="award-image">
              <img src="images/awards/award2.png" alt="image" />
            </div>
            <div class="award-content">
              <h5>World Luxury Award</h5>
              <p>2019</p>
            </div>
          </div>
          <div class="award-item">
            <div class="award-image">
              <img src="images/awards/award3.png" alt="image" />
            </div>
            <div class="award-content">
              <h5>World Luxury Award</h5>
              <p>2019</p>
            </div>
          </div>
          <div class="award-item">
            <div class="award-image">
              <img src="images/awards/award4.png" alt="image" />
            </div>
            <div class="award-content">
              <h5>World Luxury Award</h5>
              <p>2019</p>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- about awards Ends -->

    <!-- Services Starts -->
    <section class="services pad-bottom-70">
      <div class="container">
        <div class="section-title">
          <h2>Explore <span>Services</span></h2>
          <p>
            Explore a wide range of premium services designed for your comfort, including dining, spa, concierge, and tailored experiences to make every stay truly memorable.
          </p>
        </div>
        <div class="service-outer">
          <div class="row">
            <div class="col-lg-4 col-md-6 mar-bottom-30">
              <div class="service-item">
                <div class="service-image">
                  <img src="images/res.jpeg" alt="Image" style="width: 500px; height: 500px; object-fit: cover;"/>
                </div>
                <div class="service-content">
                  <h4><a href="service.php">Restaurant</a></h4>
                  <p>Breakfast and Dinner</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6 mar-bottom-30">
              <div class="service-item">
                <div class="service-image">
                  <img src="images/mas.jpeg" alt="Image" style="width: 500px; height: 500px; object-fit: cover;"/>
                </div>
                <div class="service-content">
                  <h4><a href="service.php">Massage</a></h4>
                  <p>Opens Daily</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6 mar-bottom-30">
              <div class="service-item">
                <div class="service-image">
                  <img src="images/con.jpeg" alt="Image" style="width: 500px; height: 500px; object-fit: cover;"/>
                </div>
                <div class="service-content">
                  <h4><a href="service.php">Conference Room</a></h4>
                  <p>Air Conditioning</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- Services Ends -->

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
    <script src="js/auth.js"></script>
  </body>
</html>
