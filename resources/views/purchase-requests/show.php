<?php
$active = 'purchase-requests';
$documentTitle = '採購申請明細';
$printable = false;
$canManage = \App\Core\Permission::can('purchase_requests.manage');
$canApprove = \App\Core\Permission::can('purchase_requests.approve');
$attachmentCount = count($attachments ?? []);
$statusLabel = purchase_show_status_label((string) $request['status']);
$statusTone = purchase_show_status_tone((string) $request['status']);
$quotationLabel = (int) $request['quotation_attached'] === 1 ? '已檢附' : '未檢附';
$supervisingDepartment = purchase_show_supervising_department($request);
$procurementOwner = purchase_show_procurement_owner($request, $supervisingDepartment);
$progressSteps = purchase_show_progress_steps((string) $request['status']);
$checkItems = purchase_show_check_items($request, $attachmentCount, $supervisingDepartment);
ob_start();
?>

<style>
<?php require base_path('resources/views/shared/critical-css.php'); ?>
</style>

<div class="purchase-detail">
    <section class="panel purchase-detail-hero no-print">
        <div class="purchase-detail-title">
            <div class="purchase-title-row">
                <span class="badge <?= e($statusTone) ?>"><?= e($statusLabel) ?></span>
                <span class="mono"><?= e($request['request_no']) ?></span>
            </div>
            <h2><?= e($request['subject']) ?></h2>
            <div class="purchase-hero-meta">
                <span><?= e(roc_date($request['requested_on'])) ?></span>
                <span><?= e($request['requester_name']) ?></span>
                <span><?= e($request['request_unit']) ?></span>
            </div>
        </div>
        <aside class="purchase-hero-insight">
            <span>主管部門</span>
            <strong><?= e($supervisingDepartment) ?></strong>
            <small>採購承辦：<?= e($procurementOwner) ?></small>
        </aside>
        <div class="actions purchase-detail-actions">
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
            <?php if (($canManage || owns_record($request['created_by'] ?? null)) && $request['status'] !== 'received'): ?>
                <form method="post" action="/purchase-requests/<?= e((string) $request['id']) ?>/delete" onsubmit="return confirm('確定要「刪除」此採購申請？將一併刪除明細與附件，刪除後無法復原。若僅需保留軌跡請改用作廢。');">
                    <?= csrf_field() ?>
                    <button class="btn danger" type="submit">刪除</button>
                </form>
            <?php endif; ?>
        </div>
    </section>

    <?php if ((float) $request['total_amount'] >= 50000): ?>
        <div class="alert warning no-print">本案金額達 5 萬元以上，列印單會提示需送董事長核准。</div>
    <?php endif; ?>

    <section class="purchase-summary-grid no-print">
        <article class="purchase-summary-tile purchase-summary-primary">
            <span>總金額</span>
            <strong><?= e(purchase_show_money($request['total_amount'])) ?></strong>
        </article>
        <article class="purchase-summary-tile purchase-summary-vendor">
            <span>廠商</span>
            <strong class="text-fit"><?= e($request['vendor_name'] ?: '-') ?></strong>
        </article>
        <article class="purchase-summary-tile">
            <span>主管部門</span>
            <strong class="text-fit"><?= e($supervisingDepartment) ?></strong>
        </article>
        <article class="purchase-summary-tile">
            <span>報價單</span>
            <strong class="text-fit"><?= e($quotationLabel) ?></strong>
        </article>
        <article class="purchase-summary-tile">
            <span>附件</span>
            <strong><?= e(number_format($attachmentCount)) ?></strong>
        </article>
    </section>

    <div class="purchase-detail-layout">
        <div class="purchase-detail-main">
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
                            <div><dt>主管部門</dt><dd><?= e($supervisingDepartment) ?></dd></div>
                            <div><dt>採購方式</dt><dd><?= e($request['purchase_method']) ?></dd></div>
                            <div><dt>採購承辦</dt><dd><?= e($procurementOwner) ?></dd></div>
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
        </div>

        <aside class="purchase-detail-sidebar no-print">
            <section class="panel purchase-side-panel">
                <div class="purchase-side-heading">
                    <h3>流程狀態</h3>
                    <span class="badge <?= e($statusTone) ?>"><?= e($statusLabel) ?></span>
                </div>
                <ol class="purchase-progress-list">
                    <?php foreach ($progressSteps as $step): ?>
                        <li class="<?= e($step['state']) ?>">
                            <span><?= e($step['label']) ?></span>
                            <small><?= e($step['hint']) ?></small>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </section>

            <section class="panel purchase-side-panel">
                <div class="purchase-side-heading">
                    <h3>案件檢核</h3>
                    <span><?= e($request['request_no']) ?></span>
                </div>
                <dl class="purchase-check-list">
                    <?php foreach ($checkItems as $item): ?>
                        <div class="<?= e($item['tone']) ?>">
                            <dt><?= e($item['label']) ?></dt>
                            <dd><?= e($item['value']) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </section>
        </aside>
    </div>

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

