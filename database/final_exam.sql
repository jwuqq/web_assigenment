-- ====================================
-- MilkTea Shop — Database Schema
-- ====================================

CREATE DATABASE IF NOT EXISTS final_exam;
USE final_exam;

-- Users table
DROP TABLE IF EXISTS users;
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

-- Default staff account (password: staff123)
INSERT INTO users (username, password, email, security_question, security_answer, role) VALUES
('staff01', '$2y$10$placeholder', 'staff@milktea.com', '店名是什么?', '奶茶坊', 'staff');
