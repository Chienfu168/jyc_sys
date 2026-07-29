<?php
$active = 'income-expenses';
$documentTitle = '收支紀錄明細';
$approvalStatus = (string) ($record['approval_status'] ?? 'draft');
$canManage = \App\Core\Permission::can('income_expenses.manage');
$canApprove = \App\Core\Permission::can('income_expenses.approve');
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <p class="eyebrow">財務會計</p>
            <h2><?= e($record['subject']) ?></h2>
            <p class="muted-text"><?= e(roc_date($record['occurred_on'])) ?> / <?= e($record['item_type'] === 'income' ? '收入' : '支出') ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/income-expenses">返回列表</a>
            <?php if ($canManage): ?>
                <a class="btn" href="/income-expenses/<?= e((string) $record['id']) ?>/edit">編輯</a>
                <?php if (in_array($approvalStatus, ['draft', 'rejected'], true) && $record['status'] !== 'voided'): ?>
                    <form method="post" action="/income-expenses/<?= e((string) $record['id']) ?>/submit">
                        <?= csrf_field() ?>
                        <button class="btn primary" type="submit">送審</button>
                    </form>
                <?php endif; ?>
                <?php if (\App\Core\Permission::can('accounting.manage') && $record['status'] === 'confirmed' && $approvalStatus === 'approved' && empty($record['accounting_voucher_id'])): ?>
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
        <tr><th>分類</th><td><?= e($record['category_name']) ?></td><th>金額</th><td><?= e(number_format((float) $record['amount'], 0)) ?></td></tr>
        <tr><th>主旨</th><td colspan="3"><?= e($record['subject']) ?></td></tr>
        <tr><th>對象</th><td><?= e($record['counterparty'] ?: '-') ?></td><th>統一編號</th><td><?= e($record['counterparty_tax_id'] ?: '-') ?></td></tr>
        <tr><th>付款方式</th><td><?= e($record['payment_method'] ?: '-') ?></td><th>銀行帳戶</th><td><?= e(!empty($record['bank_name']) ? $record['bank_name'] . ' / ' . $record['account_no'] : '-') ?></td></tr>
        <tr><th>專案</th><td><?= e($record['project_name'] ?: ($record['linked_project_name'] ?? '-')) ?></td><th>憑證狀態</th><td><?= e(income_expense_show_receipt_label($record['receipt_status'])) ?></td></tr>
        <tr><th>憑證號碼</th><td><?= e($record['receipt_no'] ?: '-') ?></td><th>紀錄狀態</th><td><?= e(income_expense_show_status_label($record['status'])) ?></td></tr>
        <tr><th>簽核狀態</th><td><?= e(income_expense_show_approval_label($approvalStatus)) ?></td><th>填寫人</th><td><?= e($record['created_by_name'] ?? '-') ?></td></tr>
        <tr>
            <th>會計傳票</th>
            <td colspan="3">
                <?php if (!empty($record['accounting_voucher_id'])): ?>
                    <a class="text-link mono" href="/accounting/vouchers/<?= e((string) $record['accounting_voucher_id']) ?>">
                        <?= e(($record['voucher_no'] ?: '未編號') . ' / ' . income_expense_show_voucher_label($record['voucher_status'] ?? '')) ?>
                    </a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>
        <tr><th>備註</th><td colspan="3"><?= nl2br(e($record['notes'] ?: '-')) ?></td></tr>
        </tbody>
    </table>

    <div class="section-title">
        <h3>簽核流程</h3>
        <p class="muted-text">送審後由具核准權限的人員核准或退回，核准後才能建立會計傳票。</p>
    </div>

    <?php if ($canApprove && $approvalStatus === 'submitted'): ?>
        <form class="form approval-form no-print" method="post" action="/income-expenses/<?= e((string) $record['id']) ?>/approve">
            <?= csrf_field() ?>
            <label>
                <span>核准備註</span>
                <textarea name="review_notes" rows="2"></textarea>
            </label>
            <div class="form-actions">
                <button class="btn primary" type="submit">核准</button>
                <button class="btn" type="submit" formaction="/income-expenses/<?= e((string) $record['id']) ?>/reject">退回</button>
            </div>
        </form>
    <?php endif; ?>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>時間</th>
                <th>動作</th>
                <th>狀態</th>
                <th>送審人</th>
                <th>核決人</th>
                <th>備註</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach (($approvalHistory ?? []) as $history): ?>
                <tr>
                    <td><?= e(income_expense_show_datetime($history['created_at'] ?? '')) ?></td>
                    <td><?= e(income_expense_show_approval_action((string) $history['action'])) ?></td>
                    <td><?= e(income_expense_show_request_status((string) $history['status'])) ?></td>
                    <td><?= e($history['requested_by_name'] ?? '-') ?></td>
                    <td><?= e($history['reviewed_by_name'] ?? '-') ?></td>
                    <td><?= e($history['review_notes'] ?: ($history['request_notes'] ?: '-')) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($approvalHistory)): ?>
                <tr><td colspan="6" class="empty-state">尚無簽核紀錄</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function income_expense_show_status_label(string $status): string
{
    return ['draft' => '草稿', 'confirmed' => '已確認', 'voided' => '已作廢'][$status] ?? $status;
}

function income_expense_show_approval_label(string $status): string
{
    return ['draft' => '草稿', 'submitted' => '送審中', 'approved' => '已核准', 'rejected' => '已退回'][$status] ?? $status;
}

function income_expense_show_approval_action(string $action): string
{
    return ['submit' => '送審', 'approved' => '核准', 'rejected' => '退回'][$action] ?? $action;
}

function income_expense_show_request_status(string $status): string
{
    return ['pending' => '待審', 'approved' => '已核准', 'rejected' => '已退回', 'cancelled' => '已取消'][$status] ?? $status;
}

function income_expense_show_receipt_label(string $status): string
{
    return ['not_required' => '免附', 'pending' => '待補', 'issued' => '已取得', 'voided' => '已作廢'][$status] ?? $status;
}

function income_expense_show_datetime(?string $datetime): string
{
    if (!$datetime) {
        return '-';
    }

    $date = substr($datetime, 0, 10);
    $time = substr($datetime, 11, 5);

    return roc_date($date) . ($time ? ' ' . $time : '');
}

function income_expense_show_voucher_label(string $status): string
{
    return ['draft' => '草稿', 'posted' => '已入帳', 'voided' => '已作廢'][$status] ?? $status;
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
