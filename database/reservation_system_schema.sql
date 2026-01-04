-- ============================================
-- ENHANCED RESERVATION SYSTEM SCHEMA
-- ============================================
-- This file contains additional tables and updates for the reservation system
-- Run this AFTER the main luviora_hotel_system.sql

USE luviora_hotel_system;

-- ============================================
-- UPDATE: Add missing fields to rooms table
-- ============================================
ALTER TABLE rooms 
ADD COLUMN IF NOT EXISTS view_type ENUM('ocean', 'city', 'garden', 'mountain', 'pool', 'none') DEFAULT 'none' AFTER bed_type,
ADD COLUMN IF NOT EXISTS room_style ENUM('modern', 'classic', 'boutique', 'suite', 'standard') DEFAULT 'standard' AFTER view_type,
ADD COLUMN IF NOT EXISTS ideal_for ENUM('couples', 'families', 'business', 'solo', 'groups') DEFAULT 'couples' AFTER room_style,
ADD COLUMN IF NOT EXISTS is_pet_friendly BOOLEAN DEFAULT FALSE AFTER ideal_for,
ADD COLUMN IF NOT EXISTS is_accessible BOOLEAN DEFAULT FALSE AFTER is_pet_friendly,
ADD COLUMN IF NOT EXISTS is_smoking_allowed BOOLEAN DEFAULT FALSE AFTER is_accessible,
ADD COLUMN IF NOT EXISTS free_cancellation BOOLEAN DEFAULT TRUE AFTER is_smoking_allowed,
ADD COLUMN IF NOT EXISTS breakfast_included BOOLEAN DEFAULT FALSE AFTER free_cancellation,
ADD COLUMN IF NOT EXISTS rating DECIMAL(2,1) DEFAULT 5.0 AFTER breakfast_included,
ADD COLUMN IF NOT EXISTS popularity_score INT DEFAULT 0 AFTER rating;

