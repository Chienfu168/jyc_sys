<?php

namespace App\Modules\Approvals\Controllers;

use App\Core\ApprovalCatalog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;

final class ApprovalController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $sources = ApprovalCatalog::sources();
        $module = $this->moduleFilter($sources);
        $status = $this->statusFilter();
        $scope = ($_GET['scope'] ?? '') === 'mine' ? 'mine' : 'all';

        $rows = $this->approvalRows($sources, $module, $status, $scope);
        $stats = $this->approvalStats($sources, $module, $scope);
        $stats['overdue'] = count(array_filter($rows, static fn (array $row): bool => $row['status'] === 'pending' && self::pendingDays($row) >= 3));

        $this->render('approvals.index', [
            'title' => '簽核中心',
            'section' => '主功能',
            'active' => 'approvals',
            'sources' => $sources,
            'module' => $module,
            'status' => $status,
            'scope' => $scope,
            'rows' => $rows,
            'stats' => $stats,
            'canApproveAny' => $this->canApproveAny($sources),
        ]);
    }

    private function approvalRows(array $sources, string $module, string $status, string $scope): array
    {
        $rows = [];
        foreach ($this->filteredSources($sources, $module) as $source) {
            array_push($rows, ...$this->approvalRowsForSource($source, $status, $scope));
        }

        usort($rows, static function (array $a, array $b): int {
            return strcmp((string) ($b['sort_at'] ?? ''), (string) ($a['sort_at'] ?? ''));
        });

        return $rows;
    }

    private function approvalRowsForSource(array $source, string $status, string $scope): array
    {
        $canApprove = Permission::can($source['permission']);
        $where = [
            'approval_requests.module = :module',
            'approval_requests.target_type = :target_type',
        ];
        $params = [
            'module' => $source['module'],
            'target_type' => $source['target_type'],
        ];

        if ($status !== '') {
            $where[] = 'approval_requests.status = :status';
            $params['status'] = $status;
        }
        if ($scope === 'mine' || !$canApprove) {
            $where[] = 'approval_requests.requested_by = :user_id';
            $params['user_id'] = auth()->user()['id'] ?? 0;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT approval_requests.*,
                    requesters.name AS requested_by_name,
                    reviewers.name AS reviewed_by_name,
                    target.' . $source['subject_column'] . ' AS subject,
                    target.' . $source['category_column'] . ' AS category_name,
                    target.' . $source['date_column'] . ' AS occurred_on,
                    target.' . $source['amount_column'] . ' AS amount,
                    target.' . $source['type_column'] . ' AS item_type
             FROM approval_requests
             INNER JOIN ' . $source['table'] . ' AS target ON target.id = approval_requests.target_id
             LEFT JOIN users AS requesters ON requesters.id = approval_requests.requested_by
             LEFT JOIN users AS reviewers ON reviewers.id = approval_requests.reviewed_by
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY approval_requests.created_at DESC, approval_requests.id DESC
             LIMIT 100'
        );
        $stmt->execute($params);

        return array_map(static function (array $row) use ($source, $canApprove): array {
            $targetId = (int) $row['target_id'];
            $row['source_label'] = $source['label'];
            $row['can_approve'] = $canApprove;
            $row['show_url'] = $source['show_path'] . $targetId;
            $row['approve_url'] = sprintf($source['approve_path'], $targetId);
            $row['reject_url'] = sprintf($source['reject_path'], $targetId);
            $row['sort_at'] = $row['reviewed_at'] ?: ($row['requested_at'] ?: $row['created_at']);

            return $row;
        }, $stmt->fetchAll());
    }

    private function approvalStats(array $sources, string $module, string $scope): array
    {
        $stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0, 'total' => 0];
        foreach ($this->filteredSources($sources, $module) as $source) {
            $canApprove = Permission::can($source['permission']);
            $where = [
                'module = :module',
                'target_type = :target_type',
            ];
            $params = [
                'module' => $source['module'],
                'target_type' => $source['target_type'],
            ];
            if ($scope === 'mine' || !$canApprove) {
                $where[] = 'requested_by = :user_id';
                $params['user_id'] = auth()->user()['id'] ?? 0;
            }

            $stmt = Database::pdo()->prepare(
                'SELECT status, COUNT(*) AS count
                 FROM approval_requests
                 WHERE ' . implode(' AND ', $where) . '
                 GROUP BY status'
            );
            $stmt->execute($params);
            foreach ($stmt->fetchAll() as $row) {
                $key = (string) $row['status'];
                if (array_key_exists($key, $stats)) {
                    $stats[$key] += (int) $row['count'];
                }
                $stats['total'] += (int) $row['count'];
            }
        }

        return $stats;
    }

    private function filteredSources(array $sources, string $module): array
    {
        if ($module === '') {
            return $sources;
        }

        return array_values(array_filter($sources, static fn (array $source): bool => $source['module'] === $module));
    }

    private function moduleFilter(array $sources): string
    {
        $module = (string) ($_GET['module'] ?? '');
        if ($module === '') {
            return '';
        }

        foreach ($sources as $source) {
            if ($source['module'] === $module) {
                return $module;
            }
        }

        return '';
    }

    private function statusFilter(): string
    {
        $status = (string) ($_GET['status'] ?? 'pending');
        return in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true) ? $status : '';
    }

    private function canApproveAny(array $sources): bool
    {
        foreach ($sources as $source) {
            if (Permission::can($source['permission'])) {
                return true;
            }
        }

        return false;
    }

    private static function pendingDays(array $row): int
    {
        $date = substr((string) ($row['requested_at'] ?: ($row['created_at'] ?? '')), 0, 10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return 0;
        }

        $requested = new \DateTimeImmutable($date);
        $today = new \DateTimeImmutable(date('Y-m-d'));

        return max(0, (int) $requested->diff($today)->days);
    }
}
