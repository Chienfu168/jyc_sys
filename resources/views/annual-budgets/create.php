<?php
$active = 'annual-budgets';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?php if (!empty($duplicateFromYear)): ?>複製年度預算<?php elseif (!empty($templateApplied)): ?>以 115 年度範本新增預算<?php else: ?>新增年度預算<?php endif; ?></h2>
            <?php if (!empty($duplicateFromYear)): ?>
                <p class="muted-text">由民國 <?= e((string) roc_year($duplicateFromYear)) ?> 年度預算複製;年度已預設為次一年度,請確認金額與內容後儲存,將另建立新的年度預算。</p>
            <?php elseif (!empty($templateApplied)): ?>
                <p class="muted-text">已帶入主管機關(教育局)115 年度經費預算表範本的完整款/項/目/次/節與金額,請確認後儲存;亦可再自行增刪與調整。</p>
            <?php else: ?>
                <p class="muted-text">依非營利組織年度經費預算表建立收益、費損與比較資料。可按「套用 115 年度預算範本」一鍵帶入官方格式。</p>
            <?php endif; ?>
        </div>
        <a class="btn" href="/annual-budgets">返回清單</a>
    </div>
    <?php require __DIR__ . '/form.php'; ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
