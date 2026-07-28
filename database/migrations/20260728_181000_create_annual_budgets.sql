CREATE TABLE IF NOT EXISTS annual_budgets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fiscal_year SMALLINT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  status ENUM('draft', 'submitted', 'approved') NOT NULL DEFAULT 'draft',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  approved_by BIGINT UNSIGNED NULL,
  approved_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_annual_budgets_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_annual_budgets_approved_by
    FOREIGN KEY (approved_by) REFERENCES users(id)
    ON DELETE SET NULL,
  UNIQUE KEY uk_annual_budgets_fiscal_year (fiscal_year),
  INDEX idx_annual_budgets_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS annual_budget_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  annual_budget_id BIGINT UNSIGNED NOT NULL,
  item_type ENUM('income', 'expense') NOT NULL,
  category VARCHAR(120) NOT NULL,
  item_name VARCHAR(160) NOT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  notes TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_annual_budget_items_budget
    FOREIGN KEY (annual_budget_id) REFERENCES annual_budgets(id)
    ON DELETE CASCADE,
  INDEX idx_annual_budget_items_type (item_type),
  INDEX idx_annual_budget_items_budget_sort (annual_budget_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('annual_budgets.view', '檢視年度預算', 'annual_budgets', NOW(), NOW()),
  ('annual_budgets.manage', '編寫年度預算', 'annual_budgets', NOW(), NOW()),
  ('annual_budgets.approve', '核定年度預算', 'annual_budgets', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.module = 'annual_budgets'
WHERE roles.name = '系統管理員';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('annual_budgets.view', 'annual_budgets.approve')
WHERE roles.name = '主管';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('annual_budgets.view', 'annual_budgets.manage')
WHERE roles.name = '行政人員';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'annual_budgets.view'
WHERE roles.name = '一般查詢人員';
