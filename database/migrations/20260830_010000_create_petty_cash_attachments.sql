-- 零用金憑證附件:手機快速記帳時上傳的憑證照片(伺服器端會壓縮後存放)。
CREATE TABLE IF NOT EXISTS petty_cash_attachments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  petty_cash_entry_id BIGINT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_path VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NULL,
  file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
  uploaded_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_petty_cash_attachments_entry
    FOREIGN KEY (petty_cash_entry_id) REFERENCES petty_cash_entries(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_petty_cash_attachments_uploader
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_petty_cash_attachments_entry (petty_cash_entry_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
