-- 連結外部（Google 等）公開日曆:以 iCal(.ics)訂閱網址匯入,唯讀顯示於行事曆。
-- 支援多個來源,各自顏色與啟用狀態;抓取後的 ICS 內容快取於 cached_ics,
-- 由「同步」動作或行事曆頁面過期時更新,避免每次載入都連外。
CREATE TABLE IF NOT EXISTS calendar_feeds (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  ics_url VARCHAR(1024) NOT NULL,
  color VARCHAR(20) NOT NULL DEFAULT '#4285F4',
  status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  cached_ics LONGTEXT NULL,
  last_synced_at DATETIME NULL,
  last_error VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  INDEX idx_calendar_feeds_status_sort (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
