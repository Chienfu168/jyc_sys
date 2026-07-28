CREATE TABLE IF NOT EXISTS bank_accounts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bank_name VARCHAR(120) NOT NULL,
  branch_name VARCHAR(120) NULL,
  account_name VARCHAR(160) NOT NULL,
  account_no VARCHAR(80) NOT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'TWD',
  opening_balance DECIMAL(14,2) NOT NULL DEFAULT 0,
  notes TEXT NULL,
  status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_bank_accounts_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  UNIQUE KEY uk_bank_accounts_bank_account (bank_name, account_no),
  INDEX idx_bank_accounts_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bank_account_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bank_account_id BIGINT UNSIGNED NOT NULL,
  transacted_on DATE NOT NULL,
  transaction_type ENUM('deposit', 'withdrawal', 'transfer_to_petty_cash', 'fee', 'interest') NOT NULL,
  subject VARCHAR(160) NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  reference_no VARCHAR(100) NULL,
  counterparty VARCHAR(160) NULL,
  notes TEXT NULL,
  petty_cash_entry_id BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_bank_transactions_account
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_bank_transactions_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_bank_transactions_account_date (bank_account_id, transacted_on),
  INDEX idx_bank_transactions_type (transaction_type),
  INDEX idx_bank_transactions_petty_cash (petty_cash_entry_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE petty_cash_entries
  ADD COLUMN bank_account_id BIGINT UNSIGNED NULL AFTER petty_cash_item_id,
  ADD COLUMN bank_account_transaction_id BIGINT UNSIGNED NULL AFTER bank_account_id,
  ADD CONSTRAINT fk_petty_cash_entries_bank_account
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id)
    ON DELETE SET NULL,
  ADD CONSTRAINT fk_petty_cash_entries_bank_transaction
    FOREIGN KEY (bank_account_transaction_id) REFERENCES bank_account_transactions(id)
    ON DELETE SET NULL;

INSERT INTO petty_cash_items (item_type, name, default_amount, sort_order, status, created_at, updated_at) VALUES
  ('income', '銀行撥補零用金', NULL, 5, 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  sort_order = VALUES(sort_order),
  status = VALUES(status),
  updated_at = NOW();

INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('bank_accounts.view', '檢視銀行帳戶', 'bank_accounts', NOW(), NOW()),
  ('bank_accounts.manage', '管理銀行帳戶', 'bank_accounts', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions
WHERE code IN ('bank_accounts.view', 'bank_accounts.manage');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions
WHERE code IN ('bank_accounts.view');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions
WHERE code IN ('bank_accounts.view', 'bank_accounts.manage');
