CREATE TABLE IF NOT EXISTS accounting_accounts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL,
  name VARCHAR(120) NOT NULL,
  account_type ENUM('asset', 'liability', 'net_asset', 'income', 'expense') NOT NULL,
  parent_id BIGINT UNSIGNED NULL,
  normal_balance ENUM('debit', 'credit') NOT NULL,
  description VARCHAR(255) NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_accounting_accounts_code (code),
  INDEX idx_accounting_accounts_type_status (account_type, status),
  INDEX idx_accounting_accounts_parent (parent_id),
  CONSTRAINT fk_accounting_accounts_parent
    FOREIGN KEY (parent_id) REFERENCES accounting_accounts(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS accounting_vouchers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  voucher_no VARCHAR(60) NOT NULL,
  voucher_date DATE NOT NULL,
  source_type VARCHAR(60) NULL,
  source_id BIGINT UNSIGNED NULL,
  summary VARCHAR(160) NOT NULL,
  status ENUM('draft', 'posted', 'voided') NOT NULL DEFAULT 'draft',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  posted_by BIGINT UNSIGNED NULL,
  posted_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_accounting_vouchers_no (voucher_no),
  INDEX idx_accounting_vouchers_date_status (voucher_date, status),
  INDEX idx_accounting_vouchers_source (source_type, source_id),
  CONSTRAINT fk_accounting_vouchers_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_accounting_vouchers_posted_by
    FOREIGN KEY (posted_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS accounting_voucher_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  voucher_id BIGINT UNSIGNED NOT NULL,
  account_id BIGINT UNSIGNED NOT NULL,
  description VARCHAR(255) NULL,
  debit DECIMAL(14,2) NOT NULL DEFAULT 0,
  credit DECIMAL(14,2) NOT NULL DEFAULT 0,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  INDEX idx_accounting_voucher_lines_voucher (voucher_id, sort_order),
  INDEX idx_accounting_voucher_lines_account (account_id),
  CONSTRAINT fk_accounting_voucher_lines_voucher
    FOREIGN KEY (voucher_id) REFERENCES accounting_vouchers(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_accounting_voucher_lines_account
    FOREIGN KEY (account_id) REFERENCES accounting_accounts(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO accounting_accounts (code, name, account_type, parent_id, normal_balance, description, sort_order, status, created_at, updated_at) VALUES
  ('1000', '資產', 'asset', NULL, 'debit', '基金會資產總類', 1000, 'active', NOW(), NOW()),
  ('2000', '負債', 'liability', NULL, 'credit', '基金會負債總類', 2000, 'active', NOW(), NOW()),
  ('3000', '淨資產', 'net_asset', NULL, 'credit', '非營利組織淨資產', 3000, 'active', NOW(), NOW()),
  ('4000', '收入', 'income', NULL, 'credit', '基金會收入總類', 4000, 'active', NOW(), NOW()),
  ('5000', '支出', 'expense', NULL, 'debit', '基金會支出總類', 5000, 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  account_type = VALUES(account_type),
  normal_balance = VALUES(normal_balance),
  description = VALUES(description),
  sort_order = VALUES(sort_order),
  status = VALUES(status),
  updated_at = NOW();

INSERT INTO accounting_accounts (code, name, account_type, parent_id, normal_balance, description, sort_order, status, created_at, updated_at)
SELECT '1100', '現金及銀行存款', 'asset', id, 'debit', '現金、銀行存款與零用金', 1100, 'active', NOW(), NOW()
FROM accounting_accounts WHERE code = '1000'
ON DUPLICATE KEY UPDATE parent_id = VALUES(parent_id), updated_at = NOW();

INSERT INTO accounting_accounts (code, name, account_type, parent_id, normal_balance, description, sort_order, status, created_at, updated_at)
SELECT '1200', '應收款項', 'asset', id, 'debit', '應收補助、應收捐款與其他應收款', 1200, 'active', NOW(), NOW()
FROM accounting_accounts WHERE code = '1000'
ON DUPLICATE KEY UPDATE parent_id = VALUES(parent_id), updated_at = NOW();

INSERT INTO accounting_accounts (code, name, account_type, parent_id, normal_balance, description, sort_order, status, created_at, updated_at)
SELECT '2100', '應付款項', 'liability', id, 'credit', '尚未支付之費用或款項', 2100, 'active', NOW(), NOW()
FROM accounting_accounts WHERE code = '2000'
ON DUPLICATE KEY UPDATE parent_id = VALUES(parent_id), updated_at = NOW();

INSERT INTO accounting_accounts (code, name, account_type, parent_id, normal_balance, description, sort_order, status, created_at, updated_at)
SELECT '3100', '非限定用途淨資產', 'net_asset', id, 'credit', '未受捐贈人或補助機關限制之淨資產', 3100, 'active', NOW(), NOW()
FROM accounting_accounts WHERE code = '3000'
ON DUPLICATE KEY UPDATE parent_id = VALUES(parent_id), updated_at = NOW();

INSERT INTO accounting_accounts (code, name, account_type, parent_id, normal_balance, description, sort_order, status, created_at, updated_at)
SELECT '3200', '限定用途淨資產', 'net_asset', id, 'credit', '受指定用途或期間限制之淨資產', 3200, 'active', NOW(), NOW()
FROM accounting_accounts WHERE code = '3000'
ON DUPLICATE KEY UPDATE parent_id = VALUES(parent_id), updated_at = NOW();

SET @accounting_income_parent_id = (SELECT id FROM accounting_accounts WHERE code = '4000' LIMIT 1);
SET @accounting_expense_parent_id = (SELECT id FROM accounting_accounts WHERE code = '5000' LIMIT 1);

INSERT INTO accounting_accounts (code, name, account_type, parent_id, normal_balance, description, sort_order, status, created_at, updated_at) VALUES
  ('4100', '捐款收入', 'income', @accounting_income_parent_id, 'credit', '一般捐款與指定用途捐款', 4100, 'active', NOW(), NOW()),
  ('4200', '補助收入', 'income', @accounting_income_parent_id, 'credit', '政府或民間補助收入', 4200, 'active', NOW(), NOW()),
  ('4300', '利息收入', 'income', @accounting_income_parent_id, 'credit', '銀行存款利息收入', 4300, 'active', NOW(), NOW()),
  ('5100', '人事費', 'expense', @accounting_expense_parent_id, 'debit', '薪資、勞健保、退休金與相關人事支出', 5100, 'active', NOW(), NOW()),
  ('5200', '業務費', 'expense', @accounting_expense_parent_id, 'debit', '方案、活動、課程與服務執行支出', 5200, 'active', NOW(), NOW()),
  ('5300', '行政管理費', 'expense', @accounting_expense_parent_id, 'debit', '辦公、庶務、系統、郵電與行政支出', 5300, 'active', NOW(), NOW()),
  ('5400', '講師費', 'expense', @accounting_expense_parent_id, 'debit', '講師鐘點、交通與相關支出', 5400, 'active', NOW(), NOW()),
  ('5500', '差旅費', 'expense', @accounting_expense_parent_id, 'debit', '出差交通、住宿與膳雜支出', 5500, 'active', NOW(), NOW()),
  ('5900', '其他支出', 'expense', @accounting_expense_parent_id, 'debit', '其他未分類支出', 5900, 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  account_type = VALUES(account_type),
  parent_id = VALUES(parent_id),
  normal_balance = VALUES(normal_balance),
  description = VALUES(description),
  sort_order = VALUES(sort_order),
  status = VALUES(status),
  updated_at = NOW();

INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('accounting.post', '過帳會計傳票', 'accounting', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE code = 'accounting.post';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE code = 'accounting.post';
