ALTER TABLE income_expense_records
  ADD COLUMN project_id BIGINT UNSIGNED NULL AFTER bank_account_id,
  ADD INDEX idx_income_expense_records_project_id (project_id),
  ADD CONSTRAINT fk_income_expense_records_project
    FOREIGN KEY (project_id) REFERENCES projects(id)
    ON DELETE SET NULL;

UPDATE income_expense_records
INNER JOIN projects ON projects.name = income_expense_records.project_name
SET income_expense_records.project_id = projects.id
WHERE income_expense_records.project_id IS NULL
  AND income_expense_records.project_name IS NOT NULL
  AND income_expense_records.project_name != '';

ALTER TABLE petty_cash_entries
  ADD COLUMN project_id BIGINT UNSIGNED NULL AFTER petty_cash_item_id,
  ADD INDEX idx_petty_cash_entries_project_id (project_id),
  ADD CONSTRAINT fk_petty_cash_entries_project
    FOREIGN KEY (project_id) REFERENCES projects(id)
    ON DELETE SET NULL;
