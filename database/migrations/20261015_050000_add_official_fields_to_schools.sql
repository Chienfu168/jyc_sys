-- 學校新增官方名錄相關欄位:學校代碼(教育部名錄校代碼,供比對官方資料)、
-- 行政區(新北市各區,供篩選)、學校網站。供後續匯入教育部/縣市政府公開
-- 名錄資料使用。

SET @schools_has_school_code = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'schools'
    AND COLUMN_NAME = 'school_code'
);
SET @schools_add_school_code = IF(
  @schools_has_school_code = 0,
  'ALTER TABLE schools ADD COLUMN school_code VARCHAR(20) NULL AFTER name',
  'DO 0'
);
PREPARE schools_add_school_code_stmt FROM @schools_add_school_code;
EXECUTE schools_add_school_code_stmt;
DEALLOCATE PREPARE schools_add_school_code_stmt;

SET @schools_has_district = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'schools'
    AND COLUMN_NAME = 'district'
);
SET @schools_add_district = IF(
  @schools_has_district = 0,
  'ALTER TABLE schools ADD COLUMN district VARCHAR(30) NULL AFTER category',
  'DO 0'
);
PREPARE schools_add_district_stmt FROM @schools_add_district;
EXECUTE schools_add_district_stmt;
DEALLOCATE PREPARE schools_add_district_stmt;

SET @schools_has_website = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'schools'
    AND COLUMN_NAME = 'website'
);
SET @schools_add_website = IF(
  @schools_has_website = 0,
  'ALTER TABLE schools ADD COLUMN website VARCHAR(190) NULL AFTER fax',
  'DO 0'
);
PREPARE schools_add_website_stmt FROM @schools_add_website;
EXECUTE schools_add_website_stmt;
DEALLOCATE PREPARE schools_add_website_stmt;

SET @schools_has_school_code_idx = (
  SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'schools' AND INDEX_NAME = 'uq_schools_school_code'
);
SET @schools_add_school_code_idx = IF(
  @schools_has_school_code_idx = 0,
  'ALTER TABLE schools ADD UNIQUE INDEX uq_schools_school_code (school_code)',
  'DO 0'
);
PREPARE schools_add_school_code_idx_stmt FROM @schools_add_school_code_idx;
EXECUTE schools_add_school_code_idx_stmt;
DEALLOCATE PREPARE schools_add_school_code_idx_stmt;

SET @schools_has_district_idx = (
  SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'schools' AND INDEX_NAME = 'idx_schools_district'
);
SET @schools_add_district_idx = IF(
  @schools_has_district_idx = 0,
  'ALTER TABLE schools ADD INDEX idx_schools_district (district)',
  'DO 0'
);
PREPARE schools_add_district_idx_stmt FROM @schools_add_district_idx;
EXECUTE schools_add_district_idx_stmt;
DEALLOCATE PREPARE schools_add_district_idx_stmt;
