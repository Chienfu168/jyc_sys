-- 董事會議檔案:議程／會議紀錄的附件檔案,以及會後留存的簽到簿掃描檔。
CREATE TABLE IF NOT EXISTS board_meeting_files (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  board_meeting_id BIGINT UNSIGNED NOT NULL,
  category ENUM('attachment', 'signin_sheet') NOT NULL DEFAULT 'attachment',
  title VARCHAR(160) NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_path VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NULL,
  file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  uploaded_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_board_meeting_files_meeting
    FOREIGN KEY (board_meeting_id) REFERENCES board_meetings(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_board_meeting_files_uploader
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_board_meeting_files_meeting (board_meeting_id, category, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
