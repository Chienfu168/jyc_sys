<?php
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>新增財產</h2>
            <p class="muted-text">依財產清冊格式登錄財產資料。</p>
        </div>
        <a class="btn" href="/foundation-assets">返回清冊</a>
    </div>
    <?php require __DIR__ . '/form.php'; ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
