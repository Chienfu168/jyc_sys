<?php
$active = 'donors';
$documentTitle = '捐款人資料';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="stats-grid budget-summary no-print">
    <div class="stat-card"><span>有效捐款筆數</span><strong><?= e(number_format((int) $summary['count'])) ?></strong></div>
    <div class="stat-card"><span>累計捐款</span><strong><?= e(donor_show_money($summary['amount'])) ?></strong></div>
    <div class="stat-card"><span>待處理收據</span><strong><?= e(number_format((int) $summary['pending_receipts'])) ?></strong></div>
</section>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <p class="eyebrow">捐款人</p>
            <h2><?= e($donor['name']) ?></h2>
            <p class="muted-text"><?= e(donor_show_type_label($donor['donor_type'])) ?> / <?= e($donor['status'] === 'active' ? '啟用' : '封存') ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/donors">返回列表</a>
            <?php if (\App\Core\Permission::can('donations.manage')): ?>
                <a class="btn primary" href="/donations/create?donor_id=<?= e((string) $donor['id']) ?>">新增捐款</a>
            <?php endif; ?>
            <?php if (\App\Core\Permission::can('donors.manage')): ?>
                <a class="btn" href="/donors/<?= e((string) $donor['id']) ?>/edit">編輯</a>
                <form method="post" action="/donors/<?= e((string) $donor['id']) ?>/toggle">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit"><?= $donor['status'] === 'active' ? '封存' : '啟用' ?></button>
                </form>
            <?php endif; ?>
            <?php if (\App\Core\Permission::can('donors.manage') || owns_record($donor['created_by'] ?? null)): ?>
                <form method="post" action="/donors/<?= e((string) $donor['id']) ?>/delete" onsubmit="return confirm('確定要刪除此捐款人？此操作無法復原（尚有捐款紀錄者無法刪除）。');">
                    <?= csrf_field() ?>
                    <button class="btn danger" type="submit">刪除</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr><th>姓名 / 單位</th><td><?= e($donor['name']) ?></td><th>類型</th><td><?= e(donor_show_type_label($donor['donor_type'])) ?></td></tr>
        <tr><th>電話</th><td><?= e($donor['phone'] ?: '-') ?></td><th>Email</th><td><?= e($donor['email'] ?: '-') ?></td></tr>
        <tr><th>收據抬頭</th><td><?= e($donor['receipt_title'] ?: '-') ?></td><th>統編 / 身分證字號</th><td><?= e($donor['tax_id'] ?: '-') ?></td></tr>
        <tr><th>地址</th><td colspan="3"><?= e($donor['address'] ?: '-') ?></td></tr>
        <tr><th>備註</th><td colspan="3"><?= nl2br(e($donor['notes'] ?: '-')) ?></td></tr>
        </tbody>
    </table>
</section>

<section class="panel">
    <div class="panel-header">
        <h2>捐款紀錄</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>日期</th>
                <th>方式</th>
                <th>收據</th>
                <th>專案</th>
                <th>傳票</th>
                <th class="amount">金額</th>
                <th class="actions no-print">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($donations as $donation): ?>
                <tr>
                    <td><?= e(roc_date($donation['donated_at'])) ?></td>
                    <td><?= e($donation['payment_method']) ?></td>
                    <td><?= e(donor_show_receipt_label($donation['receipt_status'])) ?> <?= e($donation['receipt_no'] ? '/ ' . $donation['receipt_no'] : '') ?></td>
                    <td><?= e($donation['project_name'] ?: '-') ?></td>
                    <td>
                        <?php if (!empty($donation['accounting_voucher_id'])): ?>
                            <a class="text-link mono" href="/accounting/vouchers/<?= e((string) $donation['accounting_voucher_id']) ?>"><?= e($donation['voucher_no'] ?: '已建立') ?></a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="amount"><?= e(donor_show_money($donation['amount'])) ?></td>
                    <td class="actions no-print"><a class="btn small" href="/donations/<?= e((string) $donation['id']) ?>">檢視</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$donations): ?>
                <tr><td colspan="7" class="empty">尚無捐款紀錄。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function donor_show_money($value): string
{
    return number_format((float) $value, 0);
}

function donor_show_type_label(string $type): string
{
    return ['person' => '個人', 'organization' => '組織'][$type] ?? $type;
}

function donor_show_receipt_label(string $status): string
{
    return ['not_required' => '免開', 'pending' => '待處理', 'issued' => '已開立', 'voided' => '作廢'][$status] ?? $status;
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
