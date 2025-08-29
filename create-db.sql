CREATE DATABASE event_management;
USE event_management;

-- HOST table
CREATE TABLE host_details (
    host_id INT AUTO_INCREMENT PRIMARY KEY,
    host_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20),
    host_email VARCHAR(100) UNIQUE,
    organization_name VARCHAR(100)
);

-- EVENT table
CREATE TABLE event_details (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    event_title VARCHAR(100) NOT NULL,
    host_id INT NOT NULL,
    organization_name VARCHAR(100),
    event_type VARCHAR(50),
    event_address VARCHAR(255),
    event_date DATETIME NOT NULL,
    event_status VARCHAR(50),
    FOREIGN KEY (host_id) REFERENCES host_details(host_id)
);

-- GUEST table
CREATE TABLE guest_details (
    guest_id INT AUTO_INCREMENT PRIMARY KEY,
    guest_name VARCHAR(100) NOT NULL,
    guest_address VARCHAR(255),
    phone_number VARCHAR(20),
    guest_email VARCHAR(100) UNIQUE,
    organization_name VARCHAR(100)
);

-- EVENT-GUEST relationship table
CREATE TABLE event_guest (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT,
    guest_id INT,
    FOREIGN KEY (event_id) REFERENCES event_details(event_id),
    FOREIGN KEY (guest_id) REFERENCES guest_details(guest_id)
);

-- PAYMENT table
CREATE TABLE payment_details (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    host_id INT NOT NULL,
    event_id INT NOT NULL,
    payable_amount DECIMAL(10,2),
    payment_method VARCHAR(50),
    payment_date DATETIME NOT NULL,
    payment_status VARCHAR(50),
    transaction_id VARCHAR(100) UNIQUE,
    FOREIGN KEY (host_id) REFERENCES host_details(host_id),
    FOREIGN KEY (event_id) REFERENCES event_details(event_id)
);