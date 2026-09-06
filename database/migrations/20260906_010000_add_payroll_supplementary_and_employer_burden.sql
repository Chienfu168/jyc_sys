-- 薪資表補強:二代健保(員工代扣)與雇主負擔明細(勞保/健保/職保),
-- 讓系統能完整呈現月薪資表的代扣項目與真實用人成本(總負擔 = 勞退6% + 勞健保職保雇主負擔)。
ALTER TABLE payroll_records
  ADD COLUMN supplementary_premium DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER other_deduction,
  ADD COLUMN employer_labor_insurance DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER employer_pension,
  ADD COLUMN employer_health_insurance DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER employer_labor_insurance,
  ADD COLUMN occupational_insurance DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER employer_health_insurance;
