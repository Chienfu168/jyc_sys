CREATE TABLE IF NOT EXISTS personnel_employees (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  employee_no VARCHAR(80) NULL,
  name VARCHAR(120) NOT NULL,
  identity_no VARCHAR(30) NULL,
  birth_date DATE NULL,
  gender ENUM('female', 'male', 'other', 'undisclosed') NOT NULL DEFAULT 'undisclosed',
  phone VARCHAR(50) NULL,
  mobile VARCHAR(50) NULL,
  email VARCHAR(160) NULL,
  address VARCHAR(255) NULL,
  department VARCHAR(120) NULL,
  job_title VARCHAR(120) NULL,
  employment_type ENUM('full_time', 'part_time', 'contract', 'volunteer_staff', 'other') NOT NULL DEFAULT 'full_time',
  hire_date DATE NULL,
  leave_date DATE NULL,
  base_salary DECIMAL(14,2) NOT NULL DEFAULT 0,
  labor_insurance_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  health_insurance_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  pension_rate DECIMAL(5,2) NOT NULL DEFAULT 6.00,
  bank_name VARCHAR(120) NULL,
  bank_branch VARCHAR(120) NULL,
  bank_account_no VARCHAR(80) NULL,
  bank_account_name VARCHAR(120) NULL,
  emergency_contact VARCHAR(120) NULL,
  emergency_phone VARCHAR(50) NULL,
  status ENUM('active', 'on_leave', 'resigned') NOT NULL DEFAULT 'active',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_personnel_employees_employee_no (employee_no),
  UNIQUE KEY uk_personnel_employees_user (user_id),
  INDEX idx_personnel_employees_status_name (status, name),
  INDEX idx_personnel_employees_department (department),
  CONSTRAINT fk_personnel_employees_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_personnel_employees_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO personnel_employees
  (user_id, employee_no, name, phone, email, department, job_title, employment_type, hire_date, base_salary, bank_account_name, status, notes, created_by, created_at, updated_at)
SELECT id, COALESCE(employee_no, CONCAT('EMP-', LPAD(id, 4, '0'))), name, phone, email, department, job_title, 'full_time', CURDATE(), 0, name, 'active', '由系統帳號建立的人事範例資料，可依實際資料修改。', id, NOW(), NOW()
FROM users
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  phone = VALUES(phone),
  email = VALUES(email),
  department = VALUES(department),
  job_title = VALUES(job_title),
  updated_at = NOW();

INSERT INTO personnel_employees
  (employee_no, name, gender, mobile, email, department, job_title, employment_type, hire_date, base_salary, bank_name, bank_branch, bank_account_no, bank_account_name, emergency_contact, emergency_phone, status, notes, created_at, updated_at)
VALUES
  ('EMP-SAMPLE-001', '李佳穎', 'female', '0912-100-001', 'staff01@example.org', '教育推廣組', '專案專員', 'full_time', '2026-01-01', 36000, '玉山銀行', '蘆洲分行', '000000100001', '李佳穎', '李先生', '0912-900-001', 'active', '範例資料，可依實際人事資料修改。', NOW(), NOW()),
  ('EMP-SAMPLE-002', '周俊廷', 'male', '0912-100-002', 'staff02@example.org', '行政管理組', '行政助理', 'part_time', '2026-03-01', 22000, '合作金庫', '三重分行', '000000100002', '周俊廷', '周小姐', '0912-900-002', 'active', '範例資料，可依實際人事資料修改。', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  department = VALUES(department),
  job_title = VALUES(job_title),
  employment_type = VALUES(employment_type),
  updated_at = NOW();
