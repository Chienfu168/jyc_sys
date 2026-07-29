CREATE TABLE IF NOT EXISTS volunteers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(40) NULL,
  email VARCHAR(190) NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_volunteers_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_volunteers_name (name),
  INDEX idx_volunteers_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS volunteer_service_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  volunteer_id BIGINT UNSIGNED NOT NULL,
  activity_id BIGINT UNSIGNED NULL,
  served_on DATE NOT NULL,
  hours DECIMAL(5,2) NOT NULL,
  description VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_service_logs_volunteer
    FOREIGN KEY (volunteer_id) REFERENCES volunteers(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_service_logs_activity
    FOREIGN KEY (activity_id) REFERENCES activities(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_service_logs_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_service_logs_served_on (served_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO volunteers (name, phone, email, status, notes, created_by, created_at, updated_at) VALUES
  ('張美玲', '0912-200-001', 'volunteer01@example.org', 'active', '範例志工資料，可依實際資料修改。', NULL, NOW(), NOW()),
  ('陳建宏', '0912-200-002', 'volunteer02@example.org', 'active', '範例志工資料，可依實際資料修改。', NULL, NOW(), NOW()),
  ('林佩君', '0912-200-003', 'volunteer03@example.org', 'active', '範例志工資料，可依實際資料修改。', NULL, NOW(), NOW());

INSERT INTO volunteer_service_logs (volunteer_id, activity_id, served_on, hours, description, created_by, created_at, updated_at)
SELECT volunteers.id, NULL, '2026-07-12', 3.00, '協助教育推廣活動報到與場地整理。', volunteers.created_by, NOW(), NOW()
FROM volunteers
WHERE volunteers.name = '張美玲'
ORDER BY volunteers.id
LIMIT 1;

INSERT INTO volunteer_service_logs (volunteer_id, activity_id, served_on, hours, description, created_by, created_at, updated_at)
SELECT volunteers.id, NULL, '2026-07-19', 4.00, '協助課程行政、教材整理與學員服務。', volunteers.created_by, NOW(), NOW()
FROM volunteers
WHERE volunteers.name = '陳建宏'
ORDER BY volunteers.id
LIMIT 1;
