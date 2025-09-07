CREATE DATABASE event_management;

USE event_management;

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(255) NOT NULL UNIQUE,
    user_password VARCHAR(255) NOT NULL,
    user_gmail VARCHAR(255) NOT NULL UNIQUE,
    user_phone_number VARCHAR(20) NOT NULL,
    user_address TEXT NOT NULL
);

-- Booking details table
CREATE TABLE booking_details (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(255) NOT NULL,
    event_address TEXT NOT NULL,
    event_date DATETIME NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    guest_number INT NOT NULL,
    food_type VARCHAR(100) NOT NULL,
    food_description TEXT,
    sound_system VARCHAR(50),
    decoration_description TEXT,
    payment_status ENUM('Paid', 'Not Paid') DEFAULT 'Not Paid',
    FOREIGN KEY (user_name) REFERENCES users(user_name) ON DELETE CASCADE
);

-- Payment details table
CREATE TABLE payment_details (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(255) NOT NULL,
    event_id INT NOT NULL,
    payment_method VARCHAR(100) NOT NULL,
    payment_amount DECIMAL(10, 2) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(50) NOT NULL UNIQUE,
    FOREIGN KEY (user_name) REFERENCES users(user_name) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES booking_details(event_id) ON DELETE CASCADE
);

-- Join query to retrieve user, booking, and payment details
SELECT 
    u.user_id, u.user_name, u.user_gmail, u.user_phone_number, u.user_address,
    b.event_id, b.event_address, b.event_date, b.event_type, b.guest_number, 
    b.food_type, b.food_description, b.sound_system, b.decoration_description, b.payment_status,
    p.payment_id, p.payment_method, p.payment_amount, p.account_number, p.transaction_id
FROM users u
LEFT JOIN booking_details b ON u.user_name = b.user_name
LEFT JOIN payment_details p ON b.event_id = p.event_id AND p.user_name = u.user_name
WHERE u.user_name = ?;