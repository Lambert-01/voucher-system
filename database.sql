-- N.HONEST Voucher Management System Database Schema
-- Drop database if exists and create fresh
DROP DATABASE IF EXISTS nhonest_voucher_system;
CREATE DATABASE nhonest_voucher_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nhonest_voucher_system;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NULL,
    phone VARCHAR(50) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('boss', 'admin', 'cashier') NOT NULL,
    status ENUM('active', 'blocked') DEFAULT 'active',
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Companies table
CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(180) NOT NULL,
    contact_person VARCHAR(150) NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(150) NULL,
    address VARCHAR(255) NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Voucher batches table
CREATE TABLE voucher_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    batch_code VARCHAR(80) NOT NULL UNIQUE,
    batch_name VARCHAR(200) NOT NULL,
    batch_month VARCHAR(20) NOT NULL,
    total_vouchers INT DEFAULT 0,
    total_amount DECIMAL(14,2) DEFAULT 0,
    payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    payment_reference VARCHAR(150) NULL,
    notes TEXT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Vouchers table
CREATE TABLE vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    voucher_no VARCHAR(80) NOT NULL UNIQUE,
    client_name VARCHAR(180) NOT NULL,
    eva_id VARCHAR(100) NULL,
    original_amount DECIMAL(14,2) NOT NULL,
    balance DECIMAL(14,2) NOT NULL,
    status ENUM('active', 'partially_used', 'used', 'blocked', 'expired', 'cancelled') DEFAULT 'active',
    qr_code_path VARCHAR(255) NULL,
    card_image_path VARCHAR(255) NULL,
    qr_token VARCHAR(120) NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES voucher_batches(id),
    INDEX idx_voucher_no (voucher_no),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Voucher transactions table
CREATE TABLE voucher_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voucher_id INT NOT NULL,
    receipt_no VARCHAR(100) NULL,
    previous_balance DECIMAL(14,2) NOT NULL,
    amount_used DECIMAL(14,2) NOT NULL,
    new_balance DECIMAL(14,2) NOT NULL,
    cashier_id INT NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (voucher_id) REFERENCES vouchers(id),
    FOREIGN KEY (cashier_id) REFERENCES users(id),
    INDEX idx_created_at (created_at),
    INDEX idx_cashier_id (cashier_id)
) ENGINE=InnoDB;

-- Audit logs table
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(150) NOT NULL,
    description TEXT NULL,
    ip_address VARCHAR(80) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Insert default users
-- Password for all: password123
INSERT INTO users (full_name, username, email, phone, password_hash, role, status) VALUES
('Boss Account', 'boss', 'boss@honestsupermarket.com', '0788633739', '$2y$10$xKLMnA703p0KvoJLOxv0SepbKHxX32wCDPOYB63hbSpeRgs22lV4i', 'boss', 'active'),
('Admin Account', 'admin', 'admin@honestsupermarket.com', '0788633740', '$2y$10$xKLMnA703p0KvoJLOxv0SepbKHxX32wCDPOYB63hbSpeRgs22lV4i', 'admin', 'active'),
('Cashier Account', 'cashier', 'cashier@honestsupermarket.com', '0788633741', '$2y$10$xKLMnA703p0KvoJLOxv0SepbKHxX32wCDPOYB63hbSpeRgs22lV4i', 'cashier', 'active');

-- Insert sample company
INSERT INTO companies (company_name, contact_person, phone, email, address, status) VALUES
('International Organization for Migration (IOM)', 'John Doe', '0788111222', 'iom@contact.rw', 'Kigali, Rwanda', 'active');
