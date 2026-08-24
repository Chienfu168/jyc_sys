-- 收支營運表(獨立手動輸入,暫不連結實際收支):
-- 參考新北市教育局收支營運表範例,逐列輸入上年度決算數、本年度決算數、本年度預算數,
-- 系統自動計算比較增(減)金額與比率。因有預支/跨年費用情形,故採手動輸入。
CREATE TABLE IF NOT EXISTS operating_statements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fiscal_year SMALLINT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  status ENUM('draft', 'confirmed') NOT NULL DEFAULT 'draft',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_operating_statements_fiscal_year (fiscal_year),
  INDEX idx_operating_statements_status (status),
  CONSTRAINT fk_operating_statements_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS operating_statement_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  operating_statement_id BIGINT UNSIGNED NOT NULL,
  section ENUM('income', 'expense', 'tax') NOT NULL DEFAULT 'income',
  item_name VARCHAR(160) NOT NULL,
  prior_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  current_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  budget_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_operating_statement_items_statement
    FOREIGN KEY (operating_statement_id) REFERENCES operating_statements(id)
    ON DELETE CASCADE,
  INDEX idx_operating_statement_items_statement_sort (operating_statement_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('operating_statements.view', '檢視收支營運表', 'operating_statements', NOW(), NOW()),
  ('operating_statements.manage', '管理收支營運表', 'operating_statements', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('operating_statements.view', 'operating_statements.manage')
WHERE roles.name = '系統管理員';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('operating_statements.view', 'operating_statements.manage')
WHERE roles.name = '行政人員';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'operating_statements.view'
WHERE roles.name = '主管';
