<?php
$active = 'lecturer-expenses';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>新增講師費用</h2>
            <p class="muted-text">建立講師「單月」請款紀錄，建立後可於下方新增多筆上課明細(日期、地點、堂數、交通補貼)。</p>
        </div>
    </div>
    <?php require base_path('resources/views/lecturer-expenses/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
