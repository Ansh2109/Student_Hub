-- Run once only when upgrading an existing StudentHub database.
ALTER TABLE students
    ADD COLUMN student_id VARCHAR(20) NULL UNIQUE AFTER user_id;
