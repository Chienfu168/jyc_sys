-- 銀行匯款單(取款憑條):基金會作為匯款人,自其銀行帳戶匯款給收款人時交付銀行的單據。
-- 目前支援彰化銀行版面(bank_code = 'chb'),設計保留 bank_code 以便未來擴充其他銀行版面。
CREATE TABLE IF NOT EXISTS bank_slips (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bank_code VARCHAR(20) NOT NULL DEFAULT 'chb',
  slip_no VARCHAR(40) NULL,
  slip_date DATE NOT NULL,
  remittance_type VARCHAR(60) NULL,
  withdrawal_bank_account_id BIGINT UNSIGNED NULL,
  remitter_name VARCHAR(160) NULL,
  remitter_id_no VARCHAR(40) NULL,
  remitter_phone VARCHAR(60) NULL,
  agent_name VARCHAR(120) NULL,
  payee_name VARCHAR(160) NOT NULL,
  payee_account VARCHAR(80) NOT NULL,
  paying_bank_name VARCHAR(160) NULL,
  amount DECIMAL(14,0) NOT NULL DEFAULT 0,
  message VARCHAR(120) NULL,
  sms_mobile VARCHAR(30) NULL,
  source_type VARCHAR(80) NULL,
  source_id BIGINT UNSIGNED NULL,
  notes VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  INDEX idx_bank_slips_bank_date (bank_code, slip_date),
  CONSTRAINT fk_bank_slips_withdrawal_account
    FOREIGN KEY (withdrawal_bank_account_id) REFERENCES bank_accounts(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_bank_slips_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('bank_slips.view', '檢視匯款單', 'bank_slips', NOW(), NOW()),
  ('bank_slips.manage', '製作匯款單', 'bank_slips', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

-- 系統管理員:檢視 + 製作
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE code IN ('bank_slips.view', 'bank_slips.manage');

-- 主管:檢視 + 製作
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE code IN ('bank_slips.view', 'bank_slips.manage');

-- 行政人員:檢視 + 製作
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE code IN ('bank_slips.view', 'bank_slips.manage');
