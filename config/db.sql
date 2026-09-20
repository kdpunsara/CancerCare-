-- ==========================================
-- CANCER PATIENT CARE SYSTEM - FULL DATABASE
-- Prepared for IS-16 Second-Year Group Project
-- Apeksha Cancer Hospital Management
-- ==========================================

-- 1. Create and Select Database
CREATE DATABASE IF NOT EXISTS cancer_care_system;
USE cancer_care_system;

-- ==========================================
-- 2. CORE AUTHENTICATION & USER ROLES
-- ==========================================

-- Base User Table (Handles login for ALL actors)
CREATE TABLE User (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('patient', 'doctor', 'staff', 'pharmacist', 'benefactor', 'admin') NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    
);

-- Patient Profile (Extends User)
CREATE TABLE Patient (
    user_id INT PRIMARY KEY,
    nic VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    dob DATE NOT NULL,
    gender ENUM('male', 'female', 'other'),
    address TEXT,
    city VARCHAR(100),
    blood_group VARCHAR(5),
    allergies TEXT,
    CONSTRAINT fk_patient_user FOREIGN KEY (user_id) REFERENCES User(user_id) ON DELETE CASCADE
);

-- Doctor Profile (Extends User)
CREATE TABLE Doctor (
    user_id INT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    specialization VARCHAR(100),
    qualification VARCHAR(200),
    license_no VARCHAR(50) UNIQUE NOT NULL,
    CONSTRAINT fk_doctor_user FOREIGN KEY (user_id) REFERENCES User(user_id) ON DELETE CASCADE
);

-- Medical Staff Profile (Extends User)
CREATE TABLE Medical_Staff (
    user_id INT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    designation VARCHAR(100),
    department VARCHAR(100),
    employee_id VARCHAR(50) UNIQUE,
    CONSTRAINT fk_staff_user FOREIGN KEY (user_id) REFERENCES User(user_id) ON DELETE CASCADE
);

-- Pharmacist Profile (Extends User)
CREATE TABLE Pharmacist (
    user_id INT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    pharmacy_name VARCHAR(150) NOT NULL,
    address TEXT,
    license_no VARCHAR(50) UNIQUE NOT NULL,
    CONSTRAINT fk_pharmacist_user FOREIGN KEY (user_id) REFERENCES User(user_id) ON DELETE CASCADE
);

-- Benefactor Profile (Extends User) - Covers Scope 3.2.5
CREATE TABLE Benefactor (
    user_id INT PRIMARY KEY,
    benefactor_type ENUM('local', 'international') NOT NULL,
    organization_name VARCHAR(150),
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    address TEXT,
    country VARCHAR(100),
    total_donations DECIMAL(12, 2) DEFAULT 0.00,
    CONSTRAINT fk_benefactor_user FOREIGN KEY (user_id) REFERENCES User(user_id) ON DELETE CASCADE
);

-- Admin Profile (Extends User)
CREATE TABLE Admin (
    user_id INT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    admin_level ENUM('super', 'regular') DEFAULT 'regular',
    CONSTRAINT fk_admin_user FOREIGN KEY (user_id) REFERENCES User(user_id) ON DELETE CASCADE
);

-- ==========================================
-- 3. CORE SYSTEM MODULES
-- ==========================================

