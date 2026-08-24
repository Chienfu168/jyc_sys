<?php $profile = $profile ?? foundation_profile(); ?>
<div class="print-document-header" style="display: none;">
    <h2><?= e($profile['foundation_name'] ?? foundation_name()) ?></h2>
    <?php if (!empty($documentTitle ?? '')): ?>
        <h3><?= e($documentTitle) ?></h3>
    <?php endif; ?>
    <div class="print-meta">
        <?php if (!empty($profile['tax_id'])): ?><span>統一編號：<?= e($profile['tax_id']) ?></span><?php endif; ?>
        <?php if (!empty($profile['registration_no'])): ?><span>登記字號：<?= e($profile['registration_no']) ?></span><?php endif; ?>
        <?php if (!empty($profile['competent_authority'])): ?><span>主管機關：<?= e($profile['competent_authority']) ?></span><?php endif; ?>
        <?php if (!empty($profile['approval_date'])): ?><span>核准日期：<?= e(roc_date($profile['approval_date'])) ?></span><?php endif; ?>
        <?php if (!empty($profile['approval_doc_no'])): ?><span>核准文號：<?= e($profile['approval_doc_no']) ?></span><?php endif; ?>
        <?php if (!empty($profile['address'])): ?><span>會址：<?= e($profile['address']) ?></span><?php endif; ?>
        <?php if (!empty($profile['mailing_address'])): ?><span>通訊地址：<?= e($profile['mailing_address']) ?></span><?php endif; ?>
    </div>
</div>
