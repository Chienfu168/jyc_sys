CREATE TABLE IF NOT EXISTS donation_receipt_deliveries (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  donation_id BIGINT UNSIGNED NOT NULL,
  receipt_no VARCHAR(80) NOT NULL,
  delivery_method VARCHAR(40) NOT NULL,
  delivered_to VARCHAR(190) NULL,
  delivered_on DATE NOT NULL,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_donation_receipt_deliveries_donation
    FOREIGN KEY (donation_id) REFERENCES donations(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_donation_receipt_deliveries_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_donation_receipt_deliveries_donation (donation_id),
  INDEX idx_donation_receipt_deliveries_receipt_no (receipt_no),
  INDEX idx_donation_receipt_deliveries_delivered_on (delivered_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
