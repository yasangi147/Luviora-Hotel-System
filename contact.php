<?php
require_once 'config/database.php';

$pageTitle = 'Contact Us | Luviora Hotel';
$db = getDB();
$successMessage = '';
$errorMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $type = $_POST['type'] ?? 'query'; // 'feedback' or 'query'
        $category = $_POST['category'] ?? 'general_inquiry';

        // Validation
        if (empty($firstName) || empty($lastName) || empty($email) || empty($message)) {
            $errorMessage = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = 'Please enter a valid email address.';
        } else {
            // Check if user is logged in
            $userId = null;
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
            }

            // Insert into new feedback_queries table
            $stmt = $db->prepare("
                INSERT INTO feedback_queries
                (first_name, last_name, email, phone, subject, message, type, category, status, user_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new', ?, NOW())
            ");

            $stmt->execute([
                $firstName,
                $lastName,
                $email,
                $phone,
                $subject,
                $message,
                $type,
                $category,
                $userId
            ]);

            $successMessage = 'Thank you for your ' . ($type === 'feedback' ? 'feedback' : 'query') . '! We will get back to you soon.';

            // Clear form
            $_POST = [];
        }
    } catch (PDOException $e) {
        error_log("Contact Form Error: " . $e->getMessage());
        $errorMessage = 'An error occurred while sending your message. Please try again later.';
    }
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="zxx">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo $pageTitle; ?></title>
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.png" />
    <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="css/default.css" rel="stylesheet" type="text/css" />
    <link href="css/style.css" rel="stylesheet" type="text/css" />
    <link href="css/plugin.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.min.css" />
    <link href="css/rounded-corners.css" rel="stylesheet" type="text/css" />
    <link href="css/modern-header.css" rel="stylesheet" type="text/css" />
    <link href="css/footer-coral.css" rel="stylesheet" type="text/css" />
    <link href="css/news-reviews-coral.css" rel="stylesheet" type="text/css" />
    <link href="css/elegant-colors.css" rel="stylesheet" type="text/css" />
    <link href="css/aboutus-luxury.css" rel="stylesheet" type="text/css" />
    <link href="css/elegant-rooms.css" rel="stylesheet" type="text/css" />
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

      .nav-btn .btn-orange:hover {
        background: #FFFFFF !important;
        color: #000000 !important;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
      }

      .contact-form {
        background: #a55d42;
        padding: 30px;
      }

      .alert {
        margin-bottom: 20px;
        padding: 15px;
        border-radius: 4px;
      }

      .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
      }

      .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
      }

      section.breadcrumb-outer {
          background: url(../images/breadcrumb.jpg) no-repeat;
          background-size: cover;
          background-position: center;
          position: relative;
          text-align: center;
          padding: 260px 0 150px;
      }

      

    </style>
