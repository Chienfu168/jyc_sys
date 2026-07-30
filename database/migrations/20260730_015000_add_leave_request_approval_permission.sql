INSERT INTO permissions (code, name, module, created_at, updated_at) VALUES
  ('leave_requests.approve', '核准人事請假', 'leave_requests', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  module = VALUES(module),
  updated_at = NOW();

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.code = 'leave_requests.approve'
WHERE roles.id IN (1, 2);

INSERT INTO approval_requests
  (module, target_type, target_id, action, status, requested_by, requested_at, request_notes, created_at, updated_at)
SELECT 'leave_requests',
       'leave_requests',
       leave_requests.id,
       'submit',
       'pending',
       leave_requests.created_by,
       leave_requests.created_at,
       leave_requests.notes,
       leave_requests.created_at,
       leave_requests.updated_at
FROM leave_requests
WHERE leave_requests.status = 'submitted'
  AND NOT EXISTS (
    SELECT 1
    FROM approval_requests existing
    WHERE existing.module = 'leave_requests'
      AND existing.target_type = 'leave_requests'
      AND existing.target_id = leave_requests.id
      AND existing.status = 'pending'
  );
