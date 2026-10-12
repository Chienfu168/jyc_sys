-- 董事會議出列席人員身分新增「董事長」選項,供簽到表/會議紀錄正確標示董事長身分
-- (與一般董事、列席人員區分)。MODIFY COLUMN 本身可重複執行、不會因已是目標定義而出錯。
ALTER TABLE board_meeting_attendees
  MODIFY COLUMN role ENUM('director', 'chairman', 'observer') NOT NULL DEFAULT 'director';
