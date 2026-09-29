-- 匯入深耕教育歷年課程資料:一學期一個專案(program),並依學校/課程建立 project_courses 明細。
-- 來源:深耕教育歷年總表(113學年度第2學期至115學年度第1學期);部分欄位(校長/主任/師資/上課日)
-- 依來源標註為待確認,保留空白而非臆造。

CREATE TABLE IF NOT EXISTS project_courses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id BIGINT UNSIGNED NOT NULL,
  school_name VARCHAR(160) NOT NULL,
  principal_name VARCHAR(120) NULL,
  director_name VARCHAR(120) NULL,
  course_name VARCHAR(160) NOT NULL,
  teacher_name VARCHAR(160) NULL,
  weekday VARCHAR(20) NULL,
  semester_label VARCHAR(40) NOT NULL,
  notes TEXT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uq_project_courses_identity (project_id, school_name(80), course_name(80)),
  INDEX idx_project_courses_project (project_id),
  CONSTRAINT fk_project_courses_project
    FOREIGN KEY (project_id) REFERENCES projects(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO projects
  (project_code, name, project_type, owner_name, department, funding_source, start_date, end_date, budget_amount, status, purpose, expected_outcome, notes, created_by, created_at, updated_at)
VALUES
  ('DEEP-113-2', '深耕教育計畫（113學年度第2學期）', 'program', NULL, NULL, NULL, '2025-02-01', '2025-07-31', 0.00, 'closed', '結合合作學校資源，於在地國小辦理深耕教育課程（如兒童音樂劇、雙語讀世界、邏輯程式機器人等），提升學童多元學習與在地連結。', '完成本學期各校課程開課、授課師資到位與課程成果紀錄。', '資料來源：深耕教育歷年總表；部分學校之校長、負責主任、授課師資或固定上課日仍待核對，詳見各課程備註。', NULL, NOW(), NOW()),
  ('DEEP-114-1', '深耕教育計畫（114學年度第1學期）', 'program', NULL, NULL, NULL, '2025-08-01', '2026-01-31', 0.00, 'closed', '結合合作學校資源，於在地國小辦理深耕教育課程（如兒童音樂劇、雙語讀世界、邏輯程式機器人等），提升學童多元學習與在地連結。', '完成本學期各校課程開課、授課師資到位與課程成果紀錄。', '資料來源：深耕教育歷年總表；部分學校之校長、負責主任、授課師資或固定上課日仍待核對，詳見各課程備註。', NULL, NOW(), NOW()),
  ('DEEP-114-2', '深耕教育計畫（114學年度第2學期）', 'program', NULL, NULL, NULL, '2026-02-01', '2026-07-31', 0.00, 'closed', '結合合作學校資源，於在地國小辦理深耕教育課程（如兒童音樂劇、雙語讀世界、邏輯程式機器人等），提升學童多元學習與在地連結。', '完成本學期各校課程開課、授課師資到位與課程成果紀錄。', '資料來源：深耕教育歷年總表；部分學校之校長、負責主任、授課師資或固定上課日仍待核對，詳見各課程備註。', NULL, NOW(), NOW()),
  ('DEEP-115-1', '深耕教育計畫（115學年度第1學期）', 'program', NULL, NULL, NULL, '2026-08-01', '2027-01-31', 0.00, 'active', '結合合作學校資源，於在地國小辦理深耕教育課程（如兒童音樂劇、雙語讀世界、邏輯程式機器人等），提升學童多元學習與在地連結。', '完成本學期各校課程開課、授課師資到位與課程成果紀錄。', '資料來源：深耕教育歷年總表；部分學校之校長、負責主任、授課師資或固定上課日仍待核對，詳見各課程備註。', NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  start_date = VALUES(start_date),
  end_date = VALUES(end_date),
  status = VALUES(status),
  purpose = VALUES(purpose),
  expected_outcome = VALUES(expected_outcome),
  notes = VALUES(notes),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '直潭國小', '劉世和', NULL, '兒童音樂劇', '林意淨、黃暐', NULL, '113學年度第2學期', '2025/2/19啟動；官網2025/6/11成果文確認師資；每週幾待核。', 1, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-113-2'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '山佳國小', NULL, NULL, '雙語讀世界', NULL, NULL, '113學年度第2學期', '2025/2開辦；上課日與師資待核對。', 2, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-113-2'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '興南國小', NULL, NULL, '雙語讀世界（緬甸專班）', NULL, NULL, '113學年度第2學期', '2025/2/22開班；上課日與師資待核對。', 3, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-113-2'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '山海璞光華德福共學園', NULL, NULL, '兒童音樂劇', '蔡詠晴', '星期四', '114學年度第1學期', '依課程名冊；非國小。', 1, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-1'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '直潭國小', '劉世和', NULL, '兒童音樂劇', '林意淨、黃暐', '星期三', '114學年度第1學期', '依課程名冊；校長依官網同期文章。', 2, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-1'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '大成國小', '陳淨怡', NULL, '兒童音樂劇', '楊玉萍', '星期五', '114學年度第1學期', '依課程名冊；校長依2026/1同期活動紀錄。', 3, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-1'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '雲海國小', NULL, NULL, '兒童音樂劇', '楊玉萍', '星期四', '114學年度第1學期', '依課程名冊。', 4, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-1'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '山佳國小', NULL, NULL, '雙語讀世界', '沈玟儀', '星期六', '114學年度第1學期', '依課程名冊。', 5, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-1'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '山佳國小', NULL, NULL, '玩聚唱歌學英文', '古蕊玲', '星期四', '114學年度第1學期', '名冊作古蕊玲、官網2025/9文章作古芯玲；姓名須以聘任資料核對。', 6, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-1'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '興南國小', '曾長麗', NULL, '雙語讀世界（緬甸專班）', '曹敏玲', '星期六', '114學年度第1學期', '依課程名冊；校長依同校公開紀錄。', 7, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-1'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '瑞平國小', NULL, NULL, '邏輯程式智慧機器人', '廖心琪', '星期三', '114學年度第1學期', '依課程名冊。', 8, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-1'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '坪林國小', NULL, NULL, '邏輯程式智慧機器人', '翁睿鍠', '星期五', '114學年度第1學期', '依課程名冊。', 9, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-1'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '長坑國小', NULL, NULL, '邏輯程式智慧機器人', '吳鎧志', '星期五', '114學年度第1學期', '依課程名冊。', 10, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-1'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '深坑國小', '李春芳', NULL, '藝術美學班', '曾日昇', '星期五', '114學年度第1學期', '依課程名冊；校長依官網成果文及下一學期紀錄。', 11, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-1'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '直潭國小', '劉世和', NULL, '兒童音樂劇', '林意淨', NULL, '114學年度第2學期', '官網2026/3/4始業文確認授課老師；上課日待補。', 1, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-2'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '大成國小', '陳淨怡', NULL, '兒童音樂劇', '楊玉萍', NULL, '114學年度第2學期', '官網確認續辦與授課老師；校長依同期活動紀錄。', 2, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-2'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '雲海國小', NULL, NULL, '兒童音樂劇', '楊玉萍', NULL, '114學年度第2學期', '官網2026/6/11成果文確認；上課日待補。', 3, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-2'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '山佳國小', NULL, NULL, '雙語讀世界', '沈玟儀', NULL, '114學年度第2學期', '官網確認續辦；每週幾待補。', 4, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-2'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '山佳國小', NULL, NULL, '玩聚唱歌學英文', NULL, NULL, '114學年度第2學期', '官網確認續辦；本學期師資待補。', 5, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-2'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '興南國小', '曾長麗', NULL, '雙語讀世界（緬甸專班）', NULL, NULL, '114學年度第2學期', '官網確認續辦；師資與每週幾待補。', 6, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-2'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '長坑國小', NULL, NULL, '邏輯程式智慧機器人', NULL, NULL, '114學年度第2學期', '2026/3/13始業式；師資待補。', 7, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-2'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '坪林國小', NULL, NULL, '邏輯程式智慧機器人', NULL, NULL, '114學年度第2學期', '官網確認續辦；師資待補。', 8, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-2'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '深坑國小', '李春芳', NULL, '藝術美學班', '曾日昇', NULL, '114學年度第2學期', '2026/4/10第二學期始業式；官網確認師資與校長。', 9, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-2'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '上林國小、柑林國小', '上林：陳彥宏；柑林：謝添達', NULL, '律動舞蹈課', NULL, NULL, '114學年度第2學期', '同一跨校課程，列一筆；官網2026/5/22成果。', 10, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-2'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '永吉國小', '鍾信昌', NULL, '豆腐食農小廚房', '鍾信昌', NULL, '114學年度第2學期', '官網2026/6/17成果：由校長親自指導；其餘師資待確認。', 11, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-2'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '福陽國小', '郭國澄', NULL, '木樂新生小學堂', NULL, NULL, '114學年度第2學期', '官網載2026/3開課、6/18成果；師資待補。', 12, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-114-2'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '橫山國小', '蔡依齡', NULL, 'AI × 3D電腦動漫課', '江易霖', NULL, '115學年度第1學期', '2026/9/2始業；每週幾待補。', 1, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-115-1'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '山佳國小', NULL, NULL, '雙語讀世界', '沈玟儀', NULL, '115學年度第1學期', '2026/9/5假日班始業；本學期固定上課日待核對。', 2, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-115-1'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '興南國小', '曾長麗', NULL, '兒童音樂劇', '楊玉菁', NULL, '115學年度第1學期', '2026/9開課；每週幾待補。', 3, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-115-1'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '興南國小', '曾長麗', NULL, '木樂新生小學堂', '陳零菲', NULL, '115學年度第1學期', '官網2026/9記錄新梯次；上課日待補。', 4, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-115-1'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '興仁國小', '陳政銓', NULL, '兒童音樂劇', '楊玉菁', NULL, '115學年度第1學期', '2026/9/11始業；每週幾待補。', 5, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-115-1'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '中角國小', '童新峯', NULL, '邏輯程式教育機器人', '翁睿鍠', NULL, '115學年度第1學期', '2026/9/18始業；每週幾待補。', 6, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-115-1'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();

INSERT INTO project_courses
  (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
SELECT p.id, '坪林國小', NULL, NULL, 'AI小小音樂家－魔法工坊', '黃譽韶', NULL, '115學年度第1學期', '2026/9/23啟動；官網標題「魔術工坊」、分類「魔法工坊」，課程正式名稱待核。', 7, NOW(), NOW()
FROM projects p WHERE p.project_code = 'DEEP-115-1'
ON DUPLICATE KEY UPDATE
  principal_name = VALUES(principal_name),
  director_name = VALUES(director_name),
  teacher_name = VALUES(teacher_name),
  weekday = VALUES(weekday),
  notes = VALUES(notes),
  sort_order = VALUES(sort_order),
  updated_at = NOW();
