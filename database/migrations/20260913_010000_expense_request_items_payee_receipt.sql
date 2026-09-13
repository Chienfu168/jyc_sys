-- 費用明細每筆增加「給誰（廠商／對象）」與「憑證類型（發票／收據／無）」。
-- payee 記錄該筆費用實際支付的對象（例:遠振資訊科技）;
-- receipt_type 記錄該筆是否附有憑證及其種類:invoice=發票、receipt=收據、none=無。
-- 皆為明細層資訊,不影響核定併入零用金的既有帳務流程。

ALTER TABLE expense_request_items
  ADD COLUMN payee VARCHAR(160) NULL AFTER item_name;

ALTER TABLE expense_request_items
  ADD COLUMN receipt_type VARCHAR(16) NOT NULL DEFAULT 'none' AFTER payee;
