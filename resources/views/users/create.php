<?php
$active = 'users';
ob_start();
?>
<section class="panel narrow">
    <div class="panel-header">
        <h2>新增使用者</h2>
        <a class="btn" href="/users">返回</a>
    </div>
    <?php require base_path('resources/views/users/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
