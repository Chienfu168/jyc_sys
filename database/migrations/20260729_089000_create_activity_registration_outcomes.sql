CREATE TABLE IF NOT EXISTS activity_participants (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  activity_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(40) NULL,
  email VARCHAR(160) NULL,
  organization VARCHAR(160) NULL,
  status ENUM('registered', 'attended', 'absent', 'cancelled') NOT NULL DEFAULT 'registered',
  checked_in_at DATETIME NULL,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  INDEX idx_activity_participants_activity (activity_id),
  INDEX idx_activity_participants_status (status),
  CONSTRAINT fk_activity_participants_activity
    FOREIGN KEY (activity_id) REFERENCES activities(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_activity_participants_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_outcomes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  activity_id BIGINT UNSIGNED NOT NULL,
  actual_participants INT UNSIGNED NOT NULL DEFAULT 0,
  satisfaction_score DECIMAL(4,2) NULL,
  outcome_summary TEXT NULL,
  improvement_notes TEXT NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uq_activity_outcomes_activity (activity_id),
  CONSTRAINT fk_activity_outcomes_activity
    FOREIGN KEY (activity_id) REFERENCES activities(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_activity_outcomes_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO activity_participants
  (activity_id, name, phone, email, organization, status, checked_in_at, notes, created_by, created_at, updated_at)
SELECT id, '王小明', '0912-000-001', 'sample1@example.com', '社區居民', 'attended', starts_at, '範例報名資料，可依實際資料修改。', NULL, NOW(), NOW()
FROM activities
WHERE title = '暑期閱讀陪伴工作坊'
LIMIT 1;

INSERT INTO activity_participants
  (activity_id, name, phone, email, organization, status, checked_in_at, notes, created_by, created_at, updated_at)
SELECT id, '林雅婷', '0912-000-002', 'sample2@example.com', '志工夥伴', 'registered', NULL, '範例報名資料，可依實際資料修改。', NULL, NOW(), NOW()
FROM activities
WHERE title = '暑期閱讀陪伴工作坊'
LIMIT 1;

INSERT INTO activity_outcomes
  (activity_id, actual_participants, satisfaction_score, outcome_summary, improvement_notes, updated_by, created_at, updated_at)
SELECT id, 1, 4.50, '完成範例活動成果紀錄，可依實際活動成果修改。', '後續可補充照片、簽到表與成果附件管理。', NULL, NOW(), NOW()
FROM activities
WHERE title = '暑期閱讀陪伴工作坊'
LIMIT 1;
