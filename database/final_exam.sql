-- ====================================
-- MilkTea Shop — Database Schema
-- ====================================

CREATE DATABASE IF NOT EXISTS final_exam;
USE final_exam;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS revenue;
DROP TABLE IF EXISTS feedback;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS inventory;
DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    security_question VARCHAR(200) NOT NULL,
    security_answer VARCHAR(100) NOT NULL,
    role ENUM('customer','staff') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default staff account (password: 114514) — no security question
INSERT INTO users (username, password, email, security_question, security_answer, role) VALUES
('milktea', '$2y$10$iCJ6K51PdN//NRaPPRcWIOl593EEA1fojii8eaNHNRTpWcaDdcYxO', 'staff@milktea.com', '', '', 'staff');

-- Drink inventory table
CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    price DECIMAL(8,2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    available TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO inventory (name, price, image, available) VALUES
('招牌珍珠奶茶', 12.00, 'images/drinks/classic-pearl.jpg', 1),
('茉莉绿奶茶', 11.00, 'images/drinks/jasmine-green.jpg', 1),
('芋泥波波奶茶', 15.00, 'images/drinks/taro-boba.jpg', 1),
('黑糖珍珠鲜奶', 16.00, 'images/drinks/brown-sugar.jpg', 1),
('芝士莓莓', 18.00, 'images/drinks/berry-cheese.jpg', 1),
('柠檬红茶', 10.00, 'images/drinks/lemon-tea.jpg', 1);

-- Customer orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    drink_id INT NOT NULL,
    drink_name VARCHAR(80) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending','done','cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_orders_user (user_id),
    INDEX idx_orders_status (status),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_orders_drink FOREIGN KEY (drink_id) REFERENCES inventory(id) ON DELETE RESTRICT
);

-- Customer feedback table
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    reply TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_feedback_user (user_id),
    CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Revenue table
CREATE TABLE revenue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    drink_name VARCHAR(80) NOT NULL,
    quantity INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_revenue_order (order_id),
    CONSTRAINT fk_revenue_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Announcements table (staff actions → customer notifications)
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message VARCHAR(200) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO announcements (message) VALUES
('今日推荐：招牌珍珠奶茶和芋泥波波奶茶。');

SET FOREIGN_KEY_CHECKS = 1;
