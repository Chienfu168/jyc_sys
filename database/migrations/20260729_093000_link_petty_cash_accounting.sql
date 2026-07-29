SET @asset_parent_id = (SELECT id FROM accounting_accounts WHERE code = '1100' LIMIT 1);

INSERT INTO accounting_accounts (code, name, account_type, parent_id, normal_balance, description, sort_order, status, created_at, updated_at)
SELECT '1110', '零用金', 'asset', @asset_parent_id, 'debit', '基金會零用金與週轉金', 1110, 'active', NOW(), NOW()
WHERE @asset_parent_id IS NOT NULL
ON DUPLICATE KEY UPDATE parent_id = VALUES(parent_id), updated_at = NOW();

ALTER TABLE petty_cash_entries
  ADD COLUMN accounting_voucher_id BIGINT UNSIGNED NULL AFTER bank_account_transaction_id,
  ADD INDEX idx_petty_cash_accounting_voucher (accounting_voucher_id),
  ADD CONSTRAINT fk_petty_cash_entries_accounting_voucher
    FOREIGN KEY (accounting_voucher_id) REFERENCES accounting_vouchers(id)
    ON DELETE SET NULL;
