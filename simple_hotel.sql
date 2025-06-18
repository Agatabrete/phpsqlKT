-- Simple Hotel Booking Database
CREATE DATABASE simple_hotel;
USE simple_hotel;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Rooms table
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) UNIQUE NOT NULL,
    room_type ENUM('single', 'double', 'family') NOT NULL,
    price_per_night DECIMAL(6,2) NOT NULL,
    description TEXT,
    is_available BOOLEAN DEFAULT TRUE
);

-- Bookings table
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

-- Insert sample data
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

-- Useful queries for the system:

-- 1. View all available rooms
SELECT room_number, room_type, price_per_night, description 
FROM rooms 
WHERE is_available = TRUE;

-- 2. Check room availability for specific dates
SELECT r.room_number, r.room_type, r.price_per_night
FROM rooms r
WHERE r.is_available = TRUE
AND r.id NOT IN (
    SELECT b.room_id 
    FROM bookings b 
    WHERE b.status = 'confirmed'
    AND (b.check_in <= '2024-12-25' AND b.check_out >= '2024-12-20')
);

-- 3. View all bookings with user and room details
SELECT 
    b.id as booking_id,
    u.name as guest_name,
    r.room_number,
    r.room_type,
    b.check_in,
    b.check_out,
    b.total_price,
    b.status
FROM bookings b
JOIN users u ON b.user_id = u.id
JOIN rooms r ON b.room_id = r.id
ORDER BY b.booking_date DESC;

-- 4. Calculate total revenue
SELECT SUM(total_price) as total_revenue 
FROM bookings 
WHERE status = 'confirmed';

-- 5. Find most popular room type
SELECT r.room_type, COUNT(b.id) as booking_count
FROM rooms r
LEFT JOIN bookings b ON r.id = b.room_id AND b.status = 'confirmed'
GROUP BY r.room_type
ORDER BY booking_count DESC;

-- 6. View bookings for a specific user
SELECT 
    r.room_number,
    r.room_type,
    b.check_in,
    b.check_out,
    b.total_price,
    b.status
FROM bookings b
JOIN rooms r ON b.room_id = r.id
WHERE b.user_id = 2;

-- 7. Admin view: All users and their booking counts
SELECT 
    u.name,
    u.email,
    COUNT(b.id) as total_bookings,
    COALESCE(SUM(b.total_price), 0) as total_spent
FROM users u
LEFT JOIN bookings b ON u.id = b.user_id AND b.status = 'confirmed'
WHERE u.is_admin = FALSE
GROUP BY u.id, u.name, u.email;

-- 8. Monthly revenue report
SELECT 
    YEAR(booking_date) as year,
    MONTH(booking_date) as month,
    COUNT(id) as total_bookings,
    SUM(total_price) as monthly_revenue
FROM bookings 
WHERE status = 'confirmed'
GROUP BY YEAR(booking_date), MONTH(booking_date)
ORDER BY year DESC, month DESC;