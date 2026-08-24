-- 基金會網址:用於收據頁尾等對外文件。
ALTER TABLE foundation_profiles
  ADD COLUMN website VARCHAR(190) NULL AFTER email;
