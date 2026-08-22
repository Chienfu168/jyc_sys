<?php
$active = 'purchase-requests';
$query = http_build_query(['month' => $month, 'status' => $status, 'q' => $keyword]);
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>有效件數</span>
        <strong><?= e(number_format((int) $summary['count'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>採購金額</span>
        <strong><?= e(purchase_request_money($summary['amount'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>送審中</span>
        <strong><?= e(number_format((int) $summary['submitted'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>已核准</span>
        <strong><?= e(number_format((int) $summary['approved'])) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <form class="search bank-filter" method="get" action="/purchase-requests">
            <input type="month" name="month" value="<?= e($month) ?>">
            <select name="status">
                <option value="">全部狀態</option>
                <?php foreach (purchase_request_status_options() as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="search" name="q" value="<?= e($keyword) ?>" placeholder="編號、項目、廠商、申請人">
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <?php if (\App\Core\Permission::can('purchase_requests.manage')): ?>
                <a class="btn primary" href="/purchase-requests/create">新增採購</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>日期</th>
                <th>編號</th>
                <th>申請項目</th>
                <th>單位 / 類別</th>
                <th>廠商</th>
                <th class="amount">金額</th>
                <th>狀態</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $request): ?>
                <tr>
                    <td><?= e(roc_date($request['requested_on'])) ?></td>
                    <td>
                        <a class="text-link mono" href="/purchase-requests/<?= e((string) $request['id']) ?>"><?= e($request['request_no']) ?></a>
                        <?php if (!empty($request['accounting_no'])): ?><div class="muted-text"><?= e($request['accounting_no']) ?></div><?php endif; ?>
                    </td>
                    <td>
                        <?= e($request['subject']) ?>
                        <div class="muted-text"><?= e($request['requester_name']) ?></div>
                    </td>
                    <td><?= e($request['request_unit']) ?><div class="muted-text"><?= e($request['purchase_category']) ?></div></td>
                    <td><?= e($request['vendor_name'] ?: '-') ?></td>
                    <td class="amount"><?= e(purchase_request_money($request['total_amount'])) ?></td>
                    <td><span class="badge <?= e(purchase_request_status_tone((string) $request['status'])) ?>"><?= e(purchase_request_status_label((string) $request['status'])) ?></span></td>
                    <td class="actions">
                        <a class="btn small" href="/purchase-requests/<?= e((string) $request['id']) ?>">檢視</a>
                        <a class="btn small" href="/purchase-requests/<?= e((string) $request['id']) ?>/print">列印</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$requests): ?>
                <tr><td colspan="8" class="empty">本月份尚無採購申請。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function purchase_request_money($value): string
{
    return number_format((float) $value, 0);
}

function purchase_request_status_options(): array
{
    return [
        'draft' => '草稿',
        'submitted' => '送審中',
        'approved' => '已核准',
        'rejected' => '已退回',
        'ordered' => '採購中',
        'received' => '已驗收',
        'voided' => '已作廢',
    ];
}

function purchase_request_status_label(string $status): string
{
    return purchase_request_status_options()[$status] ?? $status;
}

function purchase_request_status_tone(string $status): string
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
