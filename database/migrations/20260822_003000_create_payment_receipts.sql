CREATE TABLE IF NOT EXISTS payment_receipts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  receipt_no VARCHAR(80) NULL,
  source_type VARCHAR(80) NULL,
  source_id BIGINT UNSIGNED NULL,
  source_label VARCHAR(190) NULL,
  receipt_date DATE NOT NULL,
  template_version VARCHAR(40) NULL DEFAULT '2026年1月23日',
  payee_name VARCHAR(120) NOT NULL,
  payee_tax_id VARCHAR(40) NULL,
  household_address VARCHAR(255) NULL,
  phone VARCHAR(60) NULL,
  payment_type ENUM('bank', 'cash') NOT NULL DEFAULT 'bank',
  bank_name VARCHAR(120) NULL,
  bank_branch VARCHAR(120) NULL,
  bank_account VARCHAR(120) NULL,
  bank_account_name VARCHAR(120) NULL,
  fee_category VARCHAR(80) NOT NULL,
  fee_category_other VARCHAR(120) NULL,
  summary VARCHAR(255) NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  remark TEXT NULL,
  health_insurance_note TEXT NULL,
  payment_note TEXT NULL,
  tax_note TEXT NULL,
  appendix_note TEXT NULL,
  status ENUM('draft', 'issued', 'voided') NOT NULL DEFAULT 'draft',
  issued_at DATETIME NULL,
  voided_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_payment_receipts_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  UNIQUE KEY uk_payment_receipts_receipt_no (receipt_no),
  INDEX idx_payment_receipts_date (receipt_date),
  INDEX idx_payment_receipts_status (status),
  INDEX idx_payment_receipts_payee (payee_name),
  INDEX idx_payment_receipts_source (source_type, source_id),
  INDEX idx_payment_receipts_payment_type (payment_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('payment_receipts.view', '檢視領款收據', 'payment_receipts', NOW(), NOW()),
  ('payment_receipts.manage', '管理領款收據', 'payment_receipts', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.module = 'payment_receipts'
WHERE roles.id = 1;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'payment_receipts.view'
WHERE roles.id = 2;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('payment_receipts.view', 'payment_receipts.manage')
WHERE roles.id = 3;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'payment_receipts.view'
WHERE roles.id = 4;
