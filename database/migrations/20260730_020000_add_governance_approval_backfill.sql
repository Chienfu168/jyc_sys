INSERT INTO approval_requests
  (module, target_type, target_id, action, status, requested_by, requested_at, request_notes, created_at, updated_at)
SELECT 'annual_budgets',
       'annual_budgets',
       annual_budgets.id,
       'submit',
       'pending',
       annual_budgets.created_by,
       annual_budgets.created_at,
       annual_budgets.notes,
       annual_budgets.created_at,
       annual_budgets.updated_at
FROM annual_budgets
WHERE annual_budgets.status = 'submitted'
  AND NOT EXISTS (
    SELECT 1
    FROM approval_requests existing
    WHERE existing.module = 'annual_budgets'
      AND existing.target_type = 'annual_budgets'
      AND existing.target_id = annual_budgets.id
      AND existing.status = 'pending'
  );

INSERT INTO approval_requests
  (module, target_type, target_id, action, status, requested_by, requested_at, request_notes, created_at, updated_at)
SELECT 'work_plans',
       'work_plans',
       work_plans.id,
       'submit',
       'pending',
       work_plans.created_by,
       work_plans.created_at,
       work_plans.notes,
       work_plans.created_at,
       work_plans.updated_at
FROM work_plans
WHERE work_plans.status = 'submitted'
  AND NOT EXISTS (
    SELECT 1
    FROM approval_requests existing
    WHERE existing.module = 'work_plans'
      AND existing.target_type = 'work_plans'
      AND existing.target_id = work_plans.id
      AND existing.status = 'pending'
  );
