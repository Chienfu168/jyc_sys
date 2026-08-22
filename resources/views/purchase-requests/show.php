<?php
$active = 'purchase-requests';
$documentTitle = '採購申請明細';
$printable = false;
$canManage = \App\Core\Permission::can('purchase_requests.manage');
$canApprove = \App\Core\Permission::can('purchase_requests.approve');
$attachmentCount = count($attachments ?? []);
ob_start();
?>

<div class="purchase-detail">
    <section class="panel purchase-detail-hero no-print">
        <div class="purchase-detail-title">
            <div class="purchase-title-row">
                <span class="badge <?= e(purchase_show_status_tone((string) $request['status'])) ?>"><?= e(purchase_show_status_label((string) $request['status'])) ?></span>
                <span class="mono"><?= e($request['request_no']) ?></span>
            </div>
            <h2><?= e($request['subject']) ?></h2>
            <div class="purchase-hero-meta">
                <span><?= e(roc_date($request['requested_on'])) ?></span>
                <span><?= e($request['requester_name']) ?></span>
                <span><?= e($request['request_unit']) ?></span>
            </div>
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
    </section>

    <?php if ((float) $request['total_amount'] >= 50000): ?>
        <div class="alert warning no-print">本案金額達 5 萬元以上，列印單會提示需送董事長核准。</div>
    <?php endif; ?>

    <section class="purchase-summary-grid no-print">
        <article class="purchase-summary-tile">
            <span>總金額</span>
            <strong><?= e(purchase_show_money($request['total_amount'])) ?></strong>
        </article>
        <article class="purchase-summary-tile">
            <span>廠商</span>
            <strong class="text-fit"><?= e($request['vendor_name'] ?: '-') ?></strong>
        </article>
        <article class="purchase-summary-tile">
            <span>報價單</span>
            <strong class="text-fit"><?= e((int) $request['quotation_attached'] === 1 ? '已檢附' : '未檢附') ?></strong>
        </article>
        <article class="purchase-summary-tile">
            <span>附件</span>
            <strong><?= e(number_format($attachmentCount)) ?></strong>
        </article>
    </section>

    <section class="panel purchase-detail-panel">
        <div class="purchase-section-heading">
            <h3>採購資料</h3>
            <p class="muted-text">請購、廠商、報價與案件狀態集中檢視。</p>
        </div>

        <div class="purchase-info-grid">
            <section class="purchase-info-block">
                <h3>申請資訊</h3>
                <dl class="purchase-field-list">
                    <div><dt>申請編號</dt><dd class="mono"><?= e($request['request_no']) ?></dd></div>
                    <div><dt>申請日期</dt><dd><?= e(roc_date($request['requested_on'])) ?></dd></div>
                    <div><dt>申請人</dt><dd><?= e($request['requester_name']) ?></dd></div>
                    <div><dt>會計編號</dt><dd><?= e($request['accounting_no'] ?: '-') ?></dd></div>
                </dl>
            </section>

            <section class="purchase-info-block">
                <h3>採購資訊</h3>
                <dl class="purchase-field-list">
                    <div><dt>請購類別</dt><dd><?= e($request['purchase_category']) ?></dd></div>
                    <div><dt>請購單位</dt><dd><?= e($request['request_unit']) ?></dd></div>
                    <div><dt>採購方式</dt><dd><?= e($request['purchase_method']) ?></dd></div>
                    <div><dt>廠商名稱</dt><dd><?= e($request['vendor_name'] ?: '-') ?></dd></div>
                    <div><dt>報價單</dt><dd><?= e((int) $request['quotation_attached'] === 1 ? '已檢附' : '未檢附，原因：' . ($request['quotation_missing_reason'] ?: '-')) ?></dd></div>
                </dl>
            </section>
        </div>

        <div class="purchase-narrative-list">
            <section>
                <h3>申請項目</h3>
                <p><?= e($request['subject']) ?></p>
            </section>
            <section>
                <h3>請購事由</h3>
                <p><?= nl2br(e($request['reason'])) ?></p>
            </section>
            <section>
                <h3>申請目的</h3>
                <p><?= nl2br(e($request['purpose'])) ?></p>
            </section>
            <?php if (!empty($request['notes'])): ?>
                <section>
                    <h3>備註</h3>
                    <p><?= nl2br(e($request['notes'])) ?></p>
                </section>
            <?php endif; ?>
        </div>

        <div class="purchase-section-heading purchase-lines-heading">
            <h3>請購明細</h3>
            <p class="muted-text">明細採固定欄寬，長品名與規格會自動換行。</p>
        </div>

        <div class="table-wrap purchase-lines-wrap">
            <table class="purchase-lines-table">
                <colgroup>
                    <col class="purchase-col-index">
                    <col class="purchase-col-name">
                    <col class="purchase-col-spec">
                    <col class="purchase-col-qty">
                    <col class="purchase-col-price">
                    <col class="purchase-col-amount">
                    <col class="purchase-col-notes">
                </colgroup>
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
                        <td class="line-index"><?= e((string) ($index + 1)) ?></td>
                        <td class="line-name"><?= e($item['item_name']) ?></td>
                        <td class="line-spec"><?= e($item['specification'] ?: '-') ?></td>
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
    </section>

    <?php require base_path('resources/views/purchase-requests/attachments.php'); ?>

    <section class="panel purchase-approval-panel">
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
</div>
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

function purchase_show_status_tone(string $status): string
{
    return [
        'submitted' => 'warning',
        'approved' => 'ok',
        'ordered' => 'muted',
        'received' => 'ok',
        'rejected' => 'danger',
        'voided' => 'muted',
    ][$status] ?? 'muted';
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
