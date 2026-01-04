<?php
// Start session for authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include functions (which includes database connection)
require_once 'includes/functions.php';

// Get 6 rooms from database for homepage
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
    LIMIT 6
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

    <title>Home Default | Hotux</title>
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

    <!-- Force Section Title Visibility -->
    <style>
      /* Emergency fix for section title visibility */
      section.elegant-rooms .section-title,
      section.elegant-rooms .section-title h2,
      section.elegant-rooms .section-title p,
      section.elegant-rooms .section-title span {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        width: auto !important;
        height: auto !important;
        max-width: none !important;
        overflow: visible !important;
        position: relative !important;
        z-index: 9999 !important;
        transform: none !important;
      }

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
        position: absolute;
        top: 20px;
        right: 60px;
        width: 40px;
        height: 40px;
        background: var(--white);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-secondary);
        cursor: pointer;
        transition: all 0.3s ease;
        z-index: 3;
        box-shadow: 0 2px 8px var(--shadow-warm);
        display: none;
      }

      .swiper-pagination-bullet-active {
        background: #a0522d !important;
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

      .modern-reviews .section-title h2{
        font-family: 'Playfair Display', serif;
        font-size: 42px;
        font-weight: 300;
        letter-spacing: 4px;
        margin: 0 0 30px 0;
        position: relative;
        display: inline-block;
        color: #2E2E2E !important;
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

    </style>

  </head>
  <body>
    <!-- Preloader -->
    <div id="preloader">
      <div id="status"></div>
    </div>
    <!-- Preloader Ends -->

    <!-- header start -->
    <?php include 'includes/header.php'; ?>
    <!-- header Ends -->

    <!-- banner starts -->
    <section class="banner">
      <div class="slider">
        <div class="swiper-container">
          <div class="swiper-wrapper">
            <div class="swiper-slide" style="background-image: url(images/slider1.webp)">
              <div class="swiper-content">
                <div class="slider-logo">
                  <img src="images/logonew.png" alt="Image" style="width: 75px; height: 75px;" />
                </div>
                <h3 data-animation="animated fadeInUp" style="color: #FAF9F6;">Room Availability Checker & Booking</h3>
                <h1 data-animation="animated fadeInUp" style="color: #FAF9F6;">Book Early <span style="color: #C38370;">Save</span>More</h1>
                <a href="roomlist-1.php" data-animation="animated fadeInUp" class="slider-btn" style="background-color: #8B5E3C; color: #FAF9F6; border-color: #8B5E3C;">Explore Our Rooms</a>
              </div>
            </div>
            <div class="swiper-slide" style="background-image: url(images/slider2.webp)">
              <div class="swiper-content">
                <div class="slider-logo">
                  <img src="images/logonew.png" alt="Image" style="width: 75px; height: 75px;" />
                </div>
                <h3 data-animation="animated fadeInUp" style="color: #FAF9F6;">The lap of Luxury</h3>
                <h1 data-animation="animated fadeInUp" style="color: #FAF9F6;">Quality <span style="color: #C38370;">Holiday</span> With Us</h1>
                <a href="room-select.php" data-animation="animated fadeInUp" class="slider-btn btn-or" style="background-color: #8B5E3C; color: #FAF9F6; border-color: #8B5E3C;">Book A Room Now</a>
              </div>
            </div>
            <div class="swiper-slide" style="background-image: url(images/slider3.jpeg)">
              <div class="swiper-content">
                <div class="slider-logo">
                  <img src="images/logonew.png" alt="Image" style="width: 75px; height: 75px;"/>
                </div>
                <h3 data-animation="animated fadeInUp" style="color: #FAF9F6;">As We Like to Keep It That Way</h3>
                <h1 data-animation="animated fadeInUp" style="color: #FAF9F6;">A <span style="color: #C38370;">Five Star</span> Hotel</h1>
                <a href="roomlist-1.php" data-animation="animated fadeInUp" class="slider-btn btn-or mar-right-10" style="background-color: #8B5E3C; color: #FAF9F6; border-color: #8B5E3C; ">Explore Our Rooms</a>
                <a href="availability.php" data-animation="animated fadeInUp" class="slider-btn btn-wt" style="background-color: #E6D5C3; color: #2E2E2E; border-color: #E6D5C3;">Book A Room Now</a>
              </div>
            </div>
          </div>
          <!-- Add Pagination -->
          <div class="swiper-pagination"></div>
        </div>
        <div class="overlay"></div>
      </div>

      <div class="banner-form form-style-1 form-style-3">
        <div class="container">
          <div class="form-content text-center" style="border-radius: 35px; box-shadow: 0 6px 12px rgba(0,0,0,0.15); padding: 20px 30px; background-color: #FFFFFF; max-width: 1000px; margin: 0 auto;">
            <div class="table-item" style="margin-bottom: 15px;">
              <label style="color: #2E2E2E; font-weight: 500; margin-bottom: 8px;">Check In</label>
              <div class="form-group">
                <div class="date-range-inner-wrapper">
                  <input id="date-range2" class="form-control" value="Check In" style="color: #2E2E2E; border-radius: 10px;" />
                  <span class="input-group-addon">
                    <i class="fa fa-calendar" aria-hidden="true" style="color: #C38370;"></i>
                  </span>
                </div>
              </div>
            </div>
            <div class="table-item" style="margin-bottom: 15px;">
              <label style="color: #2E2E2E; font-weight: 500; margin-bottom: 8px;">Check Out</label>
              <div class="form-group">
                <div class="date-range-inner-wrapper">
                  <input id="date-range3" class="form-control" value="Check In" style="color: #2E2E2E; padding: 8px 12px;" />
                  <span class="input-group-addon">
                    <i class="fa fa-calendar" aria-hidden="true" style="color: #C38370;"></i>
                  </span>
                </div>
              </div>
            </div>
            <div class="table-item">
              <label style="color: #2E2E2E; font-weight: 500;">Guests</label>
              <div class="form-group form-icon">
                <select class="wide" style="color: #2E2E2E; border-radius: 10px;">
                  <option value="1" style="color: #2E2E2E;">1</option>
                  <option value="2" style="color: #2E2E2E;">2</option>
                  <option value="3" style="color: #2E2E2E;">3</option>
                  <option value="4" style="color: #2E2E2E;">4</option>
                  <option value="5" style="color: #2E2E2E;">5</option>
                  <option value="6" style="color: #2E2E2E;">6</option>
                  <option value="7" style="color: #2E2E2E;">7</option>
                  <option value="8" style="color: #2E2E2E;">8</option>
                  <option value="9" style="color: #2E2E2E;">9</option>
                  <option value="10" style="color: #2E2E2E;">10</option>
                  <option value="11" style="color: #2E2E2E;">11</option>
                  <option value="12" style="color: #2E2E2E;">12</option>
                  <option value="13" style="color: #2E2E2E;">13</option>
                  <option value="14" style="color: #2E2E2E;">14</option>
                  <option value="15" style="color: #2E2E2E;">15</option>
                  <option value="16" style="color: #2E2E2E;">16</option>
                  <option value="17" style="color: #2E2E2E;">17</option>
                  <option value="18" style="color: #2E2E2E;">18</option>
                  <option value="19" style="color: #2E2E2E;">19</option>
                  <option value="21" style="color: #2E2E2E;">20+</option>
                </select>
              </div>
            </div>
            <div class="table-item">
              <label style="color: #2E2E2E; font-weight: 500;">Nights</label>
              <div class="form-group form-icon">
                <select class="wide" style="color: #2E2E2E; border-radius: 10px;">
                  <option value="1" style="color: #2E2E2E;">1</option>
                  <option value="2" style="color: #2E2E2E;">2</option>
                  <option value="3" style="color: #2E2E2E;">3</option>
                  <option value="4" style="color: #2E2E2E;">4</option>
                  <option value="5" style="color: #2E2E2E;">5</option>
                  <option value="6" style="color: #2E2E2E;">6</option>
                  <option value="7" style="color: #2E2E2E;">7</option>
                  <option value="8" style="color: #2E2E2E;">8</option>
                  <option value="9" style="color: #2E2E2E;">9</option>
                  <option value="10" style="color: #2E2E2E;">10</option>
                  <option value="11" style="color: #2E2E2E;">11</option>
                  <option value="12" style="color: #2E2E2E;">12</option>
                  <option value="13" style="color: #2E2E2E;">13</option>
                  <option value="14" style="color: #2E2E2E;">14</option>
                  <option value="15" style="color: #2E2E2E;">15</option>
                  <option value="16" style="color: #2E2E2E;">16</option>
                  <option value="17" style="color: #2E2E2E;">17</option>
                  <option value="18" style="color: #2E2E2E;">18</option>
                  <option value="19" style="color: #2E2E2E;">19</option>
                  <option value="21" style="color: #2E2E2E;">20+</option>
                </select>
              </div>
            </div>
            <div class="table-item">
              <div class="form-btn mar-top-20">
                <a href="availability.php" class="btn btn-orange" style="background-color: #8B5E3C; color: #FAF9F6; border-color: #8B5E3C; padding: 10px 25px;">Check Availability</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- banner Ends -->
 
    <!-- Spacing for better balance -->
    <div style="margin: 80px 0;"></div>

    <!-- About Us Start -->
    <section id="about-us" class="about-lux" style="margin-top: -170px;">
      <div class="container">
        <div class="section-title">
          <h2>About <span>Us</span></h2>
          <p>Refined, serene, and unmistakably modern — Luviora is a boutique destination where understated luxury meets soulful hospitality.</p>
        </div>
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
            <p class="about-lux__lead">Every space at Luviora is designed with a curated calm — soft light, tactile textures, and a palette that whispers rather than shouts.</p>
            <div class="about-lux__divider"><span></span><i class="fas fa-star"></i><span></span></div>
            <ul class="about-lux__highlights">
              <li><i class="fas fa-concierge-bell"></i> Discreet, personalized service around the clock</li>
              <li><i class="fas fa-spa"></i> Wellness rituals and tranquil spa experiences</li>
              <li><i class="fas fa-utensils"></i> Elevated dining with seasonal, thoughtful menus</li>
            </ul>
            <div class="about-lux__cta">
              <a href="aboutus.php" class="btn btn-orange">Know More About Us</a>
                  </div>
                    </div>
        </div>
      </div>
    </section>
    <!-- About Us Ends -->

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
                  <span><?php echo $room['size_sqm']; ?>m²</span>
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
      </div>
    </section>

    
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

          <!-- Gallery Item 1 - Featured --> 
          <div class="gallery-item featured" tabindex="0">
            <img src="images/slider1.jpeg" alt="Luxury Hotel Room" />
            <div class="gallery-overlay">
              <div class="overlay-icon">ðŸ‘</div>
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">Rooms</div>
            <div class="image-caption">
              <div class="caption-title">Luxury Suite</div>
              <div class="caption-subtitle">Premium Ocean View</div>
            </div>
          </div>

          <!-- Gallery Item 2 -->
          <div class="gallery-item" tabindex="0">
            <img src="images/gallery/gallery16.jpg" alt="Hotel Conference Room" />
            <div class="gallery-overlay">
              <div class="overlay-icon">ðŸ‘</div>
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">Facilities</div>
            <div class="image-caption">
              <div class="caption-title">Conference Hall</div>
              <div class="caption-subtitle">Business Center</div>
            </div>
          </div>

          <!-- Gallery Item 2 -->
          <div class="gallery-item" tabindex="0">
            <img src="images/gallery/gallery9.jpg" alt="Hotel Conference Room" />
            <div class="gallery-overlay">
              <div class="overlay-icon">ðŸ‘</div>
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">Facilities</div>
            <div class="image-caption">
              <div class="caption-title">Conference Hall</div>
              <div class="caption-subtitle">Business Center</div>
            </div>
          </div>

          <!-- Gallery Item 3 - Wide -->
          <div class="gallery-item wide" tabindex="0">
            <img src="images/room5.jpeg" alt="Deluxe Hotel Room" />
            <div class="gallery-overlay">
              <div class="overlay-icon">ðŸ‘</div>
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">Rooms</div>
            <div class="image-caption">
              <div class="caption-title">Deluxe Room</div>
              <div class="caption-subtitle">Garden View</div>
            </div>
          </div>

          <!-- Gallery Item 4 -->
          <div class="gallery-item" tabindex="0">
            <img src="images/gallery/gallery19.jpg" alt="Hotel Dining Area" />
            <div class="gallery-overlay">
              <div class="overlay-icon">ðŸ‘</div>
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">Dining</div>
            <div class="image-caption">
              <div class="caption-title">Restaurant</div>
              <div class="caption-subtitle">Fine Dining Experience</div>
            </div>
          </div>

          <!-- Gallery Item 5 - Tall -->
          <div class="gallery-item tall" tabindex="0">
            <img src="images/gal6.jpeg" alt="Hotel Pool Area" />
            <div class="gallery-overlay">
              <div class="overlay-icon">ðŸ‘</div>
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">Pool</div>
            <div class="image-caption">
              <div class="caption-title">Swimming Pool</div>
              <div class="caption-subtitle">Relaxation Zone</div>
            </div>
          </div>

          <!-- Gallery Item 6 -->
          <div class="gallery-item" tabindex="0">
            <img src="images/gallery/gallery25.jpg" alt="Hotel Spa" />
            <div class="gallery-overlay">
              <div class="overlay-icon">ðŸ‘</div>
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">Spa</div>
            <div class="image-caption">
              <div class="caption-title">Wellness Center</div>
              <div class="caption-subtitle">Spa & Massage</div>
            </div>
          </div>

          <!-- Gallery Item 5 - Tall -->
          <div class="gallery-item tall" tabindex="0">
            <img src="images/gallery/gallery5.jpg" alt="Hotel Pool Area" />
            <div class="gallery-overlay">
              <div class="overlay-icon">ðŸ‘</div>
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">Pool</div>
            <div class="image-caption">
              <div class="caption-title">Swimming Pool</div>
              <div class="caption-subtitle">Relaxation Zone</div>
            </div>
          </div>

          <!-- Gallery Item 6 -->
          <div class="gallery-item" tabindex="0">
            <img src="images/gal3.jpeg" alt="Hotel Spa" />
            <div class="gallery-overlay">
              <div class="overlay-icon">ðŸ‘</div>
              <div class="overlay-text">View Full Size</div>
            </div>
            <div class="category-badge">Spa</div>
            <div class="image-caption">
              <div class="caption-title">Wellness Center</div>
              <div class="caption-subtitle">Spa & Massage</div>
            </div>
          </div>

          
        </div>
      </div>
    </section>
    <!-- Gallery Ends -->

    <!-- News Starts -->
    <section class="news pad-bottom-70">
      <div class="container">
        <div class="section-title">
          <h2>Latest <span>News</span></h2>
          <p>
            Stay updated with the latest news, events, and exciting announcements from Luviora. Discover new offers, special activities, and important updates to enhance your unforgettable experience.

          </p>
        </div>
        <div class="news-outer">
          <div class="row">
            <div class="col-lg-4 col-md-12 mar-bottom-30">
              <div class="news-item">
                <div class="news-image">
                  <img src="images/new1.jpeg" alt="image" />
                </div>
                <div class="news-content">
                  <p class="date mar-bottom-5">23 DECEMBER 2022</p>
                  <h4><a href="blog-full.php">The Ultimate Guide to Wellness and Relaxation Here</a></h4>
                  <div class="room-services mar-bottom-10">
                    <ul>
                      <li>
                        <a href="blog-full.php"><i class="fa fa-user" aria-hidden="true"></i> By Bean Sead</a>
                      </li>
                      <li>
                        <a href="blog-full.php"><i class="fa fa-comment" aria-hidden="true"></i> 3 comments</a>
                      </li>
                    </ul>
                  </div>
                  <p>Discover our exclusive wellness offerings, including spa treatments, yoga sessions, and peaceful surroundings designed to rejuvenate your body and mind during your stay with us.</p>
                  <a href="blog-full.php"></a>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6 mar-bottom-30">
              <div class="news-item">
                <div class="news-image">
                  <img src="images/new4.jpeg" alt="image" />
                </div>
                <div class="news-content">
                  <p class="date mar-bottom-5">16 July 2024</p>
                  <h4><a href="blog-full.php">Top 8 Must-Visit Attractions Near Luviora Hotel</a></h4>
                  <div class="room-services mar-bottom-10">
                    <ul>
                      <li>
                        <a href="blog-full.php"><i class="fa fa-user" aria-hidden="true"></i> By Lila Peter</a>
                      </li>
                      <li>
                        <a href="blog-full.php"><i class="fa fa-comment" aria-hidden="true"></i> 3 comments</a>
                      </li>
                    </ul>
                  </div>
                  <p>Explore the best nearby spots to visit during your stay. From cultural landmarks to natural wonders, discover unforgettable experiences just minutes from Luviora’s doorstep.</p>
                  <a href="blog-full.php"></a>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6 mar-bottom-30">
              <div class="news-item">
                <div class="news-image">
                  <img src="images/new3.jpeg" alt="image" />
                </div>
                <div class="news-content">
                  <p class="date mar-bottom-5">1 May 2025</p>
                  <h4><a href="blog-full.php">How to Make the Most of Your Hotel Stay Experience at Luviora

</a></h4>
                  <div class="room-services mar-bottom-10">
                    <ul>
                      <li>
                        <a href="blog-full.php"><i class="fa fa-user" aria-hidden="true"></i> By John Doe</a>
                      </li>
                      <li>
                        <a href="blog-full.php"><i class="fa fa-comment" aria-hidden="true"></i> 3 comments</a>
                      </li>
                    </ul>
                  </div>
                  <p>Tips and tricks to enjoy every moment at Luviora. From fine dining to fun activities, learn how to create beautiful and lasting memories on your luxury hotel getaway today.</p>
                  <a href="blog-full.php"></a>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="section-btn">
          <a href="blog-full.php" class="btn btn-orange mar-right-10" style="background:#b98678; color:#fff; padding:10px 20px; border-radius:25px;">EXPLORE ALL</a>
        </div>
      </div>
    </section>
    <!-- News Ends -->

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
    <div id="back-to-top" style="">
      <a href="#"></a>
    </div>
    <!-- Back to top ends -->

    <div class="modal fade" id="login" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content login-content">
          <div class="login-image">
            <img src="images/luvioralogoblack.png" alt="image" />
          </div>
          <h3>Hello! Sign into your account</h3>
          <form id="loginForm">
            <div class="form-group">
              <input type="email" id="login-email" placeholder="Enter email address" required />
            </div>
            <div class="form-group">
              <input type="password" id="login-password" placeholder="Enter password" required />
            </div>
            <div class="form-group form-checkbox">
              <input type="checkbox" id="login-remember" /> Remember Me
              <a href="#">Forgot password?</a>
            </div>
          </form>
          <div class="form-btn">
            <a href="#" class="btn btn-orange" id="loginBtn">LOGIN</a>
            <p>Need an Account?<a href="#" data-bs-toggle="modal" data-bs-target="#register" data-bs-dismiss="modal"> Create your Luviora account</a></p>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="register" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content login-content">
          <div class="login-image">
            <img src="images/luvioralogoblack.png" alt="image" />
          </div>
          <h3>Awesome! Create a Luviora Account</h3>
          <form id="registerForm">
            <div class="form-group">
              <input type="text" id="register-name" placeholder="Enter your name" required />
            </div>
            <div class="form-group">
              <input type="email" id="register-email" placeholder="Enter email address" required />
            </div>
            <div class="form-group">
              <input type="text" id="register-phone" placeholder="Enter phone number" />
            </div>
            <div class="form-group">
              <input type="password" id="register-password" placeholder="Enter password" required />
            </div>
            <div class="form-group">
              <input type="password" id="register-confirm" placeholder="Confirm password" required />
            </div>
          </form>
          <div class="form-btn">
            <a href="#" class="btn btn-orange" id="registerBtn">SIGN UP</a>
            <p>Already have an account?<a href="#" data-bs-toggle="modal" data-bs-target="#login" data-bs-dismiss="modal"> Login here</a></p>
          </div>
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

    <!-- JavaScript to Play Video -->
    <script>
      document.getElementById('play-video-btn').addEventListener('click', function() {
        const videoContainer = document.getElementById('video-container');
        const iframe = document.getElementById('youtube-video');
        const videoCover = document.querySelector('.video-cover');

        const videoURL = "https://www.youtube.com/embed/EiAvmDVtKQw?autoplay=1";

        iframe.src = videoURL;
        videoContainer.style.display = 'block';
        videoCover.style.display = 'none';
        this.style.display = 'none';
      });
    </script>

    <!-- Authentication JavaScript -->
    <script src="js/auth.js"></script>

  </body>
</html>

