SET @payment_receipts_has_source_type = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'payment_receipts'
    AND COLUMN_NAME = 'source_type'
);
SET @payment_receipts_add_source_type = IF(
  @payment_receipts_has_source_type = 0,
  'ALTER TABLE payment_receipts ADD COLUMN source_type VARCHAR(80) NULL AFTER receipt_no',
  'SELECT 1'
);
PREPARE payment_receipts_add_source_type_stmt FROM @payment_receipts_add_source_type;
EXECUTE payment_receipts_add_source_type_stmt;
DEALLOCATE PREPARE payment_receipts_add_source_type_stmt;

SET @payment_receipts_has_source_id = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'payment_receipts'
    AND COLUMN_NAME = 'source_id'
);
SET @payment_receipts_add_source_id = IF(
  @payment_receipts_has_source_id = 0,
  'ALTER TABLE payment_receipts ADD COLUMN source_id BIGINT UNSIGNED NULL AFTER source_type',
  'SELECT 1'
);
PREPARE payment_receipts_add_source_id_stmt FROM @payment_receipts_add_source_id;
EXECUTE payment_receipts_add_source_id_stmt;
DEALLOCATE PREPARE payment_receipts_add_source_id_stmt;

SET @payment_receipts_has_source_label = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'payment_receipts'
    AND COLUMN_NAME = 'source_label'
);
SET @payment_receipts_add_source_label = IF(
  @payment_receipts_has_source_label = 0,
  'ALTER TABLE payment_receipts ADD COLUMN source_label VARCHAR(190) NULL AFTER source_id',
  'SELECT 1'
);
PREPARE payment_receipts_add_source_label_stmt FROM @payment_receipts_add_source_label;
EXECUTE payment_receipts_add_source_label_stmt;
DEALLOCATE PREPARE payment_receipts_add_source_label_stmt;

SET @payment_receipts_has_source_index = (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'payment_receipts'
    AND INDEX_NAME = 'idx_payment_receipts_source'
);
SET @payment_receipts_add_source_index = IF(
  @payment_receipts_has_source_index = 0,
  'ALTER TABLE payment_receipts ADD INDEX idx_payment_receipts_source (source_type, source_id)',
  'SELECT 1'
);
PREPARE payment_receipts_add_source_index_stmt FROM @payment_receipts_add_source_index;
EXECUTE payment_receipts_add_source_index_stmt;
DEALLOCATE PREPARE payment_receipts_add_source_index_stmt;
