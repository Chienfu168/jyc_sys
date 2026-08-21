CREATE TABLE IF NOT EXISTS donation_receipt_sequences (
  receipt_year SMALLINT UNSIGNED PRIMARY KEY,
  current_no INT UNSIGNED NOT NULL DEFAULT 0,
  updated_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_donation_receipt_sequences_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
