ALTER TABLE lecturer_expenses
  ADD COLUMN project_id BIGINT UNSIGNED NULL AFTER service_unit,
  ADD INDEX idx_lecturer_expenses_project_id (project_id),
  ADD CONSTRAINT fk_lecturer_expenses_project
    FOREIGN KEY (project_id) REFERENCES projects(id)
    ON DELETE SET NULL;

UPDATE lecturer_expenses
INNER JOIN projects ON projects.name = lecturer_expenses.project_name
SET lecturer_expenses.project_id = projects.id
WHERE lecturer_expenses.project_id IS NULL
  AND lecturer_expenses.project_name IS NOT NULL
  AND lecturer_expenses.project_name != '';

ALTER TABLE travel_expenses
  ADD COLUMN project_id BIGINT UNSIGNED NULL AFTER purpose,
  ADD INDEX idx_travel_expenses_project_id (project_id),
  ADD CONSTRAINT fk_travel_expenses_project
    FOREIGN KEY (project_id) REFERENCES projects(id)
    ON DELETE SET NULL;

UPDATE travel_expenses
INNER JOIN projects ON projects.name = travel_expenses.project_name
SET travel_expenses.project_id = projects.id
WHERE travel_expenses.project_id IS NULL
  AND travel_expenses.project_name IS NOT NULL
  AND travel_expenses.project_name != '';

ALTER TABLE payroll_records
  ADD COLUMN project_id BIGINT UNSIGNED NULL AFTER bank_account_id,
  ADD INDEX idx_payroll_records_project_id (project_id),
  ADD CONSTRAINT fk_payroll_records_project
    FOREIGN KEY (project_id) REFERENCES projects(id)
    ON DELETE SET NULL;
