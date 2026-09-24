-- 董事會議開會通知:發文字號、發文日期、其他說明(如餐敘等附加事項)。
ALTER TABLE board_meetings
  ADD COLUMN notice_doc_no VARCHAR(60) NULL AFTER attachments,
  ADD COLUMN notice_issue_date DATE NULL AFTER notice_doc_no,
  ADD COLUMN notice_extra TEXT NULL AFTER notice_issue_date;
