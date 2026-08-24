<?php
$active = 'payroll';
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>應發合計</span>
        <strong><?= e(payroll_money($totals['gross'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>扣款合計</span>
        <strong><?= e(payroll_money($totals['deduction'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>實發合計</span>
        <strong><?= e(payroll_money($totals['net'])) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <form class="search bank-filter" method="get" action="/payroll">
            <?php require base_path('resources/views/shared/date-scope-filter.php'); ?>
            <select name="status">
                <option value="">全部狀態</option>
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>草稿</option>
                <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : '' ?>>已確認</option>
                <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>已付款</option>
                <option value="voided" <?= $status === 'voided' ? 'selected' : '' ?>>作廢</option>
            </select>
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/finance">返回財務會計</a>
            <?php if (\App\Core\Permission::can('payroll.manage')): ?>
                <a class="btn primary" href="/payroll/create">新增薪資</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>月份</th>
                <th>員工</th>
                <th>部門 / 職稱</th>
                <th class="amount">應發</th>
                <th class="amount">扣款</th>
                <th class="amount">實發</th>
                <th>狀態</th>
                <th>傳票</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($records as $record): ?>
                <tr>
                    <td class="mono"><?= e($record['payroll_month']) ?></td>
                    <td>
                        <strong><?= e($record['employee_name']) ?></strong>
                        <div class="muted-text"><?= e($record['employee_no'] ?: '-') ?></div>
                    </td>
                    <td>
                        <?= e($record['department'] ?: '-') ?>
                        <div class="muted-text"><?= e($record['job_title'] ?: '-') ?></div>
                    </td>
                    <td class="amount"><?= e(payroll_money($record['gross_pay'])) ?></td>
                    <td class="amount"><?= e(payroll_money($record['deduction_total'])) ?></td>
                    <td class="amount"><strong><?= e(payroll_money($record['net_pay'])) ?></strong></td>
                    <td><span class="badge <?= $record['payment_status'] === 'paid' ? 'ok' : 'muted' ?>"><?= e(payroll_status_label($record['payment_status'])) ?></span></td>
                    <td>
                        <?php if (!empty($record['accounting_voucher_id'])): ?>
                            <a class="link" href="/accounting/vouchers/<?= e((string) $record['accounting_voucher_id']) ?>"><?= e($record['voucher_no'] ?: '查看傳票') ?></a>
                            <div class="muted-text"><?= e(payroll_voucher_status_label((string) ($record['voucher_status'] ?? ''))) ?></div>
                        <?php else: ?>
                            <span class="muted-text">未建立</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <a class="btn small" href="/payroll/<?= e((string) $record['id']) ?>">檢視</a>
                        <?php if (\App\Core\Permission::can('payroll.manage')): ?>
                            <a class="btn small" href="/payroll/<?= e((string) $record['id']) ?>/edit">編輯</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$records): ?>
                <tr><td colspan="9" class="empty">尚無薪資紀錄。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function payroll_money($value): string
{
    return number_format((float) $value, 0);
}
function payroll_status_label(string $status): string
{
    return ['draft' => '草稿', 'confirmed' => '已確認', 'paid' => '已付款', 'voided' => '作廢'][$status] ?? $status;
}
function payroll_voucher_status_label(string $status): string
{
    return ['draft' => '草稿', 'posted' => '已過帳', 'voided' => '作廢'][$status] ?? ($status ?: '-');
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
