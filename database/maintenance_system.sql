-- ============================================
-- Maintenance System Tables
-- Luviora Hotel Management System
-- ============================================

-- Table: maintenance_issues
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
    INDEX idx_assigned (assigned_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: maintenance_schedule (for preventive maintenance)
CREATE TABLE IF NOT EXISTS maintenance_schedule (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NULL,
    task_name VARCHAR(200) NOT NULL,
    task_description TEXT,
    frequency ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') NOT NULL,
    last_performed DATE NULL,
    next_due_date DATE NOT NULL,
    assigned_to INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_next_due (next_due_date),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: maintenance_inventory
CREATE TABLE IF NOT EXISTS maintenance_inventory (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    item_category VARCHAR(50),
    quantity INT DEFAULT 0,
    unit VARCHAR(20),
    min_quantity INT DEFAULT 0,
    unit_cost DECIMAL(10, 2) DEFAULT 0.00,
    supplier VARCHAR(100),
    last_restocked DATE NULL,
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (item_category),
    INDEX idx_quantity (quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample maintenance issues
INSERT INTO maintenance_issues (room_id, reported_by, issue_title, issue_description, priority, category, status) VALUES
(1, 1, 'Air Conditioning Not Working', 'AC unit not cooling properly, making loud noise', 'urgent', 'hvac', 'reported'),
(2, 1, 'Leaking Faucet', 'Bathroom sink faucet has a slow leak', 'medium', 'plumbing', 'assigned'),
(3, 1, 'Broken Light Fixture', 'Ceiling light in bedroom not turning on', 'high', 'electrical', 'in_progress');

-- Insert sample preventive maintenance schedule
INSERT INTO maintenance_schedule (room_id, task_name, task_description, frequency, next_due_date) VALUES
(NULL, 'HVAC Filter Replacement', 'Replace all HVAC filters throughout the hotel', 'monthly', DATE_ADD(CURDATE(), INTERVAL 7 DAY)),
(NULL, 'Fire Extinguisher Inspection', 'Inspect all fire extinguishers', 'monthly', DATE_ADD(CURDATE(), INTERVAL 15 DAY)),
(1, 'Deep Cleaning', 'Complete deep cleaning of room', 'quarterly', DATE_ADD(CURDATE(), INTERVAL 30 DAY));

-- Insert sample inventory items
INSERT INTO maintenance_inventory (item_name, item_category, quantity, unit, min_quantity, unit_cost, supplier) VALUES
('HVAC Filters', 'HVAC', 50, 'pieces', 20, 15.99, 'ABC Supplies'),
('Light Bulbs (LED)', 'Electrical', 100, 'pieces', 30, 3.50, 'Lighting Co'),
('Plumbing Tape', 'Plumbing', 25, 'rolls', 10, 2.99, 'Plumbing Depot'),
('Paint (White)', 'General', 10, 'gallons', 5, 35.00, 'Paint Store'),
('Cleaning Solution', 'Cleaning', 30, 'bottles', 15, 8.99, 'Cleaning Supplies Inc');

