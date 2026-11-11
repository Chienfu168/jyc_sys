-- 聘書／感謝狀外框樣式:供開立時挑選正式(金色雙框)、獎狀(酒紅實框)、
-- 簡約(細框)等外觀,預設維持既有的正式金色雙框樣式。
ALTER TABLE certificates
  ADD COLUMN frame_style VARCHAR(30) NOT NULL DEFAULT 'classic' AFTER title;
