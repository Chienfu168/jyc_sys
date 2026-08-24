<?php
$active = 'petty-cash';
$canManage = \App\Core\Permission::can('petty_cash.manage');
$canApprove = \App\Core\Permission::can('petty_cash.approve');
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>本月收入</span>
        <strong><?= e(petty_cash_money($totals['income'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>本月支出</span>
        <strong><?= e(petty_cash_money($totals['expense'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>本月結餘</span>
        <strong><?= e(petty_cash_money($totals['balance'])) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <form class="search" method="get" action="/petty-cash">
            <?php require base_path('resources/views/shared/date-scope-filter.php'); ?>
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/petty-cash/report?year=<?= e(substr($month, 0, 4)) ?>&month=<?= e(substr($month, 5, 2)) ?>&mode=summary">統計輸出</a>
            <?php if ($canManage): ?>
                <a class="btn" href="/petty-cash-items">常用項目</a>
                <a class="btn primary" href="/petty-cash/create">新增紀錄</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>日期</th>
                <th>類型</th>
                <th>項目</th>
                <th>對象</th>
                <th>銀行帳戶</th>
                <th>憑證</th>
                <th>簽核</th>
                <th class="amount">金額</th>
                <th>填寫人</th>
                <th>傳票</th>
                <th class="no-print">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($entries as $entry): ?>
                <?php $approvalStatus = (string) ($entry['approval_status'] ?? 'draft'); ?>
                <tr>
                    <td><?= e(roc_date($entry['occurred_on'])) ?></td>
                    <td>
                        <span class="badge <?= $entry['item_type'] === 'income' ? 'ok' : 'muted' ?>">
                            <?= e($entry['item_type'] === 'income' ? '收入' : '支出') ?>
                        </span>
                    </td>
                    <td>
                        <a class="text-link" href="/petty-cash/<?= e((string) $entry['id']) ?>"><?= e($entry['item_name']) ?></a>
                    </td>
                    <td><?= e($entry['payment_to'] ?: '-') ?></td>
                    <td>
                        <?php if (!empty($entry['bank_name'])): ?>
                            <?= e($entry['bank_name']) ?>
                            <div class="muted-text mono"><?= e($entry['account_no'] ?? '') ?></div>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= e($entry['receipt_no'] ?: '-') ?></td>
                    <td><?= e(petty_cash_approval_label($approvalStatus)) ?></td>
                    <td class="amount"><?= e(petty_cash_money($entry['amount'])) ?></td>
                    <td><?= e($entry['created_by_name'] ?? '-') ?></td>
                    <td>
                        <?php if (!empty($entry['accounting_voucher_id'])): ?>
                            <a class="text-link" href="/accounting/vouchers/<?= e((string) $entry['accounting_voucher_id']) ?>"><?= e($entry['voucher_no'] ?: '查看傳票') ?></a>
                            <div class="muted-text"><?= e(petty_cash_voucher_status_label((string) ($entry['voucher_status'] ?? ''))) ?></div>
                        <?php else: ?>
                            <span class="muted-text">未建立</span>
                        <?php endif; ?>
                    </td>
                    <td class="table-actions no-print">
                        <a class="btn small" href="/petty-cash/<?= e((string) $entry['id']) ?>">查看</a>
                        <?php if ($canManage): ?>
                            <a class="btn small" href="/petty-cash/<?= e((string) $entry['id']) ?>/edit">編輯</a>
                            <?php if (in_array($approvalStatus, ['draft', 'rejected'], true) && empty($entry['accounting_voucher_id'])): ?>
                                <form method="post" action="/petty-cash/<?= e((string) $entry['id']) ?>/submit">
                                    <?= csrf_field() ?>
                                    <button class="btn small primary" type="submit">送審</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($canApprove && $approvalStatus === 'submitted'): ?>
                            <form method="post" action="/petty-cash/<?= e((string) $entry['id']) ?>/approve">
                                <?= csrf_field() ?>
                                <button class="btn small primary" type="submit">核准</button>
                            </form>
                            <form method="post" action="/petty-cash/<?= e((string) $entry['id']) ?>/reject">
                                <?= csrf_field() ?>
                                <button class="btn small" type="submit">退回</button>
                            </form>
                        <?php endif; ?>
                        <?php if (\App\Core\Permission::can('accounting.manage') && $approvalStatus === 'approved' && empty($entry['accounting_voucher_id'])): ?>
                            <form method="post" action="/petty-cash/<?= e((string) $entry['id']) ?>/voucher">
                                <?= csrf_field() ?>
                                <button class="btn small" type="submit">建傳票</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$entries): ?>
                <tr><td colspan="11" class="empty-state">本月尚無零用金紀錄</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function petty_cash_money($value): string
{
    return number_format((float) $value, 0);
}

function petty_cash_approval_label(string $status): string
{
    return ['draft' => '草稿', 'submitted' => '送審中', 'approved' => '已核准', 'rejected' => '已退回'][$status] ?? $status;
}

function petty_cash_voucher_status_label(string $status): string
{
    return ['draft' => '草稿', 'posted' => '已入帳', 'voided' => '已作廢'][$status] ?? ($status ?: '-');
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
