<?php
$active = 'lecturer-expenses';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>複製講師費用月紀錄</h2>
            <p class="muted-text"><?= e($expense['display_name'] ?: $expense['lecturer_name']) ?>，<?= e(substr((string) $expense['expense_date'], 0, 7)) ?>，共 <?= e((string) count($sessions)) ?> 筆上課明細。</p>
        </div>
        <div class="actions">
            <a class="btn" href="/lecturer-expenses/<?= e((string) $expense['id']) ?>">返回月紀錄</a>
        </div>
    </div>

    <p class="muted-text">將此筆月紀錄（服務內容、承辦單位、專案／活動、其他費用、代扣稅額）與全部上課明細複製到下方選擇的月份，上課明細日期會自動換算到新月份的相同「日」（若新月份天數較少則調整為當月最後一天），複製後請確認日期、時數等內容再使用。付款狀態、付款日期、會計傳票與憑證編號不會複製，新紀錄一律為「待付款」。</p>

    <form class="form grid-form" method="post" action="<?= e($action) ?>">
        <?= csrf_field() ?>
        <label>
            <span>目標月份</span>
            <input type="month" name="target_month" value="<?= e((string) old('target_month', $targetMonth)) ?>" required>
        </label>
        <div class="form-actions span-2">
            <a class="btn" href="/lecturer-expenses/<?= e((string) $expense['id']) ?>">返回</a>
            <button class="btn primary" type="submit">複製</button>
        </div>
    </form>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
