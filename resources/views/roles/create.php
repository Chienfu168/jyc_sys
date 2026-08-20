<?php
$active = 'roles';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <h2>新增角色</h2>
        <a class="btn" href="/roles">返回</a>
    </div>
    <?php require base_path('resources/views/roles/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
