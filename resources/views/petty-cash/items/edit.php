<?php
$active = 'petty-cash';
ob_start();
?>
<section class="panel narrow">
    <div class="panel-header">
        <h2>編輯常用項目</h2>
        <a class="btn" href="/petty-cash-items">返回清單</a>
    </div>
    <?php require __DIR__ . '/form.php'; ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
