CREATE TABLE IF NOT EXISTS income_expense_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_type ENUM('income', 'expense') NOT NULL,
  name VARCHAR(120) NOT NULL,
  default_description VARCHAR(255) NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_income_expense_categories_type_name (item_type, name),
  INDEX idx_income_expense_categories_status_sort (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS income_expense_records (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  occurred_on DATE NOT NULL,
  item_type ENUM('income', 'expense') NOT NULL,
  category_id BIGINT UNSIGNED NULL,
  category_name VARCHAR(120) NOT NULL,
  subject VARCHAR(160) NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  counterparty VARCHAR(160) NULL,
  counterparty_tax_id VARCHAR(30) NULL,
  payment_method VARCHAR(60) NULL,
  bank_account_id BIGINT UNSIGNED NULL,
  project_name VARCHAR(160) NULL,
  receipt_no VARCHAR(100) NULL,
  receipt_status ENUM('not_required', 'pending', 'issued', 'voided') NOT NULL DEFAULT 'pending',
  notes TEXT NULL,
  status ENUM('draft', 'confirmed', 'voided') NOT NULL DEFAULT 'confirmed',
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_income_expense_records_category
    FOREIGN KEY (category_id) REFERENCES income_expense_categories(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_income_expense_records_bank_account
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_income_expense_records_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_income_expense_records_date_type (occurred_on, item_type),
  INDEX idx_income_expense_records_project (project_name),
  INDEX idx_income_expense_records_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO income_expense_categories (item_type, name, default_description, sort_order, status, created_at, updated_at) VALUES
  ('income', '捐款收入', '個人或企業捐款收入', 10, 'active', NOW(), NOW()),
  ('income', '補助收入', '政府或機構補助款', 20, 'active', NOW(), NOW()),
  ('income', '利息收入', '銀行利息或金融收入', 30, 'active', NOW(), NOW()),
  ('income', '其他收入', '非經常性收入', 90, 'active', NOW(), NOW()),
  ('expense', '人事費', '薪資、勞健保、退休金與人事相關費用', 10, 'active', NOW(), NOW()),
  ('expense', '業務費', '計畫執行與活動業務支出', 20, 'active', NOW(), NOW()),
  ('expense', '行政管理費', '辦公、租金、設備、資訊與行政支出', 30, 'active', NOW(), NOW()),
  ('expense', '講師費', '講師鐘點、交通與相關支出', 40, 'active', NOW(), NOW()),
  ('expense', '差旅費', '交通、住宿、膳雜與出差支出', 50, 'active', NOW(), NOW()),
  ('expense', '其他支出', '非經常性支出', 90, 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  default_description = VALUES(default_description),
  sort_order = VALUES(sort_order),
  status = VALUES(status),
  updated_at = NOW();
