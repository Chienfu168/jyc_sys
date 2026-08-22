CREATE TABLE IF NOT EXISTS purchase_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_no VARCHAR(80) NOT NULL,
  requested_on DATE NOT NULL,
  requester_name VARCHAR(120) NOT NULL,
  requester_id BIGINT UNSIGNED NULL,
  accounting_no VARCHAR(120) NULL,
  purchase_category VARCHAR(80) NOT NULL,
  request_unit VARCHAR(80) NOT NULL,
  subject VARCHAR(190) NOT NULL,
  reason TEXT NOT NULL,
  purpose TEXT NOT NULL,
  purchase_method VARCHAR(80) NOT NULL,
  vendor_name VARCHAR(190) NULL,
  quotation_attached TINYINT(1) NOT NULL DEFAULT 1,
  quotation_missing_reason VARCHAR(255) NULL,
  total_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  status ENUM('draft', 'submitted', 'approved', 'rejected', 'ordered', 'received', 'voided') NOT NULL DEFAULT 'draft',
  submitted_at DATETIME NULL,
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  review_notes TEXT NULL,
  ordered_at DATETIME NULL,
  received_at DATETIME NULL,
  voided_at DATETIME NULL,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_purchase_requests_requester
    FOREIGN KEY (requester_id) REFERENCES users(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_purchase_requests_reviewed_by
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_purchase_requests_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  UNIQUE KEY uk_purchase_requests_request_no (request_no),
  INDEX idx_purchase_requests_requested_on (requested_on),
  INDEX idx_purchase_requests_status (status),
  INDEX idx_purchase_requests_category (purchase_category),
  INDEX idx_purchase_requests_unit (request_unit)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purchase_request_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  purchase_request_id BIGINT UNSIGNED NOT NULL,
  item_name VARCHAR(190) NOT NULL,
  specification VARCHAR(255) NULL,
  quantity DECIMAL(12,2) NOT NULL DEFAULT 1,
  unit_price DECIMAL(14,2) NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  notes TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_purchase_request_items_request
    FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests(id)
    ON DELETE CASCADE,
  INDEX idx_purchase_request_items_request_sort (purchase_request_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('purchase_requests.view', '檢視採購申請', 'purchase_requests', NOW(), NOW()),
  ('purchase_requests.manage', '管理採購申請', 'purchase_requests', NOW(), NOW()),
  ('purchase_requests.approve', '核准採購申請', 'purchase_requests', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.module = 'purchase_requests'
WHERE roles.id = 1;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('purchase_requests.view', 'purchase_requests.approve')
WHERE roles.id = 2;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('purchase_requests.view', 'purchase_requests.manage')
WHERE roles.id = 3;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'purchase_requests.view'
WHERE roles.id = 4;
