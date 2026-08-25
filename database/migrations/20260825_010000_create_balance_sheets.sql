-- 資產負債表(獨立手動輸入,暫不連結實際帳務):
-- 參考新北市教育局資產負債表範例,逐列輸入本年底決算數、上年底決算數,
-- 系統自動計算比較增(減)金額與比率,並彙總資產/負債/淨值合計。
CREATE TABLE IF NOT EXISTS balance_sheets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fiscal_year SMALLINT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  status ENUM('draft', 'confirmed') NOT NULL DEFAULT 'draft',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_balance_sheets_fiscal_year (fiscal_year),
  INDEX idx_balance_sheets_status (status),
  CONSTRAINT fk_balance_sheets_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS balance_sheet_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  balance_sheet_id BIGINT UNSIGNED NOT NULL,
  section ENUM('asset', 'liability', 'equity') NOT NULL DEFAULT 'asset',
  item_name VARCHAR(160) NOT NULL,
  current_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  prior_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_balance_sheet_items_sheet
    FOREIGN KEY (balance_sheet_id) REFERENCES balance_sheets(id)
    ON DELETE CASCADE,
  INDEX idx_balance_sheet_items_sheet_sort (balance_sheet_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('balance_sheets.view', '檢視資產負債表', 'balance_sheets', NOW(), NOW()),
  ('balance_sheets.manage', '管理資產負債表', 'balance_sheets', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('balance_sheets.view', 'balance_sheets.manage')
WHERE roles.name = '系統管理員';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('balance_sheets.view', 'balance_sheets.manage')
WHERE roles.name = '行政人員';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'balance_sheets.view'
WHERE roles.name = '主管';
