<?php
$active = 'purchase-requests';
$documentTitle = '採購申請明細';
$canManage = \App\Core\Permission::can('purchase_requests.manage');
$canApprove = \App\Core\Permission::can('purchase_requests.approve');
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <p class="eyebrow">採購申請</p>
            <h2><?= e($request['subject']) ?></h2>
            <p class="muted-text"><?= e($request['request_no']) ?> / <?= e(roc_date($request['requested_on'])) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/purchase-requests?month=<?= e(substr((string) $request['requested_on'], 0, 7)) ?>">返回列表</a>
            <a class="btn primary" href="/purchase-requests/<?= e((string) $request['id']) ?>/print">列印申請單</a>
            <?php if ($canManage): ?>
                <?php if (in_array($request['status'], ['draft', 'rejected'], true)): ?>
                    <a class="btn" href="/purchase-requests/<?= e((string) $request['id']) ?>/edit">編輯</a>
                    <form method="post" action="/purchase-requests/<?= e((string) $request['id']) ?>/submit">
                        <?= csrf_field() ?>
                        <button class="btn primary" type="submit">送審</button>
                    </form>
                <?php endif; ?>
                <?php if ($request['status'] === 'approved'): ?>
                    <form method="post" action="/purchase-requests/<?= e((string) $request['id']) ?>/mark-ordered">
                        <?= csrf_field() ?>
                        <button class="btn primary" type="submit">標記採購中</button>
                    </form>
                <?php endif; ?>
                <?php if (in_array($request['status'], ['approved', 'ordered'], true)): ?>
                    <form method="post" action="/purchase-requests/<?= e((string) $request['id']) ?>/mark-received">
                        <?= csrf_field() ?>
                        <button class="btn primary" type="submit">完成驗收</button>
                    </form>
                <?php endif; ?>
                <?php if (!in_array($request['status'], ['received', 'voided'], true)): ?>
                    <form method="post" action="/purchase-requests/<?= e((string) $request['id']) ?>/void" onsubmit="return confirm('確定要作廢此採購申請？');">
                        <?= csrf_field() ?>
                        <button class="btn" type="submit">作廢</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ((float) $request['total_amount'] >= 50000): ?>
        <div class="alert warning no-print">本案金額達 5 萬元以上，列印單會提示需送董事長核准。</div>
    <?php endif; ?>

    <table class="meta-table">
        <tbody>
        <tr><th>申請編號</th><td class="mono"><?= e($request['request_no']) ?></td><th>會計編號</th><td><?= e($request['accounting_no'] ?: '-') ?></td></tr>
        <tr><th>申請日期</th><td><?= e(roc_date($request['requested_on'])) ?></td><th>申請人</th><td><?= e($request['requester_name']) ?></td></tr>
        <tr><th>請購類別</th><td><?= e($request['purchase_category']) ?></td><th>請購單位</th><td><?= e($request['request_unit']) ?></td></tr>
        <tr><th>採購方式</th><td><?= e($request['purchase_method']) ?></td><th>廠商名稱</th><td><?= e($request['vendor_name'] ?: '-') ?></td></tr>
        <tr><th>狀態</th><td><?= e(purchase_show_status_label((string) $request['status'])) ?></td><th>總金額</th><td class="amount"><?= e(purchase_show_money($request['total_amount'])) ?></td></tr>
        <tr><th>報價單</th><td colspan="3"><?= e((int) $request['quotation_attached'] === 1 ? '已檢附' : '未檢附，原因：' . ($request['quotation_missing_reason'] ?: '-')) ?></td></tr>
        <tr><th>申請項目</th><td colspan="3"><?= e($request['subject']) ?></td></tr>
        <tr><th>請購事由</th><td colspan="3"><?= nl2br(e($request['reason'])) ?></td></tr>
        <tr><th>申請目的</th><td colspan="3"><?= nl2br(e($request['purpose'])) ?></td></tr>
        <?php if (!empty($request['notes'])): ?>
            <tr><th>備註</th><td colspan="3"><?= nl2br(e($request['notes'])) ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>序</th>
                <th>品名</th>
                <th>規格</th>
                <th class="amount">數量</th>
                <th class="amount">單價</th>
                <th class="amount">金額</th>
                <th>備註</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $index => $item): ?>
                <tr>
                    <td><?= e((string) ($index + 1)) ?></td>
                    <td><?= e($item['item_name']) ?></td>
                    <td><?= e($item['specification'] ?: '-') ?></td>
                    <td class="amount"><?= e(purchase_show_number($item['quantity'])) ?></td>
                    <td class="amount"><?= e($item['unit_price'] !== null ? purchase_show_money($item['unit_price']) : '-') ?></td>
                    <td class="amount"><?= e(purchase_show_money($item['amount'])) ?></td>
                    <td><?= e($item['notes'] ?: '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
            <tr><th colspan="5">總計</th><th class="amount"><?= e(purchase_show_money($request['total_amount'])) ?></th><th></th></tr>
            </tfoot>
        </table>
    </div>

    <?php
    $approvalTargetId = (int) $request['id'];
    $approvalStatus = (string) $request['status'];
    $approvalApproveUrl = '/purchase-requests/' . $approvalTargetId . '/approve';
    $approvalRejectUrl = '/purchase-requests/' . $approvalTargetId . '/reject';
    $approvalCanApprove = $canApprove;
    ?>
    <?php require base_path('resources/views/shared/approval-section.php'); ?>
    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function purchase_show_money($value): string
{
    return number_format((float) $value, 0);
}

function purchase_show_number($value): string
{
    $number = (float) $value;
    return floor($number) == $number ? number_format($number, 0) : number_format($number, 2);
}

function purchase_show_status_label(string $status): string
{
    return [
        'draft' => '草稿',
        'submitted' => '送審中',
        'approved' => '已核准',
        'rejected' => '已退回',
        'ordered' => '採購中',
        'received' => '已驗收',
        'voided' => '已作廢',
    ][$status] ?? $status;
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
