USE foundation_system;

INSERT INTO roles (id, name, description, created_at, updated_at) VALUES
  (1, '系統管理員', '可管理全系統功能與設定', NOW(), NOW()),
  (2, '主管', '可檢視管理資料並執行審核', NOW(), NOW()),
  (3, '行政人員', '可維護日常業務資料', NOW(), NOW()),
  (4, '一般檢視者', '僅可檢視授權資料', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  description = VALUES(description),
  updated_at = NOW();

INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('dashboard.view', '檢視總儀表板', 'dashboard', NOW(), NOW()),
  ('users.view', '檢視使用者', 'users', NOW(), NOW()),
  ('users.create', '新增使用者', 'users', NOW(), NOW()),
  ('users.update', '編輯使用者', 'users', NOW(), NOW()),
  ('roles.view', '檢視角色權限', 'roles', NOW(), NOW()),
  ('donors.view', '檢視捐款人', 'donors', NOW(), NOW()),
  ('donors.manage', '管理捐款人', 'donors', NOW(), NOW()),
  ('donations.view', '檢視捐款紀錄', 'donations', NOW(), NOW()),
  ('donations.manage', '管理捐款紀錄', 'donations', NOW(), NOW()),
  ('activities.view', '檢視活動管理', 'activities', NOW(), NOW()),
  ('activities.manage', '管理活動管理', 'activities', NOW(), NOW()),
  ('volunteers.view', '檢視志工', 'volunteers', NOW(), NOW()),
  ('volunteers.manage', '管理志工', 'volunteers', NOW(), NOW()),
  ('documents.view', '檢視文件', 'documents', NOW(), NOW()),
  ('documents.manage', '管理文件', 'documents', NOW(), NOW()),
  ('reports.view', '檢視報表', 'reports', NOW(), NOW()),
  ('reports.export', '匯出報表', 'reports', NOW(), NOW()),
  ('settings.manage', '管理系統設定', 'settings', NOW(), NOW()),
  ('system_updates.manage', '管理系統更新', 'system_updates', NOW(), NOW()),
  ('annual_budgets.view', '檢視年度預算', 'annual_budgets', NOW(), NOW()),
  ('annual_budgets.manage', '管理年度預算', 'annual_budgets', NOW(), NOW()),
  ('annual_budgets.approve', '核定年度預算', 'annual_budgets', NOW(), NOW()),
  ('work_plans.view', '檢視工作計畫', 'work_plans', NOW(), NOW()),
  ('work_plans.manage', '管理工作計畫', 'work_plans', NOW(), NOW()),
  ('personnel.view', '檢視人事管理', 'personnel', NOW(), NOW()),
  ('personnel.manage', '管理人事管理', 'personnel', NOW(), NOW()),
  ('projects.view', '檢視專案管理', 'projects', NOW(), NOW()),
  ('projects.manage', '管理專案管理', 'projects', NOW(), NOW()),
  ('lecturers.view', '檢視講師管理', 'lecturers', NOW(), NOW()),
  ('lecturers.manage', '管理講師管理', 'lecturers', NOW(), NOW()),
  ('calendar.view', '檢視行事曆管理', 'calendar', NOW(), NOW()),
  ('calendar.manage', '管理行事曆管理', 'calendar', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions
WHERE code IN (
  'dashboard.view',
  'users.view',
  'roles.view',
  'donors.view',
  'donations.view',
  'activities.view',
  'volunteers.view',
  'documents.view',
  'reports.view',
  'reports.export',
  'annual_budgets.view',
  'annual_budgets.approve',
  'work_plans.view',
  'personnel.view',
  'projects.view',
  'lecturers.view',
  'calendar.view'
);

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions
WHERE code IN (
  'dashboard.view',
  'donors.view',
  'donors.manage',
  'donations.view',
  'donations.manage',
  'activities.view',
  'activities.manage',
  'volunteers.view',
  'volunteers.manage',
  'documents.view',
  'documents.manage',
  'annual_budgets.view',
  'annual_budgets.manage',
  'work_plans.view',
  'work_plans.manage',
  'projects.view',
  'projects.manage',
  'lecturers.view',
  'lecturers.manage',
  'calendar.view',
  'calendar.manage'
);

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions
WHERE code IN (
  'dashboard.view',
  'donors.view',
  'donations.view',
  'activities.view',
  'volunteers.view',
  'documents.view',
  'reports.view',
  'annual_budgets.view',
  'work_plans.view',
  'projects.view',
  'lecturers.view',
  'calendar.view'
);
