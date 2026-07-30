ALTER TABLE annual_budget_items
  ADD COLUMN gov_level1 VARCHAR(20) NULL AFTER account_id,
  ADD COLUMN gov_level2 VARCHAR(20) NULL AFTER gov_level1,
  ADD COLUMN gov_level3 VARCHAR(20) NULL AFTER gov_level2,
  ADD COLUMN gov_level4 VARCHAR(20) NULL AFTER gov_level3,
  ADD COLUMN gov_level5 VARCHAR(20) NULL AFTER gov_level4,
  ADD COLUMN previous_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER amount,
  ADD COLUMN comparison_note VARCHAR(255) NULL AFTER previous_amount,
  ADD COLUMN is_subtotal TINYINT(1) NOT NULL DEFAULT 0 AFTER comparison_note,
  ADD INDEX idx_annual_budget_items_statement (annual_budget_id, item_type, gov_level1, gov_level2, gov_level3, gov_level4, gov_level5);
