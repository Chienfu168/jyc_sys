-- 合作學校課程新增「次項目」欄位:同一校在同學年度＋學期內,可能同時執行多個
-- 不同性質的子方案(例如「深耕教育計畫」底下的「雙語讀世界」),以此欄位區分,
-- 而不必混在課程名稱或備註內。

SET @project_courses_has_sub_program = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'project_courses'
    AND COLUMN_NAME = 'sub_program_name'
);
SET @project_courses_add_sub_program = IF(
  @project_courses_has_sub_program = 0,
  'ALTER TABLE project_courses ADD COLUMN sub_program_name VARCHAR(190) NULL AFTER school_name',
  'DO 0'
);
PREPARE project_courses_add_sub_program_stmt FROM @project_courses_add_sub_program;
EXECUTE project_courses_add_sub_program_stmt;
DEALLOCATE PREPARE project_courses_add_sub_program_stmt;
