<?php
$active = 'certificates';
$certificate = $certificate ?? [];
$documentTitle = $certificate['title'] . ' — ' . $certificate['recipient_name'];
ob_start();
?>
<style>
/* 聘書／感謝狀改為滿版 A4(210mm×297mm),列印邊界歸零由版面自身留白控制,
   僅此文件適用,不影響其他列印頁面的 @page 邊界設定。 */
@media print {
    @page { size: A4; margin: 0; }
}
</style>
<section class="panel no-print">
    <div class="panel-header">
        <div>
            <h2><?= e($certificate['title']) ?>　<?= e($certificate['recipient_name']) ?></h2>
            <p class="muted-text">
                <?= e(\App\Domain\Certificates\CertificateTemplates::get($certificate['template_key'])['label']) ?>
                ／開立日期：<?= e(roc_date($certificate['issued_on'])) ?>
                ／建立人：<?= e($certificate['created_by_name'] ?? '-') ?>
            </p>
        </div>
        <div class="actions">
            <a class="btn" href="/certificates">返回列表</a>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
            <?php if (!empty($canManage)): ?>
                <a class="btn" href="/certificates/<?= e((string) $certificate['id']) ?>/edit">編輯</a>
                <a class="btn" href="/certificates/<?= e((string) $certificate['id']) ?>/duplicate">複製</a>
                <form method="post" action="/certificates/<?= e((string) $certificate['id']) ?>/delete" onsubmit="return confirm('確定要刪除此文件？此操作無法復原。');">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit">刪除</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php $certLogo = foundation_logo_data_uri(); ?>
<?php $certSeal = foundation_seal_data_uri(); ?>
<?php $certFrameStyle = \App\Domain\Certificates\CertificateFrameStyles::normalize((string) ($certificate['frame_style'] ?? 'classic')); ?>
<article class="certificate-sheet">
    <div class="cert-frame cert-frame--<?= e($certFrameStyle) ?>">
        <span class="cert-corner cert-corner--tl" aria-hidden="true"></span>
        <span class="cert-corner cert-corner--tr" aria-hidden="true"></span>
        <span class="cert-corner cert-corner--bl" aria-hidden="true"></span>
        <span class="cert-corner cert-corner--br" aria-hidden="true"></span>

        <div class="cert-content">
            <?php if ($certLogo !== null): ?>
                <img class="cert-logo" src="<?= e($certLogo) ?>" alt="">
            <?php endif; ?>
            <div class="cert-org"><?= e($profile['foundation_name'] ?? foundation_name()) ?></div>
            <div class="cert-title"><?= e($certificate['title']) ?></div>
            <div class="cert-divider">❖</div>
            <div class="cert-body">
                <?php
                $certName = trim((string) $certificate['recipient_name']);
                $certBodyNormalized = str_replace(["\r\n", "\r"], "\n", (string) $certificate['body_text']);
                $certParagraphs = preg_split('/\n{2,}/u', trim($certBodyNormalized)) ?: [];
                ?>
                <?php foreach ($certParagraphs as $certParagraph): ?>
                    <?php $certParagraphTrim = trim($certParagraph); ?>
                    <?php if ($certName !== '' && $certParagraphTrim === $certName): ?>
                        <p class="cert-name"><?= e($certParagraphTrim) ?></p>
                    <?php elseif (str_starts_with($certParagraphTrim, '聘期')): ?>
                        <p class="cert-term"><?= nl2br(e($certParagraph)) ?></p>
                    <?php else: ?>
                        <p><?= nl2br(e($certParagraph)) ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <div class="cert-signoff-row">
                <div class="cert-signoff">
                    <?= e($profile['foundation_name'] ?? foundation_name()) ?><br>
                    <?= e($certificate['signer_title']) ?>　<strong><?= e($certificate['signer_name']) ?></strong>
                </div>
                <?php if ($certSeal !== null): ?>
                    <img class="cert-seal" src="<?= e($certSeal) ?>" alt="">
                <?php endif; ?>
            </div>
            <div class="cert-date"><?= e(roc_date($certificate['issued_on'])) ?></div>
        </div>
    </div>
</article>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
