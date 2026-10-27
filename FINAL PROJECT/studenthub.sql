-- ============================================================
-- Practical 8: MySQL Schema Design for StudentHub
-- Database: studenthub
-- Tables: users, students, events, registrations, audit_logs
-- ============================================================

-- Create the database
CREATE DATABASE IF NOT EXISTS studenthub;
USE studenthub;

-- ============================================================
-- 1. Users Table (for login/registration - Practical 9, 10)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    remember_token VARCHAR(255) DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- 2. Students Table (for CRUD - Practical 11)
-- ============================================================
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    course VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    address TEXT,
    status ENUM('active', 'deleted') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- 3. Events Table (for Event CRUD - Practical 12)
-- ============================================================
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    location VARCHAR(200) NOT NULL,
    category VARCHAR(100) NOT NULL,
    poster VARCHAR(255) DEFAULT NULL,
    event_status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- 4. Registrations Table (event registrations)
-- ============================================================
CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    event_id INT NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (student_id, event_id)
);

-- ============================================================
-- 5. Audit Logs Table (for Practical 14)
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(200) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);


-- ============================================================
-- SEED DATA
-- ============================================================

-- Seed Users (password is 'password123' hashed with password_hash)
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO users (username, email, password, role) VALUES
('Admin User', 'admin@studenthub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Jaimin Khambhaliya', 'jaimin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Raj Patel', 'raj@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Priya Sharma', 'priya@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Amit Singh', 'amit@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

-- Seed Students (minimum 20 records for Practical 11)
INSERT INTO students (fullname, email, phone, course, year, gender, address) VALUES
('Jaimin Khambhaliya', 'jaimin@gmail.com', '8523456789', 'CE', 2, 'Male', 'Ahmedabad, Gujarat'),
('Raj Patel', 'raj@gmail.com', '9876543210', 'CE', 2, 'Male', 'Surat, Gujarat'),
('Priya Sharma', 'priya@gmail.com', '9988776655', 'IT', 3, 'Female', 'Vadodara, Gujarat'),
('Amit Singh', 'amit@gmail.com', '8877665544', 'EC', 2, 'Male', 'Rajkot, Gujarat'),
('Neha Desai', 'neha@gmail.com', '7766554433', 'CE', 1, 'Female', 'Anand, Gujarat'),
('Karan Joshi', 'karan@gmail.com', '6655443322', 'ME', 3, 'Male', 'Nadiad, Gujarat'),
('Sneha Patel', 'sneha@gmail.com', '5544332211', 'IT', 2, 'Female', 'Bharuch, Gujarat'),
('Vivek Kumar', 'vivek@gmail.com', '4433221100', 'CE', 4, 'Male', 'Gandhinagar, Gujarat'),
('Anjali Mehta', 'anjali@gmail.com', '3322110099', 'EC', 1, 'Female', 'Jamnagar, Gujarat'),
('Rohit Verma', 'rohit@gmail.com', '2211009988', 'ME', 2, 'Male', 'Bhavnagar, Gujarat'),
('Pooja Gupta', 'pooja@gmail.com', '1100998877', 'IT', 3, 'Female', 'Junagadh, Gujarat'),
('Siddharth Rao', 'siddharth@gmail.com', '9900887766', 'CE', 1, 'Male', 'Mehsana, Gujarat'),
('Kavya Shah', 'kavya@gmail.com', '8899776655', 'EC', 2, 'Female', 'Navsari, Gujarat'),
('Arjun Nair', 'arjun@gmail.com', '7788665544', 'ME', 4, 'Male', 'Valsad, Gujarat'),
('Riya Trivedi', 'riya@gmail.com', '6677554433', 'CE', 3, 'Female', 'Porbandar, Gujarat'),
('Deepak Chauhan', 'deepak@gmail.com', '5566443322', 'IT', 1, 'Male', 'Morbi, Gujarat'),
('Meera Iyer', 'meera@gmail.com', '4455332211', 'EC', 3, 'Female', 'Surendranagar, Gujarat'),
('Harsh Bhatt', 'harsh@gmail.com', '3344221100', 'ME', 2, 'Male', 'Patan, Gujarat'),
('Disha Pandey', 'disha@gmail.com', '2233110099', 'CE', 4, 'Female', 'Dahod, Gujarat'),
('Nikhil Parmar', 'nikhil@gmail.com', '1122009988', 'IT', 2, 'Male', 'Amreli, Gujarat');

-- Seed Events
INSERT INTO events (title, description, event_date, location, category, event_status, created_by) VALUES
('Tech Fest 2026', 'Annual technical festival with coding competitions and workshops.', '2026-10-15', 'CSPIT', 'Technical', 'open', 1),
('Sports Day', 'Annual sports event with track and field events.', '2026-10-20', 'CHARUSAT Ground', 'Sports', 'open', 1),
('Coding Competition', 'Competitive programming contest for all years.', '2026-10-25', 'Computer Lab', 'Technical', 'open', 1),
('Cultural Festival', 'Music, dance, and drama performances by students.', '2026-11-02', 'CHARUSAT Auditorium', 'Cultural', 'open', 1),
('Robotics Workshop', 'Hands-on workshop on building basic robots.', '2026-11-05', 'CSPIT Lab', 'Workshop', 'open', 1),
('Web Development Workshop', 'Learn HTML, CSS, JavaScript, and PHP.', '2026-11-10', 'CSPIT Lab', 'Workshop', 'open', 1),
('Hackathon', '24-hour coding marathon to build innovative solutions.', '2026-11-15', 'Innovation Center', 'Technical', 'open', 1),
('Photography Contest', 'Showcase your photography skills and win prizes.', '2026-11-18', 'CHARUSAT Campus', 'Cultural', 'closed', 1),
('Football Tournament', 'Inter-department football tournament.', '2026-11-20', 'CHARUSAT Ground', 'Sports', 'open', 1),
('AI Seminar', 'Seminar on Artificial Intelligence and Machine Learning.', '2026-11-25', 'Auditorium', 'Technical', 'open', 1);

-- Seed Registrations
INSERT INTO registrations (student_id, event_id) VALUES
(1, 1), (1, 3), (1, 7),
(2, 1), (2, 2), (2, 5),
(3, 4), (3, 6),
(4, 2), (4, 9),
(5, 1), (5, 4);

-- Seed Audit Logs
INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES
(1, 'LOGIN', 'Admin logged in successfully.', '127.0.0.1'),
(1, 'CREATE_EVENT', 'Created event: Tech Fest 2026', '127.0.0.1'),
(1, 'CREATE_EVENT', 'Created event: Sports Day', '127.0.0.1');
