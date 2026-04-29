-- ═══════════════════════════════════════════════════════
-- portfolio_db.sql — Complete Database Export
-- SEN3002 Full-Stack Portfolio Project
-- Author  : Mehmet Eren ÇAKMAK
-- Created : 2025
--
-- HOW TO IMPORT:
--   Option A — phpMyAdmin:
--     1. Open phpMyAdmin (http://localhost/phpmyadmin)
--     2. Create a new database named "portfolio_db"
--     3. Click "Import" tab → choose this file → Go
--
--   Option B — Command line:
--     mysql -u root -p < portfolio_db.sql
-- ═══════════════════════════════════════════════════════

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET NAMES utf8mb4;

-- ── Database ──────────────────────────────────────────
CREATE DATABASE IF NOT EXISTS portfolio_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE portfolio_db;

-- ── Table: admin_users ────────────────────────────────
DROP TABLE IF EXISTS admin_users;
CREATE TABLE admin_users (
    id            INT          AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin user is created by admin/setup.php (password hashed with bcrypt).
-- Default credentials after running setup.php:
--   Username : admin
--   Password : Admin123!

-- ── Table: projects ───────────────────────────────────
DROP TABLE IF EXISTS projects;
CREATE TABLE projects (
    id          INT          AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(200) NOT NULL,
    description TEXT         NOT NULL,
    tags        VARCHAR(300) DEFAULT '',
    image_url   VARCHAR(300) DEFAULT '',
    demo_url    VARCHAR(300) DEFAULT '',
    github_url  VARCHAR(300) DEFAULT '',
    created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample project data (also inserted automatically by setup.php)
INSERT INTO projects (title, description, tags, image_url, demo_url, github_url) VALUES
(
    'E-Commerce Demo',
    'A demo e-commerce website where users can browse products, add items to a cart, and simulate a shopping experience. Built with vanilla HTML, CSS, and JavaScript.',
    'HTML, CSS, JavaScript',
    'images/work3.jpeg',
    '#',
    'https://github.com/mehmeterencakmak'
),
(
    'Personal Portfolio',
    'My personal portfolio website showcasing my projects and skills. Designed and developed from scratch using HTML, CSS, JavaScript, PHP, and MySQL.',
    'HTML, CSS, JavaScript, PHP, MySQL',
    'images/work4.jpeg',
    'https://mehmeterencakmak.vercel.app/',
    'https://github.com/mehmeterencakmak'
),
(
    'Freight & Logistics',
    'A freight and logistics company website featuring service listings, route information, and a contact form. Built with React and JavaScript.',
    'React, JavaScript',
    'images/work5.jpeg',
    'https://erenakliyat.vercel.app/',
    'https://github.com/mehmeterencakmak'
),
(
    'My Blog',
    'A personal blog platform where users can read articles and browse categories. Features a clean reading experience with a fully responsive design.',
    'HTML, CSS, JavaScript',
    'images/work6.jpeg',
    '#',
    'https://github.com/mehmeterencakmak'
);

-- ── Table: messages ───────────────────────────────────
DROP TABLE IF EXISTS messages;
CREATE TABLE messages (
    id         INT          AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL,
    subject    VARCHAR(200) DEFAULT '',
    message    TEXT         NOT NULL,
    ip_address VARCHAR(45)  DEFAULT NULL,
    created_at DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
