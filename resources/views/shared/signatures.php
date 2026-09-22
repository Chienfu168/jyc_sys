<?php
$profile = $profile ?? foundation_profile();
// 簽核鏈:優先採用呼叫端明確指定的 $signatureRoles;
// 否則依 $signatureContext 或頁面 $active 模組鍵自動對應該模組的核章關係。
$signatureRoles = $signatureRoles ?? signature_chain($signatureContext ?? $active ?? null);
// $signatureInline:單行「職稱：姓名」核章列,不畫用印框(主管機關經費預算表等格式)。
$signatureInline = $signatureInline ?? false;
?>
<?php if ($signatureInline): ?>
<section class="signature-inline">
    <?php foreach ($signatureRoles as $role): ?>
        <span class="signature-inline-item">
            <span class="signature-role"><?= e($role['label']) ?>：</span>
            <span class="signature-name"><?= e($role['name'] ?? '') ?></span>
        </span>
    <?php endforeach; ?>
</section>
<?php else: ?>
<section class="signature-grid">
    <?php foreach ($signatureRoles as $role): ?>
        <div class="signature-box">
            <span class="signature-role"><?= e($role['label']) ?></span>
            <span class="signature-stamp" aria-hidden="true"></span>
            <strong class="signature-name"><?= e($role['name'] ?? '') ?></strong>
        </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>
