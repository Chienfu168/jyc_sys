<?php

namespace App\Modules\Accounting\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

final class ReportController extends Controller
{
    public function generalLedger(): void
    {
        $this->requirePermission('accounting.view');
        [$start, $end] = $this->period();

        $stmt = Database::pdo()->prepare(
            'SELECT accounting_accounts.id,
                    accounting_accounts.code,
                    accounting_accounts.name,
                    accounting_accounts.account_type,
                    accounting_accounts.normal_balance,
                    COALESCE(period_totals.debit_total, 0) AS debit_total,
                    COALESCE(period_totals.credit_total, 0) AS credit_total
             FROM accounting_accounts
             LEFT JOIN (
                SELECT accounting_voucher_lines.account_id,
                       SUM(accounting_voucher_lines.debit) AS debit_total,
                       SUM(accounting_voucher_lines.credit) AS credit_total
                FROM accounting_voucher_lines
                INNER JOIN accounting_vouchers ON accounting_vouchers.id = accounting_voucher_lines.voucher_id
                WHERE accounting_vouchers.status = "posted"
                  AND accounting_vouchers.voucher_date BETWEEN :start_date AND :end_date
                GROUP BY accounting_voucher_lines.account_id
             ) AS period_totals ON period_totals.account_id = accounting_accounts.id
             WHERE accounting_accounts.status = "active"
             ORDER BY accounting_accounts.sort_order, accounting_accounts.code'
        );
        $stmt->execute(['start_date' => $start, 'end_date' => $end]);
        $accounts = $stmt->fetchAll();

        $this->render('accounting.reports.general-ledger', [
            'title' => '總帳',
            'section' => '財務會計',
            'active' => 'accounting',
            'startDate' => $start,
            'endDate' => $end,
            'accounts' => $accounts,
            'totals' => $this->totals($accounts),
            'profile' => foundation_profile(),
        ]);
    }

    public function detailLedger(): void
    {
        $this->requirePermission('accounting.view');
        [$start, $end] = $this->period();
        $accountId = (int) ($_GET['account_id'] ?? 0);
        $accounts = $this->accounts();
        $selected = $this->selectedAccount($accounts, $accountId);

        $opening = 0.0;
        $lines = [];
        if ($selected) {
            $opening = $this->openingBalance((int) $selected['id'], $start, (string) $selected['normal_balance']);
            $lines = $this->ledgerLines((int) $selected['id'], $start, $end);
        }

        $running = $opening;
        foreach ($lines as &$line) {
            $movement = $this->signedAmount((float) $line['debit'], (float) $line['credit'], (string) ($selected['normal_balance'] ?? 'debit'));
            $running += $movement;
            $line['running_balance'] = $running;
        }
        unset($line);

        $this->render('accounting.reports.detail-ledger', [
            'title' => '明細帳',
            'section' => '財務會計',
            'active' => 'accounting',
            'startDate' => $start,
            'endDate' => $end,
            'accounts' => $accounts,
            'accountId' => $accountId,
            'selected' => $selected,
            'opening' => $opening,
            'lines' => $lines,
            'totals' => $this->lineTotals($lines),
            'ending' => $running,
            'profile' => foundation_profile(),
        ]);
    }

