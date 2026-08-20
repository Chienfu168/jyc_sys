INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('roles.manage', '管理角色權限', 'roles', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'roles.manage'
WHERE roles.name = '系統管理員';

ALTER TABLE accounting_vouchers
  ADD UNIQUE KEY uk_accounting_vouchers_source_unique (source_type, source_id);
