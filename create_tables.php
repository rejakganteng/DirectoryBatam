<?php
// create_tables.php - Create database tables
require_once 'config.php';

$sql = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191),
    email VARCHAR(191) UNIQUE,
    password_hash VARCHAR(191),
    role VARCHAR(32) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT DEFAULT 1
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT,
    name VARCHAR(191) NOT NULL,
    slug VARCHAR(191) NOT NULL,
    icon VARCHAR(191),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    owner_id INT,
    title VARCHAR(191) NOT NULL,
    slug VARCHAR(191) NOT NULL,
    description TEXT,
    address VARCHAR(255),
    latitude DECIMAL(10,7),
    longitude DECIMAL(10,7),
    phone VARCHAR(64),
    website VARCHAR(255),
    map_link VARCHAR(255),
    thumbnail VARCHAR(255),
    opening_hours VARCHAR(500),
    views INT DEFAULT 0,
    status ENUM('draft','pending','published','banned') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    user_id INT,
    name VARCHAR(191),
    email VARCHAR(191),
    comment_text TEXT NOT NULL,
    rating TINYINT,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    user_id INT,
    rating TINYINT CHECK (rating >= 1 AND rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sponsors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    logo_file VARCHAR(255) NOT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    date_start DATE,
    date_end DATE,
    listing_id INT,
    website_url VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS footer (
    id INT PRIMARY KEY DEFAULT 1,
    about TEXT,
    address VARCHAR(255),
    phone VARCHAR(64),
    email VARCHAR(191),
    facebook VARCHAR(255),
    instagram VARCHAR(255),
    whatsapp VARCHAR(64),
    copyright_text VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS header_settings (
    id INT PRIMARY KEY DEFAULT 1,
    site_title VARCHAR(191) DEFAULT 'Direktori Batam',
    logo_text VARCHAR(32) DEFAULT 'DB',
    logo_file VARCHAR(255),
    logo_bg_color VARCHAR(16) DEFAULT '#FFFFFF',
    logo_text_color VARCHAR(16) DEFAULT '#17A2B8',
    navbar_bg_color VARCHAR(16) DEFAULT '#17A2B8',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
";
";
";

try {
    $pdo->exec($sql);
    echo "Tables created successfully";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>