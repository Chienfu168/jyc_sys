<?php
$active = 'petty-cash';
$documentTitle = '零用金明細';
$approvalStatus = (string) ($entry['approval_status'] ?? 'draft');
$canManage = \App\Core\Permission::can('petty_cash.manage');
$canApprove = \App\Core\Permission::can('petty_cash.approve');
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <p class="eyebrow">財務會計</p>
            <h2><?= e($entry['item_name']) ?></h2>
            <p class="muted-text"><?= e(roc_date($entry['occurred_on'])) ?> / <?= e($entry['item_type'] === 'income' ? '收入' : '支出') ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/petty-cash?month=<?= e(substr((string) $entry['occurred_on'], 0, 7)) ?>">返回列表</a>
            <?php if ($canManage): ?>
                <a class="btn" href="/petty-cash/<?= e((string) $entry['id']) ?>/edit">編輯</a>
                <?php if (in_array($approvalStatus, ['draft', 'rejected'], true) && empty($entry['accounting_voucher_id'])): ?>
                    <form method="post" action="/petty-cash/<?= e((string) $entry['id']) ?>/submit">
                        <?= csrf_field() ?>
                        <button class="btn primary" type="submit">送審</button>
                    </form>
                <?php endif; ?>
                <?php if (\App\Core\Permission::can('accounting.manage') && $approvalStatus === 'approved' && empty($entry['accounting_voucher_id'])): ?>
                    <form method="post" action="/petty-cash/<?= e((string) $entry['id']) ?>/voucher">
                        <?= csrf_field() ?>
                        <button class="btn primary" type="submit">建立會計傳票</button>
                    </form>
                <?php endif; ?>
                <?php if (!empty($entry['accounting_voucher_id'])): ?>
                    <a class="btn" href="/accounting/vouchers/<?= e((string) $entry['accounting_voucher_id']) ?>">查看傳票</a>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (\App\Core\Permission::can('petty_cash.delete') && empty($entry['accounting_voucher_id']) && empty($entry['bank_account_transaction_id'])): ?>
                <form method="post" action="/petty-cash/<?= e((string) $entry['id']) ?>/delete" onsubmit="return confirm('確定要「刪除」此零用金紀錄？刪除後無法復原。');">
                    <?= csrf_field() ?>
                    <button class="btn danger" type="submit">刪除</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr><th>日期</th><td><?= e(roc_date($entry['occurred_on'])) ?></td><th>類型</th><td><?= e($entry['item_type'] === 'income' ? '收入' : '支出') ?></td></tr>
        <tr><th>項目</th><td><?= e($entry['item_name']) ?></td><th>金額</th><td><?= e(number_format((float) $entry['amount'], 0)) ?></td></tr>
        <tr><th>付款對象</th><td><?= e($entry['payment_to'] ?: '-') ?></td><th>憑證號碼</th><td><?= e($entry['receipt_no'] ?: '-') ?></td></tr>
        <tr><th>銀行帳戶</th><td><?= e(!empty($entry['bank_name']) ? $entry['bank_name'] . ' / ' . $entry['account_no'] : '-') ?></td><th>專案</th><td><?= e($entry['project_name'] ?? '-') ?></td></tr>
        <tr><th>簽核狀態</th><td><?= e(petty_cash_show_approval_label($approvalStatus)) ?></td><th>填寫人</th><td><?= e($entry['created_by_name'] ?? '-') ?></td></tr>
        <tr>
            <th>會計傳票</th>
            <td colspan="3">
                <?php if (!empty($entry['accounting_voucher_id'])): ?>
                    <a class="text-link mono" href="/accounting/vouchers/<?= e((string) $entry['accounting_voucher_id']) ?>">
                        <?= e(($entry['voucher_no'] ?: '未編號') . ' / ' . petty_cash_show_voucher_label($entry['voucher_status'] ?? '')) ?>
                    </a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>
        <tr><th>備註</th><td colspan="3"><?= nl2br(e($entry['notes'] ?: '-')) ?></td></tr>
        </tbody>
    </table>

    <div class="section-title">
        <h3>簽核流程</h3>
        <p class="muted-text">核准後可建立會計傳票；退回後可修改再重新送審。</p>
    </div>

    <?php if ($canApprove && $approvalStatus === 'submitted'): ?>
        <form class="form approval-form no-print" method="post" action="/petty-cash/<?= e((string) $entry['id']) ?>/approve">
            <?= csrf_field() ?>
            <label>
                <span>核准備註</span>
                <textarea name="review_notes" rows="2"></textarea>
            </label>
            <div class="form-actions">
                <button class="btn primary" type="submit">核准</button>
                <button class="btn" type="submit" formaction="/petty-cash/<?= e((string) $entry['id']) ?>/reject">退回</button>
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
                    <td><?= e(petty_cash_show_datetime($history['created_at'] ?? '')) ?></td>
                    <td><?= e(petty_cash_show_approval_action((string) $history['action'])) ?></td>
                    <td><?= e(petty_cash_show_request_status((string) $history['status'])) ?></td>
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
function petty_cash_show_approval_label(string $status): string
{
    return ['draft' => '草稿', 'submitted' => '送審中', 'approved' => '已核准', 'rejected' => '已退回'][$status] ?? $status;
}

function petty_cash_show_approval_action(string $action): string
{
    return ['submit' => '送審', 'approved' => '核准', 'rejected' => '退回'][$action] ?? $action;
}

function petty_cash_show_request_status(string $status): string
{
    return ['pending' => '待審', 'approved' => '已核准', 'rejected' => '已退回', 'cancelled' => '已取消'][$status] ?? $status;
}

function petty_cash_show_voucher_label(string $status): string
{
    return ['draft' => '草稿', 'posted' => '已入帳', 'voided' => '已作廢'][$status] ?? $status;
}

function petty_cash_show_datetime(?string $datetime): string
{
    if (!$datetime) {
        return '-';
    }

    $date = substr($datetime, 0, 10);
    $time = substr($datetime, 11, 5);

    return roc_date($date) . ($time ? ' ' . $time : '');
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
