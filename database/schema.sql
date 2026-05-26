-- Database Schema for Al-Sayyida Hejama Center Management System

CREATE TABLE IF NOT EXISTS specialists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    specialty VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    dob DATE,
    phone VARCHAR(20),
    file_no VARCHAR(50),
    cpr VARCHAR(20),
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS medical_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    blood_pressure BOOLEAN DEFAULT FALSE,
    diabetes BOOLEAN DEFAULT FALSE,
    heart_disease BOOLEAN DEFAULT FALSE,
    leukemia BOOLEAN DEFAULT FALSE,
    other_diseases TEXT,
    previous_hejama BOOLEAN DEFAULT FALSE,
    blood_thinners BOOLEAN DEFAULT FALSE,
    pregnant BOOLEAN DEFAULT FALSE,
    allergies TEXT,
    additional_notes TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    appointment_date DATETIME,
    status ENUM('Scheduled', 'Waiting', 'In-Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    specialist_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (specialist_id) REFERENCES specialists(id) ON DELETE SET NULL
);

-- Initial Data
INSERT INTO specialists (name, specialty) VALUES ('Dr. Laila', 'General Hejama');
INSERT INTO specialists (name, specialty) VALUES ('Dr. Sara', 'Medical Cupping');

-- ─────────────────────────────────────────────
-- Staff users (roles: super_admin, reception, specialist)
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'reception', 'specialist') NOT NULL,
    specialist_id INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (specialist_id) REFERENCES specialists(id) ON DELETE SET NULL
);

-- ─────────────────────────────────────────────
-- Treatment sessions (per completed appointment)
-- cup_positions JSON: [{view,x_pct,y_pct,type,num}]
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS treatment_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    specialist_id INT,
    cup_positions JSON,
    cupping_types VARCHAR(255),
    cup_count INT DEFAULT 0,
    blood_density ENUM('Normal','Thick','Very Thick') DEFAULT 'Normal',
    blood_color ENUM('Bright Red','Dark Red','Blackish') DEFAULT 'Bright Red',
    specialist_notes TEXT,
    patient_notes TEXT,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (specialist_id) REFERENCES specialists(id) ON DELETE SET NULL
);
