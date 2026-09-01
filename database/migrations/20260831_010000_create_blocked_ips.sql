-- 自動封鎖累犯 IP:短時間內大量登入失敗的來源 IP 會被自動暫時封鎖(fail2ban 式),
-- 亦可由管理者手動封鎖／解除。封鎖為暫時性(blocked_until 到期即失效)。
CREATE TABLE IF NOT EXISTS blocked_ips (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45) NOT NULL,
  reason VARCHAR(60) NOT NULL DEFAULT 'login_bruteforce',
  fail_count INT UNSIGNED NOT NULL DEFAULT 0,
  blocked_until DATETIME NOT NULL,
  blocked_by BIGINT UNSIGNED NULL,
  notes VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uk_blocked_ips_ip (ip_address),
  INDEX idx_blocked_ips_until (blocked_until),
  CONSTRAINT fk_blocked_ips_blocked_by
    FOREIGN KEY (blocked_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
