-- 陳報公文加入正本/副本受文者(公文標準結尾欄位):
-- 正本通常為主管機關,副本為留存單位或其他知會對象。
ALTER TABLE official_letters
  ADD COLUMN main_copy TEXT NULL AFTER attachment_items,
  ADD COLUMN cc_copy TEXT NULL AFTER main_copy;
