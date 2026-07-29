<?php
$active = 'travel-expenses';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>新增出差費用</h2>
            <p class="muted-text">建立出差交通、住宿、膳雜與核銷資料。</p>
        </div>
    </div>
    <?php require base_path('resources/views/travel-expenses/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
