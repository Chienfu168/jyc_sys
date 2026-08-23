<?php

namespace App\Modules\Accounting\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Domain\Accounting\LedgerMath;
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

    public function netAssetsStatement(): void
    {
        $this->requirePermission('accounting.view');
        [$start, $end] = $this->period();
        $previousStart = date('Y-m-d', strtotime($start . ' -1 year'));
        $previousEnd = date('Y-m-d', strtotime($end . ' -1 year'));

        $currentNetAssets = $this->balancesUntil($end, ['net_asset']);
        $previousNetAssets = $this->balancesUntil($previousEnd, ['net_asset']);
        $currentSurplus = $this->surplusBetween($start, $end);
        $previousSurplus = $this->surplusBetween($previousStart, $previousEnd);

        $rows = [
            $this->statementCompareRow('期初淨值', $this->sumSignedRows($previousNetAssets), 0),
            $this->statementCompareRow('本年度稅後賸餘(短絀)', $currentSurplus, $previousSurplus),
            $this->statementCompareRow('其他綜合餘絀', 0, 0),
            $this->statementCompareRow('期末淨值', $this->sumSignedRows($currentNetAssets) + $currentSurplus, $this->sumSignedRows($previousNetAssets) + $previousSurplus),
        ];

        $this->render('accounting.reports.net-assets', [
            'title' => '淨值變動表',
            'section' => '財務會計',
            'active' => 'accounting',
            'startDate' => $start,
            'endDate' => $end,
            'previousStartDate' => $previousStart,
            'previousEndDate' => $previousEnd,
            'rows' => $rows,
            'profile' => foundation_profile(),
        ]);
    }

    public function cashFlowStatement(): void
    {
        $this->requirePermission('accounting.view');
        [$start, $end] = $this->period();
        $previousStart = date('Y-m-d', strtotime($start . ' -1 year'));
        $previousEnd = date('Y-m-d', strtotime($end . ' -1 year'));

        $currentSurplus = $this->surplusBetween($start, $end);
        $previousSurplus = $this->surplusBetween($previousStart, $previousEnd);
        $currentOperating = $this->cashFlowRows($start, $end);
        $previousOperating = $this->cashFlowRows($previousStart, $previousEnd);

        $rows = [
            ['section' => '業務活動之現金流量', 'items' => [
                $this->statementCompareRow('本期稅前賸餘(短絀)', $currentSurplus, $previousSurplus),
                $this->statementCompareRow('折舊及攤銷費用', $currentOperating['depreciation'], $previousOperating['depreciation']),
                $this->statementCompareRow('利息收入', -$currentOperating['interest_income'], -$previousOperating['interest_income']),
                $this->statementCompareRow('與業務活動相關之資產淨變動', -$currentOperating['operating_asset_change'], -$previousOperating['operating_asset_change']),
                $this->statementCompareRow('與業務活動相關之負債淨變動', $currentOperating['operating_liability_change'], $previousOperating['operating_liability_change']),
                $this->statementCompareRow('業務活動之淨現金流入(流出)', $currentOperating['operating_cash'], $previousOperating['operating_cash']),
            ]],
            ['section' => '投資活動之現金流量', 'items' => [
                $this->statementCompareRow('基金、投資及固定資產變動', -$currentOperating['investing_asset_change'], -$previousOperating['investing_asset_change']),
            ]],
            ['section' => '現金及約當現金', 'items' => [
                $this->statementCompareRow('本期現金及約當現金增減數', $currentOperating['cash_change'], $previousOperating['cash_change']),
                $this->statementCompareRow('期初現金及約當現金餘額', $currentOperating['cash_begin'], $previousOperating['cash_begin']),
                $this->statementCompareRow('期末現金及約當現金餘額', $currentOperating['cash_end'], $previousOperating['cash_end']),
            ]],
        ];

        $this->render('accounting.reports.cash-flow', [
            'title' => '現金流量表',
            'section' => '財務會計',
            'active' => 'accounting',
            'startDate' => $start,
            'endDate' => $end,
            'previousStartDate' => $previousStart,
            'previousEndDate' => $previousEnd,
            'rows' => $rows,
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

    private function surplusBetween(string $start, string $end): float
    {
        $rows = $this->accountBalances($start, $end, ['income', 'expense']);
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

    private function statementCompareRow(string $name, float $current, float $previous): array
    {
        $variance = $current - $previous;

        return [
            'name' => $name,
            'current' => $current,
            'previous' => $previous,
            'variance' => $variance,
            'rate' => abs($previous) > 0.005 ? round(($variance / $previous) * 100, 2) : null,
        ];
    }

    private function sumSignedRows(array $rows): float
    {
        $total = 0.0;
        foreach ($rows as $row) {
            $total += $this->signedAmount((float) $row['debit_total'], (float) $row['credit_total'], (string) $row['normal_balance']);
        }

        return $total;
    }

    private function cashFlowRows(string $start, string $end): array
    {
        $begin = date('Y-m-d', strtotime($start . ' -1 day'));
        $cashBegin = $this->sumMatchingBalances($begin, ['現金', '銀行', '存款'], ['基金存款', '定期']);
        $cashEnd = $this->sumMatchingBalances($end, ['現金', '銀行', '存款'], ['基金存款', '定期']);
        $operatingAssetsBegin = $this->sumMatchingBalances($begin, ['應收', '預付', '其他流動資產'], []);
        $operatingAssetsEnd = $this->sumMatchingBalances($end, ['應收', '預付', '其他流動資產'], []);
        $operatingLiabilitiesBegin = $this->sumMatchingBalances($begin, ['應付', '代收', '其他流動負債'], []);
        $operatingLiabilitiesEnd = $this->sumMatchingBalances($end, ['應付', '代收', '其他流動負債'], []);
        $investingBegin = $this->sumMatchingBalances($begin, ['基金存款', '定期', '設備', '保證金', '固定資產'], []);
        $investingEnd = $this->sumMatchingBalances($end, ['基金存款', '定期', '設備', '保證金', '固定資產'], []);
        $depreciation = $this->sumPeriodByName($start, $end, ['折舊', '攤銷'], ['expense']);
        $interestIncome = $this->sumPeriodByName($start, $end, ['利息', '財務收入'], ['income']);
        $surplus = $this->surplusBetween($start, $end);
        $operatingAssetChange = $operatingAssetsEnd - $operatingAssetsBegin;
        $operatingLiabilityChange = $operatingLiabilitiesEnd - $operatingLiabilitiesBegin;
        $investingAssetChange = $investingEnd - $investingBegin;

        return [
            'cash_begin' => $cashBegin,
            'cash_end' => $cashEnd,
            'cash_change' => $cashEnd - $cashBegin,
            'depreciation' => $depreciation,
            'interest_income' => $interestIncome,
            'operating_asset_change' => $operatingAssetChange,
            'operating_liability_change' => $operatingLiabilityChange,
            'investing_asset_change' => $investingAssetChange,
            'operating_cash' => $surplus + $depreciation - $interestIncome - $operatingAssetChange + $operatingLiabilityChange,
        ];
    }

    private function sumMatchingBalances(string $end, array $includeKeywords, array $excludeKeywords): float
    {
        $rows = $this->balancesUntil($end, ['asset', 'liability']);
        $total = 0.0;
        foreach ($rows as $row) {
            $name = (string) $row['name'];
            $included = false;
            foreach ($includeKeywords as $keyword) {
                if (mb_strpos($name, $keyword) !== false) {
                    $included = true;
                    break;
                }
            }
            foreach ($excludeKeywords as $keyword) {
                if (mb_strpos($name, $keyword) !== false) {
                    $included = false;
                    break;
                }
            }
            if ($included) {
                $total += $this->signedAmount((float) $row['debit_total'], (float) $row['credit_total'], (string) $row['normal_balance']);
            }
        }

        return $total;
    }

    private function sumPeriodByName(string $start, string $end, array $keywords, array $types): float
    {
        $rows = $this->accountBalances($start, $end, $types);
        $total = 0.0;
        foreach ($rows as $row) {
            foreach ($keywords as $keyword) {
                if (mb_strpos((string) $row['name'], $keyword) !== false) {
                    $total += $this->signedAmount((float) $row['debit_total'], (float) $row['credit_total'], (string) $row['normal_balance']);
                    break;
                }
            }
        }

        return $total;
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
        return LedgerMath::signedAmount($debit, $credit, $normalBalance);
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
            'ending_debit' => array_sum(array_map(static fn (array $row): float => LedgerMath::endingDebit((float) $row['ending_balance'], (string) $row['normal_balance']), $rows)),
            'ending_credit' => array_sum(array_map(static fn (array $row): float => LedgerMath::endingCredit((float) $row['ending_balance'], (string) $row['normal_balance']), $rows)),
        ];
    }
}
