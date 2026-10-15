-- 活動主要是跟著學校辦理,新增可選的「所屬學校」欄位,串接學校通訊錄(schools)。

SET @activities_has_school_id = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'activities'
    AND COLUMN_NAME = 'school_id'
);
SET @activities_add_school_id = IF(
  @activities_has_school_id = 0,
  'ALTER TABLE activities ADD COLUMN school_id BIGINT UNSIGNED NULL AFTER project_id',
  'DO 0'
);
PREPARE activities_add_school_id_stmt FROM @activities_add_school_id;
EXECUTE activities_add_school_id_stmt;
DEALLOCATE PREPARE activities_add_school_id_stmt;

SET @activities_has_school_fk = (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'activities'
    AND CONSTRAINT_NAME = 'fk_activities_school'
);
SET @activities_add_school_fk = IF(
  @activities_has_school_fk = 0,
  'ALTER TABLE activities ADD CONSTRAINT fk_activities_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL',
  'DO 0'
);
PREPARE activities_add_school_fk_stmt FROM @activities_add_school_fk;
EXECUTE activities_add_school_fk_stmt;
DEALLOCATE PREPARE activities_add_school_fk_stmt;

SET @activities_has_school_idx = (
  SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'activities' AND INDEX_NAME = 'idx_activities_school'
);
SET @activities_add_school_idx = IF(
  @activities_has_school_idx = 0,
  'ALTER TABLE activities ADD INDEX idx_activities_school (school_id)',
  'DO 0'
);
PREPARE activities_add_school_idx_stmt FROM @activities_add_school_idx;
EXECUTE activities_add_school_idx_stmt;
DEALLOCATE PREPARE activities_add_school_idx_stmt;
