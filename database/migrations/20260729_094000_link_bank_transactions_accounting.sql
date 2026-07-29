ALTER TABLE bank_account_transactions
  ADD COLUMN accounting_voucher_id BIGINT UNSIGNED NULL AFTER petty_cash_entry_id,
  ADD INDEX idx_bank_transactions_accounting_voucher (accounting_voucher_id),
  ADD CONSTRAINT fk_bank_transactions_accounting_voucher
    FOREIGN KEY (accounting_voucher_id) REFERENCES accounting_vouchers(id)
    ON DELETE SET NULL;
