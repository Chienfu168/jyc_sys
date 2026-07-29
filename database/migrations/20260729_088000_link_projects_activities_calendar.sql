ALTER TABLE activities
  ADD COLUMN project_id BIGINT UNSIGNED NULL AFTER id,
  ADD INDEX idx_activities_project_id (project_id),
  ADD CONSTRAINT fk_activities_project_id
    FOREIGN KEY (project_id) REFERENCES projects(id)
    ON DELETE SET NULL;

UPDATE activities
SET project_id = (SELECT id FROM projects WHERE project_code = 'PRJ-115-001' LIMIT 1)
WHERE title = '暑期閱讀陪伴工作坊'
  AND project_id IS NULL;

UPDATE activities
SET project_id = (SELECT id FROM projects WHERE project_code = 'PRJ-115-002' LIMIT 1)
WHERE title = '社區營造培力講座'
  AND project_id IS NULL;

INSERT INTO calendar_events
  (title, event_type, starts_at, ends_at, all_day, location, owner_name, reminder_minutes, status, description, source_module, source_id, created_by, created_at, updated_at)
SELECT
  activities.title,
  'activity',
  activities.starts_at,
  activities.ends_at,
  0,
  activities.location,
  users.name,
  60,
  CASE activities.status
    WHEN 'closed' THEN 'done'
    WHEN 'cancelled' THEN 'cancelled'
    ELSE 'scheduled'
  END,
  activities.description,
  'activities',
  activities.id,
  activities.created_by,
  NOW(),
  NOW()
FROM activities
LEFT JOIN users ON users.id = activities.created_by
LEFT JOIN calendar_events
  ON calendar_events.source_module = 'activities'
 AND calendar_events.source_id = activities.id
WHERE calendar_events.id IS NULL;
