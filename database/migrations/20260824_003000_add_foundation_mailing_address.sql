-- 通訊地址:與會址(登記地址)分開,供文件寄送使用;未填時各文件回退使用會址。
ALTER TABLE foundation_profiles
  ADD COLUMN mailing_address VARCHAR(255) NULL AFTER address;
