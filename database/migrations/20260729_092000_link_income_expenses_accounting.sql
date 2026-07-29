ALTER TABLE income_expense_records
  ADD COLUMN accounting_voucher_id BIGINT UNSIGNED NULL AFTER bank_account_id,
  ADD INDEX idx_income_expense_accounting_voucher (accounting_voucher_id),
  ADD CONSTRAINT fk_income_expense_accounting_voucher
    FOREIGN KEY (accounting_voucher_id) REFERENCES accounting_vouchers(id)
    ON DELETE SET NULL;
