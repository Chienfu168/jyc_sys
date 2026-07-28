CREATE TABLE IF NOT EXISTS petty_cash_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_type ENUM('income', 'expense') NOT NULL DEFAULT 'expense',
  name VARCHAR(120) NOT NULL,
  default_amount DECIMAL(12,2) NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_petty_cash_items_type_name (item_type, name),
  INDEX idx_petty_cash_items_status_sort (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS petty_cash_entries (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  occurred_on DATE NOT NULL,
  item_type ENUM('income', 'expense') NOT NULL DEFAULT 'expense',
  petty_cash_item_id BIGINT UNSIGNED NULL,
  item_name VARCHAR(160) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  payment_to VARCHAR(160) NULL,
  receipt_no VARCHAR(80) NULL,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_petty_cash_entries_item
    FOREIGN KEY (petty_cash_item_id) REFERENCES petty_cash_items(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_petty_cash_entries_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_petty_cash_entries_occurred_on (occurred_on),
  INDEX idx_petty_cash_entries_type (item_type),
  INDEX idx_petty_cash_entries_item (petty_cash_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO petty_cash_items (item_type, name, default_amount, sort_order, status, created_at, updated_at) VALUES
  ('expense', '文具用品', 350, 10, 'active', NOW(), NOW()),
  ('expense', '郵資快遞', 120, 20, 'active', NOW(), NOW()),
  ('expense', '交通車資', 250, 30, 'active', NOW(), NOW()),
  ('expense', '會議餐點', 800, 40, 'active', NOW(), NOW()),
  ('expense', '影印輸出', 300, 50, 'active', NOW(), NOW()),
  ('expense', '清潔用品', 450, 60, 'active', NOW(), NOW()),
  ('expense', '活動雜支', 1000, 70, 'active', NOW(), NOW()),
  ('expense', '茶水點心', 500, 80, 'active', NOW(), NOW()),
  ('expense', '停車費', 100, 90, 'active', NOW(), NOW()),
  ('expense', '小額修繕', 1200, 100, 'active', NOW(), NOW()),
  ('income', '零用金撥補', 10000, 10, 'active', NOW(), NOW()),
  ('income', '代墊歸還', 500, 20, 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  default_amount = VALUES(default_amount),
  sort_order = VALUES(sort_order),
  status = VALUES(status),
  updated_at = NOW();

INSERT INTO petty_cash_entries
  (occurred_on, item_type, petty_cash_item_id, item_name, amount, payment_to, receipt_no, notes, created_by, created_at, updated_at)
SELECT entry_data.occurred_on,
       entry_data.item_type,
       petty_cash_items.id,
       entry_data.item_name,
       entry_data.amount,
       entry_data.payment_to,
       entry_data.receipt_no,
       entry_data.notes,
       NULL,
       NOW(),
       NOW()
FROM (
  SELECT DATE_SUB(CURDATE(), INTERVAL 1 DAY) AS occurred_on, 'income' AS item_type, '零用金撥補' AS item_name, 10000.00 AS amount, '銀行提領' AS payment_to, 'PC-001' AS receipt_no, '本月零用金撥補' AS notes
  UNION ALL SELECT DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'expense', '文具用品', 420.00, '金玉堂文具', 'PC-002', '資料夾與原子筆'
  UNION ALL SELECT DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'expense', '郵資快遞', 160.00, '中華郵政', 'PC-003', '掛號郵資'
  UNION ALL SELECT DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'expense', '交通車資', 315.00, '計程車', 'PC-004', '洽公交通'
  UNION ALL SELECT DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'expense', '會議餐點', 980.00, '便當店', 'PC-005', '工作會議餐盒'
  UNION ALL SELECT DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'expense', '影印輸出', 260.00, '影印行', 'PC-006', '活動講義輸出'
  UNION ALL SELECT DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'expense', '清潔用品', 520.00, '五金百貨', 'PC-007', '洗手乳與垃圾袋'
  UNION ALL SELECT DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'expense', '活動雜支', 1100.00, '活動用品店', 'PC-008', '活動識別證與膠帶'
  UNION ALL SELECT DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'expense', '茶水點心', 650.00, '超商', 'PC-009', '志工會議茶點'
  UNION ALL SELECT DATE_SUB(CURDATE(), INTERVAL 9 DAY), 'expense', '停車費', 120.00, '停車場', 'PC-010', '洽公停車'
  UNION ALL SELECT DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'expense', '小額修繕', 1350.00, '水電行', 'PC-011', '辦公室燈具維修'
  UNION ALL SELECT DATE_SUB(CURDATE(), INTERVAL 11 DAY), 'income', '代墊歸還', 600.00, '同仁歸還', 'PC-012', '活動代墊款歸還'
  UNION ALL SELECT DATE_SUB(CURDATE(), INTERVAL 12 DAY), 'expense', '文具用品', 280.00, '文具店', 'PC-013', '標籤紙'
  UNION ALL SELECT DATE_SUB(CURDATE(), INTERVAL 13 DAY), 'expense', '郵資快遞', 95.00, '快遞公司', 'PC-014', '文件寄送'
  UNION ALL SELECT DATE_SUB(CURDATE(), INTERVAL 14 DAY), 'expense', '交通車資', 210.00, '捷運與公車', 'PC-015', '外出洽公'
  UNION ALL SELECT DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'expense', '會議餐點', 760.00, '餐飲店', 'PC-016', '董事會茶點'
  UNION ALL SELECT DATE_SUB(CURDATE(), INTERVAL 16 DAY), 'expense', '影印輸出', 410.00, '輸出中心', 'PC-017', '課程資料'
  UNION ALL SELECT DATE_SUB(CURDATE(), INTERVAL 17 DAY), 'expense', '活動雜支', 890.00, '百貨行', 'PC-018', '活動材料'
  UNION ALL SELECT DATE_SUB(CURDATE(), INTERVAL 18 DAY), 'expense', '清潔用品', 330.00, '賣場', 'PC-019', '環境整理用品'
  UNION ALL SELECT DATE_SUB(CURDATE(), INTERVAL 19 DAY), 'expense', '茶水點心', 480.00, '超商', 'PC-020', '講師接待茶水'
) AS entry_data
LEFT JOIN petty_cash_items
  ON petty_cash_items.item_type = entry_data.item_type
 AND petty_cash_items.name = entry_data.item_name
WHERE NOT EXISTS (
  SELECT 1
  FROM petty_cash_entries existing
  WHERE existing.receipt_no = entry_data.receipt_no
);
