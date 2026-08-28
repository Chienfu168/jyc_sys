-- 董事會議議程／紀錄對齊主管機關核備格式:
-- 每個討論案由補上「說明」「擬辦」;會議補上「主席致詞」(議程用)與「附件」(紀錄用)。
ALTER TABLE board_meeting_agenda_items
  ADD COLUMN explanation TEXT NULL AFTER subject,
  ADD COLUMN proposal TEXT NULL AFTER explanation;

ALTER TABLE board_meetings
  ADD COLUMN chair_remarks TEXT NULL AFTER recorder,
  ADD COLUMN attachments TEXT NULL AFTER extempore_motions;
