INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('accounting.view', '檢視會計系統', 'accounting', NOW(), NOW()),
  ('accounting.manage', '管理會計系統', 'accounting', NOW(), NOW()),
  ('petty_cash.view', '檢視零用金', 'petty_cash', NOW(), NOW()),
  ('petty_cash.manage', '管理零用金', 'petty_cash', NOW(), NOW()),
  ('income_expenses.view', '檢視收支紀錄', 'income_expenses', NOW(), NOW()),
  ('income_expenses.manage', '管理收支紀錄', 'income_expenses', NOW(), NOW()),
  ('lecturer_expenses.view', '檢視講師支出費用', 'lecturer_expenses', NOW(), NOW()),
  ('lecturer_expenses.manage', '管理講師支出費用', 'lecturer_expenses', NOW(), NOW()),
  ('travel_expenses.view', '檢視出差費用', 'travel_expenses', NOW(), NOW()),
  ('travel_expenses.manage', '管理出差費用', 'travel_expenses', NOW(), NOW()),
  ('payroll.view', '檢視薪資管理', 'payroll', NOW(), NOW()),
  ('payroll.manage', '管理薪資管理', 'payroll', NOW(), NOW()),
  ('leave_requests.view', '檢視人事請假', 'leave_requests', NOW(), NOW()),
  ('leave_requests.manage', '管理人事請假', 'leave_requests', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN (
  'accounting.view',
  'accounting.manage',
  'petty_cash.view',
  'petty_cash.manage',
  'income_expenses.view',
  'income_expenses.manage',
  'lecturer_expenses.view',
  'lecturer_expenses.manage',
  'travel_expenses.view',
  'travel_expenses.manage',
  'payroll.view',
  'payroll.manage',
  'leave_requests.view',
  'leave_requests.manage'
)
WHERE roles.name = '系統管理員';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN (
  'accounting.view',
  'petty_cash.view',
  'income_expenses.view',
  'lecturer_expenses.view',
  'travel_expenses.view',
  'payroll.view',
  'leave_requests.view'
)
WHERE roles.name = '主管';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN (
  'accounting.view',
  'accounting.manage',
  'petty_cash.view',
  'petty_cash.manage',
  'income_expenses.view',
  'income_expenses.manage',
  'lecturer_expenses.view',
  'lecturer_expenses.manage',
  'travel_expenses.view',
  'travel_expenses.manage',
  'leave_requests.view',
  'leave_requests.manage'
)
WHERE roles.name = '行政人員';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN (
  'income_expenses.view',
  'leave_requests.view'
)
WHERE roles.name = '一般檢視者';
