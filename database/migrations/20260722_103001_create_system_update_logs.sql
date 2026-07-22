USE foundation_system;

CREATE TABLE IF NOT EXISTS system_update_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  action VARCHAR(40) NOT NULL,
  status VARCHAR(40) NOT NULL,
  version_from VARCHAR(40) NULL,
  version_to VARCHAR(40) NULL,
  package_path VARCHAR(255) NULL,
  package_sha256 CHAR(64) NULL,
  message TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_system_update_logs_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_system_update_logs_created_at (created_at),
  INDEX idx_system_update_logs_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('system_updates.manage', '管理系統更新', 'system_updates', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'system_updates.manage'
WHERE roles.name = '系統管理員';
