-- 出差費用「併入當月薪資發放」整合:
-- 出差費在帳上仍為差旅費(不計入課稅薪資),但可標記為「隨當月薪資一起發放」,
-- 連結到該員工當月薪資紀錄;薪資單另列「差旅費代墊核銷」一行,匯款時合併發放。
ALTER TABLE travel_expenses
  ADD COLUMN settlement_method VARCHAR(20) NOT NULL DEFAULT 'separate' AFTER payment_status,
  ADD COLUMN payroll_record_id BIGINT UNSIGNED NULL AFTER settlement_method,
  ADD COLUMN payroll_month CHAR(7) NULL AFTER payroll_record_id,
  ADD INDEX idx_travel_expenses_payroll (payroll_record_id),
  ADD CONSTRAINT fk_travel_expenses_payroll_record
    FOREIGN KEY (payroll_record_id) REFERENCES payroll_records(id) ON DELETE SET NULL;

-- 薪資紀錄新增「差旅費代墊核銷」金額:隨薪資一起發放的出差費合計(非課稅薪資、不影響應發/實發)。
ALTER TABLE payroll_records
  ADD COLUMN travel_reimbursement DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER net_pay;