-- ============================================
-- TABLE: room_availability
-- Description: Track room availability by date
-- ============================================
CREATE TABLE IF NOT EXISTS room_availability (
    availability_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('available', 'booked', 'blocked', 'maintenance') DEFAULT 'available',
    booking_id INT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE SET NULL,
    UNIQUE KEY unique_room_date (room_id, date),
    INDEX idx_room (room_id),
    INDEX idx_date (date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: email_logs
-- Description: Track all emails sent from the system
-- ============================================
CREATE TABLE IF NOT EXISTS email_logs (
    email_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NULL,
    user_id INT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    email_type ENUM('booking_confirmation', 'cancellation', 'reminder', 'payment_receipt', 'general') NOT NULL,
    status ENUM('pending', 'sent', 'failed', 'bounced') DEFAULT 'pending',
    sent_at DATETIME,
    error_message TEXT,
    attachments TEXT, -- JSON array of attachment paths
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_booking (booking_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_type (email_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: booking_guests
-- Description: Store guest information for each booking
-- ============================================
CREATE TABLE IF NOT EXISTS booking_guests (
    guest_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    guest_name VARCHAR(100) NOT NULL,
    guest_email VARCHAR(100),
    guest_phone VARCHAR(20),
    guest_type ENUM('primary', 'additional') DEFAULT 'additional',
    age_group ENUM('adult', 'child', 'infant') DEFAULT 'adult',
    special_requirements TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- STORED PROCEDURE: Check Room Availability for Date Range
-- ============================================
DELIMITER //

DROP PROCEDURE IF EXISTS CheckRoomAvailabilityRange//

CREATE PROCEDURE CheckRoomAvailabilityRange(
    IN p_check_in DATE,
    IN p_check_out DATE,
    IN p_num_rooms INT,
    IN p_num_guests INT
)
BEGIN
    -- Get available rooms for the specified date range
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
    AND r.max_occupancy >= p_num_guests
    AND r.room_id NOT IN (
        -- Exclude rooms that are booked during the requested period
        SELECT DISTINCT room_id
        FROM bookings
        WHERE booking_status IN ('confirmed', 'checked_in')
        AND (
            (check_in_date <= p_check_in AND check_out_date > p_check_in)
            OR (check_in_date < p_check_out AND check_out_date >= p_check_out)
            OR (check_in_date >= p_check_in AND check_out_date <= p_check_out)
        )
    )
    GROUP BY r.room_id
    ORDER BY r.popularity_score DESC, r.rating DESC, r.price_per_night ASC
    LIMIT 50;
END//

DELIMITER ;

-- ============================================
-- STORED PROCEDURE: Create Booking with Availability Update
-- ============================================
DELIMITER //

DROP PROCEDURE IF EXISTS CreateBookingWithAvailability//

CREATE PROCEDURE CreateBookingWithAvailability(
    IN p_booking_reference VARCHAR(20),
    IN p_user_id INT,
    IN p_room_id INT,
    IN p_check_in DATE,
    IN p_check_out DATE,
    IN p_num_adults INT,
    IN p_num_children INT,
    IN p_total_nights INT,
    IN p_price_per_night DECIMAL(10,2),
    IN p_total_amount DECIMAL(10,2),
    IN p_special_requests TEXT,
    OUT p_booking_id INT
)
BEGIN
    DECLARE v_current_date DATE;
    DECLARE v_error_msg VARCHAR(255);
    
    -- Start transaction
    START TRANSACTION;
    
    -- Check if room is available for all dates
    SELECT COUNT(*) INTO @conflict_count
    FROM bookings
    WHERE room_id = p_room_id
    AND booking_status IN ('confirmed', 'checked_in')
    AND (
        (check_in_date <= p_check_in AND check_out_date > p_check_in)
        OR (check_in_date < p_check_out AND check_out_date >= p_check_out)
        OR (check_in_date >= p_check_in AND check_out_date <= p_check_out)
    );
    
    IF @conflict_count > 0 THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Room is not available for the selected dates';
    END IF;
    
    -- Insert booking
    INSERT INTO bookings (
        booking_reference, user_id, room_id, check_in_date, check_out_date,
        num_adults, num_children, total_nights, price_per_night, total_amount,
        special_requests, booking_status, payment_status
    ) VALUES (
        p_booking_reference, p_user_id, p_room_id, p_check_in, p_check_out,
        p_num_adults, p_num_children, p_total_nights, p_price_per_night, p_total_amount,
        p_special_requests, 'pending', 'unpaid'
    );
    
    SET p_booking_id = LAST_INSERT_ID();
    
    -- Update room availability for each date in the range
    SET v_current_date = p_check_in;
    
    WHILE v_current_date < p_check_out DO
        INSERT INTO room_availability (room_id, date, status, booking_id)
        VALUES (p_room_id, v_current_date, 'booked', p_booking_id)
        ON DUPLICATE KEY UPDATE 
            status = 'booked',
            booking_id = p_booking_id;
        
        SET v_current_date = DATE_ADD(v_current_date, INTERVAL 1 DAY);
    END WHILE;
    
    COMMIT;
END//

DELIMITER ;

-- ============================================
-- FUNCTION: Generate Booking Reference
-- ============================================
DELIMITER //

DROP FUNCTION IF EXISTS GenerateBookingRef//

CREATE FUNCTION GenerateBookingRef() 
RETURNS VARCHAR(20)
DETERMINISTIC
BEGIN
    DECLARE ref VARCHAR(20);
    DECLARE ref_exists INT;
    
    REPEAT
        SET ref = CONCAT(
            'LUV',
            DATE_FORMAT(NOW(), '%y%m%d'),
            LPAD(FLOOR(RAND() * 10000), 4, '0')
        );
        
        SELECT COUNT(*) INTO ref_exists
        FROM bookings
        WHERE booking_reference = ref;
    UNTIL ref_exists = 0
    END REPEAT;
    
    RETURN ref;
END//

DELIMITER ;

-- ============================================
-- TRIGGER: Update Room Status After Booking
-- ============================================
DELIMITER //

DROP TRIGGER IF EXISTS after_booking_insert//

CREATE TRIGGER after_booking_insert
AFTER INSERT ON bookings
FOR EACH ROW
BEGIN
    -- Update room status to reserved if booking is confirmed
    IF NEW.booking_status = 'confirmed' THEN
        UPDATE rooms 
        SET status = 'reserved'
        WHERE room_id = NEW.room_id;
    END IF;
END//

DELIMITER ;

-- ============================================
-- TRIGGER: Update Room Status After Booking Update
-- ============================================
DELIMITER //

DROP TRIGGER IF EXISTS after_booking_update//

CREATE TRIGGER after_booking_update
AFTER UPDATE ON bookings
FOR EACH ROW
BEGIN
    -- Update room status based on booking status
    IF NEW.booking_status = 'checked_in' THEN
        UPDATE rooms SET status = 'occupied' WHERE room_id = NEW.room_id;
    ELSEIF NEW.booking_status = 'checked_out' OR NEW.booking_status = 'cancelled' THEN
        UPDATE rooms SET status = 'available' WHERE room_id = NEW.room_id;
    ELSEIF NEW.booking_status = 'confirmed' THEN
        UPDATE rooms SET status = 'reserved' WHERE room_id = NEW.room_id;
    END IF;
    
    -- Update availability table if booking is cancelled
    IF NEW.booking_status = 'cancelled' AND OLD.booking_status != 'cancelled' THEN
        UPDATE room_availability
        SET status = 'available', booking_id = NULL
        WHERE booking_id = NEW.booking_id;
    END IF;
END//

DELIMITER ;

-- ============================================
-- Insert sample room specifications
-- ============================================
INSERT IGNORE INTO room_specs (spec_name, spec_icon, spec_description, display_order) VALUES
('WiFi', 'fas fa-wifi', 'High-speed wireless internet', 1),
('Air Conditioning', 'fas fa-snowflake', 'Climate control system', 2),
('Mini Bar', 'fas fa-glass-martini', 'Complimentary mini bar', 3),
('Smart TV', 'fas fa-tv', '55" Smart Television', 4),
('Balcony', 'fas fa-door-open', 'Private balcony', 5),
('Bathtub', 'fas fa-bath', 'Luxury bathtub', 6),
('Work Desk', 'fas fa-briefcase', 'Spacious work area', 7),
('Room Service', 'fas fa-concierge-bell', '24/7 room service', 8),
('Safe', 'fas fa-lock', 'In-room safe', 9),
('Coffee Maker', 'fas fa-coffee', 'Nespresso machine', 10);

-- ============================================
-- COMPLETE: Schema enhancement finished
-- ============================================

