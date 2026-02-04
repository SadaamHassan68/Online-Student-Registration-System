-- Online Student Registration System Database Schema
-- MySQL 8.0+ / MariaDB

CREATE DATABASE IF NOT EXISTS std_register CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE std_register;

-- Students table
CREATE TABLE IF NOT EXISTS students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    date_of_birth DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    admission_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    intended_major VARCHAR(100),
    high_school_name VARCHAR(150),
    high_school_grade DECIMAL(4,2),
    secondary_phone VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255),
    verification_token_expiry DATETIME,
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_verification_token (verification_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Courses table
CREATE TABLE IF NOT EXISTS courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(255) NOT NULL,
    description TEXT,
    credits INT NOT NULL DEFAULT 3,
    max_capacity INT NOT NULL DEFAULT 30,
    current_enrollment INT DEFAULT 0,
    schedule VARCHAR(255),
    instructor VARCHAR(255),
    semester VARCHAR(20) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    department VARCHAR(100),
    prerequisites VARCHAR(255),
    status ENUM('active', 'inactive', 'full') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_course_code (course_code),
    INDEX idx_semester_year (semester, academic_year),
    INDEX idx_status (status),
    INDEX idx_department (department)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users table (for admin/registrar) - Must be created before enrollments
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'registrar') DEFAULT 'registrar',
    last_login DATETIME NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enrollments table (created after users table to allow foreign key reference)
CREATE TABLE IF NOT EXISTS enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrollment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    grade DECIMAL(4,2) NULL,
    status ENUM('enrolled', 'completed', 'dropped', 'pending') DEFAULT 'pending',
    approved_by INT NULL,
    approved_at DATETIME NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL,
    UNIQUE KEY unique_enrollment (student_id, course_id),
    INDEX idx_student_id (student_id),
    INDEX idx_course_id (course_id),
    INDEX idx_status (status),
    INDEX idx_enrollment_date (enrollment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit log table
CREATE TABLE IF NOT EXISTS audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    user_type ENUM('student', 'admin', 'registrar') NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: Admin@123)
-- Change this password immediately after first login!
INSERT INTO users (username, email, password_hash, role, status) VALUES
('admin', 'admin@studentregistration.edu', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5VvXKdF3QzJz6', 'admin', 'active')
ON DUPLICATE KEY UPDATE username=username;

-- Sample courses data
INSERT INTO courses (course_code, course_name, description, credits, max_capacity, instructor, semester, academic_year, department) VALUES
('CS101', 'Introduction to Computer Science', 'Fundamental concepts of computer science and programming', 3, 40, 'Dr. John Smith', 'Fall', '2024', 'Computer Science'),
('CS201', 'Data Structures and Algorithms', 'Advanced data structures and algorithm design', 4, 35, 'Dr. Jane Doe', 'Fall', '2024', 'Computer Science'),
('MATH101', 'Calculus I', 'Differential and integral calculus', 4, 50, 'Prof. Robert Johnson', 'Fall', '2024', 'Mathematics'),
('ENG101', 'English Composition', 'College-level writing and communication skills', 3, 30, 'Dr. Mary Williams', 'Fall', '2024', 'English'),
('PHY101', 'General Physics', 'Mechanics, thermodynamics, and waves', 4, 45, 'Dr. David Brown', 'Fall', '2024', 'Physics')
ON DUPLICATE KEY UPDATE course_code=course_code;

