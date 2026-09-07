<?php
$active = 'travel-expenses';
$documentTitle = '出差費用月報表';
$groups = $groups ?? [];
$grand = $grand ?? [];

$roc = null;
if (preg_match('/^(\d{4})-(\d{2})$/', (string) $month, $m)) {
    $roc = ((int) $m[1] - 1911) . '年' . (int) $m[2] . '月';
}
$money = static fn ($v): string => number_format((float) $v, 0);
$ymd = static function (?string $d): string {
    if (!$d || !preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $d, $x)) {
        return (string) $d;
    }
    return (int) $x[2] . '/' . (int) $x[3];
};
ob_start();
?>
<style>
@media print {
    @page { size: A4 landscape; margin: 12mm; }
}
.travel-ws-wrap { overflow-x: auto; }
.travel-ws { width: 100%; border-collapse: collapse; font-size: 13px; }
.travel-ws th, .travel-ws td { border: 1px solid #666; padding: 5px 7px; }
.travel-ws thead th { background: #f1f4f3; text-align: center; }
.travel-ws td.amount, .travel-ws th.amount { text-align: right; }
.travel-ws .grp-row td { background: #eef4fb; font-weight: 700; }
.travel-ws tfoot td { background: #f6f8f7; font-weight: 700; }
.travel-ws .col-wrap { white-space: normal; }
</style>

<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2>出差月報表 / <?= e($month) ?></h2>
            <p class="muted-text">單月逐項出差,依出差人分組彙整(每人小計與全月總計),可列印或另存 PDF。</p>
        </div>
        <div class="actions">
            <form class="search" method="get" action="/travel-expenses/monthly">
                <input type="month" name="month" value="<?= e($month) ?>">
                <button class="btn" type="submit">查詢</button>
            </form>
            <a class="btn" href="/travel-expenses">返回列表</a>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        </div>
    </div>

    <div class="print-only" style="text-align:center;margin-bottom:8px">
        <strong style="font-size:16px"><?= e($roc ?? $month) ?> 出差費用月報表</strong>
    </div>

    <?php if (!$groups): ?>
        <p class="muted-text"><?= e($month) ?> 尚無出差費用紀錄。</p>
    <?php else: ?>
        <div class="travel-ws-wrap">
            <table class="travel-ws">
                <thead>
                    <tr>
                        <th>出差人</th>
                        <th>日期</th>
                        <th>目的地</th>
                        <th>事由</th>
                        <th class="amount">交通</th>
                        <th class="amount">住宿</th>
                        <th class="amount">膳雜</th>
                        <th class="amount">其他</th>
                        <th class="amount">代墊</th>
                        <th class="amount">合計</th>
                        <th class="amount">應核銷</th>
                        <th>發放</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($groups as $g): ?>
                    <?php foreach ($g['items'] as $it): ?>
                        <tr>
                            <td><?= e($g['name']) ?></td>
                            <td><?= e($ymd($it['travel_start']) . ($it['travel_end'] && $it['travel_end'] !== $it['travel_start'] ? '～' . $ymd($it['travel_end']) : '')) ?></td>
                            <td class="col-wrap"><?= e($it['destination']) ?></td>
                            <td class="col-wrap"><?= e($it['purpose']) ?></td>
                            <td class="amount"><?= e($money($it['transportation_fee'])) ?></td>
                            <td class="amount"><?= e($money($it['accommodation_fee'])) ?></td>
                            <td class="amount"><?= e($money($it['meal_fee'])) ?></td>
                            <td class="amount"><?= e($money($it['miscellaneous_fee'])) ?></td>
                            <td class="amount"><?= e($money($it['advance_amount'])) ?></td>
                            <td class="amount"><?= e($money($it['total_amount'])) ?></td>
                            <td class="amount"><?= e($money($it['reimbursable_amount'])) ?></td>
                            <td><?= ($it['settlement_method'] ?? 'separate') === 'payroll' ? '併入薪資' : (($it['payment_status'] ?? '') === 'paid' ? '已付' : '待付') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="grp-row">
                        <td colspan="4"><?= e($g['name']) ?> 小計</td>
                        <td class="amount"><?= e($money($g['subtotal']['transportation_fee'])) ?></td>
                        <td class="amount"><?= e($money($g['subtotal']['accommodation_fee'])) ?></td>
                        <td class="amount"><?= e($money($g['subtotal']['meal_fee'])) ?></td>
                        <td class="amount"><?= e($money($g['subtotal']['miscellaneous_fee'])) ?></td>
                        <td class="amount"><?= e($money($g['subtotal']['advance_amount'])) ?></td>
                        <td class="amount"><?= e($money($g['subtotal']['total_amount'])) ?></td>
                        <td class="amount"><?= e($money($g['subtotal']['reimbursable_amount'])) ?></td>
                        <td></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" style="text-align:center">全月總計</td>
                        <td class="amount"><?= e($money($grand['transportation_fee'])) ?></td>
                        <td class="amount"><?= e($money($grand['accommodation_fee'])) ?></td>
                        <td class="amount"><?= e($money($grand['meal_fee'])) ?></td>
                        <td class="amount"><?= e($money($grand['miscellaneous_fee'])) ?></td>
                        <td class="amount"><?= e($money($grand['advance_amount'])) ?></td>
                        <td class="amount"><?= e($money($grand['total_amount'])) ?></td>
                        <td class="amount"><?= e($money($grand['reimbursable_amount'])) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php require base_path('resources/views/shared/signatures.php'); ?>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
