-- 費用申請可一次申請多筆費用項目（例:車資1、車資2…）。
-- 明細存於 expense_request_items;expense_requests.item_name/amount 保留為彙總（首項名稱＋等N項、總金額），
-- 供清單顯示與核定併入零用金沿用,不需改動既有帳務流程。

CREATE TABLE IF NOT EXISTS expense_request_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  expense_request_id BIGINT UNSIGNED NOT NULL,
  petty_cash_item_id BIGINT UNSIGNED NULL,
  item_name VARCHAR(160) NOT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_expense_request_items_request
    FOREIGN KEY (expense_request_id) REFERENCES expense_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_expense_request_items_item
    FOREIGN KEY (petty_cash_item_id) REFERENCES petty_cash_items(id) ON DELETE SET NULL,
  INDEX idx_expense_request_items_request (expense_request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 既有單筆申請回填為一筆明細,確保舊資料在明細列表中正常顯示。
INSERT INTO expense_request_items (expense_request_id, petty_cash_item_id, item_name, amount, sort_order, created_at)
SELECT er.id, er.petty_cash_item_id, er.item_name, er.amount, 0, COALESCE(er.created_at, NOW())
FROM expense_requests er
LEFT JOIN expense_request_items eri ON eri.expense_request_id = er.id
WHERE eri.id IS NULL;
