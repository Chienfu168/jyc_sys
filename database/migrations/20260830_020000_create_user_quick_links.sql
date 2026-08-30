-- 使用者常用連結:各使用者自訂釘選於側邊選單上方的常用功能,依 sort_order 排序。
CREATE TABLE IF NOT EXISTS user_quick_links (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  nav_key VARCHAR(80) NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uk_user_quick_links (user_id, nav_key),
  CONSTRAINT fk_user_quick_links_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,
  INDEX idx_user_quick_links_user_sort (user_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
