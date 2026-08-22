<?php
$active = 'purchase-requests';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">採購申請</p>
            <h2>新增採購申請</h2>
        </div>
    </div>
    <?php require base_path('resources/views/purchase-requests/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
