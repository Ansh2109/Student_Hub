CREATE DATABASE IF NOT EXISTS studenthub
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE studenthub;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL UNIQUE,
    student_id VARCHAR(20) NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL DEFAULT '',
    date_of_birth DATE NULL,
    gender VARCHAR(20) NOT NULL DEFAULT '',
    course VARCHAR(100) NOT NULL,
    year VARCHAR(40) NOT NULL,
    department VARCHAR(80) NOT NULL,
    city VARCHAR(100) NOT NULL DEFAULT '',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_students_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    category VARCHAR(80) NOT NULL,
    description TEXT NOT NULL,
    event_date DATETIME NOT NULL,
    venue VARCHAR(160) NOT NULL,
    status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    poster_path VARCHAR(255) NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_creator
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS registrations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    event_id BIGINT UNSIGNED NOT NULL,
    registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_event_registration (user_id, event_id),
    CONSTRAINT fk_registrations_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_registrations_event
        FOREIGN KEY (event_id) REFERENCES events(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS remember_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    selector CHAR(24) NOT NULL UNIQUE,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_remember_tokens_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_logs_created_at (created_at),
    CONSTRAINT fk_audit_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO students (name, email, course, year, department, city) VALUES
('Aarav Joshi', 'aarav.joshi@example.edu', 'B.Tech CSE', 'Third Year', 'CSE', 'Anand'),
('Ansh Patel', 'ansh.patel@example.edu', 'B.Tech AIML', 'Second Year', 'AIML', 'Anand'),
('Dhruv Patel', 'dhruv.patel@example.edu', 'B.Tech AIML', 'Third Year', 'AIML', 'Surat'),
('Dev Mehta', 'dev.mehta@example.edu', 'B.Tech IT', 'Second Year', 'IT', 'Rajkot'),
('Harsh Shah', 'harsh.shah@example.edu', 'B.Tech CSE', 'Fourth Year', 'CSE', 'Ahmedabad'),
('Het Patel', 'het.patel@example.edu', 'B.Tech AIML', 'Fourth Year', 'AIML', 'Vadodara'),
('Isha Desai', 'isha.desai@example.edu', 'B.Tech IT', 'Third Year', 'IT', 'Vadodara'),
('Karan Joshi', 'karan.joshi@example.edu', 'B.Tech IT', 'Fourth Year', 'IT', 'Nadiad'),
('Kavya Desai', 'kavya.desai@example.edu', 'B.Tech IT', 'First Year', 'IT', 'Bharuch'),
('Mansi Shah', 'mansi.shah@example.edu', 'B.Tech CSE', 'First Year', 'CSE', 'Surat'),
('Neha Patel', 'neha.patel@example.edu', 'B.Tech AIML', 'First Year', 'AIML', 'Bharuch'),
('Priya Mehta', 'priya.mehta@example.edu', 'B.Tech IT', 'First Year', 'IT', 'Vadodara'),
('Rahul Shah', 'rahul.shah@example.edu', 'B.Tech CSE', 'Third Year', 'CSE', 'Ahmedabad'),
('Riya Shah', 'riya.shah@example.edu', 'B.Tech CSE', 'Second Year', 'CSE', 'Rajkot'),
('Yash Patel', 'yash.patel@example.edu', 'B.Tech AIML', 'Second Year', 'AIML', 'Anand'),
('Nisha Parmar', 'nisha.parmar@example.edu', 'B.Tech CE', 'First Year', 'CE', 'Vadodara'),
('Meet Trivedi', 'meet.trivedi@example.edu', 'B.Tech CSE', 'Second Year', 'CSE', 'Nadiad'),
('Jiya Shah', 'jiya.shah@example.edu', 'B.Tech IT', 'Third Year', 'IT', 'Ahmedabad'),
('Om Patel', 'om.patel@example.edu', 'B.Tech CE', 'Fourth Year', 'CE', 'Surat'),
('Aanya Desai', 'aanya.desai@example.edu', 'B.Tech AIML', 'First Year', 'AIML', 'Bharuch');

INSERT INTO events (title, category, description, event_date, venue, status)
SELECT 'AI and Machine Learning Workshop', 'Technical',
       'Introduction to artificial intelligence and machine learning.',
       '2026-11-20 10:00:00', 'Seminar Hall', 'open'
WHERE NOT EXISTS (
    SELECT 1 FROM events WHERE title = 'AI and Machine Learning Workshop'
);

INSERT INTO events (title, category, description, event_date, venue, status)
SELECT 'Annual Hackathon', 'Technical',
       'Build innovative projects and compete with other teams.',
       '2026-11-25 09:00:00', 'Innovation Lab', 'open'
WHERE NOT EXISTS (
    SELECT 1 FROM events WHERE title = 'Annual Hackathon'
);

INSERT INTO events (title, category, description, event_date, venue, status)
SELECT 'Inter College Cricket', 'Sports',
       'Annual cricket tournament for college students.',
       '2026-12-05 08:00:00', 'College Ground', 'open'
WHERE NOT EXISTS (
    SELECT 1 FROM events WHERE title = 'Inter College Cricket'
);
