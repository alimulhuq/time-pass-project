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
    total_cost DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
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