-- Add additional student profile fields to users table
ALTER TABLE users 
ADD COLUMN student_id VARCHAR(20) NULL AFTER contact_number,
ADD COLUMN date_of_birth DATE NULL AFTER student_id,
ADD COLUMN gender ENUM('male', 'female', 'other', 'prefer_not_to_say') NULL AFTER date_of_birth,
ADD COLUMN year_level ENUM('1st_year', '2nd_year', '3rd_year', '4th_year', '5th_year', 'graduate') NULL AFTER gender,
ADD COLUMN course VARCHAR(100) NULL AFTER year_level,
ADD COLUMN major VARCHAR(100) NULL AFTER course,
ADD COLUMN bio TEXT NULL AFTER major,
ADD COLUMN hometown VARCHAR(100) NULL AFTER bio,
ADD COLUMN interests TEXT NULL AFTER hometown,
ADD COLUMN emergency_contact_name VARCHAR(100) NULL AFTER interests,
ADD COLUMN emergency_contact_phone VARCHAR(15) NULL AFTER emergency_contact_name,
ADD COLUMN profile_visibility ENUM('public', 'students_only', 'private') DEFAULT 'students_only' AFTER emergency_contact_phone;