<?php
$active = 'donors';
ob_start();
?>
<section class="panel narrow">
    <div class="panel-header">
        <h2>編輯捐款人</h2>
        <a class="btn" href="/donors/<?= e((string) $donor['id']) ?>">返回</a>
    </div>
    <?php require base_path('resources/views/donors/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
