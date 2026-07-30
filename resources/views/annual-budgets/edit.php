<?php
$active = 'annual-budgets';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>編輯年度預算</h2>
            <p class="muted-text">調整預算明細、主管機關科目層級、上年度比較與會計科目對應。</p>
        </div>
        <a class="btn" href="/annual-budgets/<?= e((string) $budget['id']) ?>">返回檢視</a>
    </div>
    <?php require __DIR__ . '/form.php'; ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