function purchase_show_supervising_department(array $request): string
{
    $text = implode(' ', [
        (string) ($request['purchase_category'] ?? ''),
        (string) ($request['request_unit'] ?? ''),
        (string) ($request['purchase_method'] ?? ''),
        (string) ($request['subject'] ?? ''),
        (string) ($request['reason'] ?? ''),
        (string) ($request['purpose'] ?? ''),
    ]);

    $unit = (string) ($request['request_unit'] ?? '');
    $rules = [
        '資訊主管部門' => ['資訊', '電腦', '網站', '網域', '主機', '系統', '軟體', '雲端', '資安', '網路', 'IT'],
        '財務會計主管部門' => ['會計', '財務', '稅務', '銀行', '帳務', '憑證', '發票'],
        '企劃主管部門' => ['企劃', '計畫', '專案', '補助', '成果', '行銷', '宣傳'],
        '活動主管部門' => ['活動', '課程', '場地', '講師', '招生', '志工'],
        '總務主管部門' => ['總務', '辦公', '設備', '雜項', '修繕', '庶務', '耗材', '家具'],
    ];

    foreach ($rules as $department => $keywords) {
        if (purchase_show_contains_any($text, $keywords)) {
            return $department;
        }
    }

    return $unit !== '' ? $unit . '主管' : '主管部門';
}

function purchase_show_procurement_owner(array $request, string $supervisingDepartment): string
{
    $text = implode(' ', [
        (string) ($request['purchase_method'] ?? ''),
        (string) ($request['purchase_category'] ?? ''),
        $supervisingDepartment,
    ]);

    if (purchase_show_contains_any($text, ['資訊', '電腦', '網站', '網域', '系統', '軟體'])) {
        return '資訊採購';
    }

    if (purchase_show_contains_any($text, ['總務', '辦公', '設備', '雜項', '修繕', '庶務'])) {
        return '總務採購';
    }

    return '採購承辦';
}

function purchase_show_progress_steps(string $status): array
{
    if ($status === 'voided') {
        return [
            ['label' => '建立申請', 'hint' => '已建立採購案', 'state' => 'complete'],
            ['label' => '案件作廢', 'hint' => '此申請不再執行', 'state' => 'current danger'],
        ];
    }

    if ($status === 'rejected') {
        return [
            ['label' => '建立申請', 'hint' => '已建立採購案', 'state' => 'complete'],
            ['label' => '送出簽核', 'hint' => '已送出審查', 'state' => 'complete'],
            ['label' => '退回修正', 'hint' => '請依退回意見調整', 'state' => 'current danger'],
        ];
    }

    $steps = [
        ['status' => 'draft', 'label' => '建立申請', 'hint' => '草稿與基本資料'],
        ['status' => 'submitted', 'label' => '送出簽核', 'hint' => '等待主管審查'],
        ['status' => 'approved', 'label' => '核准採購', 'hint' => '可進入採購執行'],
        ['status' => 'ordered', 'label' => '採購執行', 'hint' => '採購或交付進行中'],
        ['status' => 'received', 'label' => '完成驗收', 'hint' => '可整理付款憑證'],
    ];

    $statusOrder = array_column($steps, 'status');
    $currentIndex = array_search($status, $statusOrder, true);
    $currentIndex = $currentIndex === false ? 0 : $currentIndex;

    foreach ($steps as $index => $step) {
        $steps[$index]['state'] = $index < $currentIndex ? 'complete' : ($index === $currentIndex ? 'current' : 'pending');
    }

    return $steps;
}

function purchase_show_check_items(array $request, int $attachmentCount, string $supervisingDepartment): array
{
    $quotationAttached = (int) ($request['quotation_attached'] ?? 0) === 1;
    $accountingNo = trim((string) ($request['accounting_no'] ?? ''));
    $totalAmount = (float) ($request['total_amount'] ?? 0);

    return [
        [
            'label' => '主管部門',
            'value' => $supervisingDepartment,
            'tone' => 'ok',
        ],
        [
            'label' => '報價單',
            'value' => $quotationAttached ? '已檢附' : '未檢附',
            'tone' => $quotationAttached ? 'ok' : 'warning',
        ],
        [
            'label' => '附件',
            'value' => number_format($attachmentCount) . ' 件',
            'tone' => $attachmentCount > 0 ? 'ok' : 'muted',
        ],
        [
            'label' => '會計編號',
            'value' => $accountingNo !== '' ? $accountingNo : '待補',
            'tone' => $accountingNo !== '' ? 'ok' : 'warning',
        ],
        [
            'label' => '金額門檻',
            'value' => $totalAmount >= 50000 ? '5萬元以上，需董事長核准' : '5萬元以下',
            'tone' => $totalAmount >= 50000 ? 'warning' : 'ok',
        ],
    ];
}

function purchase_show_contains_any(string $text, array $keywords): bool
{
    foreach ($keywords as $keyword) {
        if ($keyword !== '' && str_contains($text, $keyword)) {
            return true;
        }
    }

    return false;
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
