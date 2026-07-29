CREATE TABLE IF NOT EXISTS activities (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(160) NOT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NULL,
  location VARCHAR(160) NULL,
  status ENUM('draft', 'published', 'closed', 'cancelled') NOT NULL DEFAULT 'draft',
  description TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_activities_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_activities_starts_at (starts_at),
  INDEX idx_activities_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO activities (title, starts_at, ends_at, location, status, description, created_by, created_at, updated_at) VALUES
  ('暑期閱讀陪伴工作坊', '2026-07-12 09:00:00', '2026-07-12 12:00:00', '基金會教室', 'published', '範例活動，可依實際活動修改。', NULL, NOW(), NOW()),
  ('社區營造培力講座', '2026-07-19 13:30:00', '2026-07-19 17:30:00', '社區活動中心', 'published', '範例活動，可依實際活動修改。', NULL, NOW(), NOW()),
  ('AI 工具入門實作', '2026-07-26 09:30:00', '2026-07-26 12:30:00', '數位學習教室', 'draft', '範例活動，可依實際活動修改。', NULL, NOW(), NOW());
