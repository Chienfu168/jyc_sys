INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
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
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN (
  'work_plans.view',
  'work_plans.manage',
  'personnel.view',
  'personnel.manage',
  'projects.view',
  'projects.manage',
  'lecturers.view',
  'lecturers.manage',
  'calendar.view',
  'calendar.manage'
)
WHERE roles.name = '系統管理員';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN (
  'work_plans.view',
  'personnel.view',
  'projects.view',
  'lecturers.view',
  'calendar.view'
)
WHERE roles.name = '主管';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN (
  'work_plans.view',
  'work_plans.manage',
  'projects.view',
  'projects.manage',
  'lecturers.view',
  'lecturers.manage',
  'calendar.view',
  'calendar.manage'
)
WHERE roles.name = '行政人員';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN (
  'work_plans.view',
  'projects.view',
  'lecturers.view',
  'calendar.view'
)
WHERE roles.name = '一般檢視者';
