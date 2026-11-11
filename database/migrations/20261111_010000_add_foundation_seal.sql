-- 基金會官印:比照既有機構 LOGO 的私有上傳慣例,供聘書／感謝狀等正式文件
-- 於落款處自動帶入官印圖檔(建議上傳透明底 PNG),取代原本的「蓋章處」虛線占位框。
ALTER TABLE foundation_profiles
  ADD COLUMN seal_path VARCHAR(255) NULL AFTER logo_path;
