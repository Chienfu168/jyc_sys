<?php
$active = 'donations';
$documentTitle = '捐贈收入清冊';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <form class="search bank-filter" method="get" action="/donations/income-list">
            <input type="number" name="year" min="1" max="2100" value="<?= e((string) roc_year($year)) ?>">
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/donations/report?<?= e(http_build_query(['year' => $year])) ?>">捐款台帳</a>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>報表期間</th>
            <td><?= e(roc_year_label($year)) ?>（<?= e((string) $year) ?> 年 1 月 1 日至 12 月 31 日）</td>
            <th>單位</th>
            <td>新臺幣元</td>
        </tr>
        </tbody>
    </table>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th style="width:48px">序</th>
                <th>捐贈（單位／人）</th>
                <th class="amount">捐贈金額</th>
                <th>備註</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $index => $row): ?>
                <tr>
                    <td><?= e((string) ($index + 1)) ?></td>
                    <td><?= e($row['name']) ?></td>
                    <td class="amount"><?= e(number_format((float) $row['total_amount'], 0)) ?></td>
                    <td><?= (int) $row['donation_count'] > 1 ? e('全年 ' . (int) $row['donation_count'] . ' 筆') : '' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
                <tr><td colspan="4" class="empty-state">本年度尚無捐贈收入紀錄。</td></tr>
            <?php endif; ?>
            </tbody>
            <tfoot>
            <tr>
                <th colspan="2">合計</th>
                <th class="amount"><?= e(number_format((float) $totalAmount, 0)) ?></th>
                <th></th>
            </tr>
            </tfoot>
        </table>
    </div>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
