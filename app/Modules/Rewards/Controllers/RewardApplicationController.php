<?php

namespace App\Modules\Rewards\Controllers;

use App\Core\ApprovalFlow;
use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;
use App\Core\Validator;
use PDO;

/**
 * 員工專案及創新成果獎勵申請:依「員工專案及創新成果獎勵辦法」提出專案成果獎勵金申請。
 * 含申請事由、獎勵人員及金額(多筆)、獎勵理由、經費與擬辦,送出後由主管核定,並可列印申請書用印陳核。
 */
final class RewardApplicationController extends Controller
{
    private const DEFAULT_FUNDING = '擬由本會相關人事獎勵或業務經費項下支應,並依本會會計及相關內部作業規定辦理核銷。';
    private const DEFAULT_PROPOSED = '奉核後,依本會「員工專案及創新成果獎勵辦法」及相關會計程序辦理獎勵金核發事宜。';

    public function index(): void
    {
        $this->requirePermission('rewards.view');

        $stmt = Database::pdo()->query(
            'SELECT reward_applications.*, applicants.name AS applicant_name, reviewers.name AS reviewed_by_name
             FROM reward_applications
             LEFT JOIN users AS applicants ON applicants.id = reward_applications.applicant_id
             LEFT JOIN users AS reviewers ON reviewers.id = reward_applications.reviewed_by
             ORDER BY reward_applications.id DESC'
        );

        $this->render('rewards.index', [
            'title' => '員工獎勵申請',
            'section' => '人事差勤',
            'active' => 'rewards',
            'rewards' => $stmt->fetchAll(),
            'canManage' => Permission::can('rewards.manage'),
            'canApprove' => Permission::can('rewards.approve'),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('rewards.manage');

        $this->render('rewards.form', [
            'title' => '新增員工獎勵申請',
            'section' => '人事差勤',
            'active' => 'rewards',
            'reward' => [
                'title' => '',
                'award_reason' => '',
                'apply_date' => date('Y-m-d'),
                'reason' => '',
                'justification' => '',
                'funding_source' => self::DEFAULT_FUNDING,
                'proposed_action' => self::DEFAULT_PROPOSED,
                'status' => 'draft',
            ],
            'recipients' => [],
            'action' => '/rewards',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('rewards.manage');
        $this->validate('/rewards/create');

        $submit = ($_POST['action'] ?? '') === 'submit';
        $now = now();
        $userId = $this->currentUserId();
        $date = (string) $_POST['apply_date'];
        $lines = $this->parseRecipients();

        Database::pdo()->prepare(
            'INSERT INTO reward_applications
             (application_no, title, award_reason, apply_date, reason, justification, funding_source, proposed_action,
              total_amount, status, applicant_id, submitted_at, created_by, created_at, updated_at)
             VALUES
             (:application_no, :title, :award_reason, :apply_date, :reason, :justification, :funding_source, :proposed_action,
              :total_amount, :status, :applicant_id, :submitted_at, :created_by, :created_at, :updated_at)'
        )->execute($this->payload($lines) + [
            'application_no' => $this->nextNo($date),
            'apply_date' => $date,
            'status' => $submit ? 'submitted' : 'draft',
            'applicant_id' => $userId ?: null,
            'submitted_at' => $submit ? $now : null,
            'created_by' => $userId ?: null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        $this->writeRecipients($id, $lines);
        if ($submit) {
            ApprovalFlow::submit('rewards', 'reward_applications', $id, $this->nullable('reason'));
        }

        AuditLog::write('create', 'rewards', 'reward_applications', $id);
        flash('success', $submit ? '獎勵申請已送出待核定。' : '獎勵申請已儲存為草稿。');
        redirect('/rewards/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('rewards.view');
        $reward = $this->find((int) $id);

        $this->render('rewards.show', [
            'title' => '員工獎勵申請',
            'section' => '人事差勤',
            'active' => 'rewards',
            'reward' => $reward,
            'recipients' => $this->loadRecipients((int) $id),
            'approvalHistory' => ApprovalFlow::history('rewards', 'reward_applications', (int) $id),
            'canManage' => Permission::can('rewards.manage'),
            'canApprove' => Permission::can('rewards.approve'),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('rewards.manage');
        $reward = $this->find((int) $id);
        $this->requireEditable($reward);

        $this->render('rewards.form', [
            'title' => '編輯員工獎勵申請',
            'section' => '人事差勤',
            'active' => 'rewards',
            'reward' => $reward,
            'recipients' => $this->loadRecipients((int) $id),
            'action' => '/rewards/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('rewards.manage');
        $reward = $this->find((int) $id);
        $this->requireEditable($reward);
        $this->validate('/rewards/' . $id . '/edit');

        $submit = ($_POST['action'] ?? '') === 'submit';
        $now = now();
        $date = (string) $_POST['apply_date'];
        $lines = $this->parseRecipients();

        Database::pdo()->prepare(
            'UPDATE reward_applications SET
                title = :title, award_reason = :award_reason, apply_date = :apply_date, reason = :reason,
                justification = :justification, funding_source = :funding_source, proposed_action = :proposed_action,
                total_amount = :total_amount, status = :status, submitted_at = :submitted_at, updated_at = :updated_at
             WHERE id = :id'
        )->execute($this->payload($lines) + [
            'apply_date' => $date,
            'status' => $submit ? 'submitted' : 'draft',
            'submitted_at' => $submit ? ($reward['submitted_at'] ?: $now) : null,
            'updated_at' => $now,
            'id' => (int) $reward['id'],
        ]);

        $this->writeRecipients((int) $reward['id'], $lines);
        if ($submit) {
            ApprovalFlow::submit('rewards', 'reward_applications', (int) $reward['id'], $this->nullable('reason'));
        }

        AuditLog::write('update', 'rewards', 'reward_applications', (int) $reward['id']);
        flash('success', $submit ? '獎勵申請已送出待核定。' : '獎勵申請已更新。');
        redirect('/rewards/' . $id);
    }

    public function submit(string $id): void
    {
        $this->requirePermission('rewards.manage');
        $reward = $this->find((int) $id);
        $this->requireEditable($reward);

        Database::pdo()->prepare(
            'UPDATE reward_applications SET status = "submitted", submitted_at = :now, updated_at = :now2 WHERE id = :id'
        )->execute(['now' => now(), 'now2' => now(), 'id' => (int) $reward['id']]);
        ApprovalFlow::submit('rewards', 'reward_applications', (int) $reward['id'], null);

        AuditLog::write('submit', 'rewards', 'reward_applications', (int) $reward['id']);
        flash('success', '獎勵申請已送出待核定。');
        redirect('/rewards/' . $id);
    }

    public function approve(string $id): void
    {
        $this->requirePermission('rewards.approve');
        $reward = $this->find((int) $id);
        if ($reward['status'] !== 'submitted') {
            flash('error', '僅「待核定」的申請可核定。');
            redirect('/rewards/' . $id);
        }

        $notes = trim((string) ($_POST['review_notes'] ?? ''));
        Database::pdo()->prepare(
            'UPDATE reward_applications SET status = "approved", reviewed_by = :reviewer, reviewed_at = :now,
                review_notes = :notes, updated_at = :now2 WHERE id = :id'
        )->execute([
            'reviewer' => $this->currentUserId() ?: null,
            'now' => now(),
            'notes' => $notes !== '' ? $notes : null,
            'now2' => now(),
            'id' => (int) $reward['id'],
        ]);

        ApprovalFlow::review('rewards', 'reward_applications', (int) $reward['id'], 'approved', $notes);
        AuditLog::write('approve', 'rewards', 'reward_applications', (int) $reward['id']);
        flash('success', '獎勵申請已核定。');
        redirect('/rewards/' . $id);
    }

    public function reject(string $id): void
    {
        $this->requirePermission('rewards.approve');
        $reward = $this->find((int) $id);
        if ($reward['status'] !== 'submitted') {
            flash('error', '僅「待核定」的申請可退回。');
            redirect('/rewards/' . $id);
        }

        $notes = trim((string) ($_POST['review_notes'] ?? ''));
        Database::pdo()->prepare(
            'UPDATE reward_applications SET status = "rejected", reviewed_by = :reviewer, reviewed_at = :now,
                review_notes = :notes, updated_at = :now2 WHERE id = :id'
        )->execute([
            'reviewer' => $this->currentUserId() ?: null,
            'now' => now(),
            'notes' => $notes !== '' ? $notes : null,
            'now2' => now(),
            'id' => (int) $reward['id'],
        ]);

        ApprovalFlow::review('rewards', 'reward_applications', (int) $reward['id'], 'rejected', $notes);
        AuditLog::write('reject', 'rewards', 'reward_applications', (int) $reward['id']);
        flash('success', '獎勵申請已退回。');
        redirect('/rewards/' . $id);
    }

    public function destroy(string $id): void
    {
        $this->requirePermission('rewards.manage');
        $reward = $this->find((int) $id);

        if (!in_array($reward['status'], ['draft', 'rejected'], true) && !Permission::can('rewards.approve')) {
            flash('error', '已送出或已核定的申請僅具核定權限者可刪除,請改為退回。');
            redirect('/rewards/' . $id);
        }

        Database::pdo()->prepare('DELETE FROM reward_applications WHERE id = :id')->execute(['id' => (int) $reward['id']]);
        AuditLog::write('delete', 'rewards', 'reward_applications', (int) $reward['id'], ['title' => $reward['title']]);
        flash('success', '獎勵申請已刪除。');
        redirect('/rewards');
    }

    // ---- 內部輔助 ----

    private function validate(string $path): void
    {
        if ($error = Validator::required($_POST, ['title' => '申請項目', 'apply_date' => '申請日期'])) {
            $this->backWithInput($path, $_POST, $error);
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_POST['apply_date'])) {
            $this->backWithInput($path, $_POST, '申請日期格式不正確。');
        }
        $lines = $this->parseRecipients();
        if ($lines === []) {
            $this->backWithInput($path, $_POST, '請至少填寫一筆獎勵人員(姓名與金額)。');
        }
        foreach ($lines as $line) {
            if ($line['name'] === '') {
                $this->backWithInput($path, $_POST, '每筆獎勵人員都需填寫姓名。');
            }
            if ($line['amount'] <= 0) {
                $this->backWithInput($path, $_POST, '每筆獎勵金額必須大於 0。');
            }
        }
    }

    /**
     * @param array<int, array{name:string, job_title:?string, contribution:?string, amount:float}> $lines
     * @return array<string, mixed>
     */
    private function payload(array $lines): array
    {
        return [
            'title' => trim((string) $_POST['title']),
            'award_reason' => $this->nullable('award_reason'),
            'reason' => $this->nullable('reason'),
            'justification' => $this->nullable('justification'),
            'funding_source' => $this->nullable('funding_source'),
            'proposed_action' => $this->nullable('proposed_action'),
            'total_amount' => $this->totalAmount($lines),
        ];
    }

    /**
     * @return array<int, array{name:string, job_title:?string, contribution:?string, amount:float}>
     */
    private function parseRecipients(): array
    {
        $names = (array) ($_POST['recipient_name'] ?? []);
        $titles = (array) ($_POST['recipient_title'] ?? []);
        $contribs = (array) ($_POST['recipient_contribution'] ?? []);
        $amounts = (array) ($_POST['recipient_amount'] ?? []);

        $lines = [];
        $count = max(count($names), count($amounts));
        for ($i = 0; $i < $count; $i++) {
            $name = trim((string) ($names[$i] ?? ''));
            $amount = round((float) ($amounts[$i] ?? 0), 2);
            $title = trim((string) ($titles[$i] ?? ''));
            $contribution = trim((string) ($contribs[$i] ?? ''));

            if ($name === '' && $amount <= 0 && $title === '' && $contribution === '') {
                continue;
            }
            $lines[] = [
                'name' => $name,
                'job_title' => $title !== '' ? $title : null,
                'contribution' => $contribution !== '' ? $contribution : null,
                'amount' => $amount,
            ];
        }

        return $lines;
    }

    private function writeRecipients(int $rewardId, array $lines): void
    {
        Database::pdo()->prepare('DELETE FROM reward_application_recipients WHERE reward_application_id = :id')
            ->execute(['id' => $rewardId]);

        $stmt = Database::pdo()->prepare(
            'INSERT INTO reward_application_recipients
             (reward_application_id, name, job_title, contribution, amount, sort_order, created_at)
             VALUES (:rid, :name, :job_title, :contribution, :amount, :sort_order, :created_at)'
        );
        $now = now();
        foreach (array_values($lines) as $i => $line) {
            $stmt->execute([
                'rid' => $rewardId,
                'name' => $line['name'],
                'job_title' => $line['job_title'],
                'contribution' => $line['contribution'],
                'amount' => $line['amount'],
                'sort_order' => $i,
                'created_at' => $now,
            ]);
        }
    }

    private function loadRecipients(int $rewardId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, name, job_title, contribution, amount, sort_order
             FROM reward_application_recipients WHERE reward_application_id = :id ORDER BY sort_order, id'
        );
        $stmt->execute(['id' => $rewardId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function totalAmount(array $lines): float
    {
        $sum = 0.0;
        foreach ($lines as $line) {
            $sum += (float) $line['amount'];
        }
        return round($sum, 2);
    }

    private function nullable(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        return $value !== '' ? $value : null;
    }

    private function requireEditable(array $reward): void
    {
        if (!in_array($reward['status'], ['draft', 'rejected'], true)) {
            flash('error', '已送出或已核定的申請不可編輯,請先退回。');
            redirect('/rewards/' . $reward['id']);
        }
    }

    private function nextNo(string $date): string
    {
        $prefix = 'RW' . str_replace('-', '', substr($date, 0, 10)) . '-';
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM reward_applications WHERE application_no LIKE :prefix');
        $stmt->execute(['prefix' => $prefix . '%']);
        return $prefix . str_pad((string) ((int) $stmt->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);
    }

    /** @return array<string, mixed> */
    private function find(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT reward_applications.*, applicants.name AS applicant_name,
                    reviewers.name AS reviewed_by_name, creators.name AS created_by_name
             FROM reward_applications
             LEFT JOIN users AS applicants ON applicants.id = reward_applications.applicant_id
             LEFT JOIN users AS reviewers ON reviewers.id = reward_applications.reviewed_by
             LEFT JOIN users AS creators ON creators.id = reward_applications.created_by
             WHERE reward_applications.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $reward = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$reward) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到獎勵申請']);
            exit;
        }
        return $reward;
    }
}
