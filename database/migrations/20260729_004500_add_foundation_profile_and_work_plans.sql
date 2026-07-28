CREATE TABLE IF NOT EXISTS foundation_profiles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  foundation_name VARCHAR(160) NOT NULL,
  english_name VARCHAR(160) NULL,
  tax_id VARCHAR(30) NULL,
  registration_no VARCHAR(120) NULL,
  representative VARCHAR(120) NULL,
  executive_director VARCHAR(120) NULL,
  undertaker VARCHAR(120) NULL,
  phone VARCHAR(60) NULL,
  email VARCHAR(190) NULL,
  address VARCHAR(255) NULL,
  mission TEXT NULL,
  service_area VARCHAR(255) NULL,
  fiscal_year_start_month TINYINT UNSIGNED NOT NULL DEFAULT 1,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_foundation_profiles_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO foundation_profiles
  (id, foundation_name, english_name, tax_id, registration_no, representative, executive_director, undertaker, phone, email, address, mission, service_area, fiscal_year_start_month, updated_by, created_at, updated_at)
VALUES
  (1, '基金會管理系統', '', '', '', '', '', '', '', '', '', '', '', 1, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  updated_at = updated_at;

ALTER TABLE annual_budgets
  ADD COLUMN budget_type ENUM('annual', 'project', 'grant') NOT NULL DEFAULT 'annual' AFTER fiscal_year,
  ADD COLUMN period_start DATE NULL AFTER title,
  ADD COLUMN period_end DATE NULL AFTER period_start,
  ADD COLUMN purpose TEXT NULL AFTER notes,
  ADD COLUMN legal_basis TEXT NULL AFTER purpose,
  ADD COLUMN expected_benefit TEXT NULL AFTER legal_basis,
  ADD COLUMN board_meeting_no VARCHAR(120) NULL AFTER expected_benefit;

ALTER TABLE annual_budgets
  DROP INDEX uk_annual_budgets_fiscal_year,
  ADD INDEX idx_annual_budgets_year_type (fiscal_year, budget_type);

ALTER TABLE annual_budget_items
  ADD COLUMN description TEXT NULL AFTER item_name,
  ADD COLUMN unit VARCHAR(40) NULL AFTER description,
  ADD COLUMN quantity DECIMAL(12,2) NOT NULL DEFAULT 1 AFTER unit,
  ADD COLUMN unit_price DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER quantity,
  ADD COLUMN funding_source VARCHAR(120) NULL AFTER amount;

CREATE TABLE IF NOT EXISTS work_plans (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fiscal_year SMALLINT UNSIGNED NOT NULL,
  plan_code VARCHAR(80) NULL,
  title VARCHAR(160) NOT NULL,
  department VARCHAR(120) NULL,
  owner_name VARCHAR(120) NULL,
  period_start DATE NULL,
  period_end DATE NULL,
  objective TEXT NULL,
  target_audience VARCHAR(255) NULL,
  service_area VARCHAR(255) NULL,
  expected_outcomes TEXT NULL,
  performance_indicators TEXT NULL,
  status ENUM('draft', 'submitted', 'approved', 'closed') NOT NULL DEFAULT 'draft',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  approved_by BIGINT UNSIGNED NULL,
  approved_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_work_plans_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_work_plans_approved_by
    FOREIGN KEY (approved_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_work_plans_year_status (fiscal_year, status),
  INDEX idx_work_plans_period (period_start, period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS work_plan_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  work_plan_id BIGINT UNSIGNED NOT NULL,
  period_label VARCHAR(80) NULL,
  activity_name VARCHAR(160) NOT NULL,
  description TEXT NULL,
  expected_output VARCHAR(255) NULL,
  budget_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_work_plan_items_plan
    FOREIGN KEY (work_plan_id) REFERENCES work_plans(id)
    ON DELETE CASCADE,
  INDEX idx_work_plan_items_plan_sort (work_plan_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('foundation_profile.view', '檢視基金會基本資料', 'foundation_profile', NOW(), NOW()),
  ('foundation_profile.manage', '管理基金會基本資料', 'foundation_profile', NOW(), NOW()),
  ('work_plans.approve', '核定工作計畫', 'work_plans', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions
WHERE code IN ('foundation_profile.view', 'foundation_profile.manage', 'work_plans.approve');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions
WHERE code IN ('foundation_profile.view');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions
WHERE code IN ('foundation_profile.view', 'foundation_profile.manage', 'work_plans.approve');