</head>
  <body>
    <!-- Preloader -->
    <div id="preloader">
      <div id="status"></div>
    </div>

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

      <div class="header_menu">
        <div class="container">
          <nav class="navbar navbar-default">
            <div class="navbar-header">
              <a class="navbar-brand" href="index.php">
                <img alt="logo" src="images/luvioralogoblack.png" class="logo-black" style="width: 200px; height: 50px;"/>
              </a>
            </div>
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
              <ul class="nav navbar-nav" id="responsive-menu">
                <li class="dropdown submenu"><a href="index.php">Home</a></li>
                <li class="submenu dropdown"><a href="aboutus.php">About Us</a></li>
                <li class="submenu dropdown"><a href="roomlist-1.php">Rooms</a></li>
                <li class="submenu dropdown"><a href="testimonial.php">Testimonials</a></li>
                <li class="submenu dropdown"><a href="blog-full.php">Blog</a></li>
                <li class="submenu dropdown"><a href="gallery.php">Gallery</a></li>
                <li class="submenu dropdown"><a href="service.php">Services</a></li>
                <li class="submenu dropdown active"><a href="contact.php">Contact Us</a></li>
              </ul>
              <div class="nav-btn">
                <a href="availability.php" class="btn btn-orange" style="margin-right: 20px;">Book Now</a>
              </div>
            </div>
            <div id="slicknav-mobile"></div>
          </nav>
        </div>
      </div>
    </header>

    <section class="breadcrumb-outer" style="object-fit: cover; background-image: url('images/ab3.jpeg')">
      <div class="container">
        <div class="breadcrumb-content">
          <h2>Contact Us</h2>
        </div>
      </div>
    </section>

    <section class="contact">
      <div class="container">
        <div class="contact-map">
          <div class="row">
            <div class="contact-info">
          <div class="row">
            <div class="col-lg-4 col-md-12 mar-bottom-30">
              <div class="info-item" style="background: #a55d42; border-radius: 10px;">
                <div class="info-icon">
                  <i class="fa fa-map-marker" style="color: #fff;"></i>
                </div>
                <div class="info-content">
                  <p>23/B Galle Road,<br> Colombo</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6 mar-bottom-30">
              <div class="info-item info-item-or" style="background: #a55d42; border-radius: 10px;">
                <div class="info-icon">
                  <i class="fa fa-phone" style="color: #fff;"></i>
                </div>
                <div class="info-content" style="padding-left: 70px;">
                  <p>+94 082 1234 567</p>
                  <p>+94 082 1234 745</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6 mar-bottom-30">
              <div class="info-item" style="background: #a55d42; border-radius: 10px;">
                <div class="info-icon">
                  <i class="fa fa-envelope" style="color: #fff;"></i>
                </div>
                <div class="info-content" style="padding-left: 70px;">
                  <p>info@luviorahotel.com</p>
                  <p>help@luviorahotel.com</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="contact-support">
          <div class="row">
            <div class="col-lg-4 col-md-12 mar-bottom-30">
              <div class="support-item">
                <h4>Costumer Support</h4>
                <p class="mar-0">We're here to help with any questions or service <br> needs.</p>
              </div>
            </div>
            <div class="col-lg-4 col-md-6 mar-bottom-30">
              <div class="support-item">
                <h4>Technical Support</h4>
                <p class="mar-0">Resolve website issues, login problems, and digital service-related concerns quickly.</p>
              </div>
            </div>
            <div class="col-lg-4 col-md-6 mar-bottom-30">
              <div class="support-item">
                <h4>Booking Queries</h4>
                <p class="mar-0">Get assistance with reservations, availability, cancellations, and special requests.</p>
              </div>
            </div>
          </div>
        </div
            <div class="col-lg-6">
              <div class="map-container">
                <iframe
                  src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126745.91424977795!2d79.7735846!3d6.9270786!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae2593f88e6c9e1%3A0x6e66b4c3eaa93d07!2sSri%20Lanka!5e0!3m2!1sen!2slk!4v1720159348776!5m2!1sen!2slk"
                  width="100%"
                  height="535px"
                  style="border:0;"
                  allowfullscreen=""
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade">
                </iframe>
              </div>
            </div>
            <div class="col-lg-6">
              <div id="contact-form" class="contact-form">
                <h3>Keep in Touch</h3>
                
                <?php if (!empty($successMessage)): ?>
                  <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($errorMessage)): ?>
                  <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                <?php endif; ?>

                <form method="post" action="contact.php" name="contactform" id="contactform-new">
                  <div class="form-group">
                    <label style="color: white; margin-bottom: 8px; display: block;">Message Type</label>
                    <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                      <label style="color: white; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="radio" name="type" value="query" checked onchange="updateFormLabels()" />
                        Query
                      </label>
                      <label style="color: white; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="radio" name="type" value="feedback" onchange="updateFormLabels()" />
                        Feedback
                      </label>
                    </div>
                  </div>

                  <div class="form-group">
                    <input type="text" name="first_name" class="form-control" placeholder="First Name" required />
                  </div>
                  <div class="form-group">
                    <input type="text" name="last_name" class="form-control" placeholder="Last Name" required />
                  </div>
                  <div class="form-group">
                    <input type="email" name="email" class="form-control" placeholder="Email" required />
                  </div>
                  <div class="form-group">
                    <input type="text" name="phone" class="form-control" placeholder="Phone" />
                  </div>
                  <div class="form-group">
                    <input type="text" name="subject" class="form-control" placeholder="Subject" />
                  </div>
                  <div class="form-group">
                    <select name="category" class="form-control" id="categorySelect">
                      <option value="general_inquiry">General Inquiry</option>
                      <option value="booking_query">Booking Query</option>
                      <option value="complaint">Complaint</option>
                      <option value="suggestion">Suggestion</option>
                      <option value="other">Other</option>
                    </select>
                  </div>
                  <div class="textarea">
                    <textarea name="message" placeholder="Enter your message" required></textarea>
                  </div>
                  <div class="comment-btn text-right">
                    <input type="submit" class="btn contact-submit" value="Send Message" style="background: white; color: black; border: 1px solid rgba(0, 0, 0, 0); box-shadow: 0 2px 10px rgba(195, 131, 112, 0.2); padding: 12px 28px; border-radius: 4px; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.background= '#c98870'; this.style.color='black';" onmouseout="this.style.background='white'; this.style.color='black';" />
                  </div>
                </form>

                <script>
                  function updateFormLabels() {
                    const type = document.querySelector('input[name="type"]:checked').value;
                    const categorySelect = document.getElementById('categorySelect');

                    if (type === 'feedback') {
                      categorySelect.innerHTML = `
                        <option value="general_inquiry">General Feedback</option>
                        <option value="suggestion">Suggestion</option>
                        <option value="complaint">Complaint</option>
                        <option value="other">Other</option>
                      `;
                    } else {
                      categorySelect.innerHTML = `
                        <option value="general_inquiry">General Inquiry</option>
                        <option value="booking_query">Booking Query</option>
                        <option value="complaint">Complaint</option>
                        <option value="suggestion">Suggestion</option>
                        <option value="other">Other</option>
                      `;
                    }
                  }
                </script>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

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
    </footer>

    <div id="back-to-top">
      <a href="#"></a>
    </div>

    <script src="js/jquery-3.3.1.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/plugin.js"></script>
    <script src="js/main.js"></script>
    <script src="js/custom-nav.js"></script>
</body>
</html>

