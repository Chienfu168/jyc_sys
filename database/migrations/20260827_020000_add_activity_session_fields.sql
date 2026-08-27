ALTER TABLE activities
  ADD COLUMN session_no SMALLINT UNSIGNED NULL AFTER project_id,
  ADD COLUMN session_topic VARCHAR(200) NULL AFTER session_no,
  ADD INDEX idx_activities_session (project_id, session_no);
