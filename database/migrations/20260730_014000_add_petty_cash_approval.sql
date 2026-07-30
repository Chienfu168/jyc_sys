ALTER TABLE petty_cash_entries
  ADD COLUMN approval_status ENUM('draft', 'submitted', 'approved', 'rejected') NOT NULL DEFAULT 'draft' AFTER notes,
  ADD COLUMN submitted_at DATETIME NULL AFTER approval_status,
  ADD COLUMN reviewed_by BIGINT UNSIGNED NULL AFTER submitted_at,
  ADD COLUMN reviewed_at DATETIME NULL AFTER reviewed_by,
  ADD COLUMN review_notes TEXT NULL AFTER reviewed_at,
  ADD INDEX idx_petty_cash_entries_approval (approval_status),
  ADD CONSTRAINT fk_petty_cash_entries_reviewed_by
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
    ON DELETE SET NULL;

UPDATE petty_cash_entries
SET approval_status = 'approved',
    reviewed_at = COALESCE(updated_at, created_at)
WHERE approval_status = 'draft';

INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('petty_cash.approve', '核准零用金', 'petty_cash', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'petty_cash.approve'
WHERE roles.id IN (1, 2);
