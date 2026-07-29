<?php
$active = 'income-expenses';
$documentTitle = '收支紀錄單';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2><?= e($record['subject']) ?></h2>
            <p class="muted-text"><?= e(roc_date($record['occurred_on'])) ?>，<?= e($record['item_type'] === 'income' ? '收入' : '支出') ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/income-expenses">返回列表</a>
            <?php if (\App\Core\Permission::can('income_expenses.manage')): ?>
                <a class="btn" href="/income-expenses/<?= e((string) $record['id']) ?>/edit">編輯</a>
                <?php if (\App\Core\Permission::can('accounting.manage') && $record['status'] === 'confirmed' && empty($record['accounting_voucher_id'])): ?>
                    <form method="post" action="/income-expenses/<?= e((string) $record['id']) ?>/voucher">
                        <?= csrf_field() ?>
                        <button class="btn primary" type="submit">建立會計傳票</button>
                    </form>
                <?php endif; ?>
                <?php if (!empty($record['accounting_voucher_id'])): ?>
                    <a class="btn" href="/accounting/vouchers/<?= e((string) $record['accounting_voucher_id']) ?>">查看傳票</a>
                <?php endif; ?>
                <?php if ($record['status'] !== 'voided'): ?>
                    <form method="post" action="/income-expenses/<?= e((string) $record['id']) ?>/void">
                        <?= csrf_field() ?>
                        <button class="btn" type="submit">作廢</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr><th>日期</th><td><?= e(roc_date($record['occurred_on'])) ?></td><th>類型</th><td><?= e($record['item_type'] === 'income' ? '收入' : '支出') ?></td></tr>
        <tr><th>科目</th><td><?= e($record['category_name']) ?></td><th>金額</th><td><?= e(number_format((float) $record['amount'], 0)) ?></td></tr>
        <tr><th>摘要</th><td colspan="3"><?= e($record['subject']) ?></td></tr>
        <tr><th>對象</th><td><?= e($record['counterparty'] ?: '-') ?></td><th>統編</th><td><?= e($record['counterparty_tax_id'] ?: '-') ?></td></tr>
        <tr><th>付款方式</th><td><?= e($record['payment_method'] ?: '-') ?></td><th>銀行帳戶</th><td><?= e(!empty($record['bank_name']) ? $record['bank_name'] . ' / ' . $record['account_no'] : '-') ?></td></tr>
        <tr><th>專案</th><td><?= e($record['project_name'] ?: '-') ?></td><th>收據狀態</th><td><?= e(income_expense_show_receipt_label($record['receipt_status'])) ?></td></tr>
        <tr><th>憑證號碼</th><td><?= e($record['receipt_no'] ?: '-') ?></td><th>紀錄狀態</th><td><?= e(income_expense_show_status_label($record['status'])) ?></td></tr>
        <tr>
            <th>會計傳票</th>
            <td colspan="3">
                <?php if (!empty($record['accounting_voucher_id'])): ?>
                    <a class="text-link mono" href="/accounting/vouchers/<?= e((string) $record['accounting_voucher_id']) ?>">
                        <?= e(($record['voucher_no'] ?: '已建立') . ' / ' . income_expense_show_voucher_label($record['voucher_status'] ?? '')) ?>
                    </a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>
        <tr><th>備註</th><td colspan="3"><?= nl2br(e($record['notes'] ?: '-')) ?></td></tr>
        </tbody>
    </table>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function income_expense_show_status_label(string $status): string
{
    return ['draft' => '草稿', 'confirmed' => '確認', 'voided' => '作廢'][$status] ?? $status;
}
function income_expense_show_receipt_label(string $status): string
{
    return ['not_required' => '免開', 'pending' => '待處理', 'issued' => '已開立', 'voided' => '作廢'][$status] ?? $status;
}
function income_expense_show_voucher_label(string $status): string
{
    return ['draft' => '草稿', 'posted' => '已過帳', 'voided' => '作廢'][$status] ?? $status;
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
