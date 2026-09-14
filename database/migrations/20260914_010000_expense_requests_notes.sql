-- 費用申請增加「備註」欄位。
-- reason(事由／說明)為基本事由;notes(備註)供補充說明,可長可短。

ALTER TABLE expense_requests
  ADD COLUMN notes TEXT NULL AFTER reason;
