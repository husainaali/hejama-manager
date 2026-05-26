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
