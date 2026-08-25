-- 淨值變動表(獨立手動輸入,暫不連結實際帳務):
-- 參考新北市教育局淨值變動表範例(矩陣結構):
-- 欄為淨值組成(設立基金、其他基金、公積、累積賸餘、淨值其他項目),合計為列總和;
-- 列為各期異動與餘額(期初餘額、本期稅後賸餘、金融資產未實現餘絀、綜合餘絀總額、期末餘額)。
CREATE TABLE IF NOT EXISTS net_asset_statements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fiscal_year SMALLINT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  status ENUM('draft', 'confirmed') NOT NULL DEFAULT 'draft',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_net_asset_statements_fiscal_year (fiscal_year),
  INDEX idx_net_asset_statements_status (status),
  CONSTRAINT fk_net_asset_statements_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS net_asset_statement_rows (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  net_asset_statement_id BIGINT UNSIGNED NOT NULL,
  row_label VARCHAR(160) NOT NULL,
  founding_fund DECIMAL(16,2) NOT NULL DEFAULT 0,
  other_fund DECIMAL(16,2) NOT NULL DEFAULT 0,
  capital_reserve DECIMAL(16,2) NOT NULL DEFAULT 0,
  accumulated_surplus DECIMAL(16,2) NOT NULL DEFAULT 0,
  other_equity DECIMAL(16,2) NOT NULL DEFAULT 0,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_net_asset_statement_rows_statement
    FOREIGN KEY (net_asset_statement_id) REFERENCES net_asset_statements(id)
    ON DELETE CASCADE,
  INDEX idx_net_asset_statement_rows_statement_sort (net_asset_statement_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('net_asset_statements.view', '檢視淨值變動表', 'net_asset_statements', NOW(), NOW()),
  ('net_asset_statements.manage', '管理淨值變動表', 'net_asset_statements', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('net_asset_statements.view', 'net_asset_statements.manage')
WHERE roles.name = '系統管理員';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('net_asset_statements.view', 'net_asset_statements.manage')
WHERE roles.name = '行政人員';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'net_asset_statements.view'
WHERE roles.name = '主管';
