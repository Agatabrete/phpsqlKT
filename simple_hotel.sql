-- ===============================
-- Simple Hotel Booking Database Setup
-- ===============================

-- 1. Create database
DROP DATABASE IF EXISTS simple_hotel;
CREATE DATABASE simple_hotel;
USE simple_hotel;

-- 2. Create user (for PHP app)
DROP USER IF EXISTS 'agata'@'localhost';
CREATE USER 'agata'@'localhost' IDENTIFIED BY 'agata';
GRANT ALL PRIVILEGES ON simple_hotel.* TO 'agata'@'localhost';
FLUSH PRIVILEGES;

-- 3. Tables
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) UNIQUE NOT NULL,
    room_type ENUM('single', 'double', 'family') NOT NULL,
    price_per_night DECIMAL(6,2) NOT NULL,
    description TEXT,
    is_available BOOLEAN DEFAULT TRUE
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    total_price DECIMAL(8,2) NOT NULL,
    status ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

-- 4. Insert sample data
INSERT INTO users (name, email, password, is_admin) VALUES
('Admin User', 'admin@hotel.com', 'admin123', TRUE),
('John Smith', 'john@email.com', 'password123', FALSE),
('Sarah Johnson', 'sarah@email.com', 'password123', FALSE);

INSERT INTO rooms (room_number, room_type, price_per_night, description) VALUES
('101', 'single', 50.00, 'Cozy single room with city view'),
('102', 'double', 75.00, 'Comfortable double room with balcony'),
('103', 'family', 100.00, 'Spacious family room for up to 4 guests'),
('201', 'single', 60.00, 'Premium single room on second floor'),
('202', 'double', 90.00, 'Deluxe double room with sea view'),
('301', 'family', 120.00, 'Luxury family suite with kitchenette');

INSERT INTO bookings (user_id, room_id, check_in, check_out, total_price) VALUES
(2, 1, '2024-12-20', '2024-12-23', 150.00),
(3, 3, '2024-12-25', '2024-12-28', 300.00);

-- ===============================
-- Done! The database, user, and data are ready.
-- ===============================
