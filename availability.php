<?php
/**
 * Availability Page - Step 1 of Reservation Process
 * Check room availability for selected dates
 */
session_start();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_data'])) {
    $reservationData = json_decode($_POST['reservation_data'], true);

    if ($reservationData) {
        // Store in session
        $_SESSION['reservation'] = [
            'checkIn' => $reservationData['checkInDate'],
            'checkOut' => $reservationData['checkOutDate'],
            'adults' => $reservationData['adults'],
            'children' => $reservationData['children'],
            'rooms' => $reservationData['rooms'],
            'nights' => $reservationData['nights']
        ];

        // Also store in the format expected by room-select.php
        $_SESSION['reservationData'] = json_encode($reservationData);

        // Redirect to room selection
        header('Location: room-select.php');
        exit;
    }
}

// Initialize session data if not exists
if (!isset($_SESSION['reservation'])) {
    $_SESSION['reservation'] = [];
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="zxx">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>Make Your Reservation | Luviora Hotel</title>
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
      /* Global Styles */
      body {
        background-color: #faf8f5;
      }

      #back-to-top a {
        display: block;
        width: 40px;
        height: 40px;
        background: #a0522d;
        position: relative;
      }

      h1, h2 {
        font-weight: 700;
        color: #6b5744;
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
        background: #FFFFFF !important;
        color: #000000 !important;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
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

      /* ========================================
         RESERVATION STEPS INDICATOR STYLES
      ======================================== */
      .reservation-steps {
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 60px 0 50px;
        position: relative;
      }

      .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 2;
      }

      .step-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: 700;
        color: #d4a574;
        background-color: rgba(212, 165, 116, 0.3);
        border: 3px solid rgba(212, 165, 116, 0.5);
        transition: all 0.3s ease;
        margin-bottom: 15px;
      }

      .step-item.active .step-circle {
        background-color: #a0522d;
        color: #ffffff;
        border-color: #a0522d;
        box-shadow: 0 4px 15px rgba(160, 82, 45, 0.3);
      }

      .step-label {
        font-size: 14px;
        font-weight: 600;
        color: #8b6f47;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .step-item.active .step-label {
        color: #a0522d;
        font-weight: 700;
      }

      .step-connector {
        height: 3px;
        width: 150px;
        background-color: rgba(212, 165, 116, 0.3);
        position: relative;
        margin: 0 -20px;
        top: -45px;
        z-index: 1;
      }

      .step-item.active + .step-connector {
        background-color: #a0522d;
      }

      /* ========================================
         RESERVATION FORM STYLES
      ======================================== */
      .reservation-form-container {
        max-width: 1200px;
        margin: 0 auto;
        background: #ffffff;
        border-radius: 12px;
        padding: 50px;
        box-shadow: 0 10px 40px rgba(107, 87, 68, 0.1);
      }

      .form-title {
        text-align: center;
        color: #6b5744;
        font-family: 'Playfair Display', serif;
        font-size: 36px;
        margin-bottom: 50px;
        font-weight: 700;
      }

      /* Input Fields Row */
      .inputs-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 25px;
        margin-bottom: 40px;
      }

      .input-field {
        display: flex;
        flex-direction: column;
      }

      .input-label {
        font-size: 12px;
        font-weight: 700;
        color: #a0522d;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 10px;
      }

      .input-wrapper {
        position: relative;
      }

      .input-select {
         width: 100%;
         padding: 12px 40px 15px 15px;
         font-size: 18px;
         font-weight: 600;
         color: #6b5744;
         background-color: #faf8f5;
         border: 2px solid #d4a574;
         border-radius: 8px;
         cursor: pointer;
         appearance: none !important;
         -webkit-appearance: none !important;
         -moz-appearance: none !important;
         box-sizing: border-box;
         height: 48px;
         line-height: 18px;
         background-image: none;
         transition: all 0.3s ease;
         display: block !important;
         opacity: 1 !important;
         position: relative !important;
         z-index: 2;
       }
       
       /* Override nice-select styles */
       .nice-select {
         display: none !important;
       }
       
       .input-select:disabled {
         background-color: #f5f1eb;
         cursor: not-allowed;
       }      .input-select:focus {
        outline: none;
        border-color: #a0522d;
        box-shadow: 0 0 0 3px rgba(160, 82, 45, 0.1);
      }

      .input-select:hover {
        border-color: #a0522d;
      }

      .dropdown-arrow {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #8b6f47;
        pointer-events: none;
        font-size: 12px;
      }

      /* Hide native select arrow for IE/Edge */
      select::-ms-expand {
        display: none;
      }

      /* Small extra safety for webkit browsers */
      .input-select::-webkit-appearance {
        appearance: none;
      }

      /* Check Availability Button */
      .check-availability-section {
        text-align: right;
        margin-bottom: 50px;
      }

      .btn-check-availability {
        background-color: #a0522d;
        color: #ffffff;
        padding: 16px 40px;
        font-size: 14px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(160, 82, 45, 0.2);
      }

      .btn-check-availability:hover {
        background-color: #8b4513;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(139, 69, 19, 0.3);
      }

      /* ========================================
         CALENDAR STYLES
      ======================================== */
      .calendar-section {
        margin-top: 40px;
      }

      .calendars-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
      }

      .calendar-wrapper {
        background: #ffffff;
        border: 1px solid #d4a574;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 4px 15px rgba(212, 165, 116, 0.1);
      }

      .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f5f1eb;
      }

      .calendar-nav {
        color: #8b6f47;
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        padding: 5px 10px;
        transition: all 0.3s ease;
        border-radius: 4px;
      }

      .calendar-nav:hover {
        color: #6b5744;
        background-color: rgba(160, 82, 45, 0.1);
      }

      .calendar-nav:disabled {
        color: #d4a574;
        cursor: not-allowed;
        opacity: 0.5;
      }

      .calendar-month-year {
        font-size: 20px;
        font-weight: 700;
        color: #6b5744;
        font-family: 'Playfair Display', serif;
      }

      .calendar-weekdays {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
        margin-bottom: 10px;
      }

      .calendar-weekday {
        text-align: center;
        font-size: 12px;
        font-weight: 700;
        color: #8b6f47;
        padding: 10px 0;
        text-transform: uppercase;
      }

      .calendar-days {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
      }

      .calendar-day {
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 600;
        color: #6b5744;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
      }

      .calendar-day:hover:not(.disabled):not(.empty) {
        background-color: rgba(160, 82, 45, 0.2);
      }

      .calendar-day.disabled {
        color: rgba(212, 165, 116, 0.5);
        cursor: not-allowed;
      }

      .calendar-day.empty {
        cursor: default;
      }

      .calendar-day.today {
        border: 2px solid #d4a574;
      }

      .calendar-day.selected {
        background-color: #a0522d;
        color: #ffffff;
      }

      .calendar-day.in-range {
        background-color: rgba(160, 82, 45, 0.15);
      }

      /* ========================================
         CONTINUE BUTTON SECTION
      ======================================== */
      .continue-section {
        margin-top: 50px;
        text-align: center;
      }

      .btn-continue {
        background-color: #a0522d;
        color: #ffffff;
        padding: 18px 60px;
        font-size: 16px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 6px 20px rgba(160, 82, 45, 0.3);
        text-decoration: none;
        display: inline-block;
      }

      .btn-continue:hover {
        background-color: #8b4513;
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(139, 69, 19, 0.4);
        color: #ffffff;
        text-decoration: none;
      }

      .btn-continue:disabled {
        background-color: #d4a574;
        cursor: not-allowed;
        opacity: 0.6;
        transform: none;
      }

      /* ========================================
         VALIDATION MESSAGE STYLES
      ======================================== */
      .validation-message {
        background-color: #fff3cd;
        border: 1px solid #ffc107;
        color: #856404;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: none;
        font-weight: 600;
        text-align: center;
      }

      .validation-message.error {
        background-color: #f8d7da;
        border-color: #f5c6cb;
        color: #721c24;
      }

      .validation-message.success {
        background-color: #d4edda;
        border-color: #c3e6cb;
        color: #155724;
      }

      .validation-message.show {
        display: block;
        animation: slideDown 0.3s ease;
      }

      @keyframes slideDown {
        from {
          opacity: 0;
          transform: translateY(-10px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      /* ========================================
         RESPONSIVE DESIGN
      ======================================== */
      @media (max-width: 992px) {
        .inputs-row {
          grid-template-columns: repeat(2, 1fr);
        }

        .calendars-container {
          grid-template-columns: 1fr;
        }

        .reservation-steps {
          margin: 40px 0 30px;
        }

        .step-connector {
          width: 80px;
        }

        .step-circle {
          width: 50px;
          height: 50px;
          font-size: 20px;
        }
      }

      @media (max-width: 768px) {
        .reservation-form-container {
          padding: 30px 20px;
        }

        .inputs-row {
          grid-template-columns: 1fr;
          gap: 20px;
        }

        .form-title {
          font-size: 28px;
          margin-bottom: 30px;
        }

        .check-availability-section {
          text-align: center;
        }

        .step-connector {
          width: 50px;
          margin: 0 -10px;
        }

        .step-circle {
          width: 45px;
          height: 45px;
          font-size: 18px;
        }

        .step-label {
          font-size: 11px;
        }

        .btn-continue {
          width: 100%;
          padding: 16px 40px;
        }
      }

      @media (max-width: 576px) {
        .reservation-steps {
          flex-wrap: wrap;
        }

        .step-connector {
          display: none;
        }

        .step-item {
          margin: 10px 15px;
        }
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

    <!-- breadcrumbs starts -->
    <section class="breadcrumb-outer" style="object-fit: cover; background-image: url('images/gal6.jpeg')">
      <div class="container">
        <div class="breadcrumb-content">
          <h2>Reservations</h2>
          <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Reservations</li>
            </ul>
          </nav>
        </div>
      </div>
    </section>
    <!-- breadcrumbs Ends -->

    <!-- reservation main starts -->
    <section class="content reservation-main" style="padding: 60px 0; background-color: #faf8f5;">
      <div class="container">
        
        <!-- Reservation Steps Indicator -->
        <div class="reservation-steps">
          <div class="step-item active">
            <div class="step-circle">1</div>
            <div class="step-label">Check Availability</div>
          </div>
          <div class="step-connector"></div>
          <div class="step-item">
            <div class="step-circle">2</div>
            <div class="step-label">Select Room</div>
          </div>
          <div class="step-connector"></div>
          <div class="step-item">
            <div class="step-circle">3</div>
            <div class="step-label">Booking</div>
          </div>
          <div class="step-connector"></div>
          <div class="step-item">
            <div class="step-circle">4</div>
            <div class="step-label">Confirmation</div>
          </div>
        </div>

        <!-- Reservation Form Container -->
        <div class="reservation-form-container">
          <h2 class="form-title">Make Your Reservation</h2>

          <!-- Validation Message -->
          <div id="validationMessage" class="validation-message"></div>

          <!-- Input Fields Row -->
          <div class="inputs-row">
            <div class="input-field">
              <label class="input-label">ADULTS</label>
              <div class="input-wrapper">
                <select id="adultsSelect" class="input-select">
                  <option value="1">01</option>
                  <option value="2" selected>02</option>
                  <option value="3">03</option>
                  <option value="4">04</option>
                  <option value="5">05</option>
                  <option value="6">06</option>
                  <option value="7">07</option>
                  <option value="8">08</option>
                </select>
                <span class="dropdown-arrow">▼</span>
              </div>
            </div>

            <div class="input-field">
              <label class="input-label">ROOMS</label>
              <div class="input-wrapper">
                <select id="roomsSelect" class="input-select">
                  <option value="1" selected>01</option>
                  <option value="2">02</option>
                  <option value="3">03</option>
                  <option value="4">04</option>
                  <option value="5">05</option>
                </select>
                <span class="dropdown-arrow">▼</span>
              </div>
            </div>

            <div class="input-field">
              <label class="input-label">CHILDREN</label>
              <div class="input-wrapper">
                <select id="childrenSelect" class="input-select">
                  <option value="0" selected>00</option>
                  <option value="1">01</option>
                  <option value="2">02</option>
                  <option value="3">03</option>
                  <option value="4">04</option>
                  <option value="5">05</option>
                  <option value="6">06</option>
                </select>
                <span class="dropdown-arrow">▼</span>
              </div>
            </div>

            <div class="input-field">
              <label class="input-label">NIGHTS</label>
              <div class="input-wrapper">
                <select id="nightsSelect" class="input-select" disabled>
                  <option value="0">00</option>
                </select>
                <span class="dropdown-arrow">▼</span>
              </div>
            </div>
          </div>

          <!-- Check Availability Button -->
          <div class="check-availability-section">
            <button id="checkAvailabilityBtn" class="btn-check-availability" type="button">
              CHECK AVAILABILITY
            </button>
          </div>

          <!-- Calendar Section -->
          <div class="calendar-section">
            <div class="calendars-container">
              <!-- Calendar 1 -->
              <div class="calendar-wrapper">
                <div class="calendar-header">
                  <button class="calendar-nav" id="prevMonth1" type="button">◀</button>
                  <div class="calendar-month-year" id="monthYear1">October 2025</div>
                  <button class="calendar-nav" id="nextMonth1" type="button">▶</button>
                </div>
                <div class="calendar-weekdays">
                  <div class="calendar-weekday">SU</div>
                  <div class="calendar-weekday">MO</div>
                  <div class="calendar-weekday">TU</div>
                  <div class="calendar-weekday">WE</div>
                  <div class="calendar-weekday">TH</div>
                  <div class="calendar-weekday">FR</div>
                  <div class="calendar-weekday">SA</div>
                </div>
                <div class="calendar-days" id="calendarDays1"></div>
              </div>

              <!-- Calendar 2 -->
              <div class="calendar-wrapper">
                <div class="calendar-header">
                  <button class="calendar-nav" id="prevMonth2" type="button">◀</button>
                  <div class="calendar-month-year" id="monthYear2">November 2025</div>
                  <button class="calendar-nav" id="nextMonth2" type="button">▶</button>
                </div>
                <div class="calendar-weekdays">
                  <div class="calendar-weekday">SU</div>
                  <div class="calendar-weekday">MO</div>
                  <div class="calendar-weekday">TU</div>
                  <div class="calendar-weekday">WE</div>
                  <div class="calendar-weekday">TH</div>
                  <div class="calendar-weekday">FR</div>
                  <div class="calendar-weekday">SA</div>
                </div>
                <div class="calendar-days" id="calendarDays2"></div>
              </div>
            </div>
          </div>

          <!-- Continue Button Section -->
          <div class="continue-section">
            <form id="reservationForm" method="POST" action="">
              <input type="hidden" name="reservation_data" id="reservationDataInput">
              <button id="continueBtn" class="btn-continue" type="button" disabled>
                CONTINUE TO ROOM SELECTION
              </button>
            </form>
          </div>
        </div>

      </div>
    </section>
    <!-- reservation main Ends -->

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
    <script src="js/auth.js"></script>
    
    <!-- Reservation Calendar & Validation Script -->
    <script>
      // State Management
      let reservationState = {
        checkInDate: null,
        checkOutDate: null,
        adults: 2,
        rooms: 1,
        children: 0,
        nights: 0,
        currentMonth1: 9, // October (0-indexed)
        currentYear1: 2025,
        currentMonth2: 10, // November
        currentYear2: 2025
      };

      const monthNames = ["January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"];

      // Get today's date
      const today = new Date();
      today.setHours(0, 0, 0, 0);

      // Initialize on page load
      document.addEventListener('DOMContentLoaded', function() {
        initializeCalendars();
        setupEventListeners();
        updateNightsDisplay();
      });

      // Setup Event Listeners
      function setupEventListeners() {
        // Calendar navigation
        document.getElementById('prevMonth1').addEventListener('click', () => navigateMonth(1, -1));
        document.getElementById('nextMonth1').addEventListener('click', () => navigateMonth(1, 1));
        document.getElementById('prevMonth2').addEventListener('click', () => navigateMonth(2, -1));
        document.getElementById('nextMonth2').addEventListener('click', () => navigateMonth(2, 1));

        // Form inputs
        document.getElementById('adultsSelect').addEventListener('change', (e) => {
          reservationState.adults = parseInt(e.target.value);
        });
        
        document.getElementById('roomsSelect').addEventListener('change', (e) => {
          reservationState.rooms = parseInt(e.target.value);
        });
        
        document.getElementById('childrenSelect').addEventListener('change', (e) => {
          reservationState.children = parseInt(e.target.value);
        });

        // Check Availability Button
        document.getElementById('checkAvailabilityBtn').addEventListener('click', checkAvailability);

        // Continue Button
        document.getElementById('continueBtn').addEventListener('click', handleContinue);
      }

      // Initialize Both Calendars
      function initializeCalendars() {
        renderCalendar(1);
        renderCalendar(2);
      }

      // Navigate Month
      function navigateMonth(calendarNum, direction) {
        if (calendarNum === 1) {
          reservationState.currentMonth1 += direction;
          if (reservationState.currentMonth1 > 11) {
            reservationState.currentMonth1 = 0;
            reservationState.currentYear1++;
          } else if (reservationState.currentMonth1 < 0) {
            reservationState.currentMonth1 = 11;
            reservationState.currentYear1--;
          }
        } else {
          reservationState.currentMonth2 += direction;
          if (reservationState.currentMonth2 > 11) {
            reservationState.currentMonth2 = 0;
            reservationState.currentYear2++;
          } else if (reservationState.currentMonth2 < 0) {
            reservationState.currentMonth2 = 11;
            reservationState.currentYear2--;
          }
        }
        renderCalendar(calendarNum);
      }

      // Render Calendar
      function renderCalendar(calendarNum) {
        const month = calendarNum === 1 ? reservationState.currentMonth1 : reservationState.currentMonth2;
        const year = calendarNum === 1 ? reservationState.currentYear1 : reservationState.currentYear2;
        
        // Update header
        document.getElementById(`monthYear${calendarNum}`).textContent = 
          `${monthNames[month]} ${year}`;

        // Get calendar days container
        const daysContainer = document.getElementById(`calendarDays${calendarNum}`);
        daysContainer.innerHTML = '';

        // Get first day of month and number of days
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        // Add empty cells for days before month starts
        for (let i = 0; i < firstDay; i++) {
          const emptyDay = document.createElement('div');
          emptyDay.className = 'calendar-day empty';
          daysContainer.appendChild(emptyDay);
        }

        // Add days of the month
        for (let day = 1; day <= daysInMonth; day++) {
          const dayElement = document.createElement('div');
          dayElement.className = 'calendar-day';
          dayElement.textContent = day;
          
          const currentDate = new Date(year, month, day);
          currentDate.setHours(0, 0, 0, 0);

          // Check if date is in the past
          if (currentDate < today) {
            dayElement.classList.add('disabled');
          } else {
            // Check if today
            if (currentDate.getTime() === today.getTime()) {
              dayElement.classList.add('today');
            }

            // Check if selected
            if (reservationState.checkInDate && 
                currentDate.getTime() === reservationState.checkInDate.getTime()) {
              dayElement.classList.add('selected');
            }
            if (reservationState.checkOutDate && 
                currentDate.getTime() === reservationState.checkOutDate.getTime()) {
              dayElement.classList.add('selected');
            }

            // Check if in range
            if (reservationState.checkInDate && reservationState.checkOutDate &&
                currentDate > reservationState.checkInDate && 
                currentDate < reservationState.checkOutDate) {
              dayElement.classList.add('in-range');
            }

            // Add click handler
            dayElement.addEventListener('click', () => selectDate(currentDate));
          }

          daysContainer.appendChild(dayElement);
        }

        // Disable/enable navigation buttons for past months
        const currentMonthDate = new Date(year, month, 1);
        const todayMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        
        const prevBtn = document.getElementById(`prevMonth${calendarNum}`);
        if (currentMonthDate <= todayMonth) {
          prevBtn.disabled = true;
        } else {
          prevBtn.disabled = false;
        }
      }

      // Select Date
      function selectDate(date) {
        if (!reservationState.checkInDate || 
            (reservationState.checkInDate && reservationState.checkOutDate)) {
          // Set check-in date
          reservationState.checkInDate = date;
          reservationState.checkOutDate = null;
        } else if (date > reservationState.checkInDate) {
          // Set check-out date
          reservationState.checkOutDate = date;
          calculateNights();
        } else {
          // Reset if selected date is before check-in
          reservationState.checkInDate = date;
          reservationState.checkOutDate = null;
        }

        updateNightsDisplay();
        renderCalendar(1);
        renderCalendar(2);
        validateForm();
      }

      // Calculate Nights
      function calculateNights() {
        if (reservationState.checkInDate && reservationState.checkOutDate) {
          const diffTime = Math.abs(reservationState.checkOutDate - reservationState.checkInDate);
          const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
          reservationState.nights = diffDays;
        } else {
          reservationState.nights = 0;
        }
      }

      // Update Nights Display
      function updateNightsDisplay() {
        const nightsSelect = document.getElementById('nightsSelect');
        nightsSelect.innerHTML = '';
        if (reservationState.nights > 0) {
          const option = document.createElement('option');
          option.value = reservationState.nights;
          option.textContent = String(reservationState.nights).padStart(2, '0');
          option.selected = true;
          nightsSelect.appendChild(option);
          // enable the nights select when nights > 0
          nightsSelect.disabled = false;
          nightsSelect.style.backgroundColor = '#faf8f5';
          nightsSelect.style.color = '#6b5744';
        } else {
          const option = document.createElement('option');
          option.value = 0;
          option.textContent = '00';
          option.selected = true;
          nightsSelect.appendChild(option);
          // disable nights select when nights is zero
          nightsSelect.disabled = true;
          nightsSelect.style.backgroundColor = '#f5f1eb';
          nightsSelect.style.color = '#6b5744';
        }
      }

      // Validate Form
      function validateForm() {
        const continueBtn = document.getElementById('continueBtn');
        
        if (reservationState.checkInDate && 
            reservationState.checkOutDate && 
            reservationState.nights > 0) {
          continueBtn.disabled = false;
          return true;
        } else {
          continueBtn.disabled = true;
          return false;
        }
      }

      // Check Availability
      function checkAvailability() {
        const messageDiv = document.getElementById('validationMessage');
        
        if (!reservationState.checkInDate || !reservationState.checkOutDate) {
          showMessage('Please select both check-in and check-out dates from the calendar.', 'error');
          return;
        }

        if (reservationState.nights < 1) {
          showMessage('Please select valid check-in and check-out dates.', 'error');
          return;
        }

        // Show success message
        const checkInStr = formatDate(reservationState.checkInDate);
        const checkOutStr = formatDate(reservationState.checkOutDate);
        showMessage(
          `Great! ${reservationState.rooms} room(s) available from ${checkInStr} to ${checkOutStr} (${reservationState.nights} night${reservationState.nights > 1 ? 's' : ''}).`,
          'success'
        );

        validateForm();
      }

      // Handle Continue
      function handleContinue() {
        if (!validateForm()) {
          showMessage('Please select check-in and check-out dates before continuing.', 'error');
          return;
        }

        // Prepare reservation data with proper date format (YYYY-MM-DD for database)
        // Use local date formatting to avoid timezone issues
        const reservationData = {
          checkInDate: formatDateToYYYYMMDD(reservationState.checkInDate),
          checkOutDate: formatDateToYYYYMMDD(reservationState.checkOutDate),
          adults: reservationState.adults,
          rooms: reservationState.rooms,
          children: reservationState.children,
          nights: reservationState.nights
        };

        // Store in sessionStorage for backward compatibility
        sessionStorage.setItem('reservationData', JSON.stringify(reservationData));

        // Set the hidden input value and submit the form
        document.getElementById('reservationDataInput').value = JSON.stringify(reservationData);
        document.getElementById('reservationForm').submit();
      }

      // Format date to YYYY-MM-DD in local timezone
      function formatDateToYYYYMMDD(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
      }

      // Show Message
      function showMessage(text, type) {
        const messageDiv = document.getElementById('validationMessage');
        messageDiv.textContent = text;
        messageDiv.className = `validation-message ${type} show`;
        
        // Scroll to message
        messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
          setTimeout(() => {
            messageDiv.classList.remove('show');
          }, 5000);
        }
      }

      // Format Date
      function formatDate(date) {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                       'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return `${months[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`;
      }
    </script>
</body>
</html>
