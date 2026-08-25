-- 捐款編號:依捐款日期自動產生「YYYYMMDD-NNN」當日流水號,便於引用尚未開立收據的捐款。
ALTER TABLE donations
  ADD COLUMN donation_no VARCHAR(40) NULL AFTER donated_at,
  ADD UNIQUE KEY uk_donations_donation_no (donation_no);

-- 既有捐款紀錄回填:依捐款日期分組、以 id 排序給當日流水號(MySQL 8 / MariaDB 10.6+ 視窗函式)。
UPDATE donations AS d
JOIN (
  SELECT id,
         CONCAT(
           DATE_FORMAT(donated_at, '%Y%m%d'), '-',
           LPAD(ROW_NUMBER() OVER (PARTITION BY DATE(donated_at) ORDER BY id), 3, '0')
         ) AS generated_no
  FROM donations
) AS seq ON seq.id = d.id
SET d.donation_no = seq.generated_no
WHERE d.donation_no IS NULL;
