CREATE TABLE IF NOT EXISTS purchase_request_attachments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  purchase_request_id BIGINT UNSIGNED NOT NULL,
  category ENUM('quotation', 'invoice', 'contract', 'delivery', 'payment', 'other') NOT NULL DEFAULT 'other',
  title VARCHAR(160) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_path VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NULL,
  file_size BIGINT UNSIGNED NULL,
  notes TEXT NULL,
  uploaded_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_purchase_request_attachments_request (purchase_request_id),
  INDEX idx_purchase_request_attachments_category (category),
  CONSTRAINT fk_purchase_request_attachments_request
    FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_purchase_request_attachments_uploaded_by
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
