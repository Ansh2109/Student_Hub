ALTER TABLE students
    ADD COLUMN phone VARCHAR(20) NOT NULL DEFAULT '' AFTER email,
    ADD COLUMN date_of_birth DATE NULL AFTER phone,
    ADD COLUMN gender VARCHAR(20) NOT NULL DEFAULT '' AFTER date_of_birth;
