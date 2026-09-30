<?php
$active = 'lecturer-expenses';
$documentTitle = '講師費用銀行出帳記錄';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2>講師費用銀行出帳記錄</h2>
            <p class="muted-text">列印指定月份各講師匯款帳戶與應付金額，供財務出帳使用。</p>
        </div>
        <div class="actions">
            <form class="search" method="get" action="/lecturer-expenses/reports/bank-payout">
                <input type="month" name="month" value="<?= e($month) ?>">
                <button class="btn" type="submit">查詢</button>
            </form>
            <a class="btn" href="/lecturer-expenses">返回列表</a>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        </div>
    </div>

    <div class="print-only" style="text-align:center;margin-bottom:8px">
        <strong style="font-size:16px"><?= e($month) ?> 講師費用銀行出帳記錄</strong>
    </div>

    <table class="meta-table no-print">
        <tbody>
        <tr>
            <th>出帳筆數</th>
            <td><?= e((string) count($rows)) ?></td>
            <th>出帳總額</th>
            <td><?= e(number_format((float) $total, 0)) ?></td>
        </tr>
        </tbody>
    </table>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>老師姓名</th>
                <th>銀行</th>
                <th>分行</th>
                <th>帳號</th>
                <th>戶名</th>
                <th class="amount">當月金額</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['display_name'] ?: $row['lecturer_name']) ?></td>
                    <td><?= e($row['bank_name'] ?: '-') ?></td>
                    <td><?= e($row['bank_branch'] ?: '-') ?></td>
                    <td><?= e($row['bank_account_no'] ?: '-') ?></td>
                    <td><?= e($row['bank_account_name'] ?: '-') ?></td>
                    <td class="amount"><?= e(number_format((float) $row['net_total'], 0)) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
                <tr><td colspan="6" class="empty">查無符合條件的紀錄。</td></tr>
            <?php endif; ?>
            </tbody>
            <tfoot>
            <tr>
                <th colspan="5">合計</th>
                <th class="amount"><?= e(number_format((float) $total, 0)) ?></th>
            </tr>
            </tfoot>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
