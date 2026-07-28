<?php
ob_start();
?>
<section class="module-grid">
    <?php foreach ($modules as $module): ?>
        <a class="module-card" href="<?= e($module['href']) ?>">
            <span><?= e($section) ?></span>
            <strong><?= e($module['title']) ?></strong>
            <p><?= e($module['description']) ?></p>
        </a>
    <?php endforeach; ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
