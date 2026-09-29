-- 董事會議開會通知:地址來源(預設通訊地址,可選登記地址或自訂),與自訂地址內容。
ALTER TABLE board_meetings
  ADD COLUMN notice_address_mode VARCHAR(20) NOT NULL DEFAULT 'mailing' AFTER notice_extra,
  ADD COLUMN notice_address_custom VARCHAR(255) NULL AFTER notice_address_mode;
