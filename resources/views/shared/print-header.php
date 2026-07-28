<?php $profile = $profile ?? foundation_profile(); ?>
<div class="print-document-header">
    <h2><?= e($profile['foundation_name'] ?? foundation_name()) ?></h2>
    <?php if (!empty($documentTitle ?? '')): ?>
        <h3><?= e($documentTitle) ?></h3>
    <?php endif; ?>
    <div class="print-meta">
        <?php if (!empty($profile['tax_id'])): ?><span>統一編號：<?= e($profile['tax_id']) ?></span><?php endif; ?>
        <?php if (!empty($profile['registration_no'])): ?><span>登記字號：<?= e($profile['registration_no']) ?></span><?php endif; ?>
        <?php if (!empty($profile['address'])): ?><span>地址：<?= e($profile['address']) ?></span><?php endif; ?>
    </div>
</div>
