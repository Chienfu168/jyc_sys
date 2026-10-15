-- 學校新增「學生總人數」欄位,代表學校規模大小,供業務推動時參考;
-- 一併記錄所屬學年度,避免人數因未標示年度而失去時效意義。

SET @schools_has_student_count = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'schools'
    AND COLUMN_NAME = 'student_count'
);
SET @schools_add_student_count = IF(
  @schools_has_student_count = 0,
  'ALTER TABLE schools ADD COLUMN student_count INT UNSIGNED NULL AFTER website',
  'DO 0'
);
PREPARE schools_add_student_count_stmt FROM @schools_add_student_count;
EXECUTE schools_add_student_count_stmt;
DEALLOCATE PREPARE schools_add_student_count_stmt;

SET @schools_has_student_count_year = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'schools'
    AND COLUMN_NAME = 'student_count_year'
);
SET @schools_add_student_count_year = IF(
  @schools_has_student_count_year = 0,
  'ALTER TABLE schools ADD COLUMN student_count_year VARCHAR(10) NULL AFTER student_count',
  'DO 0'
);
PREPARE schools_add_student_count_year_stmt FROM @schools_add_student_count_year;
EXECUTE schools_add_student_count_year_stmt;
DEALLOCATE PREPARE schools_add_student_count_year_stmt;
