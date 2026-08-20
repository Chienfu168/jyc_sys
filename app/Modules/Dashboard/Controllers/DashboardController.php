<?php

namespace App\Modules\Dashboard\Controllers;

use App\Core\ApprovalCatalog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $sources = ApprovalCatalog::sources();
        $pendingApprovals = $this->pendingApprovals($sources);
        $operationalCards = $this->operationalCards();
        $operationalAlerts = $this->operationalAlerts();

        $stats = [
            'users' => (int) Database::pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'roles' => (int) Database::pdo()->query('SELECT COUNT(*) FROM roles')->fetchColumn(),
            'active_users' => (int) Database::pdo()->query('SELECT COUNT(*) FROM users WHERE status = "active"')->fetchColumn(),
            'logs' => (int) Database::pdo()->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn(),
            'pending_approvals' => count($pendingApprovals),
            'overdue_approvals' => count(array_filter($pendingApprovals, static fn (array $row): bool => self::pendingDays($row) >= 3)),
        ];

        $stmt = Database::pdo()->query(
            'SELECT audit_logs.*, users.name AS user_name
             FROM audit_logs
             LEFT JOIN users ON users.id = audit_logs.user_id
             ORDER BY audit_logs.created_at DESC
             LIMIT 8'
        );

        $this->render('dashboard.index', [
            'title' => '工作台',
            'section' => '主功能',
            'active' => 'dashboard',
            'stats' => $stats,
            'logs' => $stmt->fetchAll(),
            'pendingApprovals' => $pendingApprovals,
            'canApproveAny' => $this->canApproveAny($sources),
            'operationalCards' => $operationalCards,
            'operationalAlerts' => $operationalAlerts,
        ]);
    }

    private function operationalCards(): array
    {
        $month = date('Y-m');
        $cards = [];

        if (Permission::can('donations.view')) {
            $cards[] = [
                'label' => '待開捐款收據',
                'count' => $this->count('SELECT COUNT(*) FROM donations WHERE receipt_status = "pending"'),
                'href' => '/donations?year=' . date('Y') . '&receipt_status=pending',
                'tone' => 'warning',
            ];
        }

        if (Permission::can('income_expenses.view')) {
            $cards[] = [
                'label' => '待補收支憑證',
                'count' => $this->count('SELECT COUNT(*) FROM income_expense_records WHERE receipt_status = "pending" AND status != "voided"'),
                'href' => '/income-expenses?month=' . $month,
                'tone' => 'warning',
            ];
        }

        if (Permission::can('accounting.view')) {
            $cards[] = [
                'label' => '本月草稿傳票',
                'count' => $this->count('SELECT COUNT(*) FROM accounting_vouchers WHERE status = "draft" AND DATE_FORMAT(voucher_date, "%Y-%m") = :month', ['month' => $month]),
                'href' => '/accounting/vouchers?month=' . $month . '&status=draft',
                'tone' => 'muted',
            ];
        }

        if (Permission::can('bank_accounts.view')) {
            $cards[] = [
                'label' => '本月未對帳',
                'count' => $this->count('SELECT COUNT(*) FROM bank_account_transactions WHERE reconciliation_status = "unreconciled" AND DATE_FORMAT(transacted_on, "%Y-%m") = :month', ['month' => $month]),
                'href' => '/bank-transactions/reconciliation?month=' . $month . '&status=unreconciled',
                'tone' => 'warning',
            ];
        }

        if (Permission::can('activities.view')) {
            $cards[] = [
                'label' => '14 日內活動',
                'count' => $this->count(
                    'SELECT COUNT(*) FROM activities
                     WHERE status IN ("draft", "published")
                       AND starts_at BETWEEN :start_at AND :end_at',
                    [
                        'start_at' => date('Y-m-d 00:00:00'),
                        'end_at' => date('Y-m-d 23:59:59', strtotime('+14 days')),
                    ]
                ),
                'href' => '/activities?month=' . $month,
                'tone' => 'ok',
            ];
        }

        return $cards;
    }

    private function operationalAlerts(): array
    {
        $alerts = [];

        if (Permission::can('donations.view')) {
            array_push($alerts, ...$this->pendingDonationReceiptAlerts());
        }
        if (Permission::can('income_expenses.view')) {
            array_push($alerts, ...$this->pendingIncomeReceiptAlerts());
        }
        if (Permission::can('accounting.view')) {
            array_push($alerts, ...$this->draftVoucherAlerts());
        }
        if (Permission::can('bank_accounts.view')) {
            array_push($alerts, ...$this->unreconciledBankAlerts());
        }
        if (Permission::can('activities.view')) {
            array_push($alerts, ...$this->upcomingActivityAlerts());
        }

        usort($alerts, static function (array $a, array $b): int {
            if ((int) $a['priority'] !== (int) $b['priority']) {
                return (int) $b['priority'] <=> (int) $a['priority'];
            }

            return strcmp((string) $a['date'], (string) $b['date']);
        });

        return array_slice($alerts, 0, 12);
    }

    private function pendingDonationReceiptAlerts(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT donations.id,
                    donations.donated_at AS item_date,
                    donations.amount,
                    donations.receipt_no,
                    donors.name AS donor_name
             FROM donations
             INNER JOIN donors ON donors.id = donations.donor_id
             WHERE donations.receipt_status = "pending"
             ORDER BY donations.donated_at DESC, donations.id DESC
             LIMIT 5'
        );

        return array_map(static fn (array $row): array => [
            'source' => '捐款收據',
            'status' => '待處理',
            'tone' => 'warning',
            'date' => $row['item_date'],
            'title' => $row['donor_name'],
            'detail' => '金額 ' . number_format((float) $row['amount'], 0) . ($row['receipt_no'] ? ' / ' . $row['receipt_no'] : ''),
            'href' => '/donations/' . $row['id'],
            'priority' => 80,
        ], $stmt->fetchAll());
    }

    private function pendingIncomeReceiptAlerts(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT id, occurred_on AS item_date, item_type, subject, amount, receipt_no
             FROM income_expense_records
             WHERE receipt_status = "pending"
               AND status != "voided"
             ORDER BY occurred_on DESC, id DESC
             LIMIT 5'
        );

        return array_map(static fn (array $row): array => [
            'source' => '收支憑證',
            'status' => '待補件',
            'tone' => 'warning',
            'date' => $row['item_date'],
            'title' => $row['subject'],
            'detail' => (($row['item_type'] ?? '') === 'income' ? '收入 ' : '支出 ') . number_format((float) $row['amount'], 0) . ($row['receipt_no'] ? ' / ' . $row['receipt_no'] : ''),
            'href' => '/income-expenses/' . $row['id'],
            'priority' => 70,
        ], $stmt->fetchAll());
    }

    private function draftVoucherAlerts(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT accounting_vouchers.id,
                    accounting_vouchers.voucher_no,
                    accounting_vouchers.voucher_date AS item_date,
                    accounting_vouchers.summary,
                    COALESCE(SUM(accounting_voucher_lines.debit), 0) AS debit_total,
                    COALESCE(SUM(accounting_voucher_lines.credit), 0) AS credit_total
             FROM accounting_vouchers
             LEFT JOIN accounting_voucher_lines ON accounting_voucher_lines.voucher_id = accounting_vouchers.id
             WHERE accounting_vouchers.status = "draft"
             GROUP BY accounting_vouchers.id
             ORDER BY accounting_vouchers.voucher_date DESC, accounting_vouchers.id DESC
             LIMIT 5'
        );

        return array_map(static function (array $row): array {
            $balanced = round((float) $row['debit_total'], 2) === round((float) $row['credit_total'], 2)
                && (float) $row['debit_total'] > 0;

            return [
                'source' => '會計傳票',
                'status' => $balanced ? '待過帳' : '需檢查',
                'tone' => $balanced ? 'muted' : 'danger',
                'date' => $row['item_date'],
                'title' => ($row['voucher_no'] ?: '未編號') . ' ' . $row['summary'],
                'detail' => '借方 ' . number_format((float) $row['debit_total'], 0) . ' / 貸方 ' . number_format((float) $row['credit_total'], 0),
                'href' => '/accounting/vouchers/' . $row['id'],
                'priority' => $balanced ? 50 : 90,
            ];
        }, $stmt->fetchAll());
    }

    private function unreconciledBankAlerts(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT bank_account_transactions.id,
                    bank_account_transactions.bank_account_id,
                    bank_account_transactions.transacted_on AS item_date,
                    bank_account_transactions.subject,
                    bank_account_transactions.amount,
                    bank_accounts.bank_name,
                    bank_accounts.account_no
             FROM bank_account_transactions
             INNER JOIN bank_accounts ON bank_accounts.id = bank_account_transactions.bank_account_id
             WHERE bank_account_transactions.reconciliation_status = "unreconciled"
             ORDER BY bank_account_transactions.transacted_on DESC, bank_account_transactions.id DESC
             LIMIT 5'
        );

        return array_map(static fn (array $row): array => [
            'source' => '銀行對帳',
            'status' => '未對帳',
            'tone' => 'warning',
            'date' => $row['item_date'],
            'title' => $row['subject'],
            'detail' => trim($row['bank_name'] . ' ' . $row['account_no']) . ' / ' . number_format((float) $row['amount'], 0),
            'href' => '/bank-transactions/reconciliation?month=' . substr((string) $row['item_date'], 0, 7) . '&bank_account_id=' . (int) $row['bank_account_id'] . '&status=unreconciled',
            'priority' => 60,
        ], $stmt->fetchAll());
    }

    private function upcomingActivityAlerts(): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, starts_at AS item_date, title, location, status
             FROM activities
             WHERE status IN ("draft", "published")
               AND starts_at BETWEEN :start_at AND :end_at
             ORDER BY starts_at, id
             LIMIT 5'
        );
        $stmt->execute([
            'start_at' => date('Y-m-d 00:00:00'),
            'end_at' => date('Y-m-d 23:59:59', strtotime('+14 days')),
        ]);

        return array_map(static fn (array $row): array => [
            'source' => '近期活動',
            'status' => $row['status'] === 'published' ? '已發布' : '草稿',
            'tone' => $row['status'] === 'published' ? 'ok' : 'muted',
            'date' => substr((string) $row['item_date'], 0, 10),
            'title' => $row['title'],
            'detail' => trim(substr((string) $row['item_date'], 11, 5) . ' ' . ($row['location'] ?: '')),
            'href' => '/activities/' . $row['id'],
            'priority' => 40,
        ], $stmt->fetchAll());
    }

    private function count(string $sql, array $params = []): int
    {
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function pendingApprovals(array $sources): array
    {
        $rows = [];
        foreach ($sources as $source) {
            $source['can_approve'] = Permission::can($source['permission']);
            array_push($rows, ...$this->pendingApprovalsForSource($source));
        }

        usort($rows, static function (array $a, array $b): int {
            return strcmp((string) ($b['requested_at'] ?? ''), (string) ($a['requested_at'] ?? ''));
        });

        return array_slice($rows, 0, 12);
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

    private function pendingApprovalsForSource(array $source): array
    {
        $where = [
            'approval_requests.module = :module',
            'approval_requests.target_type = :target_type',
            'approval_requests.status = "pending"',
        ];
        $params = [
            'module' => $source['module'],
            'target_type' => $source['target_type'],
        ];

        if (!$source['can_approve']) {
            $where[] = 'approval_requests.requested_by = :user_id';
            $params['user_id'] = auth()->user()['id'] ?? 0;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT approval_requests.*,
                    requesters.name AS requested_by_name,
                    target.' . $source['subject_column'] . ' AS subject,
                    target.' . $source['category_column'] . ' AS category_name,
                    target.' . $source['date_column'] . ' AS occurred_on,
                    target.' . $source['amount_column'] . ' AS amount,
                    target.' . $source['type_column'] . ' AS item_type
             FROM approval_requests
             INNER JOIN ' . $source['table'] . ' AS target ON target.id = approval_requests.target_id
             LEFT JOIN users AS requesters ON requesters.id = approval_requests.requested_by
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY approval_requests.requested_at DESC, approval_requests.id DESC
             LIMIT 12'
        );
        $stmt->execute($params);

        return array_map(static function (array $row) use ($source): array {
            $targetId = (int) $row['target_id'];
            $row['source_label'] = $source['label'];
            $row['can_approve'] = $source['can_approve'];
            $row['show_url'] = $source['show_path'] . $targetId;
            $row['approve_url'] = sprintf($source['approve_path'], $targetId);
            $row['reject_url'] = sprintf($source['reject_path'], $targetId);

            return $row;
        }, $stmt->fetchAll());
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
