<?php

namespace App\Modules\Accounting\Controllers;

use App\Core\Controller;
use App\Core\Database;

final class AccountingController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('accounting.view');

        $month = date('Y-m');
        $summary = [
            'account_count' => 0,
            'voucher_count' => 0,
            'posted_count' => 0,
            'debit_total' => 0,
            'credit_total' => 0,
        ];

        try {
            $summary['account_count'] = (int) Database::pdo()
                ->query('SELECT COUNT(*) FROM accounting_accounts WHERE status = "active"')
                ->fetchColumn();

            $stmt = Database::pdo()->prepare(
                'SELECT COUNT(*) AS voucher_count,
                        SUM(status = "posted") AS posted_count
                 FROM accounting_vouchers
                 WHERE DATE_FORMAT(voucher_date, "%Y-%m") = :month'
            );
            $stmt->execute(['month' => $month]);
            $row = $stmt->fetch() ?: [];
            $summary['voucher_count'] = (int) ($row['voucher_count'] ?? 0);
            $summary['posted_count'] = (int) ($row['posted_count'] ?? 0);

            $stmt = Database::pdo()->prepare(
                'SELECT COALESCE(SUM(accounting_voucher_lines.debit), 0) AS debit_total,
                        COALESCE(SUM(accounting_voucher_lines.credit), 0) AS credit_total
                 FROM accounting_vouchers
                 INNER JOIN accounting_voucher_lines ON accounting_voucher_lines.voucher_id = accounting_vouchers.id
                 WHERE DATE_FORMAT(accounting_vouchers.voucher_date, "%Y-%m") = :month
                   AND accounting_vouchers.status != "voided"'
            );
            $stmt->execute(['month' => $month]);
            $totals = $stmt->fetch() ?: [];
            $summary['debit_total'] = (float) ($totals['debit_total'] ?? 0);
            $summary['credit_total'] = (float) ($totals['credit_total'] ?? 0);
        } catch (\Throwable) {
            flash('error', '會計資料表尚未建立，請先執行系統更新或安裝 migration。');
        }

        $this->render('accounting.index', [
            'title' => '會計系統',
            'section' => '財務會計',
            'active' => 'accounting',
            'month' => $month,
            'summary' => $summary,
        ]);
    }
}
