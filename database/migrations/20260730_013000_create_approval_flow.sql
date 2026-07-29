CREATE TABLE IF NOT EXISTS approval_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  module VARCHAR(80) NOT NULL,
  target_type VARCHAR(80) NOT NULL,
  target_id BIGINT UNSIGNED NOT NULL,
  action VARCHAR(40) NOT NULL,
  status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
  requested_by BIGINT UNSIGNED NULL,
  requested_at DATETIME NULL,
  request_notes TEXT NULL,
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  review_notes TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  INDEX idx_approval_requests_target (module, target_type, target_id),
  INDEX idx_approval_requests_status (status, requested_at),
  CONSTRAINT fk_approval_requests_requested_by
    FOREIGN KEY (requested_by) REFERENCES users(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_approval_requests_reviewed_by
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE income_expense_records
  ADD COLUMN approval_status ENUM('draft', 'submitted', 'approved', 'rejected') NOT NULL DEFAULT 'draft' AFTER status,
  ADD COLUMN submitted_at DATETIME NULL AFTER approval_status,
  ADD COLUMN reviewed_by BIGINT UNSIGNED NULL AFTER submitted_at,
  ADD COLUMN reviewed_at DATETIME NULL AFTER reviewed_by,
  ADD COLUMN review_notes TEXT NULL AFTER reviewed_at,
  ADD INDEX idx_income_expense_records_approval (approval_status),
  ADD CONSTRAINT fk_income_expense_records_reviewed_by
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
    ON DELETE SET NULL;

UPDATE income_expense_records
SET approval_status = CASE
    WHEN status = 'confirmed' THEN 'approved'
    WHEN status = 'voided' THEN 'rejected'
    ELSE 'draft'
  END,
  reviewed_at = CASE WHEN status = 'confirmed' THEN updated_at ELSE reviewed_at END
WHERE approval_status = 'draft';

INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('income_expenses.approve', '核准收支紀錄', 'income_expenses', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'income_expenses.approve'
WHERE roles.id IN (1, 2);
