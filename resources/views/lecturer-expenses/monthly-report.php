<?php
$active = 'lecturer-expenses';
$documentTitle = '講師費用月報表';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2>講師費用月報表</h2>
            <p class="muted-text">列印指定月份各講師之上課次數明細（上課地點、日期、時數、交通費）與應付金額。</p>
        </div>
        <div class="actions">
            <form class="search" method="get" action="/lecturer-expenses/reports/monthly">
                <input type="month" name="month" value="<?= e($month) ?>">
                <button class="btn" type="submit">查詢</button>
            </form>
            <a class="btn" href="/lecturer-expenses">返回列表</a>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        </div>
    </div>

    <div class="print-only" style="text-align:center;margin-bottom:8px">
        <strong style="font-size:16px"><?= e($month) ?> 講師費用月報表</strong>
    </div>

    <?php if (!$expenses): ?>
        <p class="muted-text"><?= e($month) ?> 尚無講師費用紀錄。</p>
    <?php endif; ?>

    <?php foreach ($expenses as $expense): ?>
        <?php $sessions = $sessionsByExpense[(int) $expense['id']] ?? []; ?>
        <div class="meta-table-wrap" style="margin-bottom:18px;break-inside:avoid;">
            <table class="meta-table">
                <tbody>
                <tr>
                    <th>講師</th>
                    <td><?= e($expense['display_name'] ?: $expense['lecturer_name']) ?></td>
                    <th>服務內容</th>
                    <td><?= e($expense['service_title']) ?></td>
                </tr>
                <tr>
                    <th>總時數</th>
                    <td><?= e(number_format((float) $expense['hours'], 2)) ?></td>
                    <th>應付總額</th>
                    <td><?= e(number_format((float) $expense['gross_total'], 0)) ?></td>
                </tr>
                </tbody>
            </table>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>日期</th>
                        <th>上課地點</th>
                        <th class="amount">時數</th>
                        <th class="amount">鐘點費單價</th>
                        <th class="amount">鐘點費</th>
                        <th class="amount">交通費</th>
                        <th class="amount">小計</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($sessions as $session): ?>
                        <tr>
                            <td><?= e(roc_date($session['session_date'])) ?></td>
                            <td><?= e($session['location'] ?: '-') ?></td>
                            <td class="amount"><?= e(number_format((float) $session['hours'], 2)) ?></td>
                            <td class="amount"><?= e(number_format((float) $session['hourly_rate'], 0)) ?></td>
                            <td class="amount"><?= e(number_format((float) $session['lecture_fee'], 0)) ?></td>
                            <td class="amount"><?= e(number_format((float) $session['transportation_fee'], 0)) ?></td>
                            <td class="amount"><?= e(number_format((float) $session['subtotal'], 0)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$sessions): ?>
                        <tr><td colspan="7" class="empty">尚無上課明細。</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
