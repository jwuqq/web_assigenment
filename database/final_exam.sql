-- ============================================================
--  在超市后门偷喝奶茶的二人 — 数据库结构 & 初始数据
--  Database: final_exam
--  MySQL 5.7+ / MariaDB 10+
-- ============================================================
--  目录:
--    1. 用户表       users          登录/注册/角色
--    2. 饮品表       inventory      菜单管理
--    3. 订单表       orders         顾客下单
--    4. 评价表       feedback       顾客留言 & 店员回复
--    5. 营收表       revenue        已完成的订单流水
--    6. 公告表       announcements  店员操作公告 & 每日推荐
-- ============================================================

CREATE DATABASE IF NOT EXISTS final_exam;
USE final_exam;

SET FOREIGN_KEY_CHECKS = 0;

-- 按依赖顺序删除（子表先删）
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS revenue;
DROP TABLE IF EXISTS feedback;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS inventory;
DROP TABLE IF EXISTS users;

-- ============================================================
--  1. 用户表 (users)
--     顾客自行注册，店员为固定账号，无密保
-- ============================================================
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    email       VARCHAR(100) NOT NULL,
    security_question VARCHAR(200) NOT NULL DEFAULT '',
    security_answer   VARCHAR(100) NOT NULL DEFAULT '',
    role        ENUM('customer','staff') DEFAULT 'customer',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 店员固定账号 (密码: 114514)
INSERT INTO users (username, password, email, security_question, security_answer, role) VALUES
('milktea', '$2y$10$iCJ6K51PdN//NRaPPRcWIOl593EEA1fojii8eaNHNRTpWcaDdcYxO',
 'staff@milktea.com', '', '', 'staff');

-- ============================================================
--  2. 饮品表 (inventory)
--     available: 1=在售  0=售罄
--     image: 图片路径 (images/drinks/xxx.jpg)
-- ============================================================
CREATE TABLE inventory (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(80)   NOT NULL,
    price       DECIMAL(8,2)  NOT NULL,
    image       VARCHAR(255)  DEFAULT NULL,
    available   TINYINT(1)    NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO inventory (name, price, image, available) VALUES
('珍珠奶茶', 12.00, 'images/drinks/classic-pearl.jpg', 1),
('椰果奶茶', 11.00, 'images/drinks/jasmine-green.jpg',  1),
('红豆奶茶', 15.00, 'images/drinks/brown-sugar.jpg',    1),
('抹茶拿铁', 16.00, 'images/drinks/taro-boba.jpg',      1),
('杨枝甘露', 18.00, 'images/drinks/berry-cheese.jpg',   1),
('柠檬绿茶', 10.00, 'images/drinks/lemon-tea.jpg',      1);

-- ============================================================
--  3. 订单表 (orders)
--     status: pending=待制作  done=已完成  cancelled=已取消
-- ============================================================
CREATE TABLE orders (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    drink_id    INT NOT NULL,
    drink_name  VARCHAR(80)   NOT NULL,
    quantity    INT           NOT NULL DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    status      ENUM('pending','done','cancelled') NOT NULL DEFAULT 'pending',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_orders_user   (user_id),
    INDEX idx_orders_status (status),
    CONSTRAINT fk_orders_user  FOREIGN KEY (user_id)  REFERENCES users(id)     ON DELETE CASCADE,
    CONSTRAINT fk_orders_drink FOREIGN KEY (drink_id) REFERENCES inventory(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  4. 评价表 (feedback)
--     顾客留言 & 店员回复
-- ============================================================
CREATE TABLE feedback (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    username    VARCHAR(50) NOT NULL,
    message     TEXT        NOT NULL,
    reply       TEXT        DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_feedback_user (user_id),
    CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  5. 营收表 (revenue)
--     订单完成时自动写入，用于统计
-- ============================================================
CREATE TABLE revenue (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT NOT NULL,
    drink_name  VARCHAR(80)   NOT NULL,
    quantity    INT           NOT NULL,
    amount      DECIMAL(10,2) NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_revenue_order (order_id),
    CONSTRAINT fk_revenue_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  6. 公告表 (announcements)
--     店员操作自动写入 (上架/售罄/调价/新品)
--     每日推荐由系统自动生成
-- ============================================================
CREATE TABLE announcements (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    message     VARCHAR(200) NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
