ALTER TABLE users
  ADD COLUMN phone VARCHAR(40) NULL AFTER email,
  ADD COLUMN department VARCHAR(120) NULL AFTER role_id,
  ADD COLUMN job_title VARCHAR(120) NULL AFTER department,
  ADD COLUMN employee_no VARCHAR(80) NULL AFTER job_title,
  ADD COLUMN profile_notes TEXT NULL AFTER employee_no;
