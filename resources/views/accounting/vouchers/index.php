<?php
$active = 'accounting';
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>借方合計</span>
        <strong><?= e(accounting_voucher_money($totals['debit'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>貸方合計</span>
        <strong><?= e(accounting_voucher_money($totals['credit'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>借貸差額</span>
        <strong><?= e(accounting_voucher_money($totals['balance'])) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <form class="search bank-filter" method="get" action="/accounting/vouchers">
            <?php require base_path('resources/views/shared/date-scope-filter.php'); ?>
            <select name="status">
                <option value="">全部狀態</option>
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>草稿</option>
                <option value="posted" <?= $status === 'posted' ? 'selected' : '' ?>>已過帳</option>
                <option value="voided" <?= $status === 'voided' ? 'selected' : '' ?>>作廢</option>
            </select>
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/accounting">返回會計系統</a>
            <?php if (\App\Core\Permission::can('accounting.manage')): ?>
                <a class="btn primary" href="/accounting/vouchers/create">新增傳票</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>日期</th>
                <th>傳票號碼</th>
                <th>摘要</th>
                <th class="amount">借方</th>
                <th class="amount">貸方</th>
                <th>狀態</th>
                <th>建立人</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($vouchers as $voucher): ?>
                <tr>
                    <td><?= e(roc_date($voucher['voucher_date'])) ?></td>
                    <td class="mono"><?= e($voucher['voucher_no']) ?></td>
                    <td><?= e($voucher['summary']) ?></td>
                    <td class="amount"><?= e(accounting_voucher_money($voucher['debit_total'])) ?></td>
                    <td class="amount"><?= e(accounting_voucher_money($voucher['credit_total'])) ?></td>
                    <td><span class="badge <?= $voucher['status'] === 'posted' ? 'ok' : 'muted' ?>"><?= e(accounting_voucher_status_label($voucher['status'])) ?></span></td>
                    <td><?= e($voucher['created_by_name'] ?: '-') ?></td>
                    <td class="actions">
                        <a class="btn small" href="/accounting/vouchers/<?= e((string) $voucher['id']) ?>">檢視</a>
                        <?php if (\App\Core\Permission::can('accounting.manage') && $voucher['status'] !== 'posted'): ?>
                            <a class="btn small" href="/accounting/vouchers/<?= e((string) $voucher['id']) ?>/edit">編輯</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$vouchers): ?>
                <tr><td colspan="8" class="empty">尚無會計傳票。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function accounting_voucher_money($value): string
{
    return number_format((float) $value, 0);
}
function accounting_voucher_status_label(string $status): string
{
    return ['draft' => '草稿', 'posted' => '已過帳', 'voided' => '作廢'][$status] ?? $status;
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
