CREATE TABLE IF NOT EXISTS projects (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_code VARCHAR(60) NULL,
  name VARCHAR(160) NOT NULL,
  project_type ENUM('program', 'grant', 'administration', 'event', 'other') NOT NULL DEFAULT 'program',
  owner_name VARCHAR(120) NULL,
  department VARCHAR(120) NULL,
  funding_source VARCHAR(160) NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  budget_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  status ENUM('planning', 'active', 'closed', 'cancelled') NOT NULL DEFAULT 'planning',
  purpose TEXT NULL,
  expected_outcome TEXT NULL,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uq_projects_project_code (project_code),
  INDEX idx_projects_status (status),
  INDEX idx_projects_dates (start_date, end_date),
  CONSTRAINT fk_projects_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO projects
  (project_code, name, project_type, owner_name, department, funding_source, start_date, end_date, budget_amount, status, purpose, expected_outcome, notes, created_by, created_at, updated_at)
VALUES
  ('PRJ-115-001', '弱勢學童課後陪伴計畫', 'program', '承辦人員', '教育推廣組', '年度預算', '2026-08-01', '2026-12-31', 350000.00, 'active', '提供弱勢學童課後陪伴、作業指導與閱讀支持。', '完成服務人次統計、學習回饋與成果報告。', '範例專案，可依實際資料修改。', NULL, NOW(), NOW()),
  ('PRJ-115-002', '社區志工培力專案', 'program', '承辦人員', '社區合作組', '指定捐款', '2026-09-01', '2026-11-30', 180000.00, 'planning', '建立志工訓練與服務流程，強化活動支援能量。', '完成志工訓練課程、服務紀錄與名冊更新。', '範例專案，可依實際資料修改。', NULL, NOW(), NOW()),
  ('PRJ-115-003', '資訊系統建置與維運', 'administration', '承辦人員', '行政管理組', '行政管理費', '2026-07-01', '2026-12-31', 220000.00, 'active', '逐步建置基金會內部管理系統，提升行政、財務與人事作業效率。', '完成核心模組、資料備份、權限與列印輸出功能。', '範例專案，可依實際資料修改。', NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  updated_at = NOW();
