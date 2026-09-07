-- 員工薪資基本資料:每位員工固定的月薪資與勞健保、勞退、雇主負擔金額,
-- 作為「最新基本資料」,建立新月份薪資時自動帶出以減少重複輸入。
CREATE TABLE IF NOT EXISTS employee_payroll_defaults (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id BIGINT UNSIGNED NOT NULL,
  base_salary DECIMAL(14,2) NOT NULL DEFAULT 0,
  allowance_total DECIMAL(14,2) NOT NULL DEFAULT 0,
  labor_insurance_deduction DECIMAL(14,2) NOT NULL DEFAULT 0,
  health_insurance_deduction DECIMAL(14,2) NOT NULL DEFAULT 0,
  pension_self_deduction DECIMAL(14,2) NOT NULL DEFAULT 0,
  employer_pension DECIMAL(14,2) NOT NULL DEFAULT 0,
  employer_labor_insurance DECIMAL(14,2) NOT NULL DEFAULT 0,
  employer_health_insurance DECIMAL(14,2) NOT NULL DEFAULT 0,
  occupational_insurance DECIMAL(14,2) NOT NULL DEFAULT 0,
  notes VARCHAR(255) NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_employee_payroll_defaults_employee (employee_id),
  CONSTRAINT fk_employee_payroll_defaults_employee
    FOREIGN KEY (employee_id) REFERENCES personnel_employees(id) ON DELETE CASCADE,
  CONSTRAINT fk_employee_payroll_defaults_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
