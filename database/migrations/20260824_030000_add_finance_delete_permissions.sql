-- 捐款與零用金的「刪除」權限:一般作業仍以作廢/退回保留軌跡,
-- 硬刪除為不可復原的高權限動作,僅授予系統管理員。
INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('donations.delete', '刪除捐款紀錄', 'donations', NOW(), NOW()),
  ('petty_cash.delete', '刪除零用金紀錄', 'petty_cash', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('donations.delete', 'petty_cash.delete')
WHERE roles.name = '系統管理員';
