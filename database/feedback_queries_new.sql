-- ============================================
-- TABLE: feedback_queries (Unified Feedback & Queries)
-- Description: Stores both feedback and queries from guests
-- ============================================

CREATE TABLE IF NOT EXISTS feedback_queries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    type ENUM('feedback', 'query') NOT NULL DEFAULT 'query',
    subject VARCHAR(255),
    message TEXT NOT NULL,
    category ENUM('general_inquiry', 'booking_query', 'complaint', 'suggestion', 'other') DEFAULT 'general_inquiry',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('new', 'read', 'in_progress', 'resolved', 'closed') DEFAULT 'new',
    user_id INT,
    booking_id INT,
    
    -- Admin Reply Fields
    admin_reply TEXT,
    admin_reply_by INT,
    admin_reply_at DATETIME,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE SET NULL,
    FOREIGN KEY (admin_reply_by) REFERENCES users(user_id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_email (email),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_category (category),
    INDEX idx_created (created_at),
    INDEX idx_user (user_id),
    INDEX idx_booking (booking_id),
    INDEX idx_admin_reply_by (admin_reply_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

