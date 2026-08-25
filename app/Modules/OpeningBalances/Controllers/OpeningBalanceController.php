<?php

namespace App\Modules\OpeningBalances\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Domain\OpeningBalances\OpeningBalanceLedger;

/**
 * 期初餘額(年度結轉)管理:以年度為單位設定零用金、收支簿的期初餘額,
 * 並可一鍵帶入前一年度的期末結餘。銀行帳戶採連續結轉(期初餘額 + 交易),
 * 於本頁以參考資訊呈現。
 */
final class OpeningBalanceController extends Controller
{
    /** 帳本型模組的顯示名稱。 */
    private const LEDGER_LABELS = [
        'petty_cash' => '零用金',
        'income_expense' => '收支簿',
    ];

    public function index(): void
    {
        $this->requirePermission('opening_balances.view');

        $year = normalize_fiscal_year($_GET['year'] ?? date('Y'));
        if ($year < 1912 || $year > 2100) {
            $year = (int) date('Y');
        }

        $ledgers = [];
        foreach (self::LEDGER_LABELS as $module => $label) {
            $current = $this->openingBalance($module, 0, $year);
            $previousClosing = OpeningBalanceLedger::closing(
                $this->openingBalance($module, 0, $year - 1),
                ...array_values($this->yearTotals($module, $year - 1))
            );

            $ledgers[] = [
                'module' => $module,
                'label' => $label,
                'opening' => $current,
                'has_opening' => $current !== null,
                'previous_closing' => $previousClosing,
            ];
        }

        $this->render('opening-balances.index', [
            'title' => '期初餘額',
            'section' => '會計與帳務',
            'active' => 'opening-balances',
            'year' => $year,
            'ledgers' => $ledgers,
            'bankAccounts' => $this->bankAccounts(),
            'canManage' => \App\Core\Permission::can('opening_balances.manage'),
            'printable' => false,
        ]);
    }

    public function save(): void
    {
        $this->requirePermission('opening_balances.manage');

        $module = (string) ($_POST['module'] ?? '');
        if (!OpeningBalanceLedger::isLedgerModule($module)) {
            redirect('/opening-balances');
        }

        $year = normalize_fiscal_year($_POST['year'] ?? date('Y'));
        if ($year < 1912 || $year > 2100) {
            redirect('/opening-balances');
        }

        $amount = round((float) ($_POST['opening_balance'] ?? 0), 2);
        $note = trim((string) ($_POST['note'] ?? ''));

        $this->upsert($module, 0, $year, $amount, $note !== '' ? $note : null);

        AuditLog::write('opening_balance_set', 'opening_balances', null, null, [
            'module' => $module,
            'year' => $year,
            'amount' => $amount,
        ]);

        flash('success', self::LEDGER_LABELS[$module] . ' ' . $year . ' 年度期初餘額已更新。');
        redirect('/opening-balances?year=' . $year);
    }

    public function destroy(): void
    {
        $this->requirePermission('opening_balances.delete');

        $module = (string) ($_POST['module'] ?? '');
        if (!OpeningBalanceLedger::isLedgerModule($module)) {
            redirect('/opening-balances');
        }

        $year = normalize_fiscal_year($_POST['year'] ?? date('Y'));
        if ($year < 1912 || $year > 2100) {
            redirect('/opening-balances');
        }

        Database::pdo()->prepare(
            'DELETE FROM opening_balances
             WHERE module = :module AND reference_id = 0 AND fiscal_year = :year'
        )->execute(['module' => $module, 'year' => $year]);

        AuditLog::write('opening_balance_delete', 'opening_balances', null, null, [
            'module' => $module,
            'year' => $year,
        ]);

        flash('success', self::LEDGER_LABELS[$module] . ' ' . $year . ' 年度期初餘額已刪除。');
        redirect('/opening-balances?year=' . $year);
    }

    /** 查詢某模組某年度的期初餘額;未設定回傳 null。 */
    private function openingBalance(string $module, int $referenceId, int $year): ?float
    {
        $stmt = Database::pdo()->prepare(
            'SELECT opening_balance FROM opening_balances
             WHERE module = :module AND reference_id = :reference_id AND fiscal_year = :year
             LIMIT 1'
        );
        $stmt->execute(['module' => $module, 'reference_id' => $referenceId, 'year' => $year]);
        $value = $stmt->fetchColumn();

        return $value === false ? null : (float) $value;
    }

    /**
     * 某模組某年度的收入/支出總額。
     *
     * @return array{income: float, expense: float}
     */
    private function yearTotals(string $module, int $year): array
    {
        if ($module === 'petty_cash') {
            $sql = 'SELECT
                        COALESCE(SUM(CASE WHEN item_type = "income" THEN amount ELSE 0 END), 0) AS income,
                        COALESCE(SUM(CASE WHEN item_type = "expense" THEN amount ELSE 0 END), 0) AS expense
                    FROM petty_cash_entries
                    WHERE YEAR(occurred_on) = :year';
        } else {
            $sql = 'SELECT
                        COALESCE(SUM(CASE WHEN item_type = "income" THEN amount ELSE 0 END), 0) AS income,
                        COALESCE(SUM(CASE WHEN item_type = "expense" THEN amount ELSE 0 END), 0) AS expense
                    FROM income_expense_records
                    WHERE YEAR(occurred_on) = :year AND status <> "voided"';
        }

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['year' => $year]);
        $row = $stmt->fetch() ?: ['income' => 0, 'expense' => 0];

        return ['income' => (float) $row['income'], 'expense' => (float) $row['expense']];
    }

    private function upsert(string $module, int $referenceId, int $year, float $amount, ?string $note): void
    {
        Database::pdo()->prepare(
            'INSERT INTO opening_balances (module, reference_id, fiscal_year, opening_balance, note, created_by, created_at, updated_at)
             VALUES (:module, :reference_id, :year, :amount, :note, :created_by, :created_at, :created_at)
             ON DUPLICATE KEY UPDATE opening_balance = VALUES(opening_balance), note = VALUES(note), updated_at = VALUES(created_at)'
        )->execute([
            'module' => $module,
            'reference_id' => $referenceId,
            'year' => $year,
            'amount' => $amount,
            'note' => $note,
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 銀行帳戶清單(含終身期初餘額與目前結餘,供參考)。
     *
     * @return array<int, array<string, mixed>>
     */
    private function bankAccounts(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT ba.id, ba.bank_name, ba.account_no, ba.opening_balance,
                    ba.opening_balance
                    + COALESCE(SUM(CASE WHEN t.transaction_type IN ("deposit", "interest") THEN t.amount
                                        ELSE -t.amount END), 0) AS current_balance
             FROM bank_accounts ba
             LEFT JOIN bank_account_transactions t ON t.bank_account_id = ba.id
             WHERE ba.status = "active"
             GROUP BY ba.id, ba.bank_name, ba.account_no, ba.opening_balance
             ORDER BY ba.bank_name, ba.account_no'
        );

        return $stmt->fetchAll();
    }
}
