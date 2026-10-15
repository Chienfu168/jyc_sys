-- 學校通訊錄:以「學校」為中心的記錄,校長／主任等人員會隨任期更替,
-- 故人員資料獨立為 school_staff,保留現任與歷任(前任)紀錄,而非僅存當下一筆。

CREATE TABLE IF NOT EXISTS schools (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  category VARCHAR(60) NULL,
  address VARCHAR(255) NULL,
  phone VARCHAR(60) NULL,
  fax VARCHAR(60) NULL,
  notes TEXT NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_schools_created_by
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_schools_name (name),
  INDEX idx_schools_category (category),
  INDEX idx_schools_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 學校人員(校長、主任等):同一職稱於同校可能先後有多人擔任,以 status 區分
-- 現任／前任,新增現任時不刪除舊紀錄,改為「卸任」保留歷史。
CREATE TABLE IF NOT EXISTS school_staff (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(60) NOT NULL,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(60) NULL,
  mobile VARCHAR(60) NULL,
  email VARCHAR(190) NULL,
  status ENUM('current', 'former') NOT NULL DEFAULT 'current',
  started_on DATE NULL,
  ended_on DATE NULL,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_school_staff_school
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
  CONSTRAINT fk_school_staff_created_by
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_school_staff_school (school_id),
  INDEX idx_school_staff_status (status),
  INDEX idx_school_staff_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 權限:檢視(全員)與管理(新增／編輯／刪除)。
INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('schools.view', '檢視學校通訊錄', 'schools', NOW(), NOW()),
  ('schools.manage', '管理學校通訊錄', 'schools', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

-- 共用資料:所有角色(系統管理員／主管／行政人員／一般檢視者)皆可檢視。
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'schools.view'
WHERE roles.id IN (1, 2, 3, 4);

-- 管理權限預設給系統管理員／主管／行政人員(可維護日常業務資料者)。
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'schools.manage'
WHERE roles.id IN (1, 2, 3);
