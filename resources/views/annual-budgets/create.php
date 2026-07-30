<?php
$active = 'annual-budgets';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>新增年度預算</h2>
            <p class="muted-text">依非營利組織年度經費預算表建立收益、費損與比較資料。</p>
        </div>
        <a class="btn" href="/annual-budgets">返回清單</a>
    </div>
    <?php require __DIR__ . '/form.php'; ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
