ALTER TABLE foundation_profiles
  ADD COLUMN competent_authority VARCHAR(160) NULL AFTER registration_no,
  ADD COLUMN approval_date DATE NULL AFTER competent_authority,
  ADD COLUMN approval_doc_no VARCHAR(120) NULL AFTER approval_date;
