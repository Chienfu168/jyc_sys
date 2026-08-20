CREATE TABLE IF NOT EXISTS donors (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  donor_type ENUM('person', 'organization') NOT NULL DEFAULT 'person',
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(40) NULL,
  email VARCHAR(190) NULL,
  address VARCHAR(255) NULL,
  receipt_title VARCHAR(120) NULL,
  tax_id VARCHAR(30) NULL,
  notes TEXT NULL,
  status ENUM('active', 'archived') NOT NULL DEFAULT 'active',
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_donors_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_donors_name (name),
  INDEX idx_donors_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS donations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  donor_id BIGINT UNSIGNED NOT NULL,
  donated_at DATE NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  payment_method VARCHAR(40) NOT NULL,
  receipt_no VARCHAR(80) NULL,
  receipt_status ENUM('not_required', 'pending', 'issued', 'voided') NOT NULL DEFAULT 'pending',
  accounting_voucher_id BIGINT UNSIGNED NULL,
  project_name VARCHAR(120) NULL,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_donations_donor
    FOREIGN KEY (donor_id) REFERENCES donors(id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_donations_accounting_voucher
    FOREIGN KEY (accounting_voucher_id) REFERENCES accounting_vouchers(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_donations_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_donations_donated_at (donated_at),
  INDEX idx_donations_receipt_status (receipt_status),
  INDEX idx_donations_accounting_voucher (accounting_voucher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @donations_has_voucher_column = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'donations'
    AND COLUMN_NAME = 'accounting_voucher_id'
);
SET @donations_add_voucher_column = IF(
  @donations_has_voucher_column = 0,
  'ALTER TABLE donations ADD COLUMN accounting_voucher_id BIGINT UNSIGNED NULL AFTER receipt_status',
  'SELECT 1'
);
PREPARE donations_add_voucher_column_stmt FROM @donations_add_voucher_column;
EXECUTE donations_add_voucher_column_stmt;
DEALLOCATE PREPARE donations_add_voucher_column_stmt;

SET @donations_has_voucher_index = (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'donations'
    AND INDEX_NAME = 'idx_donations_accounting_voucher'
);
SET @donations_add_voucher_index = IF(
  @donations_has_voucher_index = 0,
  'ALTER TABLE donations ADD INDEX idx_donations_accounting_voucher (accounting_voucher_id)',
  'SELECT 1'
);
PREPARE donations_add_voucher_index_stmt FROM @donations_add_voucher_index;
EXECUTE donations_add_voucher_index_stmt;
DEALLOCATE PREPARE donations_add_voucher_index_stmt;

SET @donations_has_voucher_fk = (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'donations'
    AND CONSTRAINT_NAME = 'fk_donations_accounting_voucher'
);
SET @donations_add_voucher_fk = IF(
  @donations_has_voucher_fk = 0,
  'ALTER TABLE donations ADD CONSTRAINT fk_donations_accounting_voucher FOREIGN KEY (accounting_voucher_id) REFERENCES accounting_vouchers(id) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE donations_add_voucher_fk_stmt FROM @donations_add_voucher_fk;
EXECUTE donations_add_voucher_fk_stmt;
DEALLOCATE PREPARE donations_add_voucher_fk_stmt;

INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('donors.view', '檢視捐款人', 'donors', NOW(), NOW()),
  ('donors.manage', '管理捐款人', 'donors', NOW(), NOW()),
  ('donations.view', '檢視捐款紀錄', 'donations', NOW(), NOW()),
  ('donations.manage', '管理捐款紀錄', 'donations', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('donors.view', 'donors.manage', 'donations.view', 'donations.manage')
WHERE roles.name IN ('系統管理員', '行政人員');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('donors.view', 'donations.view')
WHERE roles.name IN ('主管', '一般檢視者');
