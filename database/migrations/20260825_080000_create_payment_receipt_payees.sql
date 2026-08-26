-- 常用領款人:儲存經常領款者的基本與匯款資料,供領據表單一鍵帶入。
-- 領據本身已複製一份領款人資料,故刪除或停用常用領款人不影響既有領據。
CREATE TABLE IF NOT EXISTS payment_receipt_payees (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  payee_name VARCHAR(120) NOT NULL,
  payee_tax_id VARCHAR(40) NULL,
  phone VARCHAR(60) NULL,
  household_address VARCHAR(255) NULL,
  payment_type ENUM('bank', 'cash') NOT NULL DEFAULT 'bank',
  bank_name VARCHAR(120) NULL,
  bank_branch VARCHAR(120) NULL,
  bank_account VARCHAR(60) NULL,
  bank_account_name VARCHAR(120) NULL,
  fee_category VARCHAR(60) NULL,
  note VARCHAR(255) NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_payment_receipt_payees_name (payee_name),
  INDEX idx_payment_receipt_payees_status_sort (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
