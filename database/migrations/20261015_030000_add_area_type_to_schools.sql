-- 學校新增「地區類別」欄位(都會區／非山非市／偏鄉／極偏鄉),供資源分配、
-- 交通與師資安排等業務參考;與既有 category(學校類型,如國小／國中)分開記錄。

SET @schools_has_area_type = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'schools'
    AND COLUMN_NAME = 'area_type'
);
SET @schools_add_area_type = IF(
  @schools_has_area_type = 0,
  'ALTER TABLE schools ADD COLUMN area_type VARCHAR(20) NULL AFTER category',
  'DO 0'
);
PREPARE schools_add_area_type_stmt FROM @schools_add_area_type;
EXECUTE schools_add_area_type_stmt;
DEALLOCATE PREPARE schools_add_area_type_stmt;

SET @schools_has_area_type_idx = (
  SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'schools' AND INDEX_NAME = 'idx_schools_area_type'
);
SET @schools_add_area_type_idx = IF(
  @schools_has_area_type_idx = 0,
  'ALTER TABLE schools ADD INDEX idx_schools_area_type (area_type)',
  'DO 0'
);
PREPARE schools_add_area_type_idx_stmt FROM @schools_add_area_type_idx;
EXECUTE schools_add_area_type_idx_stmt;
DEALLOCATE PREPARE schools_add_area_type_idx_stmt;
