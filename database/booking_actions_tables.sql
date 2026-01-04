-- ============================================
-- BOOKING ACTIONS TABLES
-- Tables for handling booking modifications and cancellations
-- ============================================

USE luviora_hotel_system;

-- ============================================
-- TABLE: booking_modifications
-- Description: Stores booking modification requests
-- ============================================
CREATE TABLE IF NOT EXISTS booking_modifications (
    modification_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    modification_type VARCHAR(50) NOT NULL COMMENT 'dates, room, guests, general',
    request_details TEXT COMMENT 'Description of requested changes',
    requested_at DATETIME NOT NULL,
    processed_at DATETIME NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT NULL COMMENT 'Admin response or notes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id),
    INDEX idx_status (status),
    INDEX idx_requested_at (requested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: booking_cancellations
-- Description: Stores booking cancellation records
-- ============================================
CREATE TABLE IF NOT EXISTS booking_cancellations (
    cancellation_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    cancelled_by ENUM('guest', 'admin', 'system') DEFAULT 'guest',
    cancellation_reason TEXT COMMENT 'Reason for cancellation',
    refund_amount DECIMAL(10,2) DEFAULT 0 COMMENT 'Amount to be refunded',
    refund_percentage INT DEFAULT 0 COMMENT 'Percentage of total amount',
    cancelled_at DATETIME NOT NULL,
    refund_status ENUM('pending', 'processed', 'not_applicable') DEFAULT 'pending',
    refund_processed_at DATETIME NULL,
    admin_notes TEXT NULL COMMENT 'Admin notes about refund',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id),
    INDEX idx_cancelled_by (cancelled_by),
    INDEX idx_refund_status (refund_status),
    INDEX idx_cancelled_at (cancelled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SAMPLE DATA (Optional - for testing)
-- ============================================

-- You can insert test data here if needed

-- ============================================
-- VIEWS FOR REPORTING
-- ============================================

-- View for pending modification requests
CREATE OR REPLACE VIEW v_pending_modifications AS
SELECT 
    bm.modification_id,
    bm.booking_id,
    b.booking_reference,
    b.booking_status,
    u.first_name,
    u.last_name,
    u.email,
    r.room_name,
    r.room_number,
    bm.modification_type,
    bm.request_details,
    bm.requested_at,
    bm.status
FROM booking_modifications bm
JOIN bookings b ON bm.booking_id = b.booking_id
JOIN users u ON b.user_id = u.user_id
JOIN rooms r ON b.room_id = r.room_id
WHERE bm.status = 'pending'
ORDER BY bm.requested_at DESC;

-- View for cancellation statistics
CREATE OR REPLACE VIEW v_cancellation_stats AS
SELECT 
    bc.cancellation_id,
    bc.booking_id,
    b.booking_reference,
    u.first_name,
    u.last_name,
    u.email,
    r.room_name,
    b.total_amount,
    bc.refund_amount,
    bc.refund_percentage,
    bc.cancelled_by,
    bc.cancellation_reason,
    bc.cancelled_at,
    bc.refund_status,
    DATEDIFF(b.check_in_date, bc.cancelled_at) AS days_before_checkin
FROM booking_cancellations bc
JOIN bookings b ON bc.booking_id = b.booking_id
JOIN users u ON b.user_id = u.user_id
JOIN rooms r ON b.room_id = r.room_id
ORDER BY bc.cancelled_at DESC;

-- ============================================
-- SUCCESS MESSAGE
-- ============================================
SELECT 'Booking actions tables created successfully!' AS message;

