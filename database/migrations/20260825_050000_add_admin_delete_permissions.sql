-- 全面新增各模組硬刪除權限,僅授予最高權限(系統管理員)。
-- 系統管理員為超級使用者(Permission::can 對其一律回傳 true),故不需另行授權;
-- 其餘角色未取得這些權限即無法刪除。權限仍寫入 permissions 表以便日後於角色權限頁調整。
INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('donors.delete', '刪除捐款人', 'donors', NOW(), NOW()),
  ('work_plans.delete', '刪除工作計畫', 'work_plans', NOW(), NOW()),
  ('annual_budgets.delete', '刪除年度預算', 'annual_budgets', NOW(), NOW()),
  ('foundation_assets.delete', '刪除財產', 'foundation_assets', NOW(), NOW()),
  ('income_expenses.delete', '刪除收支紀錄', 'income_expenses', NOW(), NOW()),
  ('lecturer_expenses.delete', '刪除講師支出費用', 'lecturer_expenses', NOW(), NOW()),
  ('travel_expenses.delete', '刪除出差費用', 'travel_expenses', NOW(), NOW()),
  ('payroll.delete', '刪除薪資紀錄', 'payroll', NOW(), NOW()),
  ('payment_receipts.delete', '刪除領款收據', 'payment_receipts', NOW(), NOW()),
  ('bank_accounts.delete', '刪除銀行帳戶', 'bank_accounts', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();
