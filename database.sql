CREATE DATABASE IF NOT EXISTS lost_found_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lost_found_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: items  (lost or found reports)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    report_type ENUM('lost','found') NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    category VARCHAR(60) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(150) NOT NULL,
    event_date DATE NOT NULL COMMENT 'Date the item was lost or found',
    photo VARCHAR(255) DEFAULT NULL,
    status ENUM('Lost','Found','Claimed','Returned') NOT NULL DEFAULT 'Lost',
    is_verified TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Admin has reviewed/approved this report',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: claims (a user claiming a found item belongs to them)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS claims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    claimer_id INT NOT NULL,
    proof_description TEXT NOT NULL COMMENT 'Details proving ownership',
    contact_info VARCHAR(150) DEFAULT NULL,
    status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    admin_notes VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (claimer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: item_status_log (audit trail of status changes)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS item_status_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    old_status VARCHAR(20) DEFAULT NULL,
    new_status VARCHAR(20) NOT NULL,
    changed_by INT DEFAULT NULL,
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Seed: default admin account
-- Email: admin@lostfound.com | Password: admin123
-- (change this password after first login)
-- ---------------------------------------------------------
INSERT INTO users (full_name, email, password_hash, role)
VALUES ('System Admin', 'admin@lostfound.com',
'$2y$10$exsvduhMpkVdZltZoZ21Q.hcN99lNNCZ1WVejR3ZBwrYzPKPM9dSy', 'admin');

-- ---------------------------------------------------------
-- Useful indexes for search/filter performance
-- ---------------------------------------------------------
CREATE INDEX idx_items_type ON items(report_type);
CREATE INDEX idx_items_status ON items(status);
CREATE INDEX idx_items_category ON items(category);
CREATE INDEX idx_items_verified ON items(is_verified);
CREATE FULLTEXT INDEX idx_items_search ON items(item_name, description, location);
