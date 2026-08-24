-- 董事會議管理:會議議程(會前)與會議紀錄(會後)共用同一筆會議資料,
-- 依「屆次」格式參考新北市教育局範例(第X屆第Y次董事會)。
CREATE TABLE IF NOT EXISTS board_meetings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  term_no SMALLINT UNSIGNED NOT NULL,
  session_no SMALLINT UNSIGNED NOT NULL,
  meeting_date DATE NOT NULL,
  meeting_time VARCHAR(60) NULL,
  location VARCHAR(160) NULL,
  chairperson VARCHAR(120) NULL,
  recorder VARCHAR(120) NULL,
  report_items TEXT NULL,
  extempore_motions TEXT NULL,
  status ENUM('draft', 'confirmed') NOT NULL DEFAULT 'draft',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_board_meetings_term_session (term_no, session_no),
  INDEX idx_board_meetings_date (meeting_date),
  CONSTRAINT fk_board_meetings_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS board_meeting_attendees (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  board_meeting_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  role ENUM('director', 'observer') NOT NULL DEFAULT 'director',
  attendance_status ENUM('present', 'leave', 'proxy') NOT NULL DEFAULT 'present',
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_board_meeting_attendees_meeting
    FOREIGN KEY (board_meeting_id) REFERENCES board_meetings(id)
    ON DELETE CASCADE,
  INDEX idx_board_meeting_attendees_meeting (board_meeting_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS board_meeting_agenda_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  board_meeting_id BIGINT UNSIGNED NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  subject TEXT NOT NULL,
  resolution TEXT NULL,
  CONSTRAINT fk_board_meeting_agenda_items_meeting
    FOREIGN KEY (board_meeting_id) REFERENCES board_meetings(id)
    ON DELETE CASCADE,
  INDEX idx_board_meeting_agenda_items_meeting (board_meeting_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('board_meetings.view', '檢視董事會議', 'board_meetings', NOW(), NOW()),
  ('board_meetings.manage', '管理董事會議', 'board_meetings', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('board_meetings.view', 'board_meetings.manage')
WHERE roles.name = '系統管理員';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code IN ('board_meetings.view', 'board_meetings.manage')
WHERE roles.name = '行政人員';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'board_meetings.view'
WHERE roles.name = '主管';
