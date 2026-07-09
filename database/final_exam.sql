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

-- Default staff account (password: 114514) — no security question
INSERT INTO users (username, password, email, security_question, security_answer, role) VALUES
('milktea', '$2y$10$iCJ6K51PdN//NRaPPRcWIOl593EEA1fojii8eaNHNRTpWcaDdcYxO', 'staff@milktea.com', '', '', 'staff');

-- Announcements table (staff actions → customer notifications)
DROP TABLE IF EXISTS announcements;
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message VARCHAR(200) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
