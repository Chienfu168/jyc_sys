CREATE TABLE IF NOT EXISTS travel_expenses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  traveler_user_id BIGINT UNSIGNED NULL,
  traveler_name VARCHAR(120) NOT NULL,
  travel_start DATE NOT NULL,
  travel_end DATE NOT NULL,
  destination VARCHAR(160) NOT NULL,
  purpose VARCHAR(255) NOT NULL,
  project_name VARCHAR(160) NULL,
  transportation_fee DECIMAL(14,2) NOT NULL DEFAULT 0,
  accommodation_fee DECIMAL(14,2) NOT NULL DEFAULT 0,
  meal_fee DECIMAL(14,2) NOT NULL DEFAULT 0,
  miscellaneous_fee DECIMAL(14,2) NOT NULL DEFAULT 0,
  advance_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  reimbursable_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
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
  CONSTRAINT fk_travel_expenses_traveler_user
    FOREIGN KEY (traveler_user_id) REFERENCES users(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_travel_expenses_bank_account
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_travel_expenses_accounting_voucher
    FOREIGN KEY (accounting_voucher_id) REFERENCES accounting_vouchers(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_travel_expenses_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_travel_expenses_period_status (travel_start, travel_end, payment_status),
  INDEX idx_travel_expenses_traveler (traveler_name),
  INDEX idx_travel_expenses_project (project_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO travel_expenses
  (traveler_user_id, traveler_name, travel_start, travel_end, destination, purpose, project_name, transportation_fee, accommodation_fee, meal_fee, miscellaneous_fee, advance_amount, total_amount, reimbursable_amount, payment_method, payment_status, paid_on, receipt_no, notes, created_by, created_at, updated_at)
SELECT id, name, '2026-07-08', '2026-07-08', '新北市板橋區', '主管機關業務洽詢與資料送件', '行政管理', 180, 0, 120, 0, 0, 300, 300, '匯款', 'pending', NULL, 'TR-202607-001', '範例資料，可依實際出差紀錄修改。', id, NOW(), NOW()
FROM users ORDER BY id LIMIT 1;

INSERT INTO travel_expenses
  (traveler_user_id, traveler_name, travel_start, travel_end, destination, purpose, project_name, transportation_fee, accommodation_fee, meal_fee, miscellaneous_fee, advance_amount, total_amount, reimbursable_amount, payment_method, payment_status, paid_on, receipt_no, notes, created_by, created_at, updated_at)
SELECT id, name, '2026-07-16', '2026-07-17', '台中市', '教育合作單位拜訪與課程討論', '教育推廣', 1200, 2200, 600, 100, 1000, 4100, 3100, '匯款', 'paid', '2026-07-22', 'TR-202607-002', '範例資料，可依實際出差紀錄修改。', id, NOW(), NOW()
FROM users ORDER BY id LIMIT 1;
