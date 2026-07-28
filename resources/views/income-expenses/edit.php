<?php
ob_start();
?>
<section class="panel">
    <?php require base_path('resources/views/income-expenses/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
