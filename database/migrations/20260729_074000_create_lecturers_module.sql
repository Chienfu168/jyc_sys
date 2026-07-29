CREATE TABLE IF NOT EXISTS lecturers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  display_name VARCHAR(120) NULL,
  tax_id VARCHAR(30) NULL,
  phone VARCHAR(50) NULL,
  mobile VARCHAR(50) NULL,
  email VARCHAR(160) NULL,
  address VARCHAR(255) NULL,
  organization VARCHAR(160) NULL,
  job_title VARCHAR(120) NULL,
  specialty VARCHAR(255) NULL,
  hourly_rate DECIMAL(12,2) NOT NULL DEFAULT 0,
  bank_name VARCHAR(120) NULL,
  bank_branch VARCHAR(120) NULL,
  bank_account_no VARCHAR(80) NULL,
  bank_account_name VARCHAR(120) NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  INDEX idx_lecturers_status_name (status, name),
  INDEX idx_lecturers_specialty (specialty),
  CONSTRAINT fk_lecturers_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO lecturers
  (name, display_name, phone, mobile, email, organization, job_title, specialty, hourly_rate, bank_name, bank_branch, bank_account_no, bank_account_name, status, notes, created_at, updated_at)
VALUES
  ('王雅婷', '王雅婷老師', '02-0000-1001', '0912-000-001', 'lecturer01@example.org', '社區教育工作室', '講師', '親職教育、兒童陪伴', 1600, '合作金庫', '蘆洲分行', '000000000001', '王雅婷', 'active', '範例資料，可依實際講師修改。', NOW(), NOW()),
  ('林志明', '林志明老師', '02-0000-1002', '0912-000-002', 'lecturer02@example.org', '地方創生協會', '顧問', '社區營造、地方文化', 1800, '臺灣銀行', '板橋分行', '000000000002', '林志明', 'active', '範例資料，可依實際講師修改。', NOW(), NOW()),
  ('陳怡君', '陳怡君老師', '02-0000-1003', '0912-000-003', 'lecturer03@example.org', '教育推廣中心', '專案講師', '閱讀素養、繪本教學', 1500, '玉山銀行', '三重分行', '000000000003', '陳怡君', 'active', '範例資料，可依實際講師修改。', NOW(), NOW()),
  ('張文豪', '張文豪老師', '02-0000-1004', '0912-000-004', 'lecturer04@example.org', '科技教育實驗室', '講師', '數位學習、AI 應用', 2000, '第一銀行', '新莊分行', '000000000004', '張文豪', 'active', '範例資料，可依實際講師修改。', NOW(), NOW()),
  ('黃佩珊', '黃佩珊老師', '02-0000-1005', '0912-000-005', 'lecturer05@example.org', '健康促進協會', '講師', '健康促進、銀髮活動', 1600, '華南銀行', '蘆洲分行', '000000000005', '黃佩珊', 'active', '範例資料，可依實際講師修改。', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  updated_at = updated_at;
