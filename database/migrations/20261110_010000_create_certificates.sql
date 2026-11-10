-- 聘書／感謝狀:固定稱謂/用語寫死於程式碼範本(App\Domain\Certificates\CertificateTemplates),
-- 本表僅保存每次開立的最終正文與開立資訊,供查詢、重印、複製再開立使用。

CREATE TABLE IF NOT EXISTS certificates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  template_key VARCHAR(60) NOT NULL,
  variant_key VARCHAR(60) NOT NULL,
  title VARCHAR(60) NOT NULL,
  recipient_name VARCHAR(160) NOT NULL,
  body_text TEXT NOT NULL,
  term_no SMALLINT UNSIGNED NULL,
  term_start DATE NULL,
  term_end DATE NULL,
  issued_on DATE NOT NULL,
  signer_title VARCHAR(60) NOT NULL DEFAULT '董事長',
  signer_name VARCHAR(120) NOT NULL DEFAULT '',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_certificates_created_by
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_certificates_template (template_key),
  INDEX idx_certificates_recipient (recipient_name),
  INDEX idx_certificates_issued (issued_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('certificates.view', '檢視聘書與感謝狀', 'certificates', NOW(), NOW()),
  ('certificates.manage', '管理聘書與感謝狀', 'certificates', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('certificates.view', 'certificates.manage')
WHERE roles.name = '系統管理員';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('certificates.view', 'certificates.manage')
WHERE roles.name = '行政人員';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'certificates.view'
WHERE roles.name = '主管';
