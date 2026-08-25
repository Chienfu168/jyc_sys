-- 補齊尚無刪除功能的模組硬刪除權限,僅授予最高權限(系統管理員)。
-- 系統管理員為超級使用者(Permission::can 對其一律回傳 true),故不需另行授權;
-- 其餘角色未取得這些權限即無法刪除。權限仍寫入 permissions 表以便日後於角色權限頁調整。
INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('users.delete', '刪除使用者', 'users', NOW(), NOW()),
  ('roles.delete', '刪除角色', 'roles', NOW(), NOW()),
  ('accounting.delete', '刪除會計科目與傳票', 'accounting', NOW(), NOW()),
  ('opening_balances.delete', '刪除期初餘額', 'opening_balances', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();
