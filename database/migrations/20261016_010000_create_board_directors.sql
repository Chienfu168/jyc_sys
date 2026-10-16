-- 董事名冊:以「人」為中心,獨立於各次董事會議的出列席紀錄之外,維護
-- 董事／監察人的聯絡資料(含地址,供日後列印標籤等用途)與任期狀態。
-- 任期屆滿或異動時標示為卸任(前任),不刪除紀錄,保留歷任名冊。
-- 沿用既有的 board_meetings.view / board_meetings.manage 權限,與董事會議
-- 同一群使用者維護,不另外新增權限項目。

CREATE TABLE IF NOT EXISTS board_directors (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  term_no SMALLINT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  title VARCHAR(60) NULL,
  organization VARCHAR(160) NULL,
  job_title VARCHAR(120) NULL,
  phone VARCHAR(60) NULL,
  mobile VARCHAR(60) NULL,
  email VARCHAR(190) NULL,
  address VARCHAR(255) NULL,
  status ENUM('current', 'former') NOT NULL DEFAULT 'current',
  started_on DATE NULL,
  ended_on DATE NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_board_directors_created_by
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_board_directors_status (status),
  INDEX idx_board_directors_term (term_no),
  INDEX idx_board_directors_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
