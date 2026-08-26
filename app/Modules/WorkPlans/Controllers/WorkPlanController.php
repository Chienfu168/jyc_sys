<?php

namespace App\Modules\WorkPlans\Controllers;

use App\Core\AuditLog;
use App\Core\ApprovalFlow;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;
use App\Core\Validator;
use PDO;

final class WorkPlanController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('work_plans.view');

        $year = normalize_fiscal_year($_GET['year'] ?? date('Y'));
        if ($year < 1912 || $year > 2100) {
            $year = (int) date('Y');
        }

        $stmt = Database::pdo()->prepare(
            'SELECT work_plans.*, users.name AS created_by_name,
                    COALESCE(item_totals.budget_total, 0) AS budget_total
             FROM work_plans
             LEFT JOIN users ON users.id = work_plans.created_by
             LEFT JOIN (
                SELECT work_plan_id, SUM(budget_amount) AS budget_total
                FROM work_plan_items
                GROUP BY work_plan_id
             ) AS item_totals ON item_totals.work_plan_id = work_plans.id
             WHERE work_plans.fiscal_year = :year
             ORDER BY work_plans.period_start, work_plans.id'
        );
        $stmt->execute(['year' => $year]);

        $this->render('work-plans.index', [
            'title' => '工作計畫',
            'section' => '主管機關核備',
            'active' => 'work-plans',
            'year' => $year,
            'plans' => $stmt->fetchAll(),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('work_plans.manage');
        $year = (int) date('Y');

        $this->render('work-plans.create', [
            'title' => '新增工作計畫',
            'section' => '主管機關核備',
            'active' => 'work-plans',
            'plan' => [
                'fiscal_year' => $year,
                'plan_code' => '',
                'title' => roc_year($year) . ' 年度工作計畫',
                'department' => '',
                'owner_name' => auth()->user()['name'] ?? '',
                'period_start' => $year . '-01-01',
                'period_end' => $year . '-12-31',
                'objective' => '',
                'target_audience' => '',
                'service_area' => '',
                'expected_outcomes' => '',
                'performance_indicators' => '',
                'status' => 'draft',
                'notes' => '',
            ],
            'items' => $this->defaultItems(),
            'action' => '/work-plans',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('work_plans.manage');
        $this->validatePlan('/work-plans/create');

        try {
            Database::pdo()->beginTransaction();
            Database::pdo()->prepare(
                'INSERT INTO work_plans
                 (fiscal_year, plan_code, title, department, owner_name, period_start, period_end, objective, target_audience, service_area, expected_outcomes, performance_indicators, status, notes, created_by, created_at, updated_at)
                 VALUES
                 (:fiscal_year, :plan_code, :title, :department, :owner_name, :period_start, :period_end, :objective, :target_audience, :service_area, :expected_outcomes, :performance_indicators, :status, :notes, :created_by, :created_at, :updated_at)'
            )->execute($this->payload() + [
                'created_by' => auth()->user()['id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $id = (int) Database::pdo()->lastInsertId();
            $this->replaceItems($id, $_POST['items'] ?? []);
            Database::pdo()->commit();
        } catch (\Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->backWithInput('/work-plans/create', $_POST, '工作計畫儲存失敗：' . $e->getMessage());
        }

        if (($this->payload()['status'] ?? '') === 'submitted') {
            ApprovalFlow::submit('work_plans', 'work_plans', $id, trim((string) ($_POST['notes'] ?? '')));
        }
        AuditLog::write('create', 'work_plans', 'work_plans', $id);
        flash('success', '工作計畫已建立。');
        redirect('/work-plans/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('work_plans.view');
        $plan = $this->findPlan((int) $id);
        $items = $this->items((int) $id);

        $this->render('work-plans.show', [
            'title' => $plan['title'],
            'section' => '主管機關核備',
            'active' => 'work-plans',
            'plan' => $plan,
            'items' => $items,
            'budgetTotal' => $this->budgetTotal($items),
            'approvalHistory' => ApprovalFlow::history('work_plans', 'work_plans', (int) $id),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('work_plans.manage');
        $plan = $this->findPlan((int) $id);

        if ($plan['status'] === 'approved' && !Permission::can('work_plans.approve')) {
            flash('error', '已核定的工作計畫需具核定權限才能編修。');
            redirect('/work-plans/' . $id);
        }

        $this->render('work-plans.edit', [
            'title' => '編輯工作計畫',
            'section' => '主管機關核備',
            'active' => 'work-plans',
            'plan' => $plan,
            'items' => $this->items((int) $id),
            'action' => '/work-plans/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('work_plans.manage');
        $plan = $this->findPlan((int) $id);

        if ($plan['status'] === 'approved' && !Permission::can('work_plans.approve')) {
            flash('error', '已核定的工作計畫需具核定權限才能編修。');
            redirect('/work-plans/' . $id);
        }

        $this->validatePlan('/work-plans/' . $id . '/edit');
        $payload = $this->payload();

        try {
            Database::pdo()->beginTransaction();
            Database::pdo()->prepare(
                'UPDATE work_plans
                 SET fiscal_year = :fiscal_year,
                     plan_code = :plan_code,
                     title = :title,
                     department = :department,
                     owner_name = :owner_name,
                     period_start = :period_start,
                     period_end = :period_end,
                     objective = :objective,
                     target_audience = :target_audience,
                     service_area = :service_area,
                     expected_outcomes = :expected_outcomes,
                     performance_indicators = :performance_indicators,
                     status = :status,
                     notes = :notes,
                     updated_at = :updated_at
                 WHERE id = :id'
            )->execute($payload + [
                'updated_at' => now(),
                'id' => (int) $id,
            ]);

            $this->replaceItems((int) $id, $_POST['items'] ?? []);
            Database::pdo()->commit();
        } catch (\Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->backWithInput('/work-plans/' . $id . '/edit', $_POST, '工作計畫更新失敗：' . $e->getMessage());
        }

        if ($payload['status'] === 'submitted' && $plan['status'] !== 'submitted') {
            ApprovalFlow::submit('work_plans', 'work_plans', (int) $id, trim((string) ($_POST['notes'] ?? '')));
        }
        AuditLog::write('update', 'work_plans', 'work_plans', (int) $id);
        flash('success', '工作計畫已更新。');
        redirect('/work-plans/' . $id);
    }

    public function submit(string $id): void
    {
        $this->requirePermission('work_plans.manage');
        $plan = $this->findPlan((int) $id);

        if ($plan['status'] === 'approved') {
            flash('error', '已核准的工作計畫不可重新送審。');
            redirect('/work-plans/' . $id);
        }

        ApprovalFlow::submit('work_plans', 'work_plans', (int) $plan['id'], trim((string) ($_POST['request_notes'] ?? '')));
        Database::pdo()->prepare(
            'UPDATE work_plans
             SET status = "submitted", updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'updated_at' => now(),
            'id' => (int) $plan['id'],
        ]);

        AuditLog::write('submit', 'work_plans', 'work_plans', (int) $plan['id']);
        flash('success', '工作計畫已送審。');
        redirect('/work-plans/' . $id);
    }

    public function approve(string $id): void
    {
        $this->requirePermission('work_plans.approve');
        ApprovalFlow::review('work_plans', 'work_plans', (int) $id, 'approved', trim((string) ($_POST['review_notes'] ?? '')));

        Database::pdo()->prepare(
            'UPDATE work_plans
             SET status = "approved", approved_by = :approved_by, approved_at = :approved_at, updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'approved_by' => auth()->user()['id'] ?? null,
            'approved_at' => now(),
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('approve', 'work_plans', 'work_plans', (int) $id);
        flash('success', '工作計畫已核定。');
        redirect('/work-plans/' . $id);
    }

    public function reject(string $id): void
    {
        $this->requirePermission('work_plans.approve');
        ApprovalFlow::review('work_plans', 'work_plans', (int) $id, 'rejected', trim((string) ($_POST['review_notes'] ?? '')));

        Database::pdo()->prepare(
            'UPDATE work_plans
             SET status = "draft", approved_by = NULL, approved_at = NULL, updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('reject', 'work_plans', 'work_plans', (int) $id);
        flash('success', '工作計畫已退回。');
        redirect('/work-plans/' . $id);
    }

    public function destroy(string $id): void
    {
        $plan = $this->findPlan((int) $id);
        $this->requireManageOrOwner('work_plans.manage', $plan['created_by'] ?? null);

        Database::pdo()->prepare('DELETE FROM work_plans WHERE id = :id')->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'work_plans', 'work_plans', (int) $id, ['title' => $plan['title'] ?? null]);
        flash('success', '工作計畫已刪除。');
        redirect('/work-plans');
    }

    private function validatePlan(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'fiscal_year' => '民國年度',
            'title' => '計畫名稱',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        $year = normalize_fiscal_year($_POST['fiscal_year']);
        if ($year < 1912 || $year > 2100) {
            $this->backWithInput($path, $_POST, '年度格式不正確。');
        }
    }

    private function payload(): array
    {
        return [
            'fiscal_year' => normalize_fiscal_year($_POST['fiscal_year']),
            'plan_code' => trim((string) ($_POST['plan_code'] ?? '')),
            'title' => trim((string) $_POST['title']),
            'department' => trim((string) ($_POST['department'] ?? '')),
            'owner_name' => trim((string) ($_POST['owner_name'] ?? '')),
            'period_start' => $this->dateOrNull('period_start'),
            'period_end' => $this->dateOrNull('period_end'),
            'objective' => trim((string) ($_POST['objective'] ?? '')),
            'target_audience' => trim((string) ($_POST['target_audience'] ?? '')),
            'service_area' => trim((string) ($_POST['service_area'] ?? '')),
            'expected_outcomes' => trim((string) ($_POST['expected_outcomes'] ?? '')),
            'performance_indicators' => trim((string) ($_POST['performance_indicators'] ?? '')),
            'status' => $this->statusValue(),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];
    }

    private function statusValue(): string
    {
        $status = (string) ($_POST['status'] ?? 'draft');
        if ($status === 'approved' && Permission::can('work_plans.approve')) {
            return 'approved';
        }

        return in_array($status, ['draft', 'submitted', 'closed'], true) ? $status : 'draft';
    }

    private function replaceItems(int $planId, array $items): void
    {
        Database::pdo()->prepare('DELETE FROM work_plan_items WHERE work_plan_id = :work_plan_id')
            ->execute(['work_plan_id' => $planId]);

        $stmt = Database::pdo()->prepare(
            'INSERT INTO work_plan_items
             (work_plan_id, period_label, activity_name, description, expected_output, budget_amount, notes, sort_order, created_at, updated_at)
             VALUES (:work_plan_id, :period_label, :activity_name, :description, :expected_output, :budget_amount, :notes, :sort_order, :created_at, :updated_at)'
        );

        $sort = 1;
        foreach ($items as $item) {
            $activity = trim((string) ($item['activity_name'] ?? ''));
            $description = trim((string) ($item['description'] ?? ''));
            $amount = round((float) ($item['budget_amount'] ?? 0), 2);
            if ($activity === '' && $description === '' && $amount == 0.0) {
                continue;
            }

            $stmt->execute([
                'work_plan_id' => $planId,
                'period_label' => trim((string) ($item['period_label'] ?? '')),
                'activity_name' => $activity ?: '未命名工作項目',
                'description' => $description,
                'expected_output' => trim((string) ($item['expected_output'] ?? '')),
                'budget_amount' => max(0, $amount),
                'notes' => trim((string) ($item['notes'] ?? '')),
                'sort_order' => $sort++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function findPlan(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT work_plans.*, users.name AS created_by_name, approvers.name AS approved_by_name
             FROM work_plans
             LEFT JOIN users ON users.id = work_plans.created_by
             LEFT JOIN users AS approvers ON approvers.id = work_plans.approved_by
             WHERE work_plans.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到工作計畫']);
            exit;
        }

        return $plan;
    }

    private function items(int $planId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM work_plan_items
             WHERE work_plan_id = :work_plan_id
             ORDER BY sort_order, id'
        );
        $stmt->execute(['work_plan_id' => $planId]);

        return $stmt->fetchAll();
    }

    private function budgetTotal(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $total += (float) $item['budget_amount'];
        }

        return $total;
    }

    private function defaultItems(): array
    {
        return [
            ['period_label' => '第 1 季', 'activity_name' => '', 'description' => '', 'expected_output' => '', 'budget_amount' => '', 'notes' => ''],
            ['period_label' => '第 2 季', 'activity_name' => '', 'description' => '', 'expected_output' => '', 'budget_amount' => '', 'notes' => ''],
            ['period_label' => '第 3 季', 'activity_name' => '', 'description' => '', 'expected_output' => '', 'budget_amount' => '', 'notes' => ''],
            ['period_label' => '第 4 季', 'activity_name' => '', 'description' => '', 'expected_output' => '', 'budget_amount' => '', 'notes' => ''],
        ];
    }

    private function dateOrNull(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
