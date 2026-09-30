-- 專案本身新增「次項目、執行對象、負責人／校長、主要接洽」欄位:多數專案原則上
-- 對應單一學校(或單一執行對象),這些欄位直接記錄在專案本身,不必為了單一學校的
-- 情況特地去新增一筆「合作學校課程」。真正橫跨多校、需要分別記錄各校課程明細
-- 的情況,才使用既有的「合作學校課程」(project_courses)清單。

SET @projects_has_sub_program = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'projects'
    AND COLUMN_NAME = 'sub_program_name'
);
SET @projects_add_sub_program = IF(
  @projects_has_sub_program = 0,
  'ALTER TABLE projects ADD COLUMN sub_program_name VARCHAR(190) NULL AFTER name',
  'DO 0'
);
PREPARE projects_add_sub_program_stmt FROM @projects_add_sub_program;
EXECUTE projects_add_sub_program_stmt;
DEALLOCATE PREPARE projects_add_sub_program_stmt;

SET @projects_has_executing_target = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'projects'
    AND COLUMN_NAME = 'executing_target'
);
SET @projects_add_executing_target = IF(
  @projects_has_executing_target = 0,
  'ALTER TABLE projects ADD COLUMN executing_target VARCHAR(190) NULL AFTER sub_program_name',
  'DO 0'
);
PREPARE projects_add_executing_target_stmt FROM @projects_add_executing_target;
EXECUTE projects_add_executing_target_stmt;
DEALLOCATE PREPARE projects_add_executing_target_stmt;

SET @projects_has_principal_name = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'projects'
    AND COLUMN_NAME = 'principal_name'
);
SET @projects_add_principal_name = IF(
  @projects_has_principal_name = 0,
  'ALTER TABLE projects ADD COLUMN principal_name VARCHAR(190) NULL AFTER executing_target',
  'DO 0'
);
PREPARE projects_add_principal_name_stmt FROM @projects_add_principal_name;
EXECUTE projects_add_principal_name_stmt;
DEALLOCATE PREPARE projects_add_principal_name_stmt;

SET @projects_has_contact_name = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'projects'
    AND COLUMN_NAME = 'contact_name'
);
SET @projects_add_contact_name = IF(
  @projects_has_contact_name = 0,
  'ALTER TABLE projects ADD COLUMN contact_name VARCHAR(190) NULL AFTER principal_name',
  'DO 0'
);
PREPARE projects_add_contact_name_stmt FROM @projects_add_contact_name;
EXECUTE projects_add_contact_name_stmt;
DEALLOCATE PREPARE projects_add_contact_name_stmt;