    public function trialBalance(): void
    {
        $this->requirePermission('accounting.view');
        [$start, $end] = $this->period();

        $stmt = Database::pdo()->prepare(
            'SELECT accounting_accounts.id,
                    accounting_accounts.code,
                    accounting_accounts.name,
                    accounting_accounts.account_type,
                    accounting_accounts.normal_balance,
                    COALESCE(opening_totals.debit_total, 0) AS opening_debit,
                    COALESCE(opening_totals.credit_total, 0) AS opening_credit,
                    COALESCE(period_totals.debit_total, 0) AS period_debit,
                    COALESCE(period_totals.credit_total, 0) AS period_credit
             FROM accounting_accounts
             LEFT JOIN (
                SELECT accounting_voucher_lines.account_id,
                       SUM(accounting_voucher_lines.debit) AS debit_total,
                       SUM(accounting_voucher_lines.credit) AS credit_total
                FROM accounting_voucher_lines
                INNER JOIN accounting_vouchers ON accounting_vouchers.id = accounting_voucher_lines.voucher_id
                WHERE accounting_vouchers.status = "posted"
                  AND accounting_vouchers.voucher_date < :start_date
                GROUP BY accounting_voucher_lines.account_id
             ) AS opening_totals ON opening_totals.account_id = accounting_accounts.id
             LEFT JOIN (
                SELECT accounting_voucher_lines.account_id,
                       SUM(accounting_voucher_lines.debit) AS debit_total,
                       SUM(accounting_voucher_lines.credit) AS credit_total
                FROM accounting_voucher_lines
                INNER JOIN accounting_vouchers ON accounting_vouchers.id = accounting_voucher_lines.voucher_id
                WHERE accounting_vouchers.status = "posted"
                  AND accounting_vouchers.voucher_date BETWEEN :start_date_period AND :end_date
                GROUP BY accounting_voucher_lines.account_id
             ) AS period_totals ON period_totals.account_id = accounting_accounts.id
             WHERE accounting_accounts.status = "active"
             ORDER BY accounting_accounts.sort_order, accounting_accounts.code'
        );
        $stmt->execute([
            'start_date' => $start,
            'start_date_period' => $start,
            'end_date' => $end,
        ]);
        $accounts = $stmt->fetchAll();

        $rows = array_map(function (array $account): array {
            $opening = $this->signedAmount((float) $account['opening_debit'], (float) $account['opening_credit'], (string) $account['normal_balance']);
            $period = $this->signedAmount((float) $account['period_debit'], (float) $account['period_credit'], (string) $account['normal_balance']);
            $ending = $opening + $period;

            return $account + [
                'opening_balance' => $opening,
                'ending_balance' => $ending,
            ];
        }, $accounts);

        $this->render('accounting.reports.trial-balance', [
            'title' => '試算表',
            'section' => '財務會計',
            'active' => 'accounting',
            'startDate' => $start,
            'endDate' => $end,
            'rows' => $rows,
            'totals' => $this->trialTotals($rows),
            'profile' => foundation_profile(),
        ]);
    }

    public function incomeStatement(): void
    {
        $this->requirePermission('accounting.view');
        [$start, $end] = $this->period();

        $rows = $this->accountBalances($start, $end, ['income', 'expense']);
        $incomeRows = [];
        $expenseRows = [];

        foreach ($rows as $row) {
            $amount = $this->signedAmount((float) $row['debit_total'], (float) $row['credit_total'], (string) $row['normal_balance']);
            if (round($amount, 2) === 0.0) {
                continue;
            }

            $row['amount'] = $amount;
            if ($row['account_type'] === 'income') {
                $incomeRows[] = $row;
            } elseif ($row['account_type'] === 'expense') {
                $expenseRows[] = $row;
            }
        }

        $incomeTotal = array_sum(array_map(static fn (array $row): float => (float) $row['amount'], $incomeRows));
        $expenseTotal = array_sum(array_map(static fn (array $row): float => (float) $row['amount'], $expenseRows));

        $this->render('accounting.reports.income-statement', [
            'title' => '收支餘絀表',
            'section' => '財務會計',
            'active' => 'accounting',
            'startDate' => $start,
            'endDate' => $end,
            'incomeRows' => $incomeRows,
            'expenseRows' => $expenseRows,
            'totals' => [
                'income' => $incomeTotal,
                'expense' => $expenseTotal,
                'surplus' => $incomeTotal - $expenseTotal,
            ],
            'profile' => foundation_profile(),
        ]);
    }

