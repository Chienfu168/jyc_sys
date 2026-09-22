-- 年度預算明細:小計 / 合計列的「上年度預算數」是否自行輸入(1)或自動加總(0)。
-- 因本年度可能未編列而上年度有的項目,允許上年度小計彈性自行輸入,不受自動加總覆寫。
ALTER TABLE annual_budget_items
  ADD COLUMN previous_is_manual TINYINT(1) NOT NULL DEFAULT 0 AFTER is_subtotal;
