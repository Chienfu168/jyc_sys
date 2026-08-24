-- 期初餘額(年度結轉):讓基金會導入系統時,以年度為單位承接前年度結餘,
-- 而不需遷移全部歷史資料。module 標示適用模組,reference_id 供銀行帳戶等
-- 需分帳的模組使用(帳本型模組固定為 0),fiscal_year 為西元年度。
CREATE TABLE IF NOT EXISTS opening_balances (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  module VARCHAR(40) NOT NULL,
  reference_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  fiscal_year SMALLINT UNSIGNED NOT NULL,
  opening_balance DECIMAL(14,2) NOT NULL DEFAULT 0,
  note VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_opening_balances (module, reference_id, fiscal_year),
  INDEX idx_opening_balances_module_year (module, fiscal_year),
  CONSTRAINT fk_opening_balances_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('opening_balances.view', '檢視期初餘額', 'opening_balances', NOW(), NOW()),
  ('opening_balances.manage', '管理期初餘額', 'opening_balances', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE code IN ('opening_balances.view', 'opening_balances.manage');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE code IN ('opening_balances.view', 'opening_balances.manage');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE code IN ('opening_balances.view');