-- Appointments (Covers Scope 3.2.2)
CREATE TABLE Appointment (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_user_id INT NOT NULL,
    doctor_user_id INT NOT NULL,
    staff_user_id INT, -- Nullable: Assigned by medical staff for coordination
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason TEXT,
    CONSTRAINT fk_appt_patient FOREIGN KEY (patient_user_id) REFERENCES User(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_appt_doctor FOREIGN KEY (doctor_user_id) REFERENCES User(user_id) ON DELETE RESTRICT,
    CONSTRAINT fk_appt_staff FOREIGN KEY (staff_user_id) REFERENCES User(user_id) ON DELETE SET NULL
);

-- Medical Records: Diagnosis & Treatment History (Covers Scope 3.2.3)
CREATE TABLE MedicalRecord (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_user_id INT NOT NULL,
    doctor_user_id INT NOT NULL,
    record_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    diagnosis TEXT NOT NULL,
    cancer_stage VARCHAR(20),
    treatment_plan TEXT NOT NULL,
    clinical_notes TEXT,
    CONSTRAINT fk_record_patient FOREIGN KEY (patient_user_id) REFERENCES User(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_record_doctor FOREIGN KEY (doctor_user_id) REFERENCES User(user_id) ON DELETE RESTRICT
);

-- Medical Reports: Uploaded Files (Covers Use Case 07)
CREATE TABLE MedicalReport (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_user_id INT NOT NULL,
    uploaded_by_user_id INT NOT NULL, -- Medical Staff
    report_type ENUM('lab_result', 'scan', 'biopsy', 'discharge_summary', 'other') NOT NULL,
    report_title VARCHAR(200) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    CONSTRAINT fk_report_patient FOREIGN KEY (patient_user_id) REFERENCES User(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_report_staff FOREIGN KEY (uploaded_by_user_id) REFERENCES User(user_id) ON DELETE RESTRICT
);

-- Medicine Catalog (Covers Scope 3.2.4)
CREATE TABLE Medicine (
    medicine_id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_name VARCHAR(200) NOT NULL,
    generic_name VARCHAR(200),
    category VARCHAR(100),
    manufacturer VARCHAR(150)
);

-- Prescriptions (Covers Use Case 08)
CREATE TABLE Prescription (
    prescription_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_user_id INT NOT NULL,
    doctor_user_id INT NOT NULL,
    prescription_date DATE NOT NULL,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    diagnosis_notes TEXT,
    CONSTRAINT fk_rx_patient FOREIGN KEY (patient_user_id) REFERENCES User(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_rx_doctor FOREIGN KEY (doctor_user_id) REFERENCES User(user_id) ON DELETE RESTRICT
);

-- Prescription Items (Junction Table for Many-to-Many)
CREATE TABLE PrescriptionItem (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    medicine_id INT NOT NULL,
    dosage VARCHAR(100) NOT NULL,
    frequency VARCHAR(100) NOT NULL,
    duration VARCHAR(100),
    instructions TEXT,
    CONSTRAINT fk_item_rx FOREIGN KEY (prescription_id) REFERENCES Prescription(prescription_id) ON DELETE CASCADE,
    CONSTRAINT fk_item_med FOREIGN KEY (medicine_id) REFERENCES Medicine(medicine_id) ON DELETE RESTRICT
);

-- ==========================================
-- 4. PATIENT SUPPORT SYSTEM
-- ==========================================

-- Pre-made Meal Plan Templates (Master Data)
CREATE TABLE MealPlanTemplate (
    template_id INT AUTO_INCREMENT PRIMARY KEY,
    cancer_type VARCHAR(100) NOT NULL COMMENT 'e.g. Colorectal, Breast, Lung, Prostate',
    template_name VARCHAR(200) NOT NULL,
    nutritional_guidelines TEXT COMMENT 'General rules for this cancer type',
    duration_days INT DEFAULT 7,
    is_active BOOLEAN DEFAULT TRUE
);

-- Daily Meals inside a Template
CREATE TABLE MealPlanTemplateItem (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    meal_type ENUM('breakfast', 'lunch', 'dinner', 'snack') NOT NULL,
    menu_description TEXT NOT NULL,
    CONSTRAINT fk_template_item FOREIGN KEY (template_id) REFERENCES MealPlanTemplate(template_id) ON DELETE CASCADE
);

-- Assigned Meal Plans (Transactional Data)
CREATE TABLE MealPlan (
    meal_plan_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_user_id INT NOT NULL,
    doctor_user_id INT NOT NULL,
    template_id INT NULL COMMENT 'Link to pre-made template if used',
    plan_date DATE NOT NULL,
    meal_type ENUM('breakfast', 'lunch', 'dinner', 'snack') NOT NULL,
    menu_description TEXT NOT NULL,
    dietary_restrictions TEXT,
    nutritional_notes TEXT,
    CONSTRAINT fk_meal_patient FOREIGN KEY (patient_user_id) REFERENCES User(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_meal_doctor FOREIGN KEY (doctor_user_id) REFERENCES User(user_id) ON DELETE RESTRICT,
    CONSTRAINT fk_meal_template FOREIGN KEY (template_id) REFERENCES MealPlanTemplate(template_id) ON DELETE SET NULL
);

-- Motivation & Wellness Resources (Scope 3.2.6)
CREATE TABLE MotivationResource (
    resource_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content_type ENUM('article', 'video', 'audio', 'image') NOT NULL,
    content_url VARCHAR(500),
    description TEXT,
    category VARCHAR(100) COMMENT 'e.g. Mental Health, Nutrition, Recovery Stories',
    published_date DATE,
    is_active BOOLEAN DEFAULT TRUE
);

-- Transport Schedules (Scope 3.2.7)
CREATE TABLE TransportSchedule (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    route_name VARCHAR(100) NOT NULL,
    departure_location VARCHAR(200) NOT NULL,
    arrival_location VARCHAR(200) NOT NULL,
    departure_time TIME NOT NULL,
    arrival_time TIME NOT NULL,
    vehicle_type VARCHAR(50),
    capacity INT,
    operating_days VARCHAR(100) COMMENT 'e.g. Mon, Wed, Fri',
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Donations (Benefactor Contributions)
CREATE TABLE Donation (
    donation_id INT AUTO_INCREMENT PRIMARY KEY,
    benefactor_user_id INT NOT NULL,
    patient_user_id INT NULL COMMENT 'Nullable if general hospital fund',
    donation_type ENUM('financial', 'medical_aid', 'equipment') NOT NULL,
    amount DECIMAL(12, 2),
    currency VARCHAR(10) DEFAULT 'LKR',
    purpose VARCHAR(200),
    status ENUM('pending', 'processing', 'completed', 'rejected') DEFAULT 'pending',
    donation_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_donation_benefactor FOREIGN KEY (benefactor_user_id) REFERENCES User(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_donation_patient FOREIGN KEY (patient_user_id) REFERENCES User(user_id) ON DELETE SET NULL
);

-- System Logs (Admin Monitoring)
CREATE TABLE SystemLog (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action_type VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    action_description TEXT,
    ip_address VARCHAR(50),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES User(user_id) ON DELETE SET NULL
);

-- ==========================================
-- 5. SAMPLE DATA (For Testing & Demo)
-- ==========================================

-- 1. Sample Patient
INSERT INTO User (user_id, username, email, password_hash, phone, role, status) 
VALUES (1001, 'kamal_patient', 'kamal@example.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe.YYr3hFZhvvAUaQC1W0a1qOqz8y1m', '0771234567', 'patient', 'active');
INSERT INTO Patient (user_id, nic, first_name, last_name, dob, gender, address, city, blood_group, allergies) 
VALUES (1001, '198510201234', 'Kamal', 'Perera', '1985-04-12', 'male', '123 Main St', 'Colombo', 'O+', 'Penicillin');

-- 2. Sample Doctor
INSERT INTO User (user_id, username, email, password_hash, phone, role, status) 
VALUES (1002, 'dr_noel', 'noel.fernando@hospital.lk', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe.YYr3hFZhvvAUaQC1W0a1qOqz8y1m', '0718108247', 'doctor', 'active');
INSERT INTO Doctor (user_id, first_name, last_name, specialization, qualification, license_no, consultation_fee, availability_status) 
VALUES (1002, 'Noel', 'Fernando', 'Medical Oncology', 'MBBS, MD (Oncology)', 'SL-MED-9921', 2500.00, 'available');

-- 3. Sample Medical Staff
INSERT INTO User (user_id, username, email, password_hash, phone, role, status) 
VALUES (1003, 'nurse_amali', 'amali@hospital.lk', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe.YYr3hFZhvvAUaQC1W0a1qOqz8y1m', '0779876543', 'staff', 'active');
INSERT INTO Medical_Staff (user_id, first_name, last_name, designation, department, employee_id) 
VALUES (1003, 'Amali', 'Silva', 'Head Nurse', 'Oncology Ward', 'EMP-001');

-- 4. Sample Benefactor
INSERT INTO User (user_id, username, email, password_hash, phone, role, status) 
VALUES (1004, 'john_benefactor', 'john@example.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe.YYr3hFZhvvAUaQC1W0a1qOqz8y1m', '+447911123456', 'benefactor', 'active');
INSERT INTO Benefactor (user_id, benefactor_type, organization_name, first_name, last_name, address, country, total_donations) 
VALUES (1004, 'international', 'Global Health Aid', 'John', 'Anderson', '456 London Rd', 'United Kingdom', 5000.00);

-- 5. Sample Medicines
INSERT INTO Medicine (medicine_id, medicine_name, generic_name, category, stock_quantity, unit_price, manufacturer) VALUES
(1, 'Ondansetron', 'Ondansetron Hydrochloride', 'Anti-emetic', 150, 45.00, 'Aspen Pharma'),
(2, 'Tamoxifen', 'Tamoxifen Citrate', 'Hormone Therapy', 200, 120.00, 'Cipla Ltd'),
(3, 'Paracetamol', 'Acetaminophen', 'Pain Relief', 500, 15.00, 'GlaxoSmithKline');

-- 6. Sample Appointment
INSERT INTO Appointment (patient_user_id, doctor_user_id, staff_user_id, appointment_date, appointment_time, reason, status) 
VALUES (1001, 1002, 1003, '2026-06-15', '09:00:00', 'Chemotherapy Cycle 4 Review', 'approved');

-- 7. Sample Medical Record
INSERT INTO MedicalRecord (patient_user_id, doctor_user_id, diagnosis, cancer_stage, treatment_plan, clinical_notes) 
VALUES (1001, 1002, 'Colorectal Adenocarcinoma', 'Stage III', 'FOLFOX Chemotherapy for 8 cycles', 'Patient responding well. Mild neuropathy noted.');

-- 8. Sample Prescription
INSERT INTO Prescription (prescription_id, patient_user_id, doctor_user_id, prescription_date, status, diagnosis_notes) 
VALUES (1, 1001, 1002, '2026-06-15', 'active', 'Post-chemotherapy nausea management');

-- 9. Sample Prescription Items
INSERT INTO PrescriptionItem (prescription_id, medicine_id, dosage, frequency, duration, instructions) VALUES
(1, 1, '8mg', 'Twice daily', '7 days', 'Take 30 minutes before meals'),
(1, 2, '20mg', 'Once daily', '30 days', 'Take with a full glass of water');

-- 10. Sample Meal Plan Template (Colorectal Cancer)
INSERT INTO MealPlanTemplate (cancer_type, template_name, nutritional_guidelines, duration_days) 
VALUES ('Colorectal', 'High-Fiber Low-Fat Plan', 'Emphasize whole grains, lean proteins, and cooked vegetables. Avoid spicy and fried foods.', 7);

-- 11. Sample Template Items (Monday Meals)
INSERT INTO MealPlanTemplateItem (template_id, day_of_week, meal_type, menu_description) VALUES
(1, 'Monday', 'breakfast', 'Kola kanda with a boiled egg'),
(1, 'Monday', 'lunch', 'Red rice, dhal curry, steamed greens'),
(1, 'Monday', 'dinner', 'Vegetable soup with grilled fish'),
(1, 'Monday', 'snack', 'Papaya slices');

-- 12. Sample Assigned Meal Plan (Copied from Template to Patient)
INSERT INTO MealPlan (patient_user_id, doctor_user_id, template_id, plan_date, meal_type, menu_description, dietary_restrictions) 
VALUES (1001, 1002, 1, '2026-06-15', 'lunch', 'Red rice, dhal curry, steamed greens', 'Low sodium due to chemotherapy');

-- 13. Sample Transport Schedule
INSERT INTO TransportSchedule (route_name, departure_location, arrival_location, departure_time, arrival_time, vehicle_type, capacity, operating_days, status) 
VALUES ('Colombo - Maharagama', 'Colombo Fort Bus Stand', 'Apeksha Hospital, Maharagama', '07:00:00', '08:30:00', 'Air-conditioned Van', 15, 'Monday, Wednesday, Friday', 'active');

-- 14. Sample Motivation Resource
INSERT INTO MotivationResource (title, content_type, content_url, description, category, published_date, is_active) 
VALUES ('Managing Chemo Side Effects', 'article', '/resources/chemo-tips.pdf', 'Practical tips for managing nausea and fatigue during treatment.', 'Wellness', '2026-01-15', TRUE);

-- 15. Sample Donation
INSERT INTO Donation (benefactor_user_id, patient_user_id, donation_type, amount, currency, purpose, status, donation_date) 
VALUES (1004, 1001, 'financial', 50000.00, 'LKR', 'Chemotherapy support for Kamal Perera', 'completed', '2026-06-01');