-- 基金會章程管理:以單一資料表同時保存「歷史章程」的紀錄與編修,以及「現行章程」
-- (含經主管機關教育局核備之文號/日期)。status 標示現行(current)／歷史(historical),
-- 新增或編輯為現行時由程式邏輯確保僅有一筆現行(自動將原現行版本降為歷史)。
-- 每個版本可附加一份核備章程掃描檔(PDF／圖片),沿用既有模組的私有上傳目錄慣例。

CREATE TABLE IF NOT EXISTS charters (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  revision_no SMALLINT UNSIGNED NULL,
  title VARCHAR(160) NOT NULL,
  enacted_on DATE NULL,
  approval_authority VARCHAR(160) NULL,
  approval_doc_no VARCHAR(120) NULL,
  approval_date DATE NULL,
  status ENUM('current', 'historical') NOT NULL DEFAULT 'historical',
  amendment_summary TEXT NULL,
  content LONGTEXT NULL,
  original_name VARCHAR(255) NULL,
  stored_path VARCHAR(255) NULL,
  mime_type VARCHAR(120) NULL,
  file_size BIGINT UNSIGNED NULL,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_charters_created_by
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_charters_status (status),
  INDEX idx_charters_revision (revision_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('charters.view', '檢視基金會章程', 'charters', NOW(), NOW()),
  ('charters.manage', '管理基金會章程', 'charters', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('charters.view', 'charters.manage')
WHERE roles.name = '系統管理員';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('charters.view', 'charters.manage')
WHERE roles.name = '行政人員';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'charters.view'
WHERE roles.name = '主管';
