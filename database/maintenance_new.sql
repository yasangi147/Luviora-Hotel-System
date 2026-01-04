-- ============================================
-- Maintenance System - Simplified Schema
-- Luviora Hotel Management System
-- ============================================
-- This schema tracks maintenance issues for rooms
-- Simplified to focus on room maintenance tracking only
-- ============================================

-- Table: maintenance_issues
-- Tracks all maintenance issues reported for rooms
CREATE TABLE IF NOT EXISTS maintenance_issues (
    issue_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    reported_by INT NOT NULL,
    assigned_to INT NULL,
    issue_title VARCHAR(200) NOT NULL,
    issue_description TEXT,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    category ENUM('plumbing', 'electrical', 'hvac', 'furniture', 'cleaning', 'other') DEFAULT 'other',
    status ENUM('reported', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'reported',
    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_at DATETIME NULL,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    estimated_cost DECIMAL(10, 2) DEFAULT 0.00,
    actual_cost DECIMAL(10, 2) DEFAULT 0.00,
    notes TEXT,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES users(user_id),
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_room (room_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_assigned (assigned_to),
    INDEX idx_reported_at (reported_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Sample Data
-- ============================================

-- Insert sample maintenance issues
INSERT INTO maintenance_issues (room_id, reported_by, issue_title, issue_description, priority, category, status) VALUES
(1, 1, 'Air Conditioning Not Working', 'AC unit not cooling properly, making loud noise', 'urgent', 'hvac', 'reported'),
(2, 1, 'Leaking Faucet', 'Bathroom sink faucet has a slow leak', 'medium', 'plumbing', 'assigned'),
(3, 1, 'Broken Light Fixture', 'Ceiling light in bedroom not turning on', 'high', 'electrical', 'in_progress');

-- ============================================
-- Indexes for Performance
-- ============================================
-- Indexes are already created in the table definition
-- Additional indexes can be added as needed for reporting

