-- ============================================
-- LUVIORA HOTEL MANAGEMENT SYSTEM - DATABASE SCHEMA
-- ============================================
-- Created: 2025-10-24
-- Description: Complete database schema for hotel management system
-- ============================================

-- Drop database if exists and create fresh
DROP DATABASE IF EXISTS luviora_hotel_system;
CREATE DATABASE luviora_hotel_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE luviora_hotel_system;

-- ============================================
-- TABLE: users
-- Description: Stores all system users (guests, staff, clark, admin)
-- ============================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('guest', 'staff', 'clark', 'admin') DEFAULT 'guest',
    profile_image VARCHAR(255),
    address TEXT,
    city VARCHAR(50),
    country VARCHAR(50),
    postal_code VARCHAR(20),
    id_number VARCHAR(50),
    date_of_birth DATE,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(100),
    reset_token VARCHAR(100),
    reset_token_expiry DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: room_specs
-- Description: Predefined room specifications/amenities
-- ============================================
CREATE TABLE room_specs (
    spec_id INT AUTO_INCREMENT PRIMARY KEY,
    spec_name VARCHAR(50) NOT NULL UNIQUE,
    spec_icon VARCHAR(50),
    spec_description TEXT,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: rooms
-- Description: Hotel room inventory
-- ============================================
CREATE TABLE rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL UNIQUE,
    room_name VARCHAR(100) NOT NULL,
    room_type ENUM('Single', 'Double', 'Suite', 'Deluxe', 'Presidential') NOT NULL,
    floor INT NOT NULL,
    price_per_night DECIMAL(10, 2) NOT NULL,
    max_occupancy INT DEFAULT 2,
    size_sqm DECIMAL(6, 2),
    bed_type VARCHAR(50),
    description TEXT,
    room_image VARCHAR(255),
    additional_images TEXT, -- JSON array of image paths
    status ENUM('available', 'occupied', 'maintenance', 'cleaning', 'reserved') DEFAULT 'available',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_room_type (room_type),
    INDEX idx_floor (floor),
    INDEX idx_status (status),
    INDEX idx_price (price_per_night),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: room_spec_map
-- Description: Many-to-many mapping between rooms and specifications
-- ============================================
CREATE TABLE room_spec_map (
    map_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    spec_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE,
    FOREIGN KEY (spec_id) REFERENCES room_specs(spec_id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_spec (room_id, spec_id),
    INDEX idx_room (room_id),
    INDEX idx_spec (spec_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: bookings
-- Description: Guest reservations and booking records
-- ============================================
CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_reference VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    room_id INT,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    num_adults INT DEFAULT 1,
    num_children INT DEFAULT 0,
    total_nights INT NOT NULL,
    price_per_night DECIMAL(10, 2) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    special_requests TEXT,
    booking_status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show') DEFAULT 'pending',
    payment_status ENUM('unpaid', 'partial', 'paid', 'refunded') DEFAULT 'unpaid',
    check_in_time DATETIME,
    check_out_time DATETIME,
    assigned_by INT, -- staff/clark user_id who assigned the room
    cancelled_at DATETIME,
    cancellation_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_room (room_id),
    INDEX idx_dates (check_in_date, check_out_date),
    INDEX idx_booking_status (booking_status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_reference (booking_reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: payments
-- Description: Payment transaction records
-- ============================================
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    transaction_id VARCHAR(100) UNIQUE,
    payment_method ENUM('credit_card', 'debit_card', 'paypal', 'stripe', 'payhere', 'cash', 'bank_transfer') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    payment_status ENUM('pending', 'completed', 'failed', 'refunded', 'cancelled') DEFAULT 'pending',
    payment_gateway_response TEXT, -- JSON response from payment gateway
    card_last_four VARCHAR(4),
    payment_date DATETIME,
    refund_amount DECIMAL(10, 2) DEFAULT 0.00,
    refund_date DATETIME,
    refund_reason TEXT,
    processed_by INT, -- user_id of staff who processed (for manual payments)
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE RESTRICT,
    FOREIGN KEY (processed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_booking (booking_id),
    INDEX idx_transaction (transaction_id),
    INDEX idx_status (payment_status),
    INDEX idx_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: qr_codes
-- Description: QR codes for contactless check-in/check-out
-- ============================================
CREATE TABLE qr_codes (
    qr_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    qr_code_data TEXT NOT NULL, -- Encrypted QR data
    qr_code_hash VARCHAR(64) NOT NULL UNIQUE, -- SHA-256 hash for validation
    qr_image_path VARCHAR(255),
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry_time DATETIME NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    used_at DATETIME,
    scan_count INT DEFAULT 0,
    last_scanned_at DATETIME,
    status ENUM('active', 'expired', 'used', 'cancelled') DEFAULT 'active',
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id),
    INDEX idx_hash (qr_code_hash),
    INDEX idx_status (status),
    INDEX idx_expiry (expiry_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: feedback
-- Description: Guest feedback and reviews
-- ============================================
CREATE TABLE feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    booking_id INT,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    cleanliness_rating INT CHECK (cleanliness_rating BETWEEN 1 AND 5),
    service_rating INT CHECK (service_rating BETWEEN 1 AND 5),
    location_rating INT CHECK (location_rating BETWEEN 1 AND 5),
    value_rating INT CHECK (value_rating BETWEEN 1 AND 5),
    title VARCHAR(200),
    comments TEXT,
    is_published BOOLEAN DEFAULT FALSE,
    is_featured BOOLEAN DEFAULT FALSE,
    admin_response TEXT,
    responded_at DATETIME,
    responded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE SET NULL,
    FOREIGN KEY (responded_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_booking (booking_id),
    INDEX idx_rating (rating),
    INDEX idx_published (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: reports
-- Description: Daily/monthly performance reports
-- ============================================
CREATE TABLE reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    report_date DATE NOT NULL UNIQUE,
    total_bookings INT DEFAULT 0,
    total_check_ins INT DEFAULT 0,
    total_check_outs INT DEFAULT 0,
    total_cancellations INT DEFAULT 0,
    occupancy_rate DECIMAL(5, 2) DEFAULT 0.00, -- Percentage
    total_revenue DECIMAL(12, 2) DEFAULT 0.00,
    average_daily_rate DECIMAL(10, 2) DEFAULT 0.00,
    revenue_per_available_room DECIMAL(10, 2) DEFAULT 0.00,
    total_rooms_available INT DEFAULT 0,
    total_rooms_occupied INT DEFAULT 0,
    total_rooms_maintenance INT DEFAULT 0,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (report_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: activity_log
-- Description: System activity and audit trail
-- ============================================
CREATE TABLE activity_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action_type VARCHAR(50) NOT NULL,
    action_description TEXT,
    entity_type VARCHAR(50), -- e.g., 'booking', 'room', 'user'
    entity_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    request_data TEXT, -- JSON data
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action_type),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SAMPLE DATA INSERTION
-- ============================================

-- Insert default users (password: admin123 - hashed with bcrypt)
-- NOTE: All default users have password "admin123" for testing purposes
-- IMPORTANT: Change these passwords in production!
INSERT INTO users (name, email, phone, password, role, status, email_verified) VALUES
('System Administrator', 'admin@luviora.com', '+1234567890', '$2y$10$Pm7VmvjdXTqWP8zsRQ3CMOUHgcbFLmovT1KrC2GyUq4xOiJwlEcaK', 'admin', 'active', TRUE),
('Front Desk Clark', 'clark@luviora.com', '+1234567891', '$2y$10$Pm7VmvjdXTqWP8zsRQ3CMOUHgcbFLmovT1KrC2GyUq4xOiJwlEcaK', 'clark', 'active', TRUE),
('Hotel Staff', 'staff@luviora.com', '+1234567892', '$2y$10$Pm7VmvjdXTqWP8zsRQ3CMOUHgcbFLmovT1KrC2GyUq4xOiJwlEcaK', 'staff', 'active', TRUE),
('John Doe', 'john.doe@example.com', '+1234567893', '$2y$10$Pm7VmvjdXTqWP8zsRQ3CMOUHgcbFLmovT1KrC2GyUq4xOiJwlEcaK', 'guest', 'active', TRUE);

-- Insert room specifications
INSERT INTO room_specs (spec_name, spec_icon, spec_description, display_order) VALUES
('Air Conditioning', 'fa-snowflake', 'Climate controlled air conditioning', 1),
('Balcony', 'fa-door-open', 'Private balcony with seating', 2),
('Sea View', 'fa-water', 'Beautiful ocean view', 3),
('City View', 'fa-city', 'Panoramic city view', 4),
('WiFi', 'fa-wifi', 'High-speed wireless internet', 5),
('TV', 'fa-tv', 'Flat screen television with cable', 6),
('Mini Bar', 'fa-glass-martini', 'Stocked mini refrigerator', 7),
('Safe', 'fa-lock', 'In-room security safe', 8),
('Bathtub', 'fa-bath', 'Luxury bathtub', 9),
('Shower', 'fa-shower', 'Rain shower', 10),
('Coffee Maker', 'fa-coffee', 'In-room coffee/tea maker', 11),
('Work Desk', 'fa-desk', 'Spacious work desk', 12),
('Sofa', 'fa-couch', 'Comfortable seating area', 13),
('Kitchenette', 'fa-utensils', 'Small kitchen area', 14),
('Jacuzzi', 'fa-hot-tub', 'Private jacuzzi', 15),
('Fireplace', 'fa-fire', 'Electric fireplace', 16),
('Soundproof', 'fa-volume-mute', 'Soundproof windows', 17),
('Pet Friendly', 'fa-paw', 'Pets allowed', 18),
('Wheelchair Accessible', 'fa-wheelchair', 'ADA compliant', 19),
('Non-Smoking', 'fa-smoking-ban', 'Non-smoking room', 20);

-- Insert sample rooms
INSERT INTO rooms (room_number, room_name, room_type, floor, price_per_night, max_occupancy, size_sqm, bed_type, description, room_image, status) VALUES
('101', 'Cozy Single Room', 'Single', 1, 89.99, 1, 20.00, '1 Single Bed', 'Perfect for solo travelers. Compact and comfortable with all essential amenities.', 'images/rooms/single-101.jpg', 'available'),
('102', 'Comfort Single Room', 'Single', 1, 99.99, 1, 22.00, '1 Single Bed', 'Comfortable single room with modern amenities and workspace.', 'images/rooms/single-102.jpg', 'available'),
('201', 'Standard Double Room', 'Double', 2, 149.99, 2, 30.00, '1 Queen Bed', 'Spacious double room with queen bed and city view.', 'images/rooms/double-201.jpg', 'available'),
('202', 'Deluxe Double Room', 'Double', 2, 179.99, 2, 35.00, '1 King Bed', 'Luxurious double room with king bed and premium amenities.', 'images/rooms/double-202.jpg', 'available'),
('203', 'Superior Double Room', 'Double', 2, 169.99, 2, 32.00, '2 Double Beds', 'Perfect for families or friends traveling together.', 'images/rooms/double-203.jpg', 'available'),
('301', 'Junior Suite', 'Suite', 3, 249.99, 3, 45.00, '1 King Bed + Sofa Bed', 'Spacious suite with separate living area and stunning views.', 'images/rooms/suite-301.jpg', 'available'),
('302', 'Executive Suite', 'Suite', 3, 299.99, 4, 55.00, '1 King Bed + 1 Queen Bed', 'Elegant suite with two bedrooms and premium facilities.', 'images/rooms/suite-302.jpg', 'available'),
('401', 'Deluxe Ocean View', 'Deluxe', 4, 349.99, 2, 40.00, '1 King Bed', 'Premium room with breathtaking ocean views and luxury amenities.', 'images/rooms/deluxe-401.jpg', 'available'),
('402', 'Deluxe Balcony Suite', 'Deluxe', 4, 379.99, 3, 48.00, '1 King Bed + Sofa Bed', 'Luxurious suite with private balcony and ocean view.', 'images/rooms/deluxe-402.jpg', 'available'),
('501', 'Presidential Suite', 'Presidential', 5, 599.99, 4, 80.00, '2 King Beds', 'Ultimate luxury with panoramic views, jacuzzi, and exclusive amenities.', 'images/rooms/presidential-501.jpg', 'available');

-- Map room specifications to rooms
-- Room 101 (Cozy Single)
INSERT INTO room_spec_map (room_id, spec_id) VALUES
(1, 1), (1, 5), (1, 6), (1, 8), (1, 10), (1, 11), (1, 19), (1, 20);

-- Room 102 (Comfort Single)
INSERT INTO room_spec_map (room_id, spec_id) VALUES
(2, 1), (2, 4), (2, 5), (2, 6), (2, 8), (2, 10), (2, 11), (2, 12), (2, 19), (2, 20);

-- Room 201 (Standard Double)
INSERT INTO room_spec_map (room_id, spec_id) VALUES
(3, 1), (3, 4), (3, 5), (3, 6), (3, 7), (3, 8), (3, 10), (3, 11), (3, 12), (3, 20);

-- Room 202 (Deluxe Double)
INSERT INTO room_spec_map (room_id, spec_id) VALUES
(4, 1), (4, 2), (4, 4), (4, 5), (4, 6), (4, 7), (4, 8), (4, 9), (4, 11), (4, 12), (4, 13), (4, 17), (4, 20);

-- Room 203 (Superior Double)
INSERT INTO room_spec_map (room_id, spec_id) VALUES
(5, 1), (5, 4), (5, 5), (5, 6), (5, 7), (5, 8), (5, 10), (5, 11), (5, 12), (5, 20);

-- Room 301 (Junior Suite)
INSERT INTO room_spec_map (room_id, spec_id) VALUES
(6, 1), (6, 2), (6, 3), (6, 5), (6, 6), (6, 7), (6, 8), (6, 9), (6, 11), (6, 12), (6, 13), (6, 14), (6, 17), (6, 20);

-- Room 302 (Executive Suite)
INSERT INTO room_spec_map (room_id, spec_id) VALUES
(7, 1), (7, 2), (7, 3), (7, 5), (7, 6), (7, 7), (7, 8), (7, 9), (7, 11), (7, 12), (7, 13), (7, 14), (7, 16), (7, 17), (7, 20);

-- Room 401 (Deluxe Ocean View)
INSERT INTO room_spec_map (room_id, spec_id) VALUES
(8, 1), (8, 2), (8, 3), (8, 5), (8, 6), (8, 7), (8, 8), (8, 9), (8, 11), (8, 12), (8, 13), (8, 17), (8, 20);

-- Room 402 (Deluxe Balcony Suite)
INSERT INTO room_spec_map (room_id, spec_id) VALUES
(9, 1), (9, 2), (9, 3), (9, 5), (9, 6), (9, 7), (9, 8), (9, 9), (9, 11), (9, 12), (9, 13), (9, 14), (9, 15), (9, 17), (9, 20);

-- Room 501 (Presidential Suite)
INSERT INTO room_spec_map (room_id, spec_id) VALUES
(10, 1), (10, 2), (10, 3), (10, 5), (10, 6), (10, 7), (10, 8), (10, 9), (10, 11), (10, 12), (10, 13), (10, 14), (10, 15), (10, 16), (10, 17), (10, 19), (10, 20);

-- ============================================
-- STORED PROCEDURES AND FUNCTIONS
-- ============================================

DELIMITER //

-- Procedure to check room availability
CREATE PROCEDURE CheckRoomAvailability(
    IN p_check_in DATE,
    IN p_check_out DATE,
    IN p_room_type VARCHAR(50)
)
BEGIN
    SELECT r.*
    FROM rooms r
    WHERE r.is_active = TRUE
    AND r.status = 'available'
    AND (p_room_type IS NULL OR r.room_type = p_room_type)
    AND r.room_id NOT IN (
        SELECT DISTINCT room_id
        FROM bookings
        WHERE room_id IS NOT NULL
        AND booking_status NOT IN ('cancelled', 'checked_out')
        AND (
            (check_in_date <= p_check_in AND check_out_date > p_check_in)
            OR (check_in_date < p_check_out AND check_out_date >= p_check_out)
            OR (check_in_date >= p_check_in AND check_out_date <= p_check_out)
        )
    )
    ORDER BY r.price_per_night ASC;
END //

-- Procedure to generate booking reference
CREATE FUNCTION GenerateBookingReference()
RETURNS VARCHAR(20)
DETERMINISTIC
BEGIN
    DECLARE ref VARCHAR(20);
    DECLARE ref_exists INT;

    REPEAT
        SET ref = CONCAT('LUV', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(FLOOR(RAND() * 10000), 4, '0'));
        SELECT COUNT(*) INTO ref_exists FROM bookings WHERE booking_reference = ref;
    UNTIL ref_exists = 0
    END REPEAT;

    RETURN ref;
END //

-- Procedure to calculate daily occupancy
CREATE PROCEDURE CalculateDailyOccupancy(IN p_date DATE)
BEGIN
    DECLARE total_rooms INT;
    DECLARE occupied_rooms INT;
    DECLARE occupancy_rate DECIMAL(5,2);

    SELECT COUNT(*) INTO total_rooms FROM rooms WHERE is_active = TRUE;

    SELECT COUNT(DISTINCT room_id) INTO occupied_rooms
    FROM bookings
    WHERE p_date BETWEEN check_in_date AND DATE_SUB(check_out_date, INTERVAL 1 DAY)
    AND booking_status IN ('confirmed', 'checked_in');

    SET occupancy_rate = (occupied_rooms / total_rooms) * 100;

    SELECT total_rooms, occupied_rooms, occupancy_rate;
END //

-- Procedure to update room status based on bookings
CREATE PROCEDURE UpdateRoomStatuses()
BEGIN
    -- Set rooms to occupied if checked in
    UPDATE rooms r
    INNER JOIN bookings b ON r.room_id = b.room_id
    SET r.status = 'occupied'
    WHERE b.booking_status = 'checked_in'
    AND CURDATE() BETWEEN b.check_in_date AND DATE_SUB(b.check_out_date, INTERVAL 1 DAY);

    -- Set rooms to available if checked out
    UPDATE rooms r
    LEFT JOIN bookings b ON r.room_id = b.room_id
        AND b.booking_status IN ('confirmed', 'checked_in')
        AND CURDATE() BETWEEN b.check_in_date AND b.check_out_date
    SET r.status = 'available'
    WHERE b.booking_id IS NULL
    AND r.status = 'occupied'
    AND r.is_active = TRUE;
END //

-- Trigger to log user activities
CREATE TRIGGER after_booking_insert
AFTER INSERT ON bookings
FOR EACH ROW
BEGIN
    INSERT INTO activity_log (user_id, action_type, action_description, entity_type, entity_id)
    VALUES (NEW.user_id, 'booking_created', CONCAT('New booking created: ', NEW.booking_reference), 'booking', NEW.booking_id);
END //

CREATE TRIGGER after_booking_update
AFTER UPDATE ON bookings
FOR EACH ROW
BEGIN
    IF OLD.booking_status != NEW.booking_status THEN
        INSERT INTO activity_log (user_id, action_type, action_description, entity_type, entity_id)
        VALUES (NEW.user_id, 'booking_status_changed',
                CONCAT('Booking ', NEW.booking_reference, ' status changed from ', OLD.booking_status, ' to ', NEW.booking_status),
                'booking', NEW.booking_id);
    END IF;
END //

DELIMITER ;

-- ============================================
-- VIEWS FOR REPORTING
-- ============================================

-- View for current bookings
CREATE VIEW v_current_bookings AS
SELECT
    b.booking_id,
    b.booking_reference,
    b.check_in_date,
    b.check_out_date,
    b.booking_status,
    b.payment_status,
    u.name AS guest_name,
    u.email AS guest_email,
    u.phone AS guest_phone,
    r.room_number,
    r.room_name,
    r.room_type,
    b.total_amount
FROM bookings b
INNER JOIN users u ON b.user_id = u.user_id
LEFT JOIN rooms r ON b.room_id = r.room_id
WHERE b.booking_status NOT IN ('cancelled', 'checked_out');

-- View for room availability summary
CREATE VIEW v_room_availability AS
SELECT
    r.room_id,
    r.room_number,
    r.room_name,
    r.room_type,
    r.floor,
    r.price_per_night,
    r.status,
    GROUP_CONCAT(rs.spec_name SEPARATOR ', ') AS specifications
FROM rooms r
LEFT JOIN room_spec_map rsm ON r.room_id = rsm.room_id
LEFT JOIN room_specs rs ON rsm.spec_id = rs.spec_id
WHERE r.is_active = TRUE
GROUP BY r.room_id;

-- ============================================
-- INDEXES FOR PERFORMANCE OPTIMIZATION
-- ============================================

-- Additional composite indexes for common queries
CREATE INDEX idx_booking_dates_status ON bookings(check_in_date, check_out_date, booking_status);
CREATE INDEX idx_room_type_status ON rooms(room_type, status, is_active);
CREATE INDEX idx_user_role_status ON users(role, status);

-- ============================================
-- DATABASE SCHEMA COMPLETE
-- ============================================
-- Total Tables: 10
-- Total Views: 2
-- Total Stored Procedures: 4
-- Total Functions: 1
-- Total Triggers: 2
-- ============================================

