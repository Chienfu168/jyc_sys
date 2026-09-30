-- 講師費用由「一次服務一筆」改為「一人一月一筆」,底下可新增多筆上課明細
-- (日期、上課地點、時數、鐘點費、交通費、小計),月結時自動加總為當月應付
-- 金額。lecturer_expenses 保留 hours/hourly_rate/lecture_fee/transportation_fee
-- 欄位作為「彙總快取」,由 lecturer_expense_sessions 加總後回寫,不再由使用者
-- 直接於月紀錄表單填寫,避免既有(已建立會計傳票／領據)紀錄的欄位語意被破壞。

CREATE TABLE IF NOT EXISTS lecturer_expense_sessions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lecturer_expense_id BIGINT UNSIGNED NOT NULL,
  session_date DATE NOT NULL,
  location VARCHAR(160) NULL,
  hours DECIMAL(8,2) NOT NULL DEFAULT 0,
  hourly_rate DECIMAL(12,2) NOT NULL DEFAULT 0,
  lecture_fee DECIMAL(14,2) NOT NULL DEFAULT 0,
  transportation_fee DECIMAL(14,2) NOT NULL DEFAULT 0,
  subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
  notes VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_lecturer_expense_sessions_expense
    FOREIGN KEY (lecturer_expense_id) REFERENCES lecturer_expenses(id)
    ON DELETE CASCADE,
  INDEX idx_lecturer_expense_sessions_expense (lecturer_expense_id),
  INDEX idx_lecturer_expense_sessions_date (session_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 既有「一次服務一筆」的紀錄,回填為該筆月紀錄底下唯一一筆上課明細,
-- 保留原本的日期／時數／鐘點費／交通費,金額彙總不變,既有會計傳票／
-- 領據的關聯(source_id 對應 lecturer_expenses.id)完全不受影響。
INSERT INTO lecturer_expense_sessions
  (lecturer_expense_id, session_date, location, hours, hourly_rate, lecture_fee, transportation_fee, subtotal, sort_order, created_at, updated_at)
SELECT
  id, expense_date, NULL, hours, hourly_rate, lecture_fee, transportation_fee,
  (lecture_fee + transportation_fee), 1, NOW(), NOW()
FROM lecturer_expenses
WHERE NOT EXISTS (
  SELECT 1 FROM lecturer_expense_sessions WHERE lecturer_expense_sessions.lecturer_expense_id = lecturer_expenses.id
);
