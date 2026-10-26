-- Database creation
CREATE DATABASE IF NOT EXISTS petadopthub;
USE petadopthub;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    location VARCHAR(255),
    postal_code VARCHAR(20),
    bio TEXT,
    profile_picture VARCHAR(255),
    preferred_animal_types VARCHAR(255),
    preferred_sizes VARCHAR(255),
    preferred_ages VARCHAR(255),
    verification_code VARCHAR(10) NULL,
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    account_status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_role (role)
);

-- Admin credentials are stored separately; user_id links to the legacy identity
-- retained for chat and audit records.
CREATE TABLE IF NOT EXISTS admin_accounts (
    user_id INT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    account_status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_accounts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Copy legacy admin credentials into the dedicated admin table when present.
INSERT INTO admin_accounts (user_id, email, password_hash, display_name, account_status)
SELECT id, email, password, fullname, COALESCE(account_status, 'Approved')
FROM users
WHERE role = 'admin'
ON DUPLICATE KEY UPDATE
    email = VALUES(email),
    password_hash = VALUES(password_hash),
    display_name = VALUES(display_name),
    account_status = VALUES(account_status);

-- Pets table
CREATE TABLE IF NOT EXISTS pets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('Dog', 'Cat') NOT NULL,
    breed VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    gender ENUM('Male', 'Female') NOT NULL,
    size ENUM('Small', 'Medium', 'Large') NOT NULL,
    weight VARCHAR(50),
    description TEXT,
    personality TEXT,
    health_status VARCHAR(255),
    shelter_name VARCHAR(100) NOT NULL,
    shelter_email VARCHAR(100) NOT NULL,
    shelter_phone VARCHAR(20) NOT NULL,
    shelter_address VARCHAR(255) NOT NULL,
    status ENUM('Available', 'Pending', 'Adopted') DEFAULT 'Available',
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_status (status)
);

-- Adoption Applications table
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pet_id INT NOT NULL,
    pet_experience TEXT,
    home_type ENUM('House with yard', 'House without yard', 'Apartment', 'Condominium', 'Others') NOT NULL,
    other_pets TEXT,
    work_schedule TEXT,
    reason_for_adoption TEXT,
    willing_vet ENUM('Yes', 'No') NOT NULL,
    fb_profile VARCHAR(255),
    valid_id VARCHAR(255),
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    interview_type VARCHAR(50) NULL,
    interview_date DATE NULL,
    interview_time TIME NULL,
    meeting_link VARCHAR(500) NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (pet_id) REFERENCES pets(id),
    INDEX idx_status (status),
    INDEX idx_applied_at (applied_at)
);

-- Applicant-requested interview changes; confirmed interview remains in applications
-- until an administrator approves the proposed replacement slot.
CREATE TABLE IF NOT EXISTS interview_reschedule_requests (
    application_id INT PRIMARY KEY,
    requested_date DATE NOT NULL,
    requested_time TIME NOT NULL,
    reason VARCHAR(1000) NOT NULL,
    request_status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_reschedule_request_application
        FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    INDEX idx_reschedule_status (request_status),
    INDEX idx_reschedule_slot (requested_date, requested_time)
);

-- Favorite Pets table
CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pet_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (pet_id) REFERENCES pets(id),
    UNIQUE KEY (user_id, pet_id)
);

-- Archived Pets table
CREATE TABLE IF NOT EXISTS archived_pets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_pet_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('Dog', 'Cat') NOT NULL,
    breed VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    gender ENUM('Male', 'Female'),
    size ENUM('Small', 'Medium', 'Large'),
    weight VARCHAR(50),
    description TEXT,
    personality TEXT,
    health_status VARCHAR(255),
    shelter_name VARCHAR(100) NOT NULL,
    shelter_email VARCHAR(100) NOT NULL,
    shelter_phone VARCHAR(20) NOT NULL,
    shelter_address VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL,
    image VARCHAR(255),
    archive_reason ENUM('Adopted', 'Deleted') DEFAULT 'Deleted',
    archived_by INT,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    original_created_at DATETIME NULL DEFAULT NULL,
    notes TEXT
);

-- Messages table for user-admin chat
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id),
    INDEX idx_conversation (sender_id, receiver_id),
    INDEX idx_created_at (created_at),
    INDEX idx_is_read (is_read)
);

-- Supply Inventory module
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

-- Supply stock transactions
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

-- Donations module
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

-- Medical records module
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

-- Adoption history module
CREATE TABLE IF NOT EXISTS adoption_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL UNIQUE,
    pet_id INT NOT NULL,
    adopter_id INT NOT NULL,
    admin_id INT NULL,
    pet_name VARCHAR(150) NOT NULL,
    pet_species VARCHAR(100) NOT NULL,
    pet_breed VARCHAR(150) NOT NULL,
    pet_photo VARCHAR(255) NULL,
    adopter_name VARCHAR(150) NOT NULL,
    adopter_email VARCHAR(150) NOT NULL,
    adopter_contact VARCHAR(30) NULL,
    adopter_address VARCHAR(255) NULL,
    application_date DATE NULL,
    interview_date DATE NULL,
    home_visit_date DATE NULL,
    approval_date DATE NULL,
    adoption_date DATE NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Pending Review',
    outcome VARCHAR(100) NOT NULL DEFAULT 'Adoption Closed',
    remarks TEXT NULL,
    agreement_file VARCHAR(255) NULL,
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    archived_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
    FOREIGN KEY (adopter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_outcome (outcome),
    INDEX idx_adoption_date (adoption_date),
    INDEX idx_is_archived (is_archived)
);

-- Site settings table
CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(128) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


