ALTER TABLE bank_account_transactions
  ADD COLUMN reconciliation_status ENUM('unreconciled', 'reconciled', 'ignored') NOT NULL DEFAULT 'unreconciled' AFTER petty_cash_entry_id,
  ADD COLUMN reconciled_on DATE NULL AFTER reconciliation_status,
  ADD COLUMN reconciliation_note TEXT NULL AFTER reconciled_on,
  ADD COLUMN reconciled_by BIGINT UNSIGNED NULL AFTER reconciliation_note,
  ADD INDEX idx_bank_transactions_reconciliation (reconciliation_status, reconciled_on),
  ADD CONSTRAINT fk_bank_transactions_reconciled_by
    FOREIGN KEY (reconciled_by) REFERENCES users(id)
    ON DELETE SET NULL;
