-- =============================================
--  APPOINT — Database Schema
--  Run this once to initialize the database
-- =============================================

CREATE DATABASE IF NOT EXISTS appoint_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE appoint_db;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(60) NOT NULL,
    last_name VARCHAR(60) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    phone VARCHAR(30),
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer','admin') DEFAULT 'customer',
    avatar_initials VARCHAR(4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Services Table
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    duration_minutes INT NOT NULL DEFAULT 60,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    icon VARCHAR(10) DEFAULT '🗓️',
    color VARCHAR(20) DEFAULT '#c9a84c',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bookings Table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_ref VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    status ENUM('pending','confirmed','cancelled','completed','rescheduled') DEFAULT 'pending',
    notes TEXT,
    total_amount DECIMAL(10,2),
    cancelled_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('booking_confirmed','booking_cancelled','booking_reminder','admin_message') DEFAULT 'admin_message',
    title VARCHAR(200),
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
--  SEED DATA
-- =============================================

-- Admin User (password: admin123)
INSERT IGNORE INTO users (first_name, last_name, email, phone, password_hash, role, avatar_initials) VALUES
('Admin', 'User', 'admin@demo.com', '+1 555-0100', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'AU');

-- Demo Customer (password: demo123)
INSERT IGNORE INTO users (first_name, last_name, email, phone, password_hash, role, avatar_initials) VALUES
('Jane', 'Doe', 'customer@demo.com', '+1 555-0199', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'JD');

-- Services
INSERT IGNORE INTO services (id, name, description, duration_minutes, price, icon) VALUES
(1, 'Hair Styling', 'Full cut, style, and finish with our expert stylists.', 60, 85.00, '✂️'),
(2, 'Deep Massage', 'Therapeutic deep tissue massage for muscle relief.', 90, 120.00, '💆'),
(3, 'Facial Treatment', 'Luxury facial with cleansing, exfoliation, and mask.', 75, 95.00, '✨'),
(4, 'Nail Care', 'Manicure and pedicure with polish of your choice.', 60, 65.00, '💅'),
(5, 'Spa Package', 'Full body treatment including sauna and relaxation.', 180, 220.00, '🧖'),
(6, 'Consultation', 'One-on-one consultation session with a specialist.', 30, 45.00, '📋');

-- Sample Bookings (uses user IDs 1 and 2)
INSERT IGNORE INTO bookings (booking_ref, user_id, service_id, booking_date, booking_time, status, total_amount) VALUES
('APT-2025-001', 2, 1, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '10:00:00', 'confirmed', 85.00),
('APT-2025-002', 2, 2, DATE_ADD(CURDATE(), INTERVAL 7 DAY), '14:00:00', 'confirmed', 120.00),
('APT-2025-003', 2, 3, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '11:00:00', 'completed', 95.00),
('APT-2025-004', 2, 4, DATE_SUB(CURDATE(), INTERVAL 14 DAY), '15:30:00', 'completed', 65.00),
('APT-2025-005', 2, 5, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '09:00:00', 'cancelled', 220.00),
('APT-2025-006', 2, 6, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '13:00:00', 'pending', 45.00);

-- Notifications for demo user
INSERT IGNORE INTO notifications (user_id, type, title, message) VALUES
(2, 'booking_confirmed', 'Booking Confirmed', 'Your Hair Styling appointment on Friday has been confirmed.'),
(2, 'booking_reminder', 'Reminder Tomorrow', 'You have a Consultation scheduled for tomorrow at 1:00 PM.');
