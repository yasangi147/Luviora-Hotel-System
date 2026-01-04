-- ============================================
-- BOOKING ENHANCEMENTS SCHEMA
-- ============================================
-- Additional tables for booking preferences, dietary requirements, and extra services
-- Run this AFTER reservation_system_schema.sql

USE luviora_hotel_system;

-- ============================================
-- TABLE: booking_preferences
-- Description: Store room preferences for each booking
-- ============================================
CREATE TABLE IF NOT EXISTS booking_preferences (
    preference_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    view_preference ENUM('ocean', 'city', 'garden', 'mountain', 'pool', 'any') DEFAULT 'any',
    bed_preference ENUM('king', 'queen', 'twin', 'double', 'any') DEFAULT 'any',
    floor_preference ENUM('low', 'mid', 'high', 'any') DEFAULT 'any',
    accessible_room BOOLEAN DEFAULT FALSE,
    connecting_rooms BOOLEAN DEFAULT FALSE,
    quiet_room BOOLEAN DEFAULT FALSE,
    near_elevator BOOLEAN DEFAULT FALSE,
    smoking_room BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: booking_dietary_requirements
-- Description: Store dietary requirements for each booking
-- ============================================
CREATE TABLE IF NOT EXISTS booking_dietary_requirements (
    dietary_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    vegetarian BOOLEAN DEFAULT FALSE,
    vegan BOOLEAN DEFAULT FALSE,
    gluten_free BOOLEAN DEFAULT FALSE,
    halal BOOLEAN DEFAULT FALSE,
    kosher BOOLEAN DEFAULT FALSE,
    other BOOLEAN DEFAULT FALSE,
    additional_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: extra_services
-- Description: Available extra services/add-ons
-- ============================================
CREATE TABLE IF NOT EXISTS extra_services (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    service_code VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    base_price DECIMAL(10, 2) NOT NULL,
    pricing_type ENUM('per_booking', 'per_night', 'per_person', 'per_person_per_night') DEFAULT 'per_booking',
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (service_code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: booking_services
-- Description: Extra services selected for each booking
-- ============================================
CREATE TABLE IF NOT EXISTS booking_services (
    booking_service_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    service_id INT NOT NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES extra_services(service_id) ON DELETE RESTRICT,
    INDEX idx_booking (booking_id),
    INDEX idx_service (service_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: booking_arrival_info
-- Description: Store arrival information for each booking
-- ============================================
CREATE TABLE IF NOT EXISTS booking_arrival_info (
    arrival_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    arrival_time TIME,
    transportation ENUM('car', 'taxi', 'public_transport', 'airport_shuttle', 'other') DEFAULT 'car',
    flight_number VARCHAR(20),
    estimated_arrival DATETIME,
    special_instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: booking_rooms
-- Description: Junction table for multi-room bookings
-- ============================================
CREATE TABLE IF NOT EXISTS booking_rooms (
    booking_room_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    room_id INT NOT NULL,
    price_per_night DECIMAL(10, 2) NOT NULL,
    room_total DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE RESTRICT,
    INDEX idx_booking (booking_id),
    INDEX idx_room (room_id),
    UNIQUE KEY unique_booking_room (booking_id, room_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT DEFAULT EXTRA SERVICES
-- ============================================
INSERT INTO extra_services (service_name, service_code, description, base_price, pricing_type, icon, display_order) VALUES
('Airport Transfer', 'airport', 'Complimentary airport pickup and drop-off service', 50.00, 'per_booking', 'fa-plane', 1),
('Breakfast Package', 'breakfast', 'Daily continental breakfast buffet', 30.00, 'per_person_per_night', 'fa-utensils', 2),
('Spa Treatment', 'spa', 'Relaxing 60-minute spa session', 80.00, 'per_booking', 'fa-spa', 3),
('Room Upgrade', 'upgrade', 'Upgrade to next room category', 40.00, 'per_night', 'fa-arrow-up', 4),
('Early Check-in', 'early-checkin', 'Check in before standard time (2 PM)', 25.00, 'per_booking', 'fa-clock', 5),
('Late Checkout', 'late-checkout', 'Check out after standard time (11 AM)', 25.00, 'per_booking', 'fa-clock', 6),
('Champagne & Flowers', 'champagne', 'Welcome champagne bottle and fresh flowers', 60.00, 'per_booking', 'fa-glass-cheers', 7),
('City Tour', 'city-tour', 'Guided city tour with professional guide', 40.00, 'per_person', 'fa-map-marked-alt', 8)
ON DUPLICATE KEY UPDATE 
    service_name = VALUES(service_name),
    description = VALUES(description),
    base_price = VALUES(base_price),
    pricing_type = VALUES(pricing_type),
    icon = VALUES(icon),
    display_order = VALUES(display_order);

-- ============================================
-- ALTER bookings table to add new fields
-- ============================================
ALTER TABLE bookings 
ADD COLUMN IF NOT EXISTS arrival_time TIME AFTER check_out_date,
ADD COLUMN IF NOT EXISTS transportation VARCHAR(50) AFTER arrival_time,
ADD COLUMN IF NOT EXISTS booking_for_other BOOLEAN DEFAULT FALSE AFTER special_requests,
ADD COLUMN IF NOT EXISTS guest_name VARCHAR(100) AFTER booking_for_other,
ADD COLUMN IF NOT EXISTS guest_email VARCHAR(100) AFTER guest_name,
ADD COLUMN IF NOT EXISTS subtotal DECIMAL(10, 2) AFTER total_amount,
ADD COLUMN IF NOT EXISTS tax_amount DECIMAL(10, 2) AFTER subtotal,
ADD COLUMN IF NOT EXISTS services_total DECIMAL(10, 2) DEFAULT 0.00 AFTER tax_amount;

-- ============================================
-- ALTER users table to add date_of_birth and country if not exists
-- ============================================
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS date_of_birth DATE AFTER email,
ADD COLUMN IF NOT EXISTS country VARCHAR(100) AFTER city;

-- ============================================
-- STORED PROCEDURE: Create Complete Booking
-- ============================================
DELIMITER //

DROP PROCEDURE IF EXISTS CreateCompleteBooking//

CREATE PROCEDURE CreateCompleteBooking(
    IN p_user_id INT,
    IN p_room_ids TEXT, -- Comma-separated room IDs
    IN p_check_in DATE,
    IN p_check_out DATE,
    IN p_num_adults INT,
    IN p_num_children INT,
    IN p_total_nights INT,
    IN p_subtotal DECIMAL(10,2),
    IN p_tax_amount DECIMAL(10,2),
    IN p_services_total DECIMAL(10,2),
    IN p_total_amount DECIMAL(10,2),
    IN p_special_requests TEXT,
    IN p_arrival_time TIME,
    IN p_transportation VARCHAR(50),
    IN p_payment_method VARCHAR(50),
    OUT p_booking_id INT,
    OUT p_booking_reference VARCHAR(20)
)
BEGIN
    DECLARE v_room_id INT;
    DECLARE v_room_price DECIMAL(10,2);
    DECLARE v_room_total DECIMAL(10,2);
    DECLARE v_done INT DEFAULT FALSE;
    DECLARE v_error_msg VARCHAR(255);
    
    -- Generate booking reference
    SET p_booking_reference = CONCAT(
        'LUV',
        DATE_FORMAT(NOW(), '%y%m%d'),
        LPAD(FLOOR(RAND() * 10000), 4, '0')
    );
    
    -- Start transaction
    START TRANSACTION;
    
    -- Insert main booking record
    INSERT INTO bookings (
        booking_reference, user_id, check_in_date, check_out_date,
        num_adults, num_children, total_nights, 
        subtotal, tax_amount, services_total, total_amount,
        special_requests, arrival_time, transportation,
        booking_status, payment_status, price_per_night
    ) VALUES (
        p_booking_reference, p_user_id, p_check_in, p_check_out,
        p_num_adults, p_num_children, p_total_nights,
        p_subtotal, p_tax_amount, p_services_total, p_total_amount,
        p_special_requests, p_arrival_time, p_transportation,
        'pending', 'unpaid', 0
    );
    
    SET p_booking_id = LAST_INSERT_ID();
    
    -- Insert payment record
    INSERT INTO payments (
        booking_id, payment_method, amount, currency, payment_status
    ) VALUES (
        p_booking_id, p_payment_method, p_total_amount, 'USD', 'pending'
    );
    
    COMMIT;
END//

DELIMITER ;

-- ============================================
-- COMPLETE: Booking enhancements schema finished
-- ============================================

