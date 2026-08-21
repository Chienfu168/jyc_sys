CREATE TABLE IF NOT EXISTS donation_receipt_voids (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  donation_id BIGINT UNSIGNED NOT NULL,
  receipt_no VARCHAR(80) NOT NULL,
  reason TEXT NULL,
  reissued_receipt_no VARCHAR(80) NULL,
  voided_by BIGINT UNSIGNED NULL,
  voided_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_donation_receipt_voids_donation
    FOREIGN KEY (donation_id) REFERENCES donations(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_donation_receipt_voids_voided_by
    FOREIGN KEY (voided_by) REFERENCES users(id)
    ON DELETE SET NULL,
  UNIQUE KEY uk_donation_receipt_voids_receipt_no (receipt_no),
  INDEX idx_donation_receipt_voids_donation (donation_id),
  INDEX idx_donation_receipt_voids_reissued_no (reissued_receipt_no),
  INDEX idx_donation_receipt_voids_voided_at (voided_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
