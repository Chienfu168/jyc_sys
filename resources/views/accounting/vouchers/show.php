<?php
$active = 'accounting';
$documentTitle = '會計傳票';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2><?= e($voucher['voucher_no']) ?></h2>
            <p class="muted-text"><?= e(roc_date($voucher['voucher_date'])) ?>，<?= e(accounting_show_status_label($voucher['status'])) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/accounting/vouchers">返回列表</a>
            <?php if (\App\Core\Permission::can('accounting.manage') && $voucher['status'] !== 'posted'): ?>
                <a class="btn" href="/accounting/vouchers/<?= e((string) $voucher['id']) ?>/edit">編輯</a>
            <?php endif; ?>
            <?php if (\App\Core\Permission::can('accounting.post') && $voucher['status'] === 'draft'): ?>
                <form method="post" action="/accounting/vouchers/<?= e((string) $voucher['id']) ?>/post">
                    <?= csrf_field() ?>
                    <button class="btn primary" type="submit">過帳</button>
                </form>
            <?php endif; ?>
            <?php if (\App\Core\Permission::can('accounting.manage') && $voucher['status'] !== 'voided'): ?>
                <form method="post" action="/accounting/vouchers/<?= e((string) $voucher['id']) ?>/void">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit">作廢</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>傳票號碼</th>
            <td><?= e($voucher['voucher_no']) ?></td>
            <th>傳票日期</th>
            <td><?= e(roc_date($voucher['voucher_date'])) ?></td>
        </tr>
        <tr>
            <th>摘要</th>
            <td colspan="3"><?= e($voucher['summary']) ?></td>
        </tr>
        <tr>
            <th>狀態</th>
            <td><?= e(accounting_show_status_label($voucher['status'])) ?></td>
            <th>建立人</th>
            <td><?= e($voucher['created_by_name'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>過帳人</th>
            <td><?= e($voucher['posted_by_name'] ?: '-') ?></td>
            <th>過帳時間</th>
            <td><?= e($voucher['posted_at'] ?: '-') ?></td>
        </tr>
        <?php if (!empty($voucher['source_type'])): ?>
            <tr>
                <th>資料來源</th>
                <td colspan="3"><?= e($voucher['source_type'] . ($voucher['source_id'] ? ' #' . $voucher['source_id'] : '')) ?></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>科目代碼</th>
                <th>科目名稱</th>
                <th>分錄說明</th>
                <th class="amount">借方</th>
                <th class="amount">貸方</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($lines as $line): ?>
                <tr>
                    <td class="mono"><?= e($line['code']) ?></td>
                    <td><?= e($line['name']) ?></td>
                    <td><?= e($line['description'] ?: '-') ?></td>
                    <td class="amount"><?= e(accounting_show_money($line['debit'])) ?></td>
                    <td class="amount"><?= e(accounting_show_money($line['credit'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
            <tr>
                <th colspan="3">合計</th>
                <th class="amount"><?= e(accounting_show_money($totals['debit'])) ?></th>
                <th class="amount"><?= e(accounting_show_money($totals['credit'])) ?></th>
            </tr>
            </tfoot>
        </table>
    </div>

    <?php if (!empty($voucher['notes'])): ?>
        <p class="print-notes">備註：<?= nl2br(e($voucher['notes'])) ?></p>
    <?php endif; ?>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function accounting_show_money($value): string
{
    return number_format((float) $value, 0);
}
function accounting_show_status_label(string $status): string
{
    return ['draft' => '草稿', 'posted' => '已過帳', 'voided' => '作廢'][$status] ?? $status;
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
