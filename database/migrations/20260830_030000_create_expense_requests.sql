-- 員工費用申請（代墊／請款核銷）：員工提出小額代墊費用申請，核定後併入零用金，
-- 由會計確認後以現金或匯款支付給申請者。

CREATE TABLE IF NOT EXISTS expense_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_no VARCHAR(40) NOT NULL,
  applicant_id BIGINT UNSIGNED NULL,
  occurred_on DATE NOT NULL,
  petty_cash_item_id BIGINT UNSIGNED NULL,
  item_name VARCHAR(160) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  reason TEXT NULL,
  payment_type ENUM('bank', 'cash') NOT NULL DEFAULT 'cash',
  bank_name VARCHAR(120) NULL,
  bank_branch VARCHAR(120) NULL,
  bank_account VARCHAR(60) NULL,
  bank_account_name VARCHAR(120) NULL,
  status ENUM('draft', 'submitted', 'approved', 'rejected', 'paid') NOT NULL DEFAULT 'draft',
  submitted_at DATETIME NULL,
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  review_notes TEXT NULL,
  petty_cash_entry_id BIGINT UNSIGNED NULL,
  paid_by BIGINT UNSIGNED NULL,
  paid_at DATETIME NULL,
  paid_method ENUM('bank', 'cash') NULL,
  payment_notes VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_expense_requests_no (request_no),
  INDEX idx_expense_requests_status (status),
  INDEX idx_expense_requests_applicant (applicant_id),
  INDEX idx_expense_requests_occurred (occurred_on),
  CONSTRAINT fk_expense_requests_applicant
    FOREIGN KEY (applicant_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_expense_requests_item
    FOREIGN KEY (petty_cash_item_id) REFERENCES petty_cash_items(id) ON DELETE SET NULL,
  CONSTRAINT fk_expense_requests_reviewed_by
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_expense_requests_petty_cash
    FOREIGN KEY (petty_cash_entry_id) REFERENCES petty_cash_entries(id) ON DELETE SET NULL,
  CONSTRAINT fk_expense_requests_paid_by
    FOREIGN KEY (paid_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_expense_requests_created_by
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS expense_request_attachments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  expense_request_id BIGINT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_path VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NULL,
  file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
  uploaded_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_expense_request_attachments_request
    FOREIGN KEY (expense_request_id) REFERENCES expense_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_expense_request_attachments_uploader
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_expense_request_attachments_request (expense_request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 權限：檢視／建立自己的申請（一般員工）、核定、付款。
INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('expense_requests.view', '檢視與申請費用', 'expense_requests', NOW(), NOW()),
  ('expense_requests.approve', '核定費用申請', 'expense_requests', NOW(), NOW()),
  ('expense_requests.pay', '付款費用申請', 'expense_requests', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

-- 一般員工（系統管理員／主管／行政人員）皆可提出申請。
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'expense_requests.view'
WHERE roles.id IN (1, 2, 3);

-- 核定與付款預設給系統管理員與主管（會計可由管理員於角色權限中另行授予）。
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('expense_requests.approve', 'expense_requests.pay')
WHERE roles.id IN (1, 2);
