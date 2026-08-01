CREATE TABLE IF NOT EXISTS foundation_assets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  registration_status ENUM('court_registered', 'not_registered') NOT NULL DEFAULT 'not_registered',
  asset_kind ENUM('movable', 'real_estate', 'other') NOT NULL DEFAULT 'movable',
  asset_name VARCHAR(160) NOT NULL,
  unit VARCHAR(40) NULL,
  quantity DECIMAL(12,2) NOT NULL DEFAULT 1,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  acquired_on DATE NULL,
  acquisition_source VARCHAR(160) NULL,
  storage_location VARCHAR(160) NULL,
  proof_document VARCHAR(160) NULL,
  asset_no VARCHAR(120) NULL,
  notes TEXT NULL,
  status ENUM('active', 'disposed') NOT NULL DEFAULT 'active',
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_foundation_assets_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_foundation_assets_registration (registration_status, asset_kind),
  INDEX idx_foundation_assets_status (status),
  INDEX idx_foundation_assets_no (asset_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('foundation_assets.view', '檢視財產清冊', 'foundation_assets', NOW(), NOW()),
  ('foundation_assets.manage', '管理財產清冊', 'foundation_assets', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE code IN ('foundation_assets.view', 'foundation_assets.manage');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE code IN ('foundation_assets.view', 'foundation_assets.manage');
