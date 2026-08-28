<?php
$profile = $profile ?? foundation_profile();
// 簽核鏈:優先採用呼叫端明確指定的 $signatureRoles;
// 否則依 $signatureContext 或頁面 $active 模組鍵自動對應該模組的核章關係。
$signatureRoles = $signatureRoles ?? signature_chain($signatureContext ?? $active ?? null);
?>
<section class="signature-grid">
    <?php foreach ($signatureRoles as $role): ?>
        <div class="signature-box">
            <span class="signature-role"><?= e($role['label']) ?></span>
            <span class="signature-stamp" aria-hidden="true"></span>
            <strong class="signature-name"><?= e($role['name'] ?? '') ?></strong>
        </div>
    <?php endforeach; ?>
</section>