    public function balanceSheet(): void
    {
        $this->requirePermission('accounting.view');
        $end = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['end_date'] ?? '')) ? (string) $_GET['end_date'] : date('Y-m-t');

        $rows = $this->balancesUntil($end, ['asset', 'liability', 'net_asset']);
        $assetRows = [];
        $liabilityRows = [];
        $netAssetRows = [];

        foreach ($rows as $row) {
            $amount = $this->signedAmount((float) $row['debit_total'], (float) $row['credit_total'], (string) $row['normal_balance']);
            if (round($amount, 2) === 0.0) {
                continue;
            }

            $row['amount'] = $amount;
            if ($row['account_type'] === 'asset') {
                $assetRows[] = $row;
            } elseif ($row['account_type'] === 'liability') {
                $liabilityRows[] = $row;
            } elseif ($row['account_type'] === 'net_asset') {
                $netAssetRows[] = $row;
            }
        }

        $currentSurplus = $this->surplusUntil($end);
        $assetTotal = array_sum(array_map(static fn (array $row): float => (float) $row['amount'], $assetRows));
        $liabilityTotal = array_sum(array_map(static fn (array $row): float => (float) $row['amount'], $liabilityRows));
        $netAssetTotal = array_sum(array_map(static fn (array $row): float => (float) $row['amount'], $netAssetRows)) + $currentSurplus;

        $this->render('accounting.reports.balance-sheet', [
            'title' => '資產負債表',
            'section' => '財務會計',
            'active' => 'accounting',
            'endDate' => $end,
            'assetRows' => $assetRows,
            'liabilityRows' => $liabilityRows,
            'netAssetRows' => $netAssetRows,
            'currentSurplus' => $currentSurplus,
            'totals' => [
                'assets' => $assetTotal,
                'liabilities' => $liabilityTotal,
                'net_assets' => $netAssetTotal,
                'liability_and_net_assets' => $liabilityTotal + $netAssetTotal,
                'balance' => $assetTotal - $liabilityTotal - $netAssetTotal,
            ],
            'profile' => foundation_profile(),
        ]);
    }

    private function period(): array
    {
        $start = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['start_date'] ?? '')) ? (string) $_GET['start_date'] : date('Y-m-01');
        $end = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['end_date'] ?? '')) ? (string) $_GET['end_date'] : date('Y-m-t');
        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }

    private function accounts(): array
    {
        return Database::pdo()->query(
            'SELECT id, code, name, normal_balance
             FROM accounting_accounts
             WHERE status = "active"
             ORDER BY sort_order, code'
        )->fetchAll();
    }

    private function accountBalances(string $start, string $end, array $types): array
    {
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $stmt = Database::pdo()->prepare(
            'SELECT accounting_accounts.id,
                    accounting_accounts.code,
                    accounting_accounts.name,
                    accounting_accounts.account_type,
                    accounting_accounts.normal_balance,
                    COALESCE(SUM(accounting_voucher_lines.debit), 0) AS debit_total,
                    COALESCE(SUM(accounting_voucher_lines.credit), 0) AS credit_total
             FROM accounting_accounts
             LEFT JOIN accounting_voucher_lines ON accounting_voucher_lines.account_id = accounting_accounts.id
             LEFT JOIN accounting_vouchers ON accounting_vouchers.id = accounting_voucher_lines.voucher_id
                AND accounting_vouchers.status = "posted"
                AND accounting_vouchers.voucher_date BETWEEN ? AND ?
             WHERE accounting_accounts.status = "active"
               AND accounting_accounts.account_type IN (' . $placeholders . ')
             GROUP BY accounting_accounts.id
             ORDER BY accounting_accounts.sort_order, accounting_accounts.code'
        );
        $stmt->execute(array_merge([$start, $end], $types));

        return $stmt->fetchAll();
    }

    private function balancesUntil(string $end, array $types): array
    {
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $stmt = Database::pdo()->prepare(
            'SELECT accounting_accounts.id,
                    accounting_accounts.code,
                    accounting_accounts.name,
                    accounting_accounts.account_type,
                    accounting_accounts.normal_balance,
                    COALESCE(SUM(accounting_voucher_lines.debit), 0) AS debit_total,
                    COALESCE(SUM(accounting_voucher_lines.credit), 0) AS credit_total
             FROM accounting_accounts
             LEFT JOIN accounting_voucher_lines ON accounting_voucher_lines.account_id = accounting_accounts.id
             LEFT JOIN accounting_vouchers ON accounting_vouchers.id = accounting_voucher_lines.voucher_id
                AND accounting_vouchers.status = "posted"
                AND accounting_vouchers.voucher_date <= ?
             WHERE accounting_accounts.status = "active"
               AND accounting_accounts.account_type IN (' . $placeholders . ')
             GROUP BY accounting_accounts.id
             ORDER BY accounting_accounts.sort_order, accounting_accounts.code'
        );
        $stmt->execute(array_merge([$end], $types));

        return $stmt->fetchAll();
    }

    private function surplusUntil(string $end): float
    {
        $rows = $this->balancesUntil($end, ['income', 'expense']);
        $income = 0.0;
        $expense = 0.0;
        foreach ($rows as $row) {
            $amount = $this->signedAmount((float) $row['debit_total'], (float) $row['credit_total'], (string) $row['normal_balance']);
            if ($row['account_type'] === 'income') {
                $income += $amount;
            } elseif ($row['account_type'] === 'expense') {
                $expense += $amount;
            }
        }

        return $income - $expense;
    }

    private function selectedAccount(array $accounts, int $accountId): ?array
    {
        foreach ($accounts as $account) {
            if ((int) $account['id'] === $accountId) {
                return $account;
            }
        }

        return null;
    }

    private function openingBalance(int $accountId, string $start, string $normalBalance): float
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COALESCE(SUM(accounting_voucher_lines.debit), 0) AS debit_total,
                    COALESCE(SUM(accounting_voucher_lines.credit), 0) AS credit_total
             FROM accounting_voucher_lines
             INNER JOIN accounting_vouchers ON accounting_vouchers.id = accounting_voucher_lines.voucher_id
             WHERE accounting_vouchers.status = "posted"
               AND accounting_vouchers.voucher_date < :start_date
               AND accounting_voucher_lines.account_id = :account_id'
        );
        $stmt->execute(['start_date' => $start, 'account_id' => $accountId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return $this->signedAmount((float) ($row['debit_total'] ?? 0), (float) ($row['credit_total'] ?? 0), $normalBalance);
    }

    private function ledgerLines(int $accountId, string $start, string $end): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT accounting_vouchers.id AS voucher_id,
                    accounting_vouchers.voucher_no,
                    accounting_vouchers.voucher_date,
                    accounting_vouchers.summary,
                    accounting_voucher_lines.description,
                    accounting_voucher_lines.debit,
                    accounting_voucher_lines.credit
             FROM accounting_voucher_lines
             INNER JOIN accounting_vouchers ON accounting_vouchers.id = accounting_voucher_lines.voucher_id
             WHERE accounting_vouchers.status = "posted"
               AND accounting_voucher_lines.account_id = :account_id
               AND accounting_vouchers.voucher_date BETWEEN :start_date AND :end_date
             ORDER BY accounting_vouchers.voucher_date, accounting_vouchers.voucher_no, accounting_voucher_lines.id'
        );
        $stmt->execute([
            'account_id' => $accountId,
            'start_date' => $start,
            'end_date' => $end,
        ]);

        return $stmt->fetchAll();
    }

    private function signedAmount(float $debit, float $credit, string $normalBalance): float
    {
        return $normalBalance === 'credit' ? $credit - $debit : $debit - $credit;
    }

    private function totals(array $accounts): array
    {
        return [
            'debit' => array_sum(array_map(static fn (array $account): float => (float) $account['debit_total'], $accounts)),
            'credit' => array_sum(array_map(static fn (array $account): float => (float) $account['credit_total'], $accounts)),
        ];
    }

    private function lineTotals(array $lines): array
    {
        return [
            'debit' => array_sum(array_map(static fn (array $line): float => (float) $line['debit'], $lines)),
            'credit' => array_sum(array_map(static fn (array $line): float => (float) $line['credit'], $lines)),
        ];
    }

    private function trialTotals(array $rows): array
    {
        return [
            'period_debit' => array_sum(array_map(static fn (array $row): float => (float) $row['period_debit'], $rows)),
            'period_credit' => array_sum(array_map(static fn (array $row): float => (float) $row['period_credit'], $rows)),
            'ending_debit' => array_sum(array_map(static fn (array $row): float => $row['normal_balance'] === 'debit' ? max(0, (float) $row['ending_balance']) : max(0, -(float) $row['ending_balance']), $rows)),
            'ending_credit' => array_sum(array_map(static fn (array $row): float => $row['normal_balance'] === 'credit' ? max(0, (float) $row['ending_balance']) : max(0, -(float) $row['ending_balance']), $rows)),
        ];
    }
}
