-- 學校新增「互動狀態」欄位,記錄與基金會的往來程度(尚未接觸／已拜訪／
-- 已合作／已舉辦活動),區分純粹列於名錄、尚未實際接觸的學校,方便後續
-- 業務推動時優先篩選。

SET @schools_has_engagement_status = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'schools'
    AND COLUMN_NAME = 'engagement_status'
);
SET @schools_add_engagement_status = IF(
  @schools_has_engagement_status = 0,
  'ALTER TABLE schools ADD COLUMN engagement_status VARCHAR(20) NOT NULL DEFAULT ''尚未接觸'' AFTER area_type',
  'DO 0'
);
PREPARE schools_add_engagement_status_stmt FROM @schools_add_engagement_status;
EXECUTE schools_add_engagement_status_stmt;
DEALLOCATE PREPARE schools_add_engagement_status_stmt;

SET @schools_has_engagement_status_idx = (
  SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'schools' AND INDEX_NAME = 'idx_schools_engagement_status'
);
SET @schools_add_engagement_status_idx = IF(
  @schools_has_engagement_status_idx = 0,
  'ALTER TABLE schools ADD INDEX idx_schools_engagement_status (engagement_status)',
  'DO 0'
);
PREPARE schools_add_engagement_status_idx_stmt FROM @schools_add_engagement_status_idx;
EXECUTE schools_add_engagement_status_idx_stmt;
DEALLOCATE PREPARE schools_add_engagement_status_idx_stmt;
