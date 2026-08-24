-- 陳報主管機關公文(函):參考新北市教育局基金會函範例格式,
-- 用於陳報年度工作計畫、經費預算表、董事會議紀錄等文件予主管機關。
CREATE TABLE IF NOT EXISTS official_letters (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fiscal_year SMALLINT UNSIGNED NOT NULL,
  letter_number VARCHAR(60) NULL,
  letter_date DATE NOT NULL,
  recipient VARCHAR(160) NOT NULL,
  urgency VARCHAR(40) NOT NULL DEFAULT '普通件',
  confidentiality VARCHAR(80) NULL,
  attachment_note VARCHAR(80) NULL,
  subject TEXT NOT NULL,
  basis_lines TEXT NULL,
  attachment_intro VARCHAR(160) NULL,
  attachment_items TEXT NULL,
  signer_title VARCHAR(60) NOT NULL DEFAULT '董事長',
  signer_name VARCHAR(120) NULL,
  status ENUM('draft', 'issued') NOT NULL DEFAULT 'draft',
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  INDEX idx_official_letters_year (fiscal_year),
  CONSTRAINT fk_official_letters_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('official_letters.view', '檢視陳報公文', 'official_letters', NOW(), NOW()),
  ('official_letters.manage', '管理陳報公文', 'official_letters', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('official_letters.view', 'official_letters.manage')
WHERE roles.name = '系統管理員';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('official_letters.view', 'official_letters.manage')
WHERE roles.name = '行政人員';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'official_letters.view'
WHERE roles.name = '主管';
