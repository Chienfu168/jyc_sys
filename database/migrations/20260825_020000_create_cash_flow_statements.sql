-- 現金流量表(獨立手動輸入,暫不連結實際帳務):
-- 參考新北市教育局現金流量表範例,逐列輸入業務/投資/籌資活動之現金流量項目,
-- 加上匯率變動影響與期初餘額,系統自動計算各活動淨額、現金增減數與期末餘額。
CREATE TABLE IF NOT EXISTS cash_flow_statements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fiscal_year SMALLINT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  status ENUM('draft', 'confirmed') NOT NULL DEFAULT 'draft',
  exchange_current DECIMAL(14,2) NOT NULL DEFAULT 0,
  exchange_prior DECIMAL(14,2) NOT NULL DEFAULT 0,
  opening_current DECIMAL(14,2) NOT NULL DEFAULT 0,
  opening_prior DECIMAL(14,2) NOT NULL DEFAULT 0,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_cash_flow_statements_fiscal_year (fiscal_year),
  INDEX idx_cash_flow_statements_status (status),
  CONSTRAINT fk_cash_flow_statements_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cash_flow_statement_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cash_flow_statement_id BIGINT UNSIGNED NOT NULL,
  section ENUM('operating', 'investing', 'financing') NOT NULL DEFAULT 'operating',
  item_name VARCHAR(160) NOT NULL,
  current_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  prior_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_cash_flow_statement_items_statement
    FOREIGN KEY (cash_flow_statement_id) REFERENCES cash_flow_statements(id)
    ON DELETE CASCADE,
  INDEX idx_cash_flow_statement_items_statement_sort (cash_flow_statement_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('cash_flow_statements.view', '檢視現金流量表', 'cash_flow_statements', NOW(), NOW()),
  ('cash_flow_statements.manage', '管理現金流量表', 'cash_flow_statements', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('cash_flow_statements.view', 'cash_flow_statements.manage')
WHERE roles.name = '系統管理員';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('cash_flow_statements.view', 'cash_flow_statements.manage')
WHERE roles.name = '行政人員';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'cash_flow_statements.view'
WHERE roles.name = '主管';
