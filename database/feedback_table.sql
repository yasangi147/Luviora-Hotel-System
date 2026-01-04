-- ============================================
-- TABLE: contact_messages (Guest Feedback/Contact Form)
-- Description: Stores contact form submissions from guests
-- ============================================

CREATE TABLE IF NOT EXISTS contact_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(255),
    message TEXT NOT NULL,
    message_type ENUM('general_inquiry', 'booking_query', 'complaint', 'suggestion', 'other') DEFAULT 'general_inquiry',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('new', 'read', 'in_progress', 'resolved', 'closed') DEFAULT 'new',
    assigned_to INT,
    response_message TEXT,
    responded_at DATETIME,
    responded_by INT,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    is_guest BOOLEAN DEFAULT FALSE,
    user_id INT,
    booking_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (responded_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created (created_at),
    INDEX idx_user (user_id),
    INDEX idx_assigned (assigned_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better query performance
CREATE INDEX idx_message_type ON contact_messages(message_type);
CREATE INDEX idx_responded_at ON contact_messages(responded_at);

