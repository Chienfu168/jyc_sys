-- 調整深耕教育專案結構:由「一學期一專案」改為「一學校一專案」,因各校上課日、
-- 師資排程各自獨立,以學校為單位管理更貼近實際狀況;同校橫跨多學期的課程仍各自
-- 保留一筆於 project_courses,並保留其學年度＋學期欄位供報表依全部/學期/年度篩選。

ALTER TABLE project_courses DROP INDEX uq_project_courses_identity;
ALTER TABLE project_courses
  ADD UNIQUE KEY uq_project_courses_identity (project_id, semester_label, course_name(80));

INSERT INTO projects
  (project_code, name, project_type, owner_name, department, funding_source, start_date, end_date, budget_amount, status, purpose, expected_outcome, notes, created_by, created_at, updated_at)
VALUES
  ('DEEP-SCH-01', '深耕教育計畫－直潭國小', 'program', NULL, NULL, NULL, '2025-02-01', NULL, 0.00, 'active', '與直潭國小合作辦理深耕教育課程,依各學期實際排定之課程內容、授課師資與上課日執行;歷次學期課程明細請見下方「合作學校課程」清單。', '完成各學期課程開課、授課師資到位與課程成果紀錄。', '資料來源：深耕教育歷年總表；部分學期之校長、負責主任、授課師資或固定上課日仍待核對，詳見各課程備註。本專案彙整與該校歷次合作之課程，可能橫跨多個學期。', NULL, NOW(), NOW()),
  ('DEEP-SCH-02', '深耕教育計畫－山佳國小', 'program', NULL, NULL, NULL, '2025-02-01', NULL, 0.00, 'active', '與山佳國小合作辦理深耕教育課程,依各學期實際排定之課程內容、授課師資與上課日執行;歷次學期課程明細請見下方「合作學校課程」清單。', '完成各學期課程開課、授課師資到位與課程成果紀錄。', '資料來源：深耕教育歷年總表；部分學期之校長、負責主任、授課師資或固定上課日仍待核對，詳見各課程備註。本專案彙整與該校歷次合作之課程，可能橫跨多個學期。', NULL, NOW(), NOW()),
  ('DEEP-SCH-03', '深耕教育計畫－興南國小', 'program', NULL, NULL, NULL, '2025-02-01', NULL, 0.00, 'active', '與興南國小合作辦理深耕教育課程,依各學期實際排定之課程內容、授課師資與上課日執行;歷次學期課程明細請見下方「合作學校課程」清單。', '完成各學期課程開課、授課師資到位與課程成果紀錄。', '資料來源：深耕教育歷年總表；部分學期之校長、負責主任、授課師資或固定上課日仍待核對，詳見各課程備註。本專案彙整與該校歷次合作之課程，可能橫跨多個學期。', NULL, NOW(), NOW()),
  ('DEEP-SCH-04', '深耕教育計畫－山海璞光華德福共學園', 'program', NULL, NULL, NULL, '2025-08-01', NULL, 0.00, 'active', '與山海璞光華德福共學園合作辦理深耕教育課程,依各學期實際排定之課程內容、授課師資與上課日執行;歷次學期課程明細請見下方「合作學校課程」清單。', '完成各學期課程開課、授課師資到位與課程成果紀錄。', '資料來源：深耕教育歷年總表；部分學期之校長、負責主任、授課師資或固定上課日仍待核對，詳見各課程備註。本專案彙整與該校歷次合作之課程，可能橫跨多個學期。', NULL, NOW(), NOW()),
  ('DEEP-SCH-05', '深耕教育計畫－大成國小', 'program', NULL, NULL, NULL, '2025-08-01', NULL, 0.00, 'active', '與大成國小合作辦理深耕教育課程,依各學期實際排定之課程內容、授課師資與上課日執行;歷次學期課程明細請見下方「合作學校課程」清單。', '完成各學期課程開課、授課師資到位與課程成果紀錄。', '資料來源：深耕教育歷年總表；部分學期之校長、負責主任、授課師資或固定上課日仍待核對，詳見各課程備註。本專案彙整與該校歷次合作之課程，可能橫跨多個學期。', NULL, NOW(), NOW()),
  ('DEEP-SCH-06', '深耕教育計畫－雲海國小', 'program', NULL, NULL, NULL, '2025-08-01', NULL, 0.00, 'active', '與雲海國小合作辦理深耕教育課程,依各學期實際排定之課程內容、授課師資與上課日執行;歷次學期課程明細請見下方「合作學校課程」清單。', '完成各學期課程開課、授課師資到位與課程成果紀錄。', '資料來源：深耕教育歷年總表；部分學期之校長、負責主任、授課師資或固定上課日仍待核對，詳見各課程備註。本專案彙整與該校歷次合作之課程，可能橫跨多個學期。', NULL, NOW(), NOW()),
  ('DEEP-SCH-07', '深耕教育計畫－瑞平國小', 'program', NULL, NULL, NULL, '2025-08-01', NULL, 0.00, 'active', '與瑞平國小合作辦理深耕教育課程,依各學期實際排定之課程內容、授課師資與上課日執行;歷次學期課程明細請見下方「合作學校課程」清單。', '完成各學期課程開課、授課師資到位與課程成果紀錄。', '資料來源：深耕教育歷年總表；部分學期之校長、負責主任、授課師資或固定上課日仍待核對，詳見各課程備註。本專案彙整與該校歷次合作之課程，可能橫跨多個學期。', NULL, NOW(), NOW()),
  ('DEEP-SCH-08', '深耕教育計畫－坪林國小', 'program', NULL, NULL, NULL, '2025-08-01', NULL, 0.00, 'active', '與坪林國小合作辦理深耕教育課程,依各學期實際排定之課程內容、授課師資與上課日執行;歷次學期課程明細請見下方「合作學校課程」清單。', '完成各學期課程開課、授課師資到位與課程成果紀錄。', '資料來源：深耕教育歷年總表；部分學期之校長、負責主任、授課師資或固定上課日仍待核對，詳見各課程備註。本專案彙整與該校歷次合作之課程，可能橫跨多個學期。', NULL, NOW(), NOW()),
  ('DEEP-SCH-09', '深耕教育計畫－長坑國小', 'program', NULL, NULL, NULL, '2025-08-01', NULL, 0.00, 'active', '與長坑國小合作辦理深耕教育課程,依各學期實際排定之課程內容、授課師資與上課日執行;歷次學期課程明細請見下方「合作學校課程」清單。', '完成各學期課程開課、授課師資到位與課程成果紀錄。', '資料來源：深耕教育歷年總表；部分學期之校長、負責主任、授課師資或固定上課日仍待核對，詳見各課程備註。本專案彙整與該校歷次合作之課程，可能橫跨多個學期。', NULL, NOW(), NOW()),
  ('DEEP-SCH-10', '深耕教育計畫－深坑國小', 'program', NULL, NULL, NULL, '2025-08-01', NULL, 0.00, 'active', '與深坑國小合作辦理深耕教育課程,依各學期實際排定之課程內容、授課師資與上課日執行;歷次學期課程明細請見下方「合作學校課程」清單。', '完成各學期課程開課、授課師資到位與課程成果紀錄。', '資料來源：深耕教育歷年總表；部分學期之校長、負責主任、授課師資或固定上課日仍待核對，詳見各課程備註。本專案彙整與該校歷次合作之課程，可能橫跨多個學期。', NULL, NOW(), NOW()),
  ('DEEP-SCH-11', '深耕教育計畫－上林國小、柑林國小', 'program', NULL, NULL, NULL, '2026-02-01', NULL, 0.00, 'active', '與上林國小、柑林國小合作辦理深耕教育課程,依各學期實際排定之課程內容、授課師資與上課日執行;歷次學期課程明細請見下方「合作學校課程」清單。', '完成各學期課程開課、授課師資到位與課程成果紀錄。', '資料來源：深耕教育歷年總表；部分學期之校長、負責主任、授課師資或固定上課日仍待核對，詳見各課程備註。本專案彙整與該校歷次合作之課程，可能橫跨多個學期。', NULL, NOW(), NOW()),
  ('DEEP-SCH-12', '深耕教育計畫－永吉國小', 'program', NULL, NULL, NULL, '2026-02-01', NULL, 0.00, 'active', '與永吉國小合作辦理深耕教育課程,依各學期實際排定之課程內容、授課師資與上課日執行;歷次學期課程明細請見下方「合作學校課程」清單。', '完成各學期課程開課、授課師資到位與課程成果紀錄。', '資料來源：深耕教育歷年總表；部分學期之校長、負責主任、授課師資或固定上課日仍待核對，詳見各課程備註。本專案彙整與該校歷次合作之課程，可能橫跨多個學期。', NULL, NOW(), NOW()),
  ('DEEP-SCH-13', '深耕教育計畫－福陽國小', 'program', NULL, NULL, NULL, '2026-02-01', NULL, 0.00, 'active', '與福陽國小合作辦理深耕教育課程,依各學期實際排定之課程內容、授課師資與上課日執行;歷次學期課程明細請見下方「合作學校課程」清單。', '完成各學期課程開課、授課師資到位與課程成果紀錄。', '資料來源：深耕教育歷年總表；部分學期之校長、負責主任、授課師資或固定上課日仍待核對，詳見各課程備註。本專案彙整與該校歷次合作之課程，可能橫跨多個學期。', NULL, NOW(), NOW()),
  ('DEEP-SCH-14', '深耕教育計畫－橫山國小', 'program', NULL, NULL, NULL, '2026-08-01', NULL, 0.00, 'active', '與橫山國小合作辦理深耕教育課程,依各學期實際排定之課程內容、授課師資與上課日執行;歷次學期課程明細請見下方「合作學校課程」清單。', '完成各學期課程開課、授課師資到位與課程成果紀錄。', '資料來源：深耕教育歷年總表；部分學期之校長、負責主任、授課師資或固定上課日仍待核對，詳見各課程備註。本專案彙整與該校歷次合作之課程，可能橫跨多個學期。', NULL, NOW(), NOW()),
  ('DEEP-SCH-15', '深耕教育計畫－興仁國小', 'program', NULL, NULL, NULL, '2026-08-01', NULL, 0.00, 'active', '與興仁國小合作辦理深耕教育課程,依各學期實際排定之課程內容、授課師資與上課日執行;歷次學期課程明細請見下方「合作學校課程」清單。', '完成各學期課程開課、授課師資到位與課程成果紀錄。', '資料來源：深耕教育歷年總表；部分學期之校長、負責主任、授課師資或固定上課日仍待核對，詳見各課程備註。本專案彙整與該校歷次合作之課程，可能橫跨多個學期。', NULL, NOW(), NOW()),
  ('DEEP-SCH-16', '深耕教育計畫－中角國小', 'program', NULL, NULL, NULL, '2026-08-01', NULL, 0.00, 'active', '與中角國小合作辦理深耕教育課程,依各學期實際排定之課程內容、授課師資與上課日執行;歷次學期課程明細請見下方「合作學校課程」清單。', '完成各學期課程開課、授課師資到位與課程成果紀錄。', '資料來源：深耕教育歷年總表；部分學期之校長、負責主任、授課師資或固定上課日仍待核對，詳見各課程備註。本專案彙整與該校歷次合作之課程，可能橫跨多個學期。', NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  start_date = VALUES(start_date),
  status = VALUES(status),
  purpose = VALUES(purpose),
  expected_outcome = VALUES(expected_outcome),
  notes = VALUES(notes),
  updated_at = NOW();

UPDATE project_courses
SET project_id = (SELECT id FROM projects WHERE project_code = 'DEEP-SCH-01' LIMIT 1)
WHERE school_name = '直潭國小';

UPDATE project_courses
SET project_id = (SELECT id FROM projects WHERE project_code = 'DEEP-SCH-02' LIMIT 1)
WHERE school_name = '山佳國小';

UPDATE project_courses
SET project_id = (SELECT id FROM projects WHERE project_code = 'DEEP-SCH-03' LIMIT 1)
WHERE school_name = '興南國小';

UPDATE project_courses
SET project_id = (SELECT id FROM projects WHERE project_code = 'DEEP-SCH-04' LIMIT 1)
WHERE school_name = '山海璞光華德福共學園';

UPDATE project_courses
SET project_id = (SELECT id FROM projects WHERE project_code = 'DEEP-SCH-05' LIMIT 1)
WHERE school_name = '大成國小';

UPDATE project_courses
SET project_id = (SELECT id FROM projects WHERE project_code = 'DEEP-SCH-06' LIMIT 1)
WHERE school_name = '雲海國小';

UPDATE project_courses
SET project_id = (SELECT id FROM projects WHERE project_code = 'DEEP-SCH-07' LIMIT 1)
WHERE school_name = '瑞平國小';

UPDATE project_courses
SET project_id = (SELECT id FROM projects WHERE project_code = 'DEEP-SCH-08' LIMIT 1)
WHERE school_name = '坪林國小';

UPDATE project_courses
SET project_id = (SELECT id FROM projects WHERE project_code = 'DEEP-SCH-09' LIMIT 1)
WHERE school_name = '長坑國小';

UPDATE project_courses
SET project_id = (SELECT id FROM projects WHERE project_code = 'DEEP-SCH-10' LIMIT 1)
WHERE school_name = '深坑國小';

UPDATE project_courses
SET project_id = (SELECT id FROM projects WHERE project_code = 'DEEP-SCH-11' LIMIT 1)
WHERE school_name = '上林國小、柑林國小';

UPDATE project_courses
SET project_id = (SELECT id FROM projects WHERE project_code = 'DEEP-SCH-12' LIMIT 1)
WHERE school_name = '永吉國小';

UPDATE project_courses
SET project_id = (SELECT id FROM projects WHERE project_code = 'DEEP-SCH-13' LIMIT 1)
WHERE school_name = '福陽國小';

UPDATE project_courses
SET project_id = (SELECT id FROM projects WHERE project_code = 'DEEP-SCH-14' LIMIT 1)
WHERE school_name = '橫山國小';

UPDATE project_courses
SET project_id = (SELECT id FROM projects WHERE project_code = 'DEEP-SCH-15' LIMIT 1)
WHERE school_name = '興仁國小';

UPDATE project_courses
SET project_id = (SELECT id FROM projects WHERE project_code = 'DEEP-SCH-16' LIMIT 1)
WHERE school_name = '中角國小';

DELETE FROM projects WHERE project_code IN ('DEEP-113-2', 'DEEP-114-1', 'DEEP-114-2', 'DEEP-115-1');
