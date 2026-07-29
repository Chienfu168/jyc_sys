CREATE TABLE IF NOT EXISTS calendar_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(160) NOT NULL,
  event_type ENUM('meeting', 'activity', 'project', 'deadline', 'leave', 'reminder', 'other') NOT NULL DEFAULT 'meeting',
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NULL,
  all_day TINYINT(1) NOT NULL DEFAULT 0,
  location VARCHAR(160) NULL,
  owner_name VARCHAR(120) NULL,
  reminder_minutes INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('scheduled', 'done', 'cancelled') NOT NULL DEFAULT 'scheduled',
  description TEXT NULL,
  source_module VARCHAR(60) NULL,
  source_id BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  INDEX idx_calendar_events_starts_at (starts_at),
  INDEX idx_calendar_events_status (status),
  INDEX idx_calendar_events_type (event_type),
  INDEX idx_calendar_events_source (source_module, source_id),
  CONSTRAINT fk_calendar_events_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO calendar_events
  (title, event_type, starts_at, ends_at, all_day, location, owner_name, reminder_minutes, status, description, source_module, source_id, created_by, created_at, updated_at)
VALUES
  ('董事會例會', 'meeting', '2026-08-05 14:00:00', '2026-08-05 16:00:00', 0, '基金會會議室', '執行長', 120, 'scheduled', '範例行事曆事件，可依實際資料修改。', NULL, NULL, NULL, NOW(), NOW()),
  ('弱勢學童課後陪伴計畫啟動', 'project', '2026-08-01 09:00:00', '2026-08-01 10:00:00', 0, '基金會教室', '教育推廣組', 1440, 'scheduled', '範例行事曆事件，可依實際資料修改。', 'projects', NULL, NULL, NOW(), NOW()),
  ('月結資料檢核期限', 'deadline', '2026-08-31 17:00:00', NULL, 0, NULL, '財務會計組', 1440, 'scheduled', '範例行事曆事件，可依實際資料修改。', NULL, NULL, NULL, NOW(), NOW());
