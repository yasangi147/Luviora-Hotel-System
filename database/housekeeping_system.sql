-- ============================================
-- Housekeeping System Tables
-- Luviora Hotel Management System
-- ============================================

-- Table: housekeeping_tasks
CREATE TABLE IF NOT EXISTS housekeeping_tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    task_type ENUM('cleaning', 'inspection', 'maintenance', 'turnover') DEFAULT 'cleaning',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    assigned_to INT NULL,
    description TEXT,
    notes TEXT,
    estimated_duration INT DEFAULT 30, -- in minutes
    actual_duration INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_at DATETIME NULL,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_room (room_id),
    INDEX idx_status (status),
    INDEX idx_assigned (assigned_to),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: housekeeping_schedule
CREATE TABLE IF NOT EXISTS housekeeping_schedule (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
    scheduled_time TIME,
    task_type VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE,
    INDEX idx_room (room_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: housekeeping_checklist
CREATE TABLE IF NOT EXISTS housekeeping_checklist (
    checklist_id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    item_name VARCHAR(200) NOT NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES housekeeping_tasks(task_id) ON DELETE CASCADE,
    INDEX idx_task (task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: housekeeping_staff_availability
CREATE TABLE IF NOT EXISTS housekeeping_staff_availability (
    availability_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('available', 'unavailable', 'on_leave') DEFAULT 'available',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_staff_date (staff_id, date),
    INDEX idx_staff (staff_id),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample housekeeping tasks
INSERT INTO housekeeping_tasks (room_id, task_type, priority, status, description) VALUES
(1, 'cleaning', 'high', 'pending', 'Room cleaning after checkout'),
(2, 'inspection', 'medium', 'pending', 'Room inspection and quality check'),
(3, 'turnover', 'urgent', 'in_progress', 'Quick turnover cleaning for next guest');

-- Insert sample housekeeping schedule
INSERT INTO housekeeping_schedule (room_id, day_of_week, scheduled_time, task_type) VALUES
(1, 'Monday', '10:00:00', 'cleaning'),
(1, 'Wednesday', '10:00:00', 'cleaning'),
(1, 'Friday', '10:00:00', 'cleaning'),
(2, 'Tuesday', '14:00:00', 'inspection'),
(3, 'Daily', '09:00:00', 'turnover');

