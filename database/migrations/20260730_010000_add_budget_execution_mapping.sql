ALTER TABLE annual_budget_items
  ADD COLUMN account_id BIGINT UNSIGNED NULL AFTER item_type,
  ADD INDEX idx_annual_budget_items_account (account_id),
  ADD CONSTRAINT fk_annual_budget_items_account
    FOREIGN KEY (account_id) REFERENCES accounting_accounts(id)
    ON DELETE SET NULL;
