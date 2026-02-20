-- Asset Management Portal Database Schema
-- Created: 2026-02-16
-- Description: Complete database structure for Asset Management System

-- Drop database if exists and create fresh
DROP DATABASE IF EXISTS asset_management;
CREATE DATABASE asset_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE asset_management;

-- Table: users
-- Stores all user information with role-based access
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'hr', 'employee') NOT NULL DEFAULT 'employee',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: asset_categories
-- Stores asset category information
CREATE TABLE asset_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: assets
-- Stores all asset information
CREATE TABLE assets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    asset_name VARCHAR(200) NOT NULL,
    serial_number VARCHAR(100) UNIQUE NOT NULL,
    model_number VARCHAR(100),
    price DECIMAL(10, 2),
    currency ENUM('USD', 'INR') NOT NULL DEFAULT 'USD',
    purchase_date DATE,
    company_unit ENUM('Business', 'Creative', 'Shop') NOT NULL,
    status ENUM('available', 'assigned', 'inactive') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES asset_categories(id) ON DELETE RESTRICT,
    INDEX idx_category (category_id),
    INDEX idx_status (status),
    INDEX idx_serial (serial_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: asset_requests
-- Stores employee asset requests
CREATE TABLE asset_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    request_note TEXT,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_employee (employee_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: request_items
-- Stores individual items/categories in each request
CREATE TABLE request_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    category_id INT NOT NULL,
    FOREIGN KEY (request_id) REFERENCES asset_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES asset_categories(id) ON DELETE CASCADE,
    INDEX idx_request (request_id),
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: asset_assignments
-- Tracks asset assignments to employees
CREATE TABLE asset_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    asset_id INT NOT NULL,
    employee_id INT NOT NULL,
    assigned_date DATE NOT NULL,
    returned_date DATE NULL,
    status ENUM('active', 'returned') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_asset (asset_id),
    INDEX idx_employee (employee_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: audit_logs
-- Logs all important system actions for security and tracking
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: asset_service_requests
-- Stores employee requests for asset service/repair
CREATE TABLE asset_service_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    asset_id INT NOT NULL,
    problem_description TEXT NOT NULL,
    problem_since DATE NOT NULL,
    remarks TEXT,
    status ENUM('pending', 'approved', 'rejected', 'completed') NOT NULL DEFAULT 'pending',
    admin_response TEXT,
    repair_amount DECIMAL(10, 2),
    repair_currency ENUM('USD', 'INR') DEFAULT 'USD',
    bill_attachment VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    approved_by INT NULL,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_employee (employee_id),
    INDEX idx_asset (asset_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user
-- Password: Admin@123 (hashed with PASSWORD_HASH)
INSERT INTO users (name, email, password, role, status) VALUES
('System Admin', 'admin@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Insert sample asset categories
INSERT INTO asset_categories (category_name, status) VALUES
('Laptop', 'active'),
('Desktop', 'active'),
('Monitor', 'active'),
('Keyboard', 'active'),
('Mouse', 'active'),
('Headphones', 'active'),
('Mobile Phone', 'active'),
('Printer', 'active'),
('Scanner', 'active'),
('Projector', 'active');

-- Log initial setup
INSERT INTO audit_logs (user_id, action, description) VALUES
(1, 'SYSTEM_SETUP', 'Database initialized with default admin user and sample categories');
