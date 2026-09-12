-- 員工專案及創新成果獎勵申請:依「員工專案及創新成果獎勵辦法」提出專案成果獎勵金申請,
-- 含申請事由、獎勵人員及金額(多筆)、獎勵理由、經費來源與擬辦,可列印申請書供用印陳核。

CREATE TABLE IF NOT EXISTS reward_applications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  application_no VARCHAR(40) NOT NULL,
  title VARCHAR(200) NOT NULL,
  award_reason VARCHAR(500) NULL,
  apply_date DATE NOT NULL,
  reason TEXT NULL,
  justification TEXT NULL,
  funding_source TEXT NULL,
  proposed_action TEXT NULL,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  status ENUM('draft', 'submitted', 'approved', 'rejected') NOT NULL DEFAULT 'draft',
  applicant_id BIGINT UNSIGNED NULL,
  submitted_at DATETIME NULL,
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  review_notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_reward_applications_no (application_no),
  INDEX idx_reward_applications_status (status),
  INDEX idx_reward_applications_apply_date (apply_date),
  CONSTRAINT fk_reward_applications_applicant
    FOREIGN KEY (applicant_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_reward_applications_reviewed_by
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_reward_applications_created_by
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reward_application_recipients (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reward_application_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  job_title VARCHAR(120) NULL,
  contribution TEXT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_reward_recipients_application
    FOREIGN KEY (reward_application_id) REFERENCES reward_applications(id) ON DELETE CASCADE,
  INDEX idx_reward_recipients_application (reward_application_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 權限:檢視、管理(新增／編輯／刪除)、核定。
INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('rewards.view', '檢視員工獎勵申請', 'rewards', NOW(), NOW()),
  ('rewards.manage', '管理員工獎勵申請', 'rewards', NOW(), NOW()),
  ('rewards.approve', '核定員工獎勵申請', 'rewards', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

-- 檢視／管理預設給系統管理員／主管／行政人員。
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('rewards.view', 'rewards.manage')
WHERE roles.id IN (1, 2, 3);

-- 核定預設給系統管理員／主管。
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'rewards.approve'
WHERE roles.id IN (1, 2);
