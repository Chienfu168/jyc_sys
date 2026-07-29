CREATE TABLE IF NOT EXISTS payroll_records (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id BIGINT UNSIGNED NOT NULL,
  payroll_month CHAR(7) NOT NULL,
  pay_date DATE NULL,
  base_salary DECIMAL(14,2) NOT NULL DEFAULT 0,
  allowance_total DECIMAL(14,2) NOT NULL DEFAULT 0,
  overtime_pay DECIMAL(14,2) NOT NULL DEFAULT 0,
  bonus DECIMAL(14,2) NOT NULL DEFAULT 0,
  gross_pay DECIMAL(14,2) NOT NULL DEFAULT 0,
  labor_insurance_deduction DECIMAL(14,2) NOT NULL DEFAULT 0,
  health_insurance_deduction DECIMAL(14,2) NOT NULL DEFAULT 0,
  pension_self_deduction DECIMAL(14,2) NOT NULL DEFAULT 0,
  income_tax DECIMAL(14,2) NOT NULL DEFAULT 0,
  leave_deduction DECIMAL(14,2) NOT NULL DEFAULT 0,
  other_deduction DECIMAL(14,2) NOT NULL DEFAULT 0,
  deduction_total DECIMAL(14,2) NOT NULL DEFAULT 0,
  net_pay DECIMAL(14,2) NOT NULL DEFAULT 0,
  employer_pension DECIMAL(14,2) NOT NULL DEFAULT 0,
  payment_method VARCHAR(60) NULL,
  payment_status ENUM('draft', 'confirmed', 'paid', 'voided') NOT NULL DEFAULT 'draft',
  paid_on DATE NULL,
  bank_account_id BIGINT UNSIGNED NULL,
  accounting_voucher_id BIGINT UNSIGNED NULL,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_payroll_records_employee_month (employee_id, payroll_month),
  INDEX idx_payroll_records_month_status (payroll_month, payment_status),
  CONSTRAINT fk_payroll_records_employee
    FOREIGN KEY (employee_id) REFERENCES personnel_employees(id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_payroll_records_bank_account
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_payroll_records_accounting_voucher
    FOREIGN KEY (accounting_voucher_id) REFERENCES accounting_vouchers(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_payroll_records_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO payroll_records
  (employee_id, payroll_month, pay_date, base_salary, allowance_total, overtime_pay, bonus, gross_pay, labor_insurance_deduction, health_insurance_deduction, pension_self_deduction, income_tax, leave_deduction, other_deduction, deduction_total, net_pay, employer_pension, payment_method, payment_status, paid_on, notes, created_by, created_at, updated_at)
SELECT id, '2026-07', '2026-07-31', base_salary, 1000, 0, 0,
       base_salary + 1000,
       500, 450, 0, 0, 0, 0,
       950,
       base_salary + 1000 - 950,
       ROUND(base_salary * pension_rate / 100, 0),
       '匯款', 'confirmed', NULL, '範例資料，可依實際薪資修改。', user_id, NOW(), NOW()
FROM personnel_employees
WHERE status = 'active'
ORDER BY id
LIMIT 2;
