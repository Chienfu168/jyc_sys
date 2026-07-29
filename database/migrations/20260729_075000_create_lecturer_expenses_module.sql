CREATE TABLE IF NOT EXISTS lecturer_expenses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lecturer_id BIGINT UNSIGNED NOT NULL,
  expense_date DATE NOT NULL,
  service_title VARCHAR(160) NOT NULL,
  service_unit VARCHAR(160) NULL,
  project_name VARCHAR(160) NULL,
  activity_name VARCHAR(160) NULL,
  hours DECIMAL(8,2) NOT NULL DEFAULT 0,
  hourly_rate DECIMAL(12,2) NOT NULL DEFAULT 0,
  lecture_fee DECIMAL(14,2) NOT NULL DEFAULT 0,
  transportation_fee DECIMAL(14,2) NOT NULL DEFAULT 0,
  other_fee DECIMAL(14,2) NOT NULL DEFAULT 0,
  withholding_tax DECIMAL(14,2) NOT NULL DEFAULT 0,
  gross_total DECIMAL(14,2) NOT NULL DEFAULT 0,
  net_total DECIMAL(14,2) NOT NULL DEFAULT 0,
  payment_method VARCHAR(60) NULL,
  payment_status ENUM('pending', 'paid', 'voided') NOT NULL DEFAULT 'pending',
  paid_on DATE NULL,
  bank_account_id BIGINT UNSIGNED NULL,
  accounting_voucher_id BIGINT UNSIGNED NULL,
  receipt_no VARCHAR(100) NULL,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_lecturer_expenses_lecturer
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_lecturer_expenses_bank_account
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_lecturer_expenses_accounting_voucher
    FOREIGN KEY (accounting_voucher_id) REFERENCES accounting_vouchers(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_lecturer_expenses_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_lecturer_expenses_date_status (expense_date, payment_status),
  INDEX idx_lecturer_expenses_lecturer (lecturer_id),
  INDEX idx_lecturer_expenses_project (project_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO lecturer_expenses
  (lecturer_id, expense_date, service_title, service_unit, project_name, activity_name, hours, hourly_rate, lecture_fee, transportation_fee, other_fee, withholding_tax, gross_total, net_total, payment_method, payment_status, paid_on, receipt_no, notes, created_at, updated_at)
SELECT id, '2026-07-03', '暑期閱讀陪伴課程', '教育推廣組', '暑期學習陪伴', '閱讀素養工作坊', 2, hourly_rate, hourly_rate * 2, 200, 0, 0, hourly_rate * 2 + 200, hourly_rate * 2 + 200, '匯款', 'pending', NULL, 'LE-202607-001', '範例資料，可依實際請款修改。', NOW(), NOW()
FROM lecturers WHERE name = '陳怡君' LIMIT 1;

INSERT INTO lecturer_expenses
  (lecturer_id, expense_date, service_title, service_unit, project_name, activity_name, hours, hourly_rate, lecture_fee, transportation_fee, other_fee, withholding_tax, gross_total, net_total, payment_method, payment_status, paid_on, receipt_no, notes, created_at, updated_at)
SELECT id, '2026-07-10', '社區營造培力講座', '社區合作組', '地方文化推廣', '社區營造講座', 3, hourly_rate, hourly_rate * 3, 300, 0, 0, hourly_rate * 3 + 300, hourly_rate * 3 + 300, '匯款', 'paid', '2026-07-15', 'LE-202607-002', '範例資料，可依實際請款修改。', NOW(), NOW()
FROM lecturers WHERE name = '林志明' LIMIT 1;

INSERT INTO lecturer_expenses
  (lecturer_id, expense_date, service_title, service_unit, project_name, activity_name, hours, hourly_rate, lecture_fee, transportation_fee, other_fee, withholding_tax, gross_total, net_total, payment_method, payment_status, paid_on, receipt_no, notes, created_at, updated_at)
SELECT id, '2026-07-17', 'AI 工具入門實作', '數位學習組', '數位能力提升', 'AI 應用工作坊', 2, hourly_rate, hourly_rate * 2, 250, 0, 0, hourly_rate * 2 + 250, hourly_rate * 2 + 250, '匯款', 'pending', NULL, 'LE-202607-003', '範例資料，可依實際請款修改。', NOW(), NOW()
FROM lecturers WHERE name = '張文豪' LIMIT 1;
