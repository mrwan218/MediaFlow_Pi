-- MediaFlow Database Schema for Raspberry Pi
CREATE DATABASE IF NOT EXISTS mediaflow_db;
USE mediaflow_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    theme ENUM('dark', 'light') DEFAULT 'dark',
    max_allowed_rating ENUM('G', 'PG', 'PG-13', 'R', 'NC-17') DEFAULT 'R',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Media items table
CREATE TABLE IF NOT EXISTS media_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_path TEXT NOT NULL,
    relative_path VARCHAR(500),
    file_hash VARCHAR(32) NOT NULL,
    library_name VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    year INT,
    overview TEXT,
    poster_path VARCHAR(255),
    backdrop_path VARCHAR(255),
    rating ENUM('G', 'PG', 'PG-13', 'R', 'NC-17') DEFAULT 'R',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (library_name),
    INDEX (rating)
) ENGINE=InnoDB;

-- User library access table
CREATE TABLE IF NOT EXISTS user_library_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    library_name VARCHAR(100) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
