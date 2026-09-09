-- 共用通訊錄:集中管理各學校、政府機關、合作單位、廠商等外部聯絡人。
-- 屬全機構共用資料,具檢視權限者皆可查閱,具管理權限者可新增／編輯／刪除。

CREATE TABLE IF NOT EXISTS contacts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  organization VARCHAR(160) NULL,
  job_title VARCHAR(120) NULL,
  category VARCHAR(60) NULL,
  phone VARCHAR(60) NULL,
  mobile VARCHAR(60) NULL,
  fax VARCHAR(60) NULL,
  email VARCHAR(190) NULL,
  address VARCHAR(255) NULL,
  notes TEXT NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_contacts_created_by
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_contacts_name (name),
  INDEX idx_contacts_organization (organization),
  INDEX idx_contacts_category (category),
  INDEX idx_contacts_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 權限:檢視(全員)與管理(新增／編輯／刪除)。
INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('contacts.view', '檢視通訊錄', 'contacts', NOW(), NOW()),
  ('contacts.manage', '管理通訊錄', 'contacts', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

-- 共用資料:所有角色(系統管理員／主管／行政人員／一般檢視者)皆可檢視。
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'contacts.view'
WHERE roles.id IN (1, 2, 3, 4);

-- 管理權限預設給系統管理員／主管／行政人員(可維護日常業務資料者)。
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'contacts.manage'
WHERE roles.id IN (1, 2, 3);

-- 範例資料(可依實際情況修改或刪除)。
INSERT INTO contacts (name, organization, job_title, category, phone, mobile, email, address, notes, status, created_by, created_at, updated_at) VALUES
  ('王小明', '新北市立范例國民小學', '總務主任', '學校', '(02)2222-3333', '0912-345-678', 'example01@school.ntpc.edu.tw', '新北市板橋區范例路 1 號', '教育推廣合作窗口。', 'active', NULL, NOW(), NOW()),
  ('李美華', '新北市政府教育局', '承辦人', '政府機關', '(02)2960-3456', NULL, 'example02@ntpc.gov.tw', '新北市板橋區中山路一段 161 號', '核備公文往來窗口。', 'active', NULL, NOW(), NOW());
