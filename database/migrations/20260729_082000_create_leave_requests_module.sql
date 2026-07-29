CREATE TABLE IF NOT EXISTS leave_types (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  code VARCHAR(30) NOT NULL,
  paid ENUM('yes', 'no', 'partial') NOT NULL DEFAULT 'yes',
  default_hours_per_day DECIMAL(5,2) NOT NULL DEFAULT 8.00,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_leave_types_code (code),
  UNIQUE KEY uk_leave_types_name (name),
  INDEX idx_leave_types_status_sort (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leave_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id BIGINT UNSIGNED NOT NULL,
  leave_type_id BIGINT UNSIGNED NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  start_time TIME NULL,
  end_time TIME NULL,
  total_hours DECIMAL(8,2) NOT NULL DEFAULT 0,
  reason VARCHAR(255) NOT NULL,
  handover_person VARCHAR(120) NULL,
  status ENUM('draft', 'submitted', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'submitted',
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  review_notes TEXT NULL,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_leave_requests_employee
    FOREIGN KEY (employee_id) REFERENCES personnel_employees(id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_leave_requests_type
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_leave_requests_reviewed_by
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_leave_requests_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_leave_requests_period_status (start_date, end_date, status),
  INDEX idx_leave_requests_employee (employee_id),
  INDEX idx_leave_requests_type (leave_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO leave_types (name, code, paid, default_hours_per_day, sort_order, status, created_at, updated_at) VALUES
  ('特別休假', 'annual', 'yes', 8, 10, 'active', NOW(), NOW()),
  ('事假', 'personal', 'no', 8, 20, 'active', NOW(), NOW()),
  ('病假', 'sick', 'partial', 8, 30, 'active', NOW(), NOW()),
  ('公假', 'official', 'yes', 8, 40, 'active', NOW(), NOW()),
  ('婚假', 'marriage', 'yes', 8, 50, 'active', NOW(), NOW()),
  ('喪假', 'bereavement', 'yes', 8, 60, 'active', NOW(), NOW()),
  ('補休', 'compensatory', 'yes', 8, 70, 'active', NOW(), NOW()),
  ('其他', 'other', 'partial', 8, 90, 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  paid = VALUES(paid),
  default_hours_per_day = VALUES(default_hours_per_day),
  sort_order = VALUES(sort_order),
  status = VALUES(status),
  updated_at = NOW();

INSERT INTO leave_requests
  (employee_id, leave_type_id, start_date, end_date, start_time, end_time, total_hours, reason, handover_person, status, notes, created_by, created_at, updated_at)
SELECT personnel_employees.id, leave_types.id, '2026-07-24', '2026-07-24', '09:00:00', '18:00:00', 8, '家庭事務處理', '行政同仁', 'submitted', '範例資料，可依實際請假紀錄修改。', personnel_employees.user_id, NOW(), NOW()
FROM personnel_employees
INNER JOIN leave_types ON leave_types.code = 'personal'
ORDER BY personnel_employees.id
LIMIT 1;

INSERT INTO leave_requests
  (employee_id, leave_type_id, start_date, end_date, start_time, end_time, total_hours, reason, handover_person, status, reviewed_by, reviewed_at, review_notes, notes, created_by, created_at, updated_at)
SELECT personnel_employees.id, leave_types.id, '2026-07-29', '2026-07-29', '13:00:00', '17:00:00', 4, '身體不適就醫', '行政同仁', 'approved', personnel_employees.user_id, NOW(), '範例核准紀錄。', '範例資料，可依實際請假紀錄修改。', personnel_employees.user_id, NOW(), NOW()
FROM personnel_employees
INNER JOIN leave_types ON leave_types.code = 'sick'
ORDER BY personnel_employees.id
LIMIT 1;
