-- Migration: Supply Inventory, Donations, Medical Records modules
-- Run this on the petadopthub database

USE petadopthub;

-- Supply Inventory
CREATE TABLE IF NOT EXISTS supplies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(150) NOT NULL,
    category ENUM('Dog Food','Cat Food', 'Medicine', 'Cleaning Supplies', 'Toys', 'Leashes & Collars', 'Bedding', 'Other') NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
    unit VARCHAR(50) NOT NULL DEFAULT 'pcs',
    min_stock_level DECIMAL(10,2) NOT NULL DEFAULT 0,
    supplier VARCHAR(150) NULL,
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_updated TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_archived (is_archived),
    INDEX idx_item_name (item_name)
);

-- Supply Stock Transactions
CREATE TABLE IF NOT EXISTS supply_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supply_id INT NOT NULL,
    transaction_type ENUM('Stock In', 'Stock Out') NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    notes TEXT NULL,
    admin_id INT NOT NULL,
    admin_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supply_id) REFERENCES supplies(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id),
    INDEX idx_supply_id (supply_id),
    INDEX idx_created_at (created_at)
);

-- Donations
CREATE TABLE IF NOT EXISTS donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donor_name VARCHAR(150) NOT NULL,
    contact_number VARCHAR(30) NULL,
    donation_type ENUM('Cash Donation', 'Item Donation', 'Item Withdrawal') NOT NULL,
    item_name VARCHAR(150) NULL,
    quantity DECIMAL(10,2) NULL DEFAULT 0,
    estimated_value DECIMAL(12,2) NULL DEFAULT 0,
    cash_amount DECIMAL(12,2) NULL DEFAULT 0,
    donation_date DATE NOT NULL,
    remarks TEXT NULL,
    added_to_inventory TINYINT(1) NOT NULL DEFAULT 0,
    supply_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supply_id) REFERENCES supplies(id) ON DELETE SET NULL,
    INDEX idx_donation_type (donation_type),
    INDEX idx_donation_date (donation_date)
);

-- Medical Records
CREATE TABLE IF NOT EXISTS medical_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pet_id INT NOT NULL,
    treatment_type ENUM('Vaccination', 'Checkup', 'Treatment', 'Surgery', 'Medication', 'Other') NOT NULL,
    diagnosis TEXT NULL,
    medication VARCHAR(255) NULL,
    vaccine_name VARCHAR(150) NULL,
    vaccination_date DATE NULL,
    next_due_date DATE NULL,
    veterinarian VARCHAR(150) NULL,
    weight VARCHAR(50) NULL,
    notes TEXT NULL,
    attachment VARCHAR(255) NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_pet_id (pet_id),
    INDEX idx_treatment_type (treatment_type),
    INDEX idx_next_due_date (next_due_date)
);

-- Site settings table
CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(128) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
