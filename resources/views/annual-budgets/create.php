<?php
$active = 'annual-budgets';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <h2>新增年度預算</h2>
        <a class="btn" href="/annual-budgets">返回</a>
    </div>
    <?php require base_path('resources/views/annual-budgets/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
