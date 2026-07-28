<?php $profile = $profile ?? foundation_profile(); ?>
<section class="signature-grid">
    <div class="signature-box">
        <span>承辦</span>
        <strong><?= e($profile['undertaker'] ?? '') ?></strong>
    </div>
    <div class="signature-box">
        <span>執行長</span>
        <strong><?= e($profile['executive_director'] ?? '') ?></strong>
    </div>
    <div class="signature-box">
        <span>董事長</span>
        <strong><?= e($profile['representative'] ?? '') ?></strong>
    </div>
</section>
