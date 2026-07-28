<?php
ob_start();
?>
<section class="panel narrow">
    <?php require base_path('resources/views/bank-accounts/transactions/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
